@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Purchase Orders</h1>
        <p class="text-muted mb-0">Draft, approve, send and receive supplier orders.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @can('export-reports')
            <div class="btn-group">
                <a href="{{ route('exports.purchase-orders') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv"></i> CSV</a>
                <a href="{{ route('exports.pdf', 'purchase-orders') }}" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            </div>
        @endcan
        @can('create', App\Models\PurchaseOrder::class)
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New purchase order</a>
        @endcan
    </div>
</div>

<div class="card app-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase-orders.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label small fw-semibold">Search</label>
                <input type="search" name="search" id="search" value="{{ $filters['search'] ?? '' }}"
                       class="form-control" placeholder="Reference, supplier or product">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="supplier" class="form-label small fw-semibold">Supplier</label>
                <select name="supplier" id="supplier" class="form-select">
                    <option value="">All</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier'] ?? '') === (string) $supplier->id)>{{ $supplier->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="from" class="form-label small fw-semibold">From</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label small fw-semibold">To</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card app-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Supplier</th>
                    <th>Order date</th>
                    <th>Expected</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('purchase-orders.show', $order) }}" class="text-decoration-none fw-semibold">{{ $order->reference_number }}</a>
                        <span class="d-block small text-muted">{{ $order->createdBy?->name ?? $order->user?->name ?? 'System' }}</span>
                    </td>
                    <td>
                        @if ($order->supplier_id)
                            <a href="{{ route('suppliers.show', $order->supplier_id) }}" class="text-decoration-none">{{ $order->supplier_name }}</a>
                        @else
                            {{ $order->supplier_name }}
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $order->order_date?->format('M d, Y') }}</td>
                    <td class="text-nowrap">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</td>
                    <td class="text-center">{{ $order->items->count() }}</td>
                    <td class="text-end fw-semibold">₱{{ number_format($order->total, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span>
                        @if ($order->partially_received)<span class="badge bg-info text-dark">Partial</span>@endif
                        @if ($order->isAwaitingFinalApproval())<span class="badge bg-warning text-dark">2nd approval</span>@endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                        @can('update', $order)
                            <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No purchase orders match your filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body border-top">{{ $orders->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
