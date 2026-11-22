@extends('layouts.app')

@section('title', 'Purchase Order '.$order->reference_number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $order->reference_number }}</h1>
        <p class="text-muted mb-0">
            Created {{ $order->created_at?->format('F d, Y h:i A') }} by
            {{ $order->createdBy?->name ?? $order->user?->name ?? 'System' }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        @can('update', $order)
            <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
        @can('approve', $order)
            @if ($order->canBeApproved())
                <form method="POST" action="{{ route('purchase-orders.approve', $order) }}">
                    @csrf
                    <button class="btn btn-success" type="submit"><i class="bi bi-check2-circle"></i> Approve</button>
                </form>
            @endif
        @endcan
        @can('send', $order)
            <form method="POST" action="{{ route('purchase-orders.send', $order) }}">
                @csrf
                <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Mark as sent</button>
            </form>
        @endcan
        @can('delete', $order)
            <form method="POST" action="{{ route('purchase-orders.destroy', $order) }}" onsubmit="return confirm('Delete this draft order?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
            </form>
        @endcan
    </div>
</div>

@if ($order->isAwaitingFinalApproval())
    <div class="alert alert-warning"><i class="bi bi-shield-lock"></i> This high-value order received first-level approval and requires administrator approval.</div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card app-card mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-0">Order details</h2></div>
            <div class="card-body px-4">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span></dd>
                    <dt class="col-5 text-muted">Supplier</dt>
                    <dd class="col-7">
                        @if ($order->supplier_id)
                            <a href="{{ route('suppliers.show', $order->supplier_id) }}" class="text-decoration-none">{{ $order->supplier_name }}</a>
                        @else {{ $order->supplier_name }} @endif
                    </dd>
                    <dt class="col-5 text-muted">Order date</dt><dd class="col-7">{{ $order->order_date?->format('F d, Y') }}</dd>
                    <dt class="col-5 text-muted">Delivery expected</dt><dd class="col-7">{{ $order->expected_delivery_date?->format('F d, Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Approved by</dt><dd class="col-7">{{ $order->approvedBy?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Sent at</dt><dd class="col-7">{{ $order->sent_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Approval level</dt><dd class="col-7">{{ $order->approval_level }}/{{ $order->isHighValue() ? 2 : 1 }}</dd>
                    <dt class="col-5 text-muted">Terms</dt><dd class="col-7">{{ $order->terms ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Notes</dt><dd class="col-7">{{ $order->notes ?: '—' }}</dd>
                    @if ($order->rejection_reason)
                        <dt class="col-5 text-muted">Rejection reason</dt><dd class="col-7 text-danger">{{ $order->rejection_reason }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card app-card">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-0">Approval history</h2></div>
            <div class="list-group list-group-flush px-4 pb-3">
                @forelse ($order->approvalNotes as $note)
                    <div class="list-group-item px-0">
                        <span class="d-block small">{{ $note->body }}</span>
                        <span class="text-muted small">{{ $note->user?->name ?? 'System' }} · {{ $note->created_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-muted small py-3 mb-0">No approval activity recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card app-card mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-0">Ordered items</h2></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr>
                        <th>Product</th><th class="text-end">Ordered</th><th class="text-end">Received</th>
                        <th class="text-end">Outstanding</th><th class="text-end">Unit price</th><th class="text-end">Line total</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($order->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $item->product_id) }}" class="text-decoration-none fw-semibold">{{ $item->product?->name ?? 'Deleted product' }}</a>
                                <span class="d-block small text-muted">{{ $item->product?->sku }}</span>
                            </td>
                            <td class="text-end">{{ number_format($item->quantity) }}</td>
                            <td class="text-end">{{ number_format($item->received_quantity) }}</td>
                            <td class="text-end">{{ number_format($item->outstanding_quantity) }}</td>
                            <td class="text-end">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-semibold">₱{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No items on this order.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end">₱{{ number_format($order->subtotal, 2) }}</td></tr>
                        <tr><td colspan="5" class="text-end">Tax ({{ $order->tax_rate }}%)</td><td class="text-end">₱{{ number_format($order->tax_amount, 2) }}</td></tr>
                        <tr class="fw-bold"><td colspan="5" class="text-end">Order total</td><td class="text-end">₱{{ number_format($order->total, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @can('reject', $order)
            @if ($order->canBeApproved())
                <div class="card app-card mb-4">
                    <div class="card-body">
                        <form method="POST" action="{{ route('purchase-orders.reject', $order) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-10">
                                <label for="rejection_reason" class="form-label small fw-semibold">Rejection reason</label>
                                <input type="text" id="rejection_reason" name="rejection_reason" class="form-control" required maxlength="500"
                                       placeholder="Explain why this order cannot be approved">
                            </div>
                            <div class="col-md-2"><button class="btn btn-outline-danger w-100" type="submit">Reject</button></div>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('receive', $order)
            @if ($order->canBeReceived())
                <div class="card app-card">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h2 class="h5 fw-bold mb-0">Receive goods</h2>
                        <p class="text-muted small mb-0">Enter the quantity delivered for each line. Stock is updated through inventory transactions.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <form method="POST" action="{{ route('purchase-orders.receive', $order) }}">
                            @csrf
                            <div class="row g-3">
                                @foreach ($order->items as $item)
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">{{ $item->product?->name ?? 'Product #'.$item->product_id }}</label>
                                        <input type="number" class="form-control" name="received[{{ $item->id }}]" min="0"
                                               max="{{ $item->outstanding_quantity }}" value="{{ $item->outstanding_quantity }}">
                                        <span class="form-text">Outstanding: {{ number_format($item->outstanding_quantity) }} units</span>
                                    </div>
                                @endforeach
                            </div>
                            <button class="btn btn-success mt-3" type="submit" onclick="return confirm('Record this receipt and update stock?')">
                                <i class="bi bi-box-arrow-in-down"></i> Record receipt
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('fulfil-orders')
            @if (! in_array($order->status, [\App\Models\PurchaseOrder::STATUS_RECEIVED, \App\Models\PurchaseOrder::STATUS_CANCELLED], true))
                <form method="POST" action="{{ route('purchase-orders.cancel', $order) }}" class="mt-3" onsubmit="return confirm('Cancel this purchase order?')">
                    @csrf
                    <button class="btn btn-outline-warning" type="submit">Cancel order</button>
                </form>
            @endif
        @endcan
    </div>
</div>
@endsection
