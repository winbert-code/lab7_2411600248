<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::forceCreate([
            'name' => 'Admin', 'email' => 'admin@test.test', 'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->staff = User::forceCreate([
            'name' => 'Staff', 'email' => 'staff@test.test', 'password' => bcrypt('password'),
            'role' => User::ROLE_STAFF, 'status' => User::STATUS_ACTIVE,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'SUP-1001',
            'company_name' => 'Test Supplier Inc.',
            'contact_person' => 'Juan Dela Cruz',
            'email' => 'juan@testsupplier.test',
            'phone' => '09171234567',
            'address' => '123 Test Street',
            'city' => 'General Santos',
            'payment_terms' => 'Net 30',
            'lead_time_days' => 7,
            'minimum_order_amount' => 5000,
            'status' => 'active',
        ], $overrides);
    }

    public function test_admin_can_create_update_and_list_suppliers(): void
    {
        $this->actingAs($this->admin)
            ->post(route('suppliers.store'), $this->payload())
            ->assertRedirect();

        $supplier = Supplier::firstOrFail();
        $this->assertSame('SUP-1001', $supplier->code);

        $this->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Test Supplier Inc.')
            ->assertSee('Net 30');

        $this->get(route('suppliers.show', $supplier))->assertOk()->assertSee('On-time delivery');

        $this->patch(route('suppliers.update', $supplier), $this->payload(['company_name' => 'Renamed Supplier']))
            ->assertRedirect();

        $this->assertSame('Renamed Supplier', $supplier->fresh()->company_name);
    }

    public function test_supplier_validation_rejects_duplicate_email_and_bad_phone(): void
    {
        $this->actingAs($this->admin)
            ->post(route('suppliers.store'), $this->payload())
            ->assertRedirect();

        $this->post(route('suppliers.store'), $this->payload([
            'code' => 'SUP-1002', 'email' => 'juan@testsupplier.test',
        ]))->assertSessionHasErrors('email');

        $this->post(route('suppliers.store'), $this->payload([
            'code' => 'SUP-1003', 'email' => 'other@testsupplier.test', 'phone' => 'abc',
        ]))->assertSessionHasErrors('phone');
    }

    public function test_soft_deleted_supplier_is_excluded_from_lists_but_can_be_restored(): void
    {
        $supplier = Supplier::create($this->payload());

        $this->actingAs($this->admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertSoftDeleted($supplier);
        $this->get(route('suppliers.index'))->assertDontSee('Test Supplier Inc.');

        $this->patch(route('suppliers.restore', $supplier))->assertRedirect();
        $this->assertNotSoftDeleted($supplier);
    }

    public function test_supplier_with_open_purchase_order_cannot_be_deleted(): void
    {
        $supplier = Supplier::create($this->payload());
        PurchaseOrder::create([
            'order_number' => 'PO-OPEN-1', 'reference_number' => 'PO-OPEN-1',
            'supplier_id' => $supplier->id, 'supplier' => $supplier->company_name,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($supplier);
    }

    public function test_staff_cannot_create_or_delete_suppliers(): void
    {
        $supplier = Supplier::create($this->payload());

        $this->actingAs($this->staff)
            ->get(route('suppliers.create'))->assertForbidden();

        $this->post(route('suppliers.store'), $this->payload())->assertForbidden();

        $this->delete(route('suppliers.destroy', $supplier))->assertForbidden();

        $this->get(route('suppliers.index'))->assertOk();
    }

    public function test_performance_metrics_are_calculated_from_orders(): void
    {
        $supplier = Supplier::create($this->payload());

        PurchaseOrder::create([
            'order_number' => 'PO-METRIC-1', 'reference_number' => 'PO-METRIC-1',
            'supplier_id' => $supplier->id, 'supplier' => $supplier->company_name,
            'order_date' => now()->subDays(10)->toDateString(),
            'expected_delivery_date' => now()->subDays(3)->toDateString(),
            'received_date' => now()->subDays(5)->toDateString(),
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'approval_level' => PurchaseOrder::APPROVAL_FINAL,
            'approved_at' => now()->subDays(9),
            'total' => 1000,
        ]);

        $metrics = $supplier->performanceMetrics();

        $this->assertSame(1, $metrics['total_orders']);
        $this->assertSame(1, $metrics['received_orders']);
        $this->assertSame(100.0, (float) $metrics['on_time_percentage']);
        $this->assertSame(1000.0, (float) $metrics['total_value']);
    }
}
