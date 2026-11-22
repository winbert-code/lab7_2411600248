<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('role', User::ROLE_ADMIN)->first();
        $staff = User::query()->where('email', 'staff@bytestock.test')->first()
            ?? User::query()->where('role', User::ROLE_STAFF)->first();

        $this->seedPurchaseOrders($user);
        $this->seedSalesOrders($user, $staff);
    }

    private function seedPurchaseOrders(?User $user): void
    {
        $supplierIds = \App\Models\Supplier::query()->pluck('id', 'company_name');

        $orders = [
            [
                'order_number' => 'DEMO-PO-2001',
                'supplier' => 'Labangal Parts Depot',
                'order_date' => now()->subDays(12)->toDateString(),
                'expected_date' => now()->subDays(8)->toDateString(),
                'received_date' => now()->subDays(9)->toDateString(),
                'status' => PurchaseOrder::STATUS_RECEIVED,
                'approval_level' => PurchaseOrder::APPROVAL_FINAL,
                'notes' => 'Restock of high-end graphics cards.',
                'items' => [
                    ['sku' => 'GPU-NV-4070S', 'quantity' => 12, 'unit_cost' => 520.00],
                ],
            ],
            [
                'order_number' => 'DEMO-PO-2002',
                'supplier' => 'Dadiangas West Components',
                'order_date' => now()->subDays(6)->toDateString(),
                'expected_date' => now()->subDays(2)->toDateString(),
                'received_date' => now()->subDays(2)->toDateString(),
                'status' => PurchaseOrder::STATUS_RECEIVED,
                'approval_level' => PurchaseOrder::APPROVAL_FINAL,
                'notes' => 'Cases for the upcoming build queue.',
                'items' => [
                    ['sku' => 'CASE-LI-O11M', 'quantity' => 6, 'unit_cost' => 145.00],
                ],
            ],
            [
                'order_number' => 'DEMO-PO-2003',
                'supplier' => 'Calumpang Computer Supply',
                'order_date' => now()->subDays(1)->toDateString(),
                'expected_date' => now()->addDays(4)->toDateString(),
                'status' => PurchaseOrder::STATUS_PENDING,
                'notes' => 'Waiting for manager approval on the PSU price.',
                'items' => [
                    ['sku' => 'PSU-COR-RM750', 'quantity' => 8, 'unit_cost' => 95.00],
                    ['sku' => 'RAM-COR-32D5', 'quantity' => 6, 'unit_cost' => 115.00],
                ],
            ],
            [
                'order_number' => 'DEMO-PO-2004',
                'supplier' => 'Brgy. Fatima Electronics',
                'order_date' => now()->toDateString(),
                'expected_date' => now()->addDays(8)->toDateString(),
                'status' => PurchaseOrder::STATUS_SENT,
                'approval_level' => PurchaseOrder::APPROVAL_FINAL,
                'notes' => 'Sent to supplier; awaiting delivery.',
                'items' => [
                    ['sku' => 'SSD-SAM-990-2T', 'quantity' => 10, 'unit_cost' => 155.00],
                ],
            ],
        ];

        foreach ($orders as $data) {
            $items = $data['items'];
            $data['reference_number'] = $data['order_number'];
            $data['expected_delivery_date'] = $data['expected_date'];
            $data['supplier_id'] = $supplierIds->get($data['supplier']);
            $data['currency'] = 'PHP';
            unset($data['items']);

            $existing = PurchaseOrder::query()->where('order_number', $data['order_number'])->first();

            if ($existing !== null) {
                $existing->update([
                    'supplier' => $data['supplier'],
                    'supplier_id' => $data['supplier_id'],
                    'expected_delivery_date' => $data['expected_delivery_date'],
                    'notes' => $data['notes'],
                ]);

                InventoryTransaction::query()
                    ->where('reference', $existing->order_number)
                    ->update(['notes' => "Received from {$data['supplier']}"]);

                $existing->calculateTotal();
                continue;
            }

            $order = DB::transaction(function () use ($data, $items, $user) {
                $approved = in_array($data['status'], [
                    PurchaseOrder::STATUS_RECEIVED,
                    PurchaseOrder::STATUS_SENT,
                ], true);

                $order = PurchaseOrder::create($data + [
                    'user_id' => $user?->id,
                    'created_by' => $user?->id,
                    'approved_by' => $approved ? $user?->id : null,
                    'approved_at' => $approved ? now()->subDays(3) : null,
                    'sent_at' => $data['status'] === PurchaseOrder::STATUS_SENT ? now()->subDay() : null,
                ]);

                foreach ($items as $item) {
                    $product = Product::query()->where('sku', $item['sku'])->first();

                    if ($product === null) {
                        continue;
                    }

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'received_quantity' => $order->isReceived() ? $item['quantity'] : 0,
                    ]);
                }

                $order->calculateTotal();

                // Older demo stock movements use the same order_number reference,
                // so only write a new movement when one does not exist yet.
                if ($order->isReceived()) {
                    foreach ($order->items as $line) {
                        if (InventoryTransaction::query()->where('reference', $order->order_number)->exists()) {
                            break;
                        }

                        InventoryTransaction::create([
                            'product_id' => $line->product_id,
                            'type' => InventoryTransaction::TYPE_IN,
                            'quantity' => $line->quantity,
                            'reference' => $order->order_number,
                            'reason' => 'Purchase order received',
                            'notes' => "Received from {$order->supplier_name}",
                            'user_id' => $user?->id,
                        ]);
                    }
                }

                return $order;
            });

            ActivityLog::record(
                ActivityLog::MODULE_PURCHASE_ORDER,
                ActivityLog::ACTION_CREATED,
                "Purchase order {$order->reference_number} was created for {$order->supplier_name} with {$order->items->count()} item(s).",
                $order,
                $user,
            );
        }
    }

    private function seedSalesOrders(?User $user, ?User $staff): void
    {
        SalesOrder::query()
            ->where('status', SalesOrder::STATUS_COMPLETED)
            ->whereNull('approved_at')
            ->update([
                'approved_at' => DB::raw('COALESCE(order_date, created_at)'),
                'approved_by' => $user?->id,
            ]);

        $orders = [
            [
                'order_number' => 'DEMO-SO-1001',
                'customer' => 'Purok 2, Brgy. Dadiangas East',
                'order_date' => now()->subDays(10)->toDateString(),
                'due_date' => now()->subDays(7)->toDateString(),
                'status' => SalesOrder::STATUS_COMPLETED,
                'notes' => 'Walk-in customer, paid in full.',
                'items' => [
                    ['sku' => 'CPU-AMD-7800X3D', 'quantity' => 3, 'unit_price' => 359.99],
                ],
            ],
            [
                'order_number' => 'DEMO-SO-1002',
                'customer' => 'Purok 5, Brgy. Labangal',
                'order_date' => now()->subDays(9)->toDateString(),
                'due_date' => now()->subDays(6)->toDateString(),
                'status' => SalesOrder::STATUS_COMPLETED,
                'notes' => 'SSD upgrade for a rental unit.',
                'items' => [
                    ['sku' => 'SSD-WD-BLUE-1T', 'quantity' => 1, 'unit_price' => 69.95],
                ],
            ],
            [
                'order_number' => 'DEMO-SO-1003',
                'customer' => 'Purok 1, Brgy. Lagao',
                'order_date' => now()->subDays(5)->toDateString(),
                'due_date' => now()->subDays(3)->toDateString(),
                'status' => SalesOrder::STATUS_COMPLETED,
                'notes' => 'Shipped via courier.',
                'items' => [
                    ['sku' => 'RAM-GS-16D4', 'quantity' => 3, 'unit_price' => 59.00],
                ],
            ],
            [
                'order_number' => 'DEMO-SO-1004',
                'customer' => 'Purok 7, Brgy. Calumpang',
                'order_date' => now()->subDays(3)->toDateString(),
                'due_date' => now()->subDays(1)->toDateString(),
                'status' => SalesOrder::STATUS_COMPLETED,
                'notes' => 'Distributor order with a 5% discount.',
                'items' => [
                    ['sku' => 'COOL-ARC-LF3-360', 'quantity' => 2, 'unit_price' => 129.00],
                ],
            ],
            [
                'order_number' => 'DEMO-SO-1005',
                'customer' => 'Purok 3, Brgy. San Isidro',
                'order_date' => now()->subDays(1)->toDateString(),
                'due_date' => now()->addDays(3)->toDateString(),
                'status' => SalesOrder::STATUS_PENDING,
                'notes' => 'Awaiting final confirmation of the quantity.',
                'items' => [
                    ['sku' => 'PER-LOG-MX3S', 'quantity' => 4, 'unit_price' => 89.00],
                    ['sku' => 'PER-KEY-K8P', 'quantity' => 2, 'unit_price' => 129.00],
                ],
            ],
            [
                'order_number' => 'DEMO-SO-1006',
                'customer' => 'Purok 4, Brgy. City Heights',
                'order_date' => now()->toDateString(),
                'due_date' => now()->addDays(2)->toDateString(),
                'status' => SalesOrder::STATUS_PENDING,
                'notes' => 'Prepared by staff and waiting for admin approval.',
                'user_id' => $staff?->id,
                'items' => [
                    ['sku' => 'PSU-COR-RM750', 'quantity' => 2, 'unit_price' => 95.00],
                    ['sku' => 'RAM-COR-32D5', 'quantity' => 1, 'unit_price' => 115.00],
                ],
            ],
        ];

        foreach ($orders as $data) {
            $items = $data['items'];
            unset($data['items']);

            $existing = SalesOrder::query()->where('order_number', $data['order_number'])->first();

            if ($existing !== null) {
                $existing->update([
                    'customer' => $data['customer'],
                    'notes' => $data['notes'],
                ]);

                if ($existing->isCompleted()) {
                    InventoryTransaction::query()
                        ->where('reference', $existing->order_number)
                        ->update(['notes' => "Sold to {$data['customer']}"]);
                }

                ActivityLog::query()
                    ->where('module', ActivityLog::MODULE_SALES_ORDER)
                    ->where('action', ActivityLog::ACTION_CREATED)
                    ->where('subject_id', $existing->id)
                    ->update([
                        'description' => "Sales order {$existing->order_number} was created for {$data['customer']} with {$existing->items->count()} item(s).",
                    ]);

                continue;
            }

            $order = DB::transaction(function () use ($data, $items, $user) {
                $order = SalesOrder::create($data + ['user_id' => $user?->id]);

                if ($order->isCompleted()) {
                    $order->update([
                        'approved_at' => $order->order_date?->copy()->addDay() ?? now(),
                        'approved_by' => $user?->id,
                    ]);
                }

                foreach ($items as $item) {
                    $product = Product::query()->where('sku', $item['sku'])->first();

                    if ($product === null) {
                        continue;
                    }

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ]);
                }

                // Reuse the matching demo stock-out when the database already
                // has one, so re-seeding never deducts the same stock twice.
                if ($order->isCompleted()) {
                    foreach ($order->items as $line) {
                        if (InventoryTransaction::query()->where('reference', $order->order_number)->exists()) {
                            break;
                        }

                        InventoryTransaction::create([
                            'product_id' => $line->product_id,
                            'type' => InventoryTransaction::TYPE_OUT,
                            'quantity' => $line->quantity,
                            'reference' => $order->order_number,
                            'reason' => 'Sales order completed',
                            'notes' => "Sold to {$order->customer}",
                            'user_id' => $user?->id,
                        ]);
                    }
                }

                return $order;
            });

            ActivityLog::record(
                ActivityLog::MODULE_SALES_ORDER,
                ActivityLog::ACTION_CREATED,
                "Sales order {$order->order_number} was created for {$order->customer} with {$order->items->count()} item(s).",
                $order,
                $user,
            );
        }
    }
}
