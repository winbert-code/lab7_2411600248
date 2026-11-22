<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ReorderService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(ReorderService $reorder): void
    {
        $ttl = (int) config('replenishment.cache_ttl_seconds', 60);

        View::composer('widgets.pending-orders', function ($view) use ($ttl): void {
            $view->with(Cache::remember('widget:pending-orders', $ttl, fn (): array => [
                'count' => PurchaseOrder::pending()->count(),
                'awaitingFinal' => PurchaseOrder::pending()->where('approval_level', 1)->count(),
                'totalValue' => (float) PurchaseOrder::pending()->sum('total'),
            ]));
        });

        // Cache plain arrays, not Eloquent models: the live database cache
        // serialises values and model instances do not reliably round-trip.
        View::composer('widgets.recent-orders', function ($view) use ($ttl): void {
            $view->with(Cache::remember('widget:recent-orders', $ttl, fn (): array => [
                'orders' => PurchaseOrder::query()
                    ->latest('order_date')
                    ->orderByDesc('id')
                    ->take(5)
                    ->get()
                    ->map(fn (PurchaseOrder $order): array => [
                        'id' => $order->id,
                        'reference_number' => $order->reference_number,
                        'supplier' => $order->supplier_name,
                        'order_date' => $order->order_date?->format('M d') ?? '—',
                        'status_color' => $order->status_color,
                        'status_label' => $order->status_label,
                        'url' => route('purchase-orders.show', $order),
                    ])->all(),
            ]));
        });

        View::composer('widgets.supplier-performance', function ($view) use ($ttl): void {
            $view->with(Cache::remember('widget:supplier-performance', $ttl, function (): array {
                $suppliers = Supplier::active()->orderBy('company_name')->take(5)->get();

                return [
                    'suppliers' => $suppliers->map(fn (Supplier $supplier): array => [
                        'id' => $supplier->id,
                        'company_name' => $supplier->company_name,
                        'url' => route('suppliers.show', $supplier),
                        'metrics' => $supplier->performanceMetrics(),
                    ])->all(),
                ];
            }));
        });

        View::composer('widgets.reorder-alerts', function ($view) use ($reorder, $ttl): void {
            $view->with(Cache::remember('widget:reorder-alerts', $ttl, fn (): array => [
                'alerts' => $reorder->getProductsNeedingReorder()
                    ->take(6)
                    ->map(fn (array $alert): array => [
                        'product_id' => $alert['product']->id,
                        'product_name' => $alert['product']->name,
                        'current_stock' => $alert['current_stock'],
                        'reorder_point' => $alert['reorder_point'],
                        'suggested_quantity' => $alert['suggested_quantity'],
                        'supplier' => $alert['supplier']->company_name,
                    ])->all(),
            ]));
        });
    }
}
