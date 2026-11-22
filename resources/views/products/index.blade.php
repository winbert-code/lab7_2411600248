@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Inventory</h1>
            <p class="text-muted mb-0">Manage computer parts, stock levels, and supplier details.</p>
        </div>
        @can('manage-inventory')
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Product
            </a>
        @endcan
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="search" class="form-label small">Search</label>
                    <input id="search" name="search" type="search" class="form-control"
                           placeholder="Name, SKU, or supplier"
                           value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <label for="brand_id" class="form-label small">Brand</label>
                    <select id="brand_id" name="brand_id" class="form-select">
                        <option value="">All brands</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" {{ $brandId === $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="category_id" class="form-label small">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ $categoryId === $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label small">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All products</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($status !== 'active')
        <p class="small text-muted mb-3">
            <i class="bi bi-archive"></i> Inactive products remain in the inventory and can be restored using the restore button.
        </p>
    @endif

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
                        <th class="text-end">Price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
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
                                <div class="small text-muted">{{ $product->supplier ?? 'No supplier listed' }}</div>
                            </td>
                            <td>{{ $product->brand?->name ?? '—' }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($product->quantity) }}</td>
                            <td class="text-end">&#8369;{{ number_format((float) $product->unit_price, 2) }}</td>
                            <td><x-stock-badge :status="$product->availabilityStatus()" /></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary"
                                       title="View" aria-label="View product">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('manage-inventory')
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary"
                                           title="Edit" aria-label="Edit product">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if ($product->isActive())
                                            <form method="POST" action="{{ route('products.destroy', $product) }}"
                                                  onsubmit="return confirm('Mark {{ addslashes($product->name) }} as inactive? The product and its inventory records will be kept.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger"
                                                        title="Deactivate" aria-label="Deactivate product">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('products.restore', $product) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-success"
                                                        title="Restore" aria-label="Restore product">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No products found.
                                @can('manage-inventory')
                                    <a href="{{ route('products.create') }}">Add the first one</a>.
                                @endcan
                            </td>
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
