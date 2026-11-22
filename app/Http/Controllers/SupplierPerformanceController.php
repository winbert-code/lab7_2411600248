<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SupplierPerformanceController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        $suppliers = Supplier::query()
            ->withCount('purchaseOrders')
            ->when($search !== '', fn ($query) => $query->where('company_name', 'like', "%{$search}%"))
            ->orderBy('company_name')
            ->get();

        // Store a plain array (not a Collection of models) so the database
        // cache can serialise it safely between requests.
        $metrics = Cache::remember(
            'supplier-performance:'.md5($search),
            (int) config('replenishment.cache_ttl_seconds', 60),
            fn (): array => $suppliers->mapWithKeys(fn (Supplier $supplier) => [
                $supplier->id => $supplier->performanceMetrics(),
            ])->all(),
        );

        $ranked = $suppliers->map(fn (Supplier $supplier) => [
            'supplier' => $supplier,
            'metrics' => $metrics[$supplier->id] ?? [],
        ])->sortByDesc(fn (array $row) => $row['metrics']['on_time_percentage'] ?? 0)->values();

        return view('supplier-performance.index', [
            'rows' => $ranked,
            'top' => $ranked->take(3),
            'bottom' => $ranked->reverse()->take(3)->values(),
            'totals' => [
                'orders' => $ranked->sum(fn (array $row) => $row['metrics']['total_orders'] ?? 0),
                'value' => $ranked->sum(fn (array $row) => $row['metrics']['total_value'] ?? 0),
                'average_on_time' => $ranked->isEmpty()
                    ? 0
                    : round($ranked->avg(fn (array $row) => $row['metrics']['on_time_percentage'] ?? 0), 1),
            ],
            'chart' => [
                'labels' => $ranked->take(8)->map(fn (array $row) => $row['supplier']->company_name)->values(),
                'onTime' => $ranked->take(8)->map(fn (array $row) => $row['metrics']['on_time_percentage'] ?? 0)->values(),
                'accuracy' => $ranked->take(8)->map(fn (array $row) => $row['metrics']['order_accuracy'] ?? 0)->values(),
                'value' => $ranked->take(8)->map(fn (array $row) => $row['metrics']['total_value'] ?? 0)->values(),
            ],
            'search' => $search,
        ]);
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = $this->search->supplierTotals();

        return \App\Exports\CsvExporter::stream(
            'supplier-performance.csv',
            ['ID', 'Supplier', 'Orders', 'Total value'],
            $rows->map(fn ($row) => [$row->id, $row->company_name, $row->orders, $row->value]),
        );
    }
}
