<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\OrderActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand', 'description' => null]);
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category', 'description' => null]);

        $this->user = User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Test Processor',
            'sku' => 'TEST-CPU-001',
            'description' => 'A test product',
            'quantity' => 20,
            'reorder_level' => 5,
            'unit_price' => 100.00,
            'supplier' => 'Test Supplier',
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-TEST-001',
            'company_name' => 'Test Supplier',
            'contact_person' => 'Test Contact',
            'email' => 'supplier@example.test',
            'phone' => '09171234567',
            'address' => 'Test Address',
            'payment_terms' => 'Net 30',
            'lead_time_days' => 7,
            'minimum_order_amount' => 0,
            'status' => Supplier::STATUS_ACTIVE,
        ]);
    }

    public function test_receiving_a_purchase_order_adds_stock_and_writes_audit_rows(): void
    {
        $order = PurchaseOrder::create([
            'order_number' => 'PO-TEST-001',
            'reference_number' => 'PO-TEST-001',
            'supplier_id' => $this->supplier->id,
            'supplier' => 'Test Supplier',
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_SENT,
            'user_id' => $this->user->id,
        ]);
        $item = $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_cost' => 80.00,
        ]);

        $this->actingAs($this->user)
            ->post(route('purchase-orders.receive', $order), [
                'received' => [$item->id => 5],
            ])
            ->assertRedirect(route('purchase-orders.show', $order));

        $this->assertSame(25, $this->product->fresh()->quantity);
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);

        $movement = InventoryTransaction::query()->where('reference', 'PO-TEST-001')->firstOrFail();
        $this->assertSame(InventoryTransaction::TYPE_IN, $movement->type);
        $this->assertSame(20, $movement->previous_quantity);
        $this->assertSame(25, $movement->balance_after);

        $this->assertDatabaseHas('activity_logs', [
            'module' => ActivityLog::MODULE_PURCHASE_ORDER,
            'action' => ActivityLog::ACTION_RECEIVED,
        ]);
    }

    public function test_completing_a_sales_order_deducts_stock_and_writes_audit_rows(): void
    {
        $order = SalesOrder::create([
            'order_number' => 'SO-TEST-001',
            'customer' => 'Test Customer',
            'order_date' => now()->toDateString(),
            'status' => SalesOrder::STATUS_PENDING,
            'user_id' => $this->user->id,
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 4,
            'unit_price' => 150.00,
        ]);

        $this->actingAs($this->user);

        $this->post(route('sales-orders.approve', $order))
            ->assertRedirect(route('sales-orders.show', $order));

        $this->post(route('sales-orders.complete', $order))
            ->assertRedirect(route('sales-orders.show', $order));

        $this->assertSame(16, $this->product->fresh()->quantity);
        $this->assertSame(SalesOrder::STATUS_COMPLETED, $order->fresh()->status);

        $movement = InventoryTransaction::query()->where('reference', 'SO-TEST-001')->firstOrFail();
        $this->assertSame(InventoryTransaction::TYPE_OUT, $movement->type);
        $this->assertSame(20, $movement->previous_quantity);
        $this->assertSame(16, $movement->balance_after);

        $this->assertDatabaseHas('activity_logs', [
            'module' => ActivityLog::MODULE_SALES_ORDER,
            'action' => ActivityLog::ACTION_COMPLETED,
        ]);
    }

    public function test_duplicate_order_lines_are_checked_together_before_approval(): void
    {
        $order = SalesOrder::create([
            'order_number' => 'SO-TEST-002',
            'customer' => 'Test Customer',
            'order_date' => now()->toDateString(),
            'status' => SalesOrder::STATUS_PENDING,
            'user_id' => $this->user->id,
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 12,
            'unit_price' => 150.00,
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 12,
            'unit_price' => 150.00,
        ]);

        $this->actingAs($this->user)
            ->from(route('sales-orders.show', $order))
            ->post(route('sales-orders.approve', $order))
            ->assertRedirect(route('sales-orders.show', $order))
            ->assertSessionHas('error');

        $this->assertSame(20, $this->product->fresh()->quantity);
        $this->assertSame(SalesOrder::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_a_received_purchase_order_cannot_be_edited_again(): void
    {
        // Admins bypass gates by design; use a manager to exercise the policy.
        $manager = User::forceCreate([
            'name' => 'Manager',
            'email' => 'manager@example.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_MANAGER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $order = PurchaseOrder::create([
            'order_number' => 'PO-TEST-003',
            'reference_number' => 'PO-TEST-003',
            'supplier_id' => $this->supplier->id,
            'supplier' => 'Test Supplier',
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($manager)
            ->get(route('purchase-orders.edit', $order))
            ->assertForbidden();
    }

    public function test_out_of_stock_products_cannot_be_added_to_a_sales_order(): void
    {
        $this->product->update(['quantity' => 0]);

        $this->actingAs($this->user)
            ->post(route('sales-orders.store'), [
                'customer' => 'Walk-in Customer',
                'order_date' => now()->toDateString(),
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                ]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_every_sales_order_needs_admin_approval_before_completion(): void
    {
        $order = SalesOrder::create([
            'order_number' => 'SO-TEST-003',
            'customer' => 'Test Customer',
            'order_date' => now()->toDateString(),
            'status' => SalesOrder::STATUS_PENDING,
            'user_id' => $this->user->id,
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 150.00,
        ]);

        $this->actingAs($this->user)
            ->post(route('sales-orders.complete', $order))
            ->assertForbidden();

        $this->assertSame(SalesOrder::STATUS_PENDING, $order->fresh()->status);

        $this->post(route('sales-orders.approve', $order))
            ->assertRedirect(route('sales-orders.show', $order));

        $this->assertSame(SalesOrder::STATUS_APPROVED, $order->fresh()->status);
        $this->assertSame($this->user->id, $order->fresh()->approved_by);
    }

    public function test_staff_cannot_approve_a_sales_order(): void
    {
        $staff = User::forceCreate([
            'name' => 'Staff User',
            'email' => 'staff@example.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $order = SalesOrder::create([
            'order_number' => 'SO-TEST-004',
            'customer' => 'Test Customer',
            'order_date' => now()->toDateString(),
            'status' => SalesOrder::STATUS_PENDING,
            'user_id' => $staff->id,
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 150.00,
        ]);

        $this->actingAs($staff)
            ->post(route('sales-orders.approve', $order))
            ->assertForbidden();

        $this->assertSame(SalesOrder::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_purchase_order_actions_notify_admin_and_staff_both_ways(): void
    {
        Notification::fake();

        $staff = User::forceCreate([
            'name' => 'Staff User',
            'email' => 'staff@example.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($staff)
            ->post(route('purchase-orders.store'), [
                'supplier_id' => $this->supplier->id,
                'order_date' => now()->toDateString(),
                'submit_for_approval' => 1,
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_cost' => 80.00,
                ]],
            ])
            ->assertRedirect();

        $order = PurchaseOrder::query()->where('supplier_id', $this->supplier->id)->firstOrFail();

        Notification::assertSentTo($this->user, OrderActionNotification::class,
            fn (OrderActionNotification $notification) => $notification->module === 'Purchase order'
                && $notification->action === 'created');

        $this->actingAs($this->user)
            ->post(route('purchase-orders.approve', $order))
            ->assertRedirect(route('purchase-orders.show', $order));

        $this->post(route('purchase-orders.send', $order))
            ->assertRedirect(route('purchase-orders.show', $order));

        $item = $order->items()->firstOrFail();

        $this->post(route('purchase-orders.receive', $order), [
                'received' => [$item->id => 2],
            ])
            ->assertRedirect(route('purchase-orders.show', $order));

        Notification::assertSentTo($staff, OrderActionNotification::class,
            fn (OrderActionNotification $notification) => $notification->module === 'Purchase order'
                && $notification->action === 'received');
    }

    public function test_sales_order_creation_notifies_admin_and_approval_notifies_staff(): void
    {
        Notification::fake();

        $staff = User::forceCreate([
            'name' => 'Staff User',
            'email' => 'staff@example.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($staff)
            ->post(route('sales-orders.store'), [
                'customer' => 'Walk-in Customer',
                'order_date' => now()->toDateString(),
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 150.00,
                ]],
            ])
            ->assertRedirect();

        $order = SalesOrder::query()->where('customer', 'Walk-in Customer')->firstOrFail();

        Notification::assertSentTo($this->user, OrderActionNotification::class,
            fn (OrderActionNotification $notification) => $notification->module === 'Sales order'
                && $notification->action === 'created');

        $this->actingAs($this->user)
            ->post(route('sales-orders.approve', $order))
            ->assertRedirect(route('sales-orders.show', $order));

        Notification::assertSentTo($staff, OrderActionNotification::class,
            fn (OrderActionNotification $notification) => $notification->module === 'Sales order'
                && $notification->action === 'approved');
    }

    public function test_receipt_is_available_only_for_completed_sales_orders(): void
    {
        $order = SalesOrder::create([
            'order_number' => 'SO-TEST-005',
            'customer' => 'Receipt Customer',
            'order_date' => now()->toDateString(),
            'status' => SalesOrder::STATUS_PENDING,
            'user_id' => $this->user->id,
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 150.00,
        ]);

        $staff = User::forceCreate([
            'name' => 'Staff User',
            'email' => 'staff@example.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->user);

        $this->get(route('sales-orders.receipt', $order))->assertForbidden();

        $this->post(route('sales-orders.approve', $order))
            ->assertRedirect(route('sales-orders.show', $order));
        $this->post(route('sales-orders.complete', $order))
            ->assertRedirect(route('sales-orders.show', $order));

        $this->get(route('sales-orders.receipt', $order))
            ->assertOk()
            ->assertSee('Sales Receipt')
            ->assertSee($order->order_number)
            ->assertSee($this->product->name)
            ->assertSee('300.00')
            ->assertDontSee('Print receipt')
            ->assertDontSee('window.print()');

        $pending = SalesOrder::create([
            'order_number' => 'SO-TEST-006',
            'customer' => 'Pending Receipt Customer',
            'order_date' => now()->toDateString(),
            'status' => SalesOrder::STATUS_PENDING,
            'user_id' => $this->user->id,
        ]);

        $this->get(route('receipts.index'))
            ->assertOk()
            ->assertSee('Sales receipts')
            ->assertSee($order->order_number)
            ->assertSee($order->customer)
            ->assertSee('300.00')
            ->assertDontSee($pending->order_number);

        $this->actingAs($staff);

        $this->get(route('receipts.index'))->assertOk();
        $this->get(route('sales-orders.receipt', $order))->assertOk();
    }
}
