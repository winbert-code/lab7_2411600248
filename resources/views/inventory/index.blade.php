@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Inventory</h1>
        <p class="text-muted mb-0">A live overview of stock levels across all computer parts.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card app-card h-100"><div class="card-body">
                <div class="small text-muted">Active products</div>
                <div class="h4 fw-bold mb-0">{{ $activeProducts }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card app-card h-100"><div class="card-body">
                <div class="small text-muted">Units in stock</div>
                <div class="h4 fw-bold mb-0">{{ number_format($totalUnits) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card app-card h-100"><div class="card-body">
                <div class="small text-muted">Low stock</div>
                <div class="h4 fw-bold mb-0">{{ $lowStock }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card app-card h-100"><div class="card-body">
                <div class="small text-muted">Out of stock</div>
                <div class="h4 fw-bold mb-0">{{ $outOfStock }}</div>
            </div></div>
        </div>
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="search" class="form-label small">Search</label>
                    <input id="search" name="search" type="search" class="form-control"
                           placeholder="Name, SKU, or supplier" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th class="text-end">Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><code>{{ $product->sku }}</code></td>
                            <td>
                                <a href="{{ route('products.show', $product) }}" class="fw-semibold text-decoration-none">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td>{{ $product->brand?->name ?? '—' }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($product->quantity) }}</td>
                            <td><x-stock-badge :status="$product->availabilityStatus()" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
