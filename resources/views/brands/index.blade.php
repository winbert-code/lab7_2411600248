@extends('layouts.app')

@section('title', 'Brands')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Brands</h1>
            <p class="text-muted mb-0">Manage the manufacturers of your computer parts.</p>
        </div>
        @can('manage-inventory')
            <a href="{{ route('brands.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Brand
            </a>
        @endcan
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('brands.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="status" class="form-label small">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All brands</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
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
                        <th>Name</th>
                        <th>Slug</th>
                        <th class="text-end">Active products</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td class="fw-semibold">{{ $brand->name }}</td>
                            <td><code>{{ $brand->slug }}</code></td>
                            <td class="text-end">{{ number_format($brand->active_products_count) }}</td>
                            <td>
                                <span class="badge {{ $brand->isActive() ? 'bg-success' : 'badge-inactive' }}">
                                    {{ ucfirst($brand->status) }}
                                </span>
                            </td>
                            <td>{{ $brand->description ?? '—' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('manage-inventory')
                                        <a href="{{ route('brands.edit', $brand) }}" class="btn btn-outline-primary"
                                           title="Edit" aria-label="Edit brand"><i class="bi bi-pencil"></i></a>
                                        @if ($brand->isActive())
                                            <form method="POST" action="{{ route('brands.destroy', $brand) }}"
                                                  onsubmit="return confirm('Remove {{ addslashes($brand->name) }}? It will be marked inactive and can be restored anytime.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger"
                                                        title="Remove" aria-label="Remove brand"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('brands.restore', $brand) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-success"
                                                        title="Restore" aria-label="Restore brand">
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
                            <td colspan="6" class="text-center text-muted py-4">
                                No brands found.
                                @can('manage-inventory')
                                    <a href="{{ route('brands.create') }}">Add the first one</a>.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $brands->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
