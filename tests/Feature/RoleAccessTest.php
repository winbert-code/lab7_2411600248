<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::forceCreate([
            'name' => ucfirst($role).' User',
            'email' => $role.'@example.test',
            'password' => bcrypt('password123'),
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_staff_can_view_records_and_draft_orders(): void
    {
        $staff = $this->user(User::ROLE_STAFF);

        $this->actingAs($staff);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('sales-orders.index'))->assertOk();
        $this->get(route('sales-orders.create'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
    }

    public function test_staff_cannot_change_stock_or_manage_users(): void
    {
        $staff = $this->user(User::ROLE_STAFF);

        $this->actingAs($staff);

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('products.create'))->assertForbidden();
        $this->get(route('transactions.create'))->assertForbidden();
        $this->get(route('reports.export'))->assertForbidden();
    }

    public function test_staff_can_edit_a_draft_but_cannot_receive_stock(): void
    {
        $staff = $this->user(User::ROLE_STAFF);
        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand', 'description' => null]);
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category', 'description' => null]);
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Test Processor',
            'sku' => 'TEST-CPU-001',
            'quantity' => 10,
            'reorder_level' => 5,
            'unit_price' => 100.00,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $order = PurchaseOrder::create([
            'order_number' => 'PO-STAFF-001',
            'supplier' => 'Test Supplier',
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'user_id' => $staff->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 80.00,
        ]);

        $this->actingAs($staff);

        $this->get(route('purchase-orders.edit', $order))->assertOk();
        $this->post(route('purchase-orders.receive', $order))->assertForbidden();

        $this->assertSame(10, $product->fresh()->quantity);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->fresh()->status);
    }

    public function test_manager_can_manage_stock_but_not_user_accounts(): void
    {
        $manager = $this->user(User::ROLE_MANAGER);

        $this->actingAs($manager);

        $this->get(route('products.create'))->assertOk();
        $this->get(route('transactions.create'))->assertOk();
        $this->get(route('reports.export'))->assertOk();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_has_full_access(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin);

        $this->get(route('users.index'))->assertOk();
        $this->get(route('products.create'))->assertOk();
        $this->get(route('transactions.create'))->assertOk();
        $this->get(route('reports.export'))->assertOk();
    }

    public function test_plain_user_is_read_only(): void
    {
        $user = $this->user(User::ROLE_USER);

        $this->actingAs($user);

        $this->get(route('products.index'))->assertOk();
        $this->get(route('sales-orders.create'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }
}
