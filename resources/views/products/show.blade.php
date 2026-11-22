@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('products.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left"></i> Back to inventory
        </a>
    </div>

    @if ($product->isInactive())
        <div class="alert alert-warning border-0 small">
            <i class="bi bi-archive"></i>
            This product is inactive. It is hidden from the active inventory, but its record and stock history are preserved.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card app-card h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <h1 class="h4 fw-bold mb-1">{{ $product->name }}</h1>
                            <code>{{ $product->sku }}</code>
                        </div>
                        <x-stock-badge :status="$product->availabilityStatus()" />
                    </div>
                </div>
                <div class="card-body px-4">
                    <p class="text-muted">{{ $product->description ?: 'No description provided.' }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Brand</span><strong>{{ $product->brand?->name ?? '—' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Category</span>
                            <strong>{{ $product->category?->name ?? '—' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Supplier</span><strong>{{ $product->supplier ?? '—' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Quantity</span><strong>{{ number_format($product->quantity) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Reorder level</span><strong>{{ number_format($product->reorder_level) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Unit price</span>
                            <strong>&#8369;{{ number_format((float) $product->unit_price, 2) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Stock value</span>
                            <strong>&#8369;{{ number_format($product->stockValue(), 2) }}</strong>
                        </li>
                    </ul>
                </div>
                @can('manage-inventory')
                    <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex gap-2">
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        @if ($product->isActive())
                            <form method="POST" action="{{ route('products.destroy', $product) }}"
                                  onsubmit="return confirm('Mark {{ addslashes($product->name) }} as inactive? The product and its inventory records will be kept.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="bi bi-x-circle"></i> Mark inactive
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('products.restore', $product) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-success">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                            </form>
                        @endif
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card app-card">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h2 class="h5 fw-bold mb-0">Stock movements</h2>
                            <p class="text-muted small mb-0">Running balance for every recorded stock movement.</p>
                        </div>
                        @can('manage-inventory')
                            <a href="{{ route('transactions.create', ['product_id' => $product->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Record movement
                            </a>
                        @endcan
                    </div>
                    <form method="GET" action="{{ route('products.show', $product) }}" class="row g-2 mt-3">
                        <div class="col-auto">
                            <input type="date" class="form-control form-control-sm" name="from" value="{{ $from }}" aria-label="From date">
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control form-control-sm" name="to" value="{{ $to }}" aria-label="To date">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th class="text-end">Movement</th>
                                <th class="text-end">Balance</th>
                                <th>Reference</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="text-nowrap">
                                        <a href="{{ route('transactions.show', $transaction) }}" class="text-decoration-none">
                                            {{ $transaction->created_at?->format('M d, Y h:i A') }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="stock-badge badge-{{ $transaction->type_color }}">
                                            <i class="bi {{ $transaction->isStockIn() ? 'bi-arrow-down-left' : ($transaction->isStockOut() ? 'bi-arrow-up-right' : 'bi-sliders2') }}"></i>
                                            {{ $transaction->type_label }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold text-{{ $transaction->type_color }}">
                                        {{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ number_format($transaction->signedQuantity()) }}
                                    </td>
                                    <td class="text-end fw-semibold">{{ $transaction->balance_after ?? '—' }}</td>
                                    <td><code>{{ $transaction->reference_number }}</code></td>
                                    <td>{{ $transaction->user?->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No stock movements recorded for this date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
