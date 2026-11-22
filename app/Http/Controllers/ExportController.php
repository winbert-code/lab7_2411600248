<?php

namespace App\Http\Controllers;

use App\Exports\CsvExporter;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function products(Request $request): StreamedResponse
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'supplier:id,company_name'])
            ->when($request->string('search')->toString() !== '', fn ($q) => $q->search($request->string('search')->toString()))
            ->orderBy('name')
            ->get();

        return CsvExporter::stream(
            'products.csv',
            ['SKU', 'Name', 'Category', 'Brand', 'Supplier', 'Quantity', 'Reorder level', 'Unit price', 'Status'],
            $products->map(fn (Product $p) => [
                $p->sku, $p->name, $p->category?->name, $p->brand?->name,
                $p->supplier_record?->company_name ?? $p->supplier, $p->quantity,
                $p->reorder_level, $p->unit_price, $p->status,
            ]),
        );
    }

    public function purchaseOrders(Request $request): StreamedResponse
    {
        $orders = PurchaseOrder::query()
            ->with('supplier:id,company_name')
            ->byStatus($request->string('status')->toString())
            ->latest('order_date')
            ->get();

        return CsvExporter::stream(
            'purchase-orders.csv',
            ['Reference', 'Supplier', 'Order date', 'Expected delivery', 'Status', 'Subtotal', 'Tax', 'Total'],
            $orders->map(fn (PurchaseOrder $o) => [
                $o->reference_number, $o->supplier_name, $o->order_date?->toDateString(),
                $o->expected_delivery_date?->toDateString(), $o->status_label,
                $o->subtotal, $o->tax_amount, $o->total,
            ]),
        );
    }

    public function transactions(Request $request): StreamedResponse
    {
        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->betweenDates($request->string('from')->toString() ?: null, $request->string('to')->toString() ?: null)
            ->latest()
            ->get();

        return CsvExporter::stream(
            'inventory-transactions.csv',
            ['Date', 'Product', 'SKU', 'Type', 'Quantity', 'Reference', 'User', 'Balance after'],
            $transactions->map(fn (InventoryTransaction $t) => [
                $t->created_at?->format('Y-m-d H:i:s'), $t->product?->name, $t->product?->sku,
                $t->type_label, $t->quantity, $t->reference, $t->user?->name ?? 'System', $t->balance_after,
            ]),
        );
    }

    /** PDF export using barryvdh/laravel-dompdf when installed. */
    public function pdf(string $type): \Illuminate\Http\Response|\Illuminate\View\View
    {
        [$title, $headers, $rows] = $this->pdfData($type);

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            // Graceful fallback for environments where the optional package is absent.
            return view('exports.pdf', compact('title', 'headers', 'rows'));
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf', compact('title', 'headers', 'rows'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($type.'-report.pdf');
    }

    /** @return array{0: string, 1: array<int, string>, 2: \Illuminate\Support\Collection} */
    private function pdfData(string $type): array
    {
        return match ($type) {
            'suppliers' => [
                'Supplier performance report',
                ['Code', 'Company', 'Contact', 'Lead time', 'Status'],
                Supplier::orderBy('company_name')->get()->map(fn (Supplier $s) => [
                    $s->code, $s->company_name, $s->contact_person, $s->lead_time_days.' d', $s->status,
                ]),
            ],
            'purchase-orders' => [
                'Purchase order report',
                ['Reference', 'Supplier', 'Date', 'Status', 'Total'],
                PurchaseOrder::with('supplier:id,company_name')->latest('order_date')->get()->map(fn (PurchaseOrder $o) => [
                    $o->reference_number, $o->supplier_name, $o->order_date?->toDateString(), $o->status_label, $o->total,
                ]),
            ],
            default => [
                'Products report',
                ['SKU', 'Name', 'Quantity', 'Reorder level', 'Unit price'],
                Product::orderBy('name')->get()->map(fn (Product $p) => [
                    $p->sku, $p->name, $p->quantity, $p->reorder_level, $p->unit_price,
                ]),
            ],
        };
    }
}
