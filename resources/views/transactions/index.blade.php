@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Stock Transactions</h1>
            <p class="text-muted mb-0">Complete audit trail of every inventory movement.</p>
        </div>
        <div class="d-flex gap-2">
            @can('manage-inventory')
                <a href="{{ route('transactions.adjust') }}" class="btn btn-outline-warning">
                    <i class="bi bi-sliders2"></i> Adjust stock
                </a>
                <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Record movement
                </a>
            @endcan
        </div>
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="search" class="form-label small fw-semibold">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           value="{{ $filters['search'] }}" placeholder="Product, SKU, reference…">
                </div>
                <div class="col-12 col-md-3">
                    <label for="product_id" class="form-label small fw-semibold">Product</label>
                    <select class="form-select" id="product_id" name="product_id">
                        <option value="">All products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected($filters['productId'] === $product->id)>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="type" class="form-label small fw-semibold">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
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
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th class="text-end">Quantity</th>
                        <th>Reference</th>
                        <th>User</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="text-nowrap">{{ $transaction->created_at?->format('M d, Y h:i A') }}</td>
                            <td>
                                <a href="{{ route('products.show', $transaction->product) }}" class="text-decoration-none fw-semibold">
                                    {{ $transaction->product?->name ?? 'Deleted product' }}
                                </a>
                                <div class="small text-muted">{{ $transaction->product?->sku }}</div>
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
                            <td><code>{{ $transaction->reference_number }}</code></td>
                            <td>{{ $transaction->user?->name ?? 'System' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('admin')
                                    <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this transaction? The stock balance will be reversed.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No transactions match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
