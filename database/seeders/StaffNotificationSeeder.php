<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StaffNotificationSeeder extends Seeder
{
    /**
     * Give the demo staff account an example low-stock alert in the bell menu.
     */
    public function run(): void
    {
        $staff = User::query()
            ->where('email', 'staff@bytestock.test')
            ->first()
            ?? User::query()
                ->where('role', User::ROLE_STAFF)
                ->first();

        if (! $staff) {
            return;
        }

        $product = Product::query()->lowStock()->first();

        if (! $product) {
            return;
        }

        $alreadyNotified = $staff->notifications()
            ->where('type', LowStockNotification::class)
            ->where('data', 'like', '%"product_id":'.$product->id.'%')
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $staff->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => LowStockNotification::class,
            'data' => (new LowStockNotification($product))->toArray($staff),
        ]);
    }
}
