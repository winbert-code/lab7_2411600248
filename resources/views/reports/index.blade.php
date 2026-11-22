@extends('layouts.app')

@section('title', 'Inventory Reports')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Inventory Reports</h1>
            <p class="text-muted mb-0">Stock movement, low stock and valuation summaries.</p>
        </div>
        @can('export-reports')
            <a href="{{ route('reports.export', request()->only('from', 'to')) }}" class="btn btn-primary">
                <i class="bi bi-download"></i> Export CSV
            </a>
        @endcan
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label for="from" class="form-label small fw-semibold">From</label>
                    <input type="date" class="form-control" id="from" name="from" value="{{ $from }}">
                </div>
                <div class="col-auto">
                    <label for="to" class="form-label small fw-semibold">To</label>
                    <input type="date" class="form-control" id="to" name="to" value="{{ $to }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card card h-100">
                <div class="card-body">
                    <p class="stat-label mb-1">Products valued</p>
                    <h2 class="stat-value mb-0">{{ number_format($valuation->products ?? 0) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card h-100">
                <div class="card-body">
                    <p class="stat-label mb-1">Units on hand</p>
                    <h2 class="stat-value mb-0">{{ number_format($valuation->units ?? 0) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card h-100">
                <div class="card-body">
                    <p class="stat-label mb-1">Inventory valuation</p>
                    <h2 class="stat-value mb-0">&#8369;{{ number_format((float) ($valuation->total ?? 0), 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card app-card">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-0">Stock movement report</h2>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th class="text-end">Movement</th>
                                <th class="text-end">Balance</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="text-nowrap">{{ $movement->created_at?->format('M d, Y h:i') }}</td>
                                    <td>{{ $movement->product?->name ?? '—' }}</td>
                                    <td><span class="stock-badge badge-{{ $movement->type_color }}">{{ $movement->type_label }}</span></td>
                                    <td class="text-end">{{ $movement->signedQuantity() > 0 ? '+' : '' }}{{ number_format($movement->signedQuantity()) }}</td>
                                    <td class="text-end">{{ $movement->balance_after ?? '—' }}</td>
                                    <td><code>{{ $movement->reference_number }}</code></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No movements in this date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">{{ $movements->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card app-card h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-0">Low stock report</h2>
                    <p class="text-muted small mb-0">{{ $lowStock->count() }} item(s) at or below reorder level.</p>
                </div>
                <div class="list-group list-group-flush px-4">
                    @forelse ($lowStock as $product)
                        <a href="{{ route('products.show', $product) }}" class="list-group-item list-group-item-action px-0">
                            <span class="fw-semibold d-block">{{ $product->name }}</span>
                            <span class="small text-muted">{{ $product->quantity }} / {{ $product->reorder_level }} units &middot; {{ $product->sku }}</span>
                        </a>
                    @empty
                        <p class="text-muted py-3">No low stock items.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
