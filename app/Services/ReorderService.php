<?php

namespace App\Services;

use App\Mail\PurchaseOrderCreated;
use App\Models\ActivityLog;
use App\Models\InventoryTransaction;
use App\Models\Note;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReorderService
{
    /**
     * Reorder point = (average daily usage x lead time) + safety stock.
     */
    public function calculateReorderPoint(Product $product, ?float $dailyUsage = null): int
    {
        $usage = $dailyUsage ?? $this->averageDailyUsage($product);
        $leadTime = $this->effectiveLeadTime($product);
        $safetyStock = (int) ($product->safety_stock ?: config('replenishment.default_safety_stock', 0));

        return (int) ceil($usage * $leadTime + $safetyStock);
    }

    /**
     * Simplified EOQ rounded up to the supplier's package / minimum order size.
     */
    public function calculateOptimalOrderQuantity(Product $product, ?int $reorderPoint = null, ?float $dailyUsage = null): int
    {
        $usage = $dailyUsage ?? $this->averageDailyUsage($product);
        $annualDemand = $usage * 365;
        $unitCost = $this->effectiveUnitCost($product);
        $holdingCost = max(0.01, $unitCost * (float) config('replenishment.holding_cost_rate', 0.20));
        $orderingCost = (float) config('replenishment.ordering_cost', 500);

        $eoq = sqrt((2 * $annualDemand * $orderingCost) / $holdingCost);
        $minimum = max(
            1,
            (int) ($product->min_order_qty ?: config('replenishment.minimum_order_quantity', 1))
        );

        $suggested = (int) max($minimum, ceil($eoq));

        // Always cover at least the expected lead-time demand plus safety stock.
        $cover = max(0, ($reorderPoint ?? $this->calculateReorderPoint($product)) - (int) $product->quantity);
        $suggested = max($suggested, $cover);

        $package = max(1, (int) ($product->package_size ?: 1));

        return (int) max($minimum, ceil($suggested / $package) * $package);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function getProductsNeedingReorder(bool $fresh = false): Collection
    {
        $ttl = (int) config('replenishment.cache_ttl_seconds', 60);
        $key = 'reorder:products-needing-reorder';

        if ($fresh) {
            Cache::forget($key);
        }

        // Cache only scalar values. Eloquent models and Collections do not
        // survive serialisation in the database cache reliably.
        $rows = Cache::remember($key, $ttl, function (): array {
            $window = (int) config('replenishment.usage_window_days', 30);

            $usageByProduct = DB::table('inventory_transactions')
                ->select('product_id', DB::raw('SUM(quantity) as units'))
                ->where('type', InventoryTransaction::TYPE_OUT)
                ->where('created_at', '>=', now()->subDays($window))
                ->groupBy('product_id')
                ->pluck('units', 'product_id');

            $products = Product::query()
                ->active()
                ->with(['supplier:id,company_name,lead_time_days', 'category:id,name'])
                ->with(['suppliers' => fn ($query) => $query->wherePivot('is_primary', true)])
                ->orderBy('quantity')
                ->get();

            return $products->map(function (Product $product) use ($usageByProduct, $window): ?array {
                $supplier = $product->suppliers->first() ?: $product->supplier_record;

                if (! $supplier) {
                    return null;
                }

                $dailyUsage = round(((float) ($usageByProduct->get($product->id) ?? 0)) / $window, 2);
                $reorderPoint = $this->calculateReorderPoint($product, $dailyUsage);

                if ((int) $product->quantity > $reorderPoint) {
                    return null;
                }

                return [
                    'product_id' => $product->id,
                    'supplier_id' => $supplier->id,
                    'current_stock' => (int) $product->quantity,
                    'daily_usage' => $dailyUsage,
                    'lead_time_days' => $this->effectiveLeadTime($product),
                    'reorder_point' => $reorderPoint,
                    'suggested_quantity' => $this->calculateOptimalOrderQuantity($product, $reorderPoint, $dailyUsage),
                    'unit_cost' => $this->effectiveUnitCost($product),
                    'days_of_cover' => $dailyUsage > 0 ? round($product->quantity / $dailyUsage, 1) : null,
                ];
            })->filter()->values()->all();
        });

        $productIds = array_values(array_unique(array_column($rows, 'product_id')));
        $supplierIds = array_values(array_unique(array_column($rows, 'supplier_id')));

        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
        $suppliers = Supplier::query()->whereIn('id', $supplierIds)->get()->keyBy('id');

        return collect($rows)
            ->map(fn (array $row): ?array => [
                'product' => $products->get($row['product_id']),
                'supplier' => $suppliers->get($row['supplier_id']),
            ] + $row)
            ->filter(fn (array $row): bool => $row['product'] !== null && $row['supplier'] !== null)
            ->values();
    }

    /** @param  Collection<int, array<string, mixed>>  $products */
    public function groupProductsBySupplier(Collection $products): Collection
    {
        return $products->groupBy('supplier_id')->map(function (Collection $lines, int $supplierId): array {
            $supplier = $lines->first()['supplier'];

            return [
                'supplier' => $supplier,
                'supplier_id' => $supplierId,
                'lines' => $lines,
            ];
        });
    }

    /**
     * Create one draft purchase order per supplier for products below their
     * reorder point. Existing open orders prevent duplicate replenishment.
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function generatePurchaseOrders(?User $creator = null): Collection
    {
        $groups = $this->groupProductsBySupplier($this->getProductsNeedingReorder(fresh: true));
        $created = collect();
        $dedupeDays = (int) config('replenishment.dedupe_days', 7);

        foreach ($groups as $group) {
            /** @var Supplier $supplier */
            $supplier = $group['supplier'];
            $lines = collect($group['lines'])->filter(function (array $line) use ($supplier, $dedupeDays): bool {
                return ! $this->hasOpenOrderForProduct($line['product'], $supplier, $dedupeDays);
            });

            if ($lines->isEmpty()) {
                continue;
            }

            $order = DB::transaction(function () use ($supplier, $lines, $creator): PurchaseOrder {
                $reference = PurchaseOrder::generateReferenceNumber();
                $order = PurchaseOrder::create([
                    'reference_number' => $reference,
                    // Legacy Lab 6 column is NOT NULL; keep it in sync.
                    'order_number' => $reference,
                    'supplier_id' => $supplier->id,
                    'supplier' => $supplier->company_name,
                    'status' => PurchaseOrder::STATUS_DRAFT,
                    'order_date' => now()->toDateString(),
                    'expected_delivery_date' => now()->addDays(
                        (int) ($supplier->lead_time_days ?: config('replenishment.default_lead_time_days', 7))
                    )->toDateString(),
                    'expected_date' => null,
                    'tax_rate' => config('replenishment.tax_rate', 12),
                    'currency' => 'PHP',
                    'notes' => 'Auto-generated by the scheduled reorder check.',
                    'terms' => $supplier->payment_terms,
                    'created_by' => $creator?->id,
                    'user_id' => $creator?->id,
                ]);

                foreach ($lines as $line) {
                    $order->items()->create([
                        'product_id' => $line['product']->id,
                        'quantity' => $line['suggested_quantity'],
                        'unit_cost' => $line['unit_cost'],
                    ]);
                }

                $order->calculateTotal();

                Note::create([
                    'notable_type' => $order->getMorphClass(),
                    'notable_id' => $order->id,
                    'user_id' => $creator?->id,
                    'type' => 'system',
                    'body' => 'Draft created automatically because '.count($lines).' product(s) fell below their reorder point.',
                ]);

                ActivityLog::record(
                    ActivityLog::MODULE_REPLENISHMENT,
                    ActivityLog::ACTION_CREATED,
                    "Auto-generated purchase order {$order->reference_number} for {$supplier->company_name}.",
                    $order,
                    $creator,
                );

                return $order;
            });

            $this->notifyManagers($order);
            $created->push($order);
        }

        return $created;
    }

    /** Average daily usage is derived from the inventory ledger, not guessed. */
    private function averageDailyUsage(Product $product): float
    {
        if ($product->relationLoaded('transactions')) {
            $units = $product->transactions
                ->where('type', InventoryTransaction::TYPE_OUT)
                ->sum('quantity');
        } else {
            $units = (float) DB::table('inventory_transactions')
                ->where('product_id', $product->id)
                ->where('type', InventoryTransaction::TYPE_OUT)
                ->where('created_at', '>=', now()->subDays((int) config('replenishment.usage_window_days', 30)))
                ->sum('quantity');
        }

        if ($units <= 0) {
            return (float) config('replenishment.default_daily_usage', 1);
        }

        return round($units / max(1, (int) config('replenishment.usage_window_days', 30)), 2);
    }

    private function effectiveLeadTime(Product $product): int
    {
        $pivot = $product->suppliers->first()?->pivot->lead_time_days ?? null;

        // Supplier lead time is authoritative for replenishment. The product
        // column has a schema default of 7, so it must only act as a fallback.
        return max(1, (int) (
            $pivot
            ?: $product->supplier_record?->lead_time_days
            ?: $product->lead_time_days
            ?: config('replenishment.default_lead_time_days', 7)
        ));
    }

    private function effectiveUnitCost(Product $product): float
    {
        $pivot = $product->suppliers->first()?->pivot->unit_cost ?? null;

        return (float) ($pivot ?: $product->cost_price ?: $product->unit_price ?: 0);
    }

    private function hasOpenOrderForProduct(Product $product, Supplier $supplier, int $days): bool
    {
        return PurchaseOrder::query()
            ->where('supplier_id', $supplier->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_PENDING,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SENT,
            ])
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->exists();
    }

    private function notifyManagers(PurchaseOrder $order): void
    {
        $recipients = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER])
            ->where('status', User::STATUS_ACTIVE)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new PurchaseOrderCreated($order));
        }
    }
}
