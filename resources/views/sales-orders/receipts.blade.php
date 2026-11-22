@extends('layouts.app')

@section('title', 'Sales receipts')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Sales receipts</h1>
            <p class="text-muted mb-0">View the receipt for any completed sales order.</p>
        </div>
        <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-cart-check"></i> Sales orders
        </a>
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('receipts.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-8">
                    <label for="search" class="form-label small fw-semibold">Search receipts</label>
                    <input type="text" class="form-control" id="search" name="search"
                           value="{{ $search }}" placeholder="Order number, customer, product…">
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Customer</th>
                        <th>Completed at</th>
                        <th>Prepared by</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('sales-orders.receipt', $order) }}" class="text-decoration-none fw-semibold">
                                    {{ $order->order_number }}
                                </a>
                                <div class="small text-muted">{{ $order->items->count() }} line(s) · {{ $order->totalQuantity() }} units</div>
                            </td>
                            <td>{{ $order->customer }}</td>
                            <td class="text-nowrap">{{ $order->updated_at?->format('M d, Y h:i A') ?? '—' }}</td>
                            <td>{{ $order->user?->name ?? 'System' }}</td>
                            <td class="text-end fw-semibold">₱{{ number_format($order->totalAmount(), 2) }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('sales-orders.receipt', $order) }}" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-receipt"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No completed sales orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="card-footer bg-transparent border-0 px-4 pb-4">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
