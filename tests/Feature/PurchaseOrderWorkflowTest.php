<?php

namespace Tests\Feature;

use App\Mail\PurchaseOrderApproved;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    private Product $product;

    private User $admin;

    private User $manager;

    private User $staff;

    private User $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::create([
            'code' => 'SUP-WF', 'company_name' => 'Workflow Supplier',
            'contact_person' => 'Contact', 'email' => 'wf@supplier.test',
            'phone' => '09171234567', 'address' => 'Address',
            'payment_terms' => 'Net 30', 'lead_time_days' => 7,
            'minimum_order_amount' => 0, 'status' => 'active',
        ]);

        $brand = Brand::create(['name' => 'Workflow Brand', 'slug' => 'workflow-brand', 'description' => null]);
        $category = Category::create(['name' => 'Workflow Category', 'slug' => 'workflow-category', 'description' => null]);

        $this->product = Product::create([
            'brand_id' => $brand->id, 'category_id' => $category->id,
            'name' => 'Workflow Product', 'sku' => 'WF-PROD-1',
            'quantity' => 10, 'reorder_level' => 5, 'unit_price' => 100,
            'cost_price' => 70, 'supplier_id' => $this->supplier->id,
            'supplier' => $this->supplier->company_name, 'status' => 'active',
        ]);

        $this->admin = $this->makeUser('admin', 'wf-admin@test.test');
        $this->manager = $this->makeUser(User::ROLE_MANAGER, 'wf-manager@test.test');
        $this->staff = $this->makeUser(User::ROLE_STAFF, 'wf-staff@test.test');
        $this->warehouse = $this->makeUser(User::ROLE_WAREHOUSE, 'wf-warehouse@test.test');
    }

    private function makeUser(string $role, string $email): User
    {
        return User::forceCreate([
            'name' => ucfirst($role), 'email' => $email, 'password' => bcrypt('password'),
            'role' => $role, 'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createOrder(array $overrides = []): PurchaseOrder
    {
        $reference = $overrides['reference_number'] ?? 'PO-WF-'.PurchaseOrder::count();

        $order = PurchaseOrder::create(array_merge([
            'order_number' => $reference,
            'reference_number' => $reference,
            'supplier_id' => $this->supplier->id,
            'supplier' => $this->supplier->company_name,
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'status' => PurchaseOrder::STATUS_PENDING,
            'user_id' => $this->staff->id,
            'created_by' => $this->staff->id,
        ], $overrides));

        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_cost' => 80,
        ]);

        // A caller-supplied total models a special/high-value order and must
        // not be overwritten by the ordinary line-item calculation.
        if (! array_key_exists('total', $overrides)) {
            $order->calculateTotal();
        }

        return $order;
    }

    public function test_staff_can_create_a_draft_but_cannot_approve_it(): void
    {
        Mail::fake();

        $this->actingAs($this->staff)
            ->post(route('purchase-orders.store'), [
                'supplier_id' => $this->supplier->id,
                'order_date' => now()->toDateString(),
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_cost' => 80,
                ]],
            ])->assertRedirect();

        $order = PurchaseOrder::firstOrFail();
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertSame($order->reference_number, $order->order_number);
        $this->assertEquals(268.8, (float) $order->total); // 240 + 12% tax

        $this->post(route('purchase-orders.approve', $order))->assertForbidden();
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->fresh()->status);
    }

    public function test_manager_can_approve_and_send_and_warehouse_can_receive(): void
    {
        Mail::fake();
        $order = $this->createOrder();

        $this->actingAs($this->manager)
            ->post(route('purchase-orders.approve', $order))
            ->assertRedirect(route('purchase-orders.show', $order));
        $this->assertSame(PurchaseOrder::STATUS_APPROVED, $order->fresh()->status);
        $this->assertSame($this->manager->id, $order->fresh()->approved_by);

        $this->post(route('purchase-orders.send', $order))
            ->assertRedirect(route('purchase-orders.show', $order));
        $this->assertSame(PurchaseOrder::STATUS_SENT, $order->fresh()->status);

        $item = $order->items()->firstOrFail();

        $this->actingAs($this->warehouse)
            ->post(route('purchase-orders.receive', $order), [
                'received' => [$item->id => 6],
            ])
            ->assertRedirect(route('purchase-orders.show', $order));

        $this->assertSame(PurchaseOrder::STATUS_SENT, $order->fresh()->status);
        $this->assertTrue((bool) $order->fresh()->partially_received);
        $this->assertSame(6, $item->fresh()->received_quantity);
        $this->assertSame(16, $this->product->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $this->product->id,
            'reference' => $order->reference_number,
            'quantity' => 6,
        ]);

        $this->post(route('purchase-orders.receive', $order), [
            'received' => [$item->id => 4],
        ])->assertRedirect();
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);
        $this->assertSame(20, $this->product->fresh()->quantity);
    }

    public function test_high_value_order_requires_manager_then_admin_approval(): void
    {
        $order = $this->createOrder(['total' => 75000, 'status' => PurchaseOrder::STATUS_PENDING]);

        $this->actingAs($this->manager)
            ->post(route('purchase-orders.approve', $order))
            ->assertRedirect();

        $this->assertSame(PurchaseOrder::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(PurchaseOrder::APPROVAL_FIRST, (int) $order->fresh()->approval_level);
        $this->assertTrue($order->fresh()->isAwaitingFinalApproval());

        $this->post(route('purchase-orders.approve', $order))->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('purchase-orders.approve', $order))
            ->assertRedirect();

        $this->assertSame(PurchaseOrder::STATUS_APPROVED, $order->fresh()->status);
        $this->assertSame(PurchaseOrder::APPROVAL_FINAL, (int) $order->fresh()->approval_level);
        $this->assertSame($this->admin->id, $order->fresh()->approved_by);
    }

    public function test_rejecting_an_order_records_the_reason_and_a_note(): void
    {
        $order = $this->createOrder();

        $this->actingAs($this->manager)
            ->post(route('purchase-orders.reject', $order), [
                'rejection_reason' => 'Budget not approved.',
            ])->assertRedirect();

        $this->assertSame(PurchaseOrder::STATUS_REJECTED, $order->fresh()->status);
        $this->assertSame('Budget not approved.', $order->fresh()->rejection_reason);
        $this->assertDatabaseHas('notes', [
            'notable_type' => $order->getMorphClass(),
            'notable_id' => $order->id,
            'type' => 'rejection',
        ]);
    }

    public function test_approval_and_creation_emails_are_queued(): void
    {
        Mail::fake();
        $order = $this->createOrder();

        $this->actingAs($this->manager)
            ->post(route('purchase-orders.approve', $order))
            ->assertRedirect();

        Mail::assertQueued(PurchaseOrderApproved::class);
    }

    public function test_only_draft_orders_can_be_edited_by_their_creator(): void
    {
        $order = $this->createOrder(['status' => PurchaseOrder::STATUS_DRAFT]);
        $other = $this->makeUser(User::ROLE_STAFF, 'other-staff@test.test');

        $this->actingAs($this->staff)
            ->get(route('purchase-orders.edit', $order))->assertOk();

        $this->actingAs($other)
            ->get(route('purchase-orders.edit', $order))->assertForbidden();

        $order->update(['status' => PurchaseOrder::STATUS_APPROVED]);
        $this->actingAs($this->staff)
            ->get(route('purchase-orders.edit', $order))->assertForbidden();
    }

    public function test_line_item_helpers_track_outstanding_quantity(): void
    {
        $order = $this->createOrder();
        $item = $order->items()->firstOrFail();

        $item->update(['received_quantity' => 4]);

        $this->assertSame(6, $item->fresh()->outstanding_quantity);
        $this->assertEquals(800.0, (float) $item->fresh()->line_total);
        $this->assertEquals(80.0, (float) $item->fresh()->unit_price);
    }
}
