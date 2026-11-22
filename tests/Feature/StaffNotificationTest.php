<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Database\Seeders\StaffNotificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_gets_an_example_low_stock_alert_in_the_bell(): void
    {
        $staff = User::forceCreate([
            'name' => 'Staff User',
            'email' => 'staff@bytestock.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand', 'description' => null]);
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category', 'description' => null]);
        Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Low Stock SSD',
            'sku' => 'LOW-STOCK-SSD',
            'quantity' => 1,
            'reorder_level' => 5,
            'unit_price' => 100.00,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->seed(StaffNotificationSeeder::class);

        $this->assertDatabaseHas('notifications', [
            'type' => LowStockNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Low stock alert: Low Stock SSD');
    }
}
