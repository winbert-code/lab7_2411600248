@extends('layouts.app')

@section('title', 'Sales Orders')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Sales Orders</h1>
            <p class="text-muted mb-0">Create orders, then complete them to deduct stock automatically.</p>
        </div>
        @can('create-orders')
            <a href="{{ route('sales-orders.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New sales order
            </a>
        @endcan
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('sales-orders.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="search" class="form-label small fw-semibold">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           value="{{ $filters['search'] }}" placeholder="Order number, customer, product…">
                </div>
                <div class="col-12 col-md-3">
                    <label for="status" class="form-label small fw-semibold">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="from" class="form-label small fw-semibold">From</label>
                    <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label for="to" class="form-label small fw-semibold">To</label>
                    <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] }}">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Order date</th>
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
                                <a href="{{ route('sales-orders.show', $order) }}" class="text-decoration-none fw-semibold">
                                    {{ $order->order_number }}
                                </a>
                                <div class="small text-muted">{{ $order->user?->name ?? 'System' }}</div>
                            </td>
                            <td>{{ $order->customer }}</td>
                            <td class="text-nowrap">{{ $order->order_date?->format('M d, Y') }}</td>
                            <td class="text-center">{{ $order->items->count() }}</td>
                            <td class="text-end fw-semibold">₱{{ number_format($order->totalAmount(), 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('sales-orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if ($order->isCompleted())
                                    <a href="{{ route('sales-orders.receipt', $order) }}" class="btn btn-sm btn-outline-dark" title="View receipt">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                @endif
                                @can('create-orders')
                                    @if ($order->isPending())
                                        <a href="{{ route('sales-orders.edit', $order) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No sales orders match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
