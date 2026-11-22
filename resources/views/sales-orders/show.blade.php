@extends('layouts.app')

@section('title', 'Sales Order '.$order->order_number)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $order->order_number }}</h1>
            <p class="text-muted mb-0">Created {{ $order->created_at?->format('F d, Y h:i A') }} by {{ $order->user?->name ?? 'System' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to list
            </a>
            @if ($order->isCompleted())
                <a href="{{ route('sales-orders.receipt', $order) }}" class="btn btn-outline-dark">
                    <i class="bi bi-receipt"></i> View receipt
                </a>
            @endif
            @can('create-orders')
                @if ($order->isPending())
                    <a href="{{ route('sales-orders.edit', $order) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endif
            @endcan
            @can('admin')
                @if ($order->isPending())
                    <form method="POST" action="{{ route('sales-orders.approve', $order) }}"
                          onsubmit="return confirm('Approve this sales order?')">
                        @csrf
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Approve order</button>
                    </form>
                @endif
                @if ($order->isPending() || $order->isApproved())
                    <form method="POST" action="{{ route('sales-orders.cancel', $order) }}"
                          onsubmit="return confirm('Cancel this sales order?')">
                        @csrf
                        <button class="btn btn-outline-warning" type="submit">Cancel order</button>
                    </form>
                @endif
            @endcan
            @can('fulfil-orders')
                @if ($order->isApproved())
                    <form method="POST" action="{{ route('sales-orders.complete', $order) }}"
                          onsubmit="return confirm('Complete this order? Stock levels will decrease immediately.')">
                        @csrf
                        <button class="btn btn-success" type="submit"><i class="bi bi-box-arrow-out-down"></i> Complete order</button>
                    </form>
                @endif
                @if ($order->isPending())
                    <form method="POST" action="{{ route('sales-orders.destroy', $order) }}"
                          onsubmit="return confirm('Delete this pending order permanently?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card app-card h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-0">Order details</h2>
                </div>
                <div class="card-body px-4">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7"><span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span></dd>
                        <dt class="col-5 text-muted">Approved by</dt>
                        <dd class="col-7">{{ $order->approvedBy?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Approved at</dt>
                        <dd class="col-7">{{ $order->approved_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Customer</dt>
                        <dd class="col-7">{{ $order->customer }}</dd>
                        <dt class="col-5 text-muted">Order date</dt>
                        <dd class="col-7">{{ $order->order_date?->format('F d, Y') }}</dd>
                        <dt class="col-5 text-muted">Due date</dt>
                        <dd class="col-7">{{ $order->due_date?->format('F d, Y') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Items</dt>
                        <dd class="col-7">{{ $order->items->count() }} lines / {{ $order->totalQuantity() }} units</dd>
                        <dt class="col-5 text-muted">Total</dt>
                        <dd class="col-7 fw-bold">₱{{ number_format($order->totalAmount(), 2) }}</dd>
                        <dt class="col-5 text-muted">Notes</dt>
                        <dd class="col-7">{{ $order->notes ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card app-card">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-0">Ordered items</h2>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Current stock</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit price</th>
                                <th class="text-end">Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->items as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('products.show', $item->product_id) }}" class="text-decoration-none fw-semibold">
                                            {{ $item->product?->name ?? 'Deleted product' }}
                                        </a>
                                        <div class="small text-muted">{{ $item->product?->sku }}</div>
                                    </td>
                                    <td class="text-end">{{ $item->product?->quantity ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($item->quantity) }}</td>
                                    <td class="text-end">₱{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end fw-semibold">₱{{ number_format($item->lineTotal(), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No items on this order.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Order total</td>
                                <td class="text-end">₱{{ number_format($order->totalAmount(), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
