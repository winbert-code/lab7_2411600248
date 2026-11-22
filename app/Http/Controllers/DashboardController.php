<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = Product::active()->toBase()
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END), 0) as low_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as inventory_value')
            ->first();

        $recentProducts = Product::active()->with('category')->latest()->orderByDesc('id')->take(5)->get();
        $lowStockProducts = Product::active()->with('category')->lowStock()->orderBy('quantity')->orderBy('id')->take(5)->get();
        $recentTransactions = InventoryTransaction::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->latest()
            ->orderByDesc('id')
            ->take(8)
            ->get();

        $categories = Product::active()
            ->selectRaw('category_id, COUNT(*) as product_count, SUM(quantity) as units')
            ->groupBy('category_id')
            ->orderByDesc('product_count')
            ->with('category')
            ->get();

        return view('dashboard.index', [
            'stats' => $stats,
            'recentProducts' => $recentProducts,
            'lowStockProducts' => $lowStockProducts,
            'recentTransactions' => $recentTransactions,
            'categories' => $categories,
            'recentActivity' => ActivityLog::with('user')->latestFirst()->take(5)->get(),
            'chart' => [
                'labels' => $categories->map(fn ($row) => $row->category?->name ?? 'Uncategorized')->all(),
                'values' => $categories->pluck('product_count')->map(fn ($count) => (int) $count)->all(),
            ],
        ]);
    }
}
