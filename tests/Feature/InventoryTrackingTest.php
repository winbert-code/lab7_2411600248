<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InventoryTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand', 'description' => null]);
        Category::create(['name' => 'Test Category', 'slug' => 'test-category', 'description' => null]);
    }

    private function user(string $role = User::ROLE_ADMIN): User
    {
        return User::forceCreate([
            'name' => ucfirst($role),
            'email' => $role.'@example.test',
            'password' => bcrypt('password123'),
            'role' => $role,
        ]);
    }

    private function product(int $quantity = 20, int $reorderLevel = 5): Product
    {
        return Product::create([
            'brand_id' => 1,
            'category_id' => 1,
            'name' => 'Test Processor',
            'sku' => 'TEST-CPU-001',
            'description' => 'A test product',
            'quantity' => $quantity,
            'reorder_level' => $reorderLevel,
            'unit_price' => 100.00,
            'supplier' => 'Test Supplier',
            'status' => Product::STATUS_ACTIVE,
        ]);
    }

    public function test_stock_in_increases_quantity_and_records_the_balance(): void
    {
        $product = $this->product(10);

        $transaction = InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => InventoryTransaction::TYPE_IN,
            'quantity' => 5,
            'reference' => 'PO-001',
            'user_id' => $this->user()->id,
        ]);

        $this->assertSame(15, $product->fresh()->quantity);
        $this->assertSame(10, $transaction->previous_quantity);
        $this->assertSame(15, $transaction->balance_after);
    }

    public function test_stock_out_decreases_quantity(): void
    {
        $product = $this->product(10);

        InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => InventoryTransaction::TYPE_OUT,
            'quantity' => 4,
            'reference' => 'SO-001',
            'user_id' => $this->user()->id,
        ]);

        $this->assertSame(6, $product->fresh()->quantity);
    }

    public function test_observer_blocks_a_stock_out_that_would_make_quantity_negative(): void
    {
        $product = $this->product(2);

        $this->expectException(InsufficientStockException::class);

        DB::transaction(function () use ($product) {
            InventoryTransaction::create([
                'product_id' => $product->id,
                'type' => InventoryTransaction::TYPE_OUT,
                'quantity' => 10,
                'user_id' => $this->user()->id,
            ]);
        });
    }

    public function test_stock_out_validation_rejects_insufficient_stock_over_http(): void
    {
        $product = $this->product(3);
        $user = $this->user();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'product_id' => $product->id,
            'type' => InventoryTransaction::TYPE_OUT,
            'quantity' => 30,
            'reference' => 'SO-BAD',
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(3, $product->fresh()->quantity);
        $this->assertSame(0, InventoryTransaction::count());
    }

    public function test_adjustment_sets_the_absolute_physical_count(): void
    {
        $product = $this->product(10);
        $user = $this->user();

        $this->actingAs($user)->post(route('transactions.adjust.store'), [
            'product_id' => $product->id,
            'physical_count' => 7,
            'reason' => 'Physical count',
            'notes' => 'Annual stock take',
        ])->assertRedirect();

        $this->assertSame(7, $product->fresh()->quantity);
        $this->assertTrue(InventoryTransaction::first()->isAdjustment());
    }

    public function test_low_stock_notification_is_queued_when_stock_crosses_reorder_level(): void
    {
        Notification::fake();
        $product = $this->product(8, 5);
        $admin = $this->user();

        InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => InventoryTransaction::TYPE_OUT,
            'quantity' => 4,
            'reference' => 'SO-002',
            'user_id' => $admin->id,
        ]);

        Notification::assertSentTo($admin, LowStockNotification::class);
    }

    public function test_deleting_a_transaction_reverses_the_stock_movement(): void
    {
        $product = $this->product(10);
        $user = $this->user();

        $transaction = InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => InventoryTransaction::TYPE_IN,
            'quantity' => 6,
            'user_id' => $user->id,
        ]);

        $this->assertSame(16, $product->fresh()->quantity);

        DB::transaction(fn () => $transaction->delete());

        $this->assertSame(10, $product->fresh()->quantity);
    }

    public function test_dashboard_api_returns_json_statistics(): void
    {
        $this->product(2, 5);

        $this->actingAs($this->user())
            ->getJson(route('api.dashboard.stats'))
            ->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'total_products',
                    'low_stock',
                    'out_of_stock',
                    'inventory_value',
                    'total_transactions',
                    'today_transactions',
                ],
                'generated_at',
            ]);

        $this->getJson(route('api.products.low-stock'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(route('api.transactions.recent'))->assertOk()->assertJsonStructure(['data', 'generated_at']);
    }

    public function test_regular_users_cannot_record_or_delete_transactions(): void
    {
        $user = $this->user(User::ROLE_USER);

        $this->actingAs($user)->get(route('transactions.create'))->assertForbidden();
        $this->actingAs($user)->get(route('transactions.adjust'))->assertForbidden();
    }

    public function test_transaction_pages_render_for_authenticated_users(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('transactions.index'))->assertOk()->assertSee('Stock Transactions');
        $this->actingAs($user)->get(route('transactions.create'))->assertOk()->assertSee('Record Stock Movement');
        $this->actingAs($user)->get(route('reports.index'))->assertOk()->assertSee('Inventory Reports');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Recent stock movements');
    }
}
