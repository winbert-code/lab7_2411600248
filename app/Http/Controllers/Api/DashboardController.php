<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryTransactionResource;
use App\Http\Resources\ProductResource;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ReorderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly ReorderService $reorder) {}

    /** GET /api/dashboard/stats */
    public function stats(): JsonResponse
    {
        $stats = Product::active()->toBase()
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END), 0) as low_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as inventory_value')
            ->first();

        $movements = InventoryTransaction::toBase()
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as stock_in_units', [InventoryTransaction::TYPE_IN])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as stock_out_units', [InventoryTransaction::TYPE_OUT])
            ->selectRaw('COALESCE(SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END), 0) as today_transactions', [now()->startOfDay()])
            ->first();

        return response()->json([
            'stats' => [
                'total_products' => (int) $stats->total_products,
                'low_stock' => (int) $stats->low_stock,
                'out_of_stock' => (int) $stats->out_of_stock,
                'inventory_value' => (float) $stats->inventory_value,
                'total_transactions' => (int) $movements->total_transactions,
                'stock_in_units' => (int) $movements->stock_in_units,
                'stock_out_units' => (int) $movements->stock_out_units,
                'today_transactions' => (int) $movements->today_transactions,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }

    /** GET /api/products/low-stock */
    public function lowStock(Request $request): JsonResponse
    {
        $limit = min(max($request->integer('limit', 10), 1), 50);

        $products = Product::active()
            ->with('category')
            ->lowStock()
            ->orderBy('quantity')
            ->orderBy('id')
            ->take($limit)
            ->get();

        return ProductResource::collection($products)
            ->additional(['generated_at' => now()->toISOString()])
            ->response();
    }

    /** GET /api/transactions/recent */
    public function recentTransactions(Request $request): JsonResponse
    {
        $limit = min(max($request->integer('limit', 8), 1), 50);

        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->latest()
            ->orderByDesc('id')
            ->take($limit)
            ->get();

        return InventoryTransactionResource::collection($transactions)
            ->additional(['generated_at' => now()->toISOString()])
            ->response();
    }

    /** GET /api/dashboard/pending-orders */
    public function pendingOrders(): JsonResponse
    {
        $data = Cache::remember('api:pending-orders', 30, fn (): array => [
            'count' => PurchaseOrder::pending()->count(),
            'awaiting_final_approval' => PurchaseOrder::pending()->where('approval_level', 1)->count(),
            'total_value' => (float) PurchaseOrder::pending()->sum('total'),
        ]);

        return response()->json($data + ['generated_at' => now()->toISOString()]);
    }

    /** GET /api/dashboard/recent-orders */
    public function recentOrders(Request $request): JsonResponse
    {
        $limit = min(max($request->integer('limit', 5), 1), 25);

        $orders = Cache::remember("api:recent-orders:{$limit}", 30, fn () => PurchaseOrder::query()
            ->with('supplier:id,company_name')
            ->latest('order_date')
            ->orderByDesc('id')
            ->take($limit)
            ->get()
            ->map(fn (PurchaseOrder $order) => [
                'id' => $order->id,
                'reference_number' => $order->reference_number,
                'supplier' => $order->supplier_name,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'total' => (float) $order->total,
                'order_date' => $order->order_date?->toDateString(),
                'url' => route('purchase-orders.show', $order),
            ])->all());

        return response()->json(['data' => $orders, 'generated_at' => now()->toISOString()]);
    }

    /** GET /api/dashboard/supplier-performance */
    public function supplierPerformance(): JsonResponse
    {
        $data = Cache::remember('api:supplier-performance', 60, fn () => Supplier::active()
            ->orderBy('company_name')
            ->take(10)
            ->get()
            ->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'company_name' => $supplier->company_name,
                'lead_time_days' => $supplier->lead_time_days,
            ] + $supplier->performanceMetrics())->all());

        return response()->json(['data' => $data, 'generated_at' => now()->toISOString()]);
    }

    /** GET /api/dashboard/reorder-alerts */
    public function reorderAlerts(Request $request): JsonResponse
    {
        $limit = min(max($request->integer('limit', 10), 1), 50);

        $alerts = $this->reorder->getProductsNeedingReorder()
            ->take($limit)
            ->map(fn (array $alert) => [
                'product_id' => $alert['product']->id,
                'product' => $alert['product']->name,
                'sku' => $alert['product']->sku,
                'supplier' => $alert['supplier']->company_name,
                'current_stock' => $alert['current_stock'],
                'reorder_point' => $alert['reorder_point'],
                'suggested_quantity' => $alert['suggested_quantity'],
                'lead_time_days' => $alert['lead_time_days'],
            ]);

        return response()->json(['data' => $alerts, 'generated_at' => now()->toISOString()]);
    }
}
