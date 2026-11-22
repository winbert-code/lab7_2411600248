<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InventoryTransactionSeeder extends Seeder
{
    /**
     * Create a small, repeatable demo audit trail through the observer so the
     * lab UI has meaningful statistics to display.
     */
    public function run(): void
    {
        $user = User::query()->orderBy('id')->value('id');

        if ($user === null) {
            return;
        }

        $movements = [
            ['sku' => 'CPU-AMD-7800X3D', 'type' => InventoryTransaction::TYPE_OUT, 'quantity' => 3, 'reference' => 'DEMO-SO-1001', 'reason' => 'Sales order', 'days' => 0],
            ['sku' => 'GPU-NV-4070S', 'type' => InventoryTransaction::TYPE_IN, 'quantity' => 12, 'reference' => 'DEMO-PO-2001', 'reason' => 'Purchase order', 'days' => 0],
            ['sku' => 'SSD-WD-BLUE-1T', 'type' => InventoryTransaction::TYPE_OUT, 'quantity' => 1, 'reference' => 'DEMO-SO-1002', 'reason' => 'Sales order', 'days' => 1],
            ['sku' => 'RAM-GS-16D4', 'type' => InventoryTransaction::TYPE_OUT, 'quantity' => 3, 'reference' => 'DEMO-SO-1003', 'reason' => 'Sales order', 'days' => 2],
            ['sku' => 'CASE-LI-O11M', 'type' => InventoryTransaction::TYPE_IN, 'quantity' => 6, 'reference' => 'DEMO-PO-2002', 'reason' => 'Purchase order', 'days' => 3],
            ['sku' => 'COOL-ARC-LF3-360', 'type' => InventoryTransaction::TYPE_OUT, 'quantity' => 2, 'reference' => 'DEMO-SO-1004', 'reason' => 'Sales order', 'days' => 4],
        ];

        foreach ($movements as $movement) {
            if (InventoryTransaction::query()->where('reference', $movement['reference'])->exists()) {
                continue;
            }

            $product = Product::query()->where('sku', $movement['sku'])->first();

            if ($product === null) {
                continue;
            }

            DB::transaction(function () use ($movement, $product, $user) {
                $transaction = InventoryTransaction::create([
                    'product_id' => $product->id,
                    'type' => $movement['type'],
                    'quantity' => $movement['quantity'],
                    'reference' => $movement['reference'],
                    'reason' => $movement['reason'],
                    'notes' => 'Demo data for Laboratory Exercise 6.',
                    'user_id' => $user,
                ]);

                if ($movement['days'] > 0) {
                    DB::table('inventory_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'created_at' => now()->subDays($movement['days']),
                            'updated_at' => now()->subDays($movement['days']),
                        ]);
                }
            });
        }

        // Give the notification bell something to show. The real alert logic is
        // exercised by the observer; this simply queues one demo alert.
        if (DB::table('notifications')->count() === 0) {
            $lowStockProduct = Product::query()->lowStock()->orderBy('quantity')->first();

            if ($lowStockProduct !== null) {
                $recipients = User::query()->whereIn('role', ['admin', 'manager'])->get();

                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, new LowStockNotification($lowStockProduct));
                }
            }
        }
    }
}
