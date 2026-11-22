<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $movements = InventoryTransaction::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->betweenDates($from !== '' ? $from : null, $to !== '' ? $to : null)
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $lowStock = Product::active()->with('category')->lowStock()->orderBy('quantity')->get();

        $valuation = Product::active()->toBase()
            ->selectRaw('COUNT(*) as products')
            ->selectRaw('SUM(quantity) as units')
            ->selectRaw('SUM(quantity * unit_price) as total')
            ->first();

        return view('reports.index', [
            'movements' => $movements,
            'lowStock' => $lowStock,
            'valuation' => $valuation,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->betweenDates($from !== '' ? $from : null, $to !== '' ? $to : null)
            ->orderBy('created_at')
            ->get();

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Product', 'SKU', 'Quantity', 'Previous', 'Balance', 'Reference', 'Reason', 'User', 'Notes']);

            foreach ($transactions as $transaction) {
                fputcsv($handle, [
                    $transaction->created_at?->format('Y-m-d H:i:s'),
                    $transaction->type_label,
                    $transaction->product?->name,
                    $transaction->product?->sku,
                    $transaction->signedQuantity(),
                    $transaction->previous_quantity,
                    $transaction->balance_after,
                    $transaction->reference,
                    $transaction->reason,
                    $transaction->user?->name ?? 'System',
                    $transaction->notes,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stock-movement-report.csv"',
        ]);
    }
}
