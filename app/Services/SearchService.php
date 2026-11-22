<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /** @param  array<string, mixed>  $filters */
    public function searchProducts(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) ($filters['search'] ?? ''));
        $category = $filters['category'] ?? '';
        $supplier = $filters['supplier'] ?? '';
        $status = $filters['status'] ?? '';

        return Product::query()
            ->with(['category:id,name', 'brand:id,name', 'supplier:id,company_name'])
            ->search($term)
            ->when($category !== '', fn ($query) => $query->inCategory($category))
            ->when($supplier !== '', fn ($query) => $query->where('supplier_id', (int) $supplier))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @param  array<string, mixed>  $filters */
    public function searchPurchaseOrders(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? '';
        $supplier = $filters['supplier'] ?? '';
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';

        return PurchaseOrder::query()
            ->with(['supplier:id,company_name', 'createdBy:id,name', 'items.product:id,name,sku'])
            ->byStatus($status)
            ->when($supplier !== '', fn ($query) => $query->where('supplier_id', (int) $supplier))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('reference_number', 'like', "%{$term}%")
                        ->orWhere('order_number', 'like', "%{$term}%")
                        ->orWhereHas('supplier', fn ($s) => $s->where('company_name', 'like', "%{$term}%"))
                        ->orWhereHas('items.product', fn ($p) => $p->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($from !== '', fn ($query) => $query->whereDate('order_date', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('order_date', '<=', $to))
            ->latest('order_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @param  array<string, mixed>  $filters */
    public function searchTransactions(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) ($filters['search'] ?? ''));
        $product = $filters['product'] ?? '';
        $type = $filters['type'] ?? '';
        $user = $filters['user'] ?? '';
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';

        return InventoryTransaction::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->betweenDates($from ?: null, $to ?: null)
            ->when($product !== '', fn ($query) => $query->forProduct((int) $product))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($user !== '', fn ($query) => $query->where('user_id', (int) $user))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('reference', 'like', "%{$term}%")
                        ->orWhere('reason', 'like', "%{$term}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")
                            ->orWhere('sku', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Fast aggregate used by the supplier performance report. */
    public function supplierTotals(): \Illuminate\Support\Collection
    {
        return DB::table('suppliers')
            ->leftJoin('purchase_orders', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->whereNull('suppliers.deleted_at')
            ->whereNull('purchase_orders.deleted_at')
            ->groupBy('suppliers.id', 'suppliers.company_name')
            ->select([
                'suppliers.id',
                'suppliers.company_name',
                DB::raw('COUNT(purchase_orders.id) as orders'),
                DB::raw('COALESCE(SUM(purchase_orders.total), 0) as value'),
            ])
            ->orderByDesc('value')
            ->get();
    }
}
