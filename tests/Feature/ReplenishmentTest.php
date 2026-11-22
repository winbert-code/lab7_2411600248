<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReorderService;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplenishmentTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    private Product $product;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::forceCreate([
            'name' => 'Admin', 'email' => 'replenish-admin@test.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-REP', 'company_name' => 'Replenishment Supplier',
            'contact_person' => 'Contact', 'email' => 'rep@supplier.test',
            'phone' => '09171234567', 'address' => 'Address',
            'payment_terms' => 'Net 30', 'lead_time_days' => 5,
            'minimum_order_amount' => 0, 'status' => 'active',
        ]);

        $brand = Brand::create(['name' => 'Replenishment Brand', 'slug' => 'replenishment-brand', 'description' => null]);
        $category = Category::create(['name' => 'Replenishment Category', 'slug' => 'replenishment-category', 'description' => null]);

        $this->product = Product::create([
            'brand_id' => $brand->id, 'category_id' => $category->id,
            'name' => 'Replenishment Product', 'sku' => 'REP-001',
            'quantity' => 100, 'reorder_level' => 5, 'safety_stock' => 10,
            'unit_price' => 100, 'cost_price' => 70,
            'supplier_id' => $this->supplier->id,
            'supplier' => $this->supplier->company_name,
            'status' => 'active',
        ]);
    }

    public function test_reorder_point_uses_usage_lead_time_and_safety_stock(): void
    {
        // 60 units consumed over the 30-day window => 2 units/day.
        for ($i = 0; $i < 3; $i++) {
            InventoryTransaction::create([
                'product_id' => $this->product->id,
                'type' => InventoryTransaction::TYPE_OUT,
                'quantity' => 20,
                'reference' => 'USAGE-'.$i,
                'user_id' => $this->admin->id,
            ]);
        }

        $service = app(ReorderService::class);

        // (2 x 5) + 10 safety stock = 20
        $this->assertSame(20, $service->calculateReorderPoint($this->product->fresh()));
        $this->assertGreaterThanOrEqual(20, $service->calculateOptimalOrderQuantity($this->product->fresh(), 20));
    }

    public function test_command_generates_one_draft_order_per_supplier_and_is_idempotent(): void
    {
        $this->product->update(['quantity' => 2]);

        $this->artisan('replenishment:check-reorder')
            ->expectsOutputToContain('Reorder check finished successfully.')
            ->assertSuccessful();

        $this->assertDatabaseCount('purchase_orders', 1);
        $order = PurchaseOrder::firstOrFail();
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertSame($this->supplier->id, (int) $order->supplier_id);
        $this->assertSame($order->reference_number, $order->order_number);
        $this->assertSame('REP-001', $order->items()->firstOrFail()->product->sku);

        // Running again must not duplicate the open order.
        $this->artisan('replenishment:check-reorder')->assertSuccessful();
        $this->assertDatabaseCount('purchase_orders', 1);
    }

    public function test_dashboard_widgets_and_api_endpoints_return_procurement_data(): void
    {
        $this->product->update(['quantity' => 1]);
        PurchaseOrder::create([
            'order_number' => 'PO-DASH-1', 'reference_number' => 'PO-DASH-1',
            'supplier_id' => $this->supplier->id, 'supplier' => $this->supplier->company_name,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_PENDING,
            'total' => 500,
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pending approvals')
            ->assertSee('Reorder alerts')
            ->assertSee('Supplier performance');

        $this->getJson(route('api.dashboard.pending-orders'))
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->getJson(route('api.dashboard.recent-orders'))
            ->assertOk()
            ->assertJsonPath('data.0.reference_number', 'PO-DASH-1');

        $this->getJson(route('api.dashboard.supplier-performance'))
            ->assertOk()
            ->assertJsonPath('data.0.company_name', 'Replenishment Supplier');

        $this->getJson(route('api.dashboard.reorder-alerts'))
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'REP-001');
    }

    public function test_csv_and_pdf_exports_are_available(): void
    {
        $this->actingAs($this->admin)
            ->get(route('exports.products'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get(route('exports.purchase-orders'))->assertOk();
        $this->get(route('supplier-performance.export'))->assertOk();

        $this->get(route('exports.pdf', 'suppliers'))->assertOk();
    }

    public function test_search_service_filters_products_orders_and_suppliers(): void
    {
        $reference = 'PO-SEARCH-99';
        PurchaseOrder::create([
            'order_number' => $reference, 'reference_number' => $reference,
            'supplier_id' => $this->supplier->id, 'supplier' => $this->supplier->company_name,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_SENT,
            'total' => 1000,
        ]);

        $search = app(SearchService::class);

        $this->assertSame(1, $search->searchProducts(['search' => 'REP-001'])->total());
        $this->assertSame(0, $search->searchProducts(['search' => 'NOT-FOUND'])->total());
        $this->assertSame(1, $search->searchPurchaseOrders(['search' => 'PO-SEARCH'])->total());
        $this->assertSame(1, $search->searchPurchaseOrders(['status' => 'sent'])->total());
        $this->assertSame(0, $search->searchPurchaseOrders(['status' => 'draft'])->total());
        $this->assertSame(1, $search->supplierTotals()->count());
    }

    public function test_supplier_performance_report_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('supplier-performance.index'))
            ->assertOk()
            ->assertSee('Supplier Performance Report')
            ->assertSee('Replenishment Supplier')
            ->assertSee('Performance summary');
    }
}
