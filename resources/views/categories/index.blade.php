@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Categories</h1>
            <p class="text-muted mb-0">Group your computer parts into product categories.</p>
        </div>
        @can('manage-inventory')
            <a href="{{ route('categories.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Category
            </a>
        @endcan
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('categories.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="status" class="form-label small">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All categories</option>
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
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td class="text-end">{{ number_format($category->active_products_count) }}</td>
                            <td>
                                <span class="badge {{ $category->isActive() ? 'bg-success' : 'badge-inactive' }}">
                                    {{ ucfirst($category->status) }}
                                </span>
                            </td>
                            <td>{{ $category->description ?? '—' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('manage-inventory')
                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-outline-primary"
                                           title="Edit" aria-label="Edit category"><i class="bi bi-pencil"></i></a>
                                        @if ($category->isActive())
                                            <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                                  onsubmit="return confirm('Remove {{ addslashes($category->name) }}? It will be marked inactive and can be restored anytime.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger"
                                                        title="Remove" aria-label="Remove category"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('categories.restore', $category) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-success"
                                                        title="Restore" aria-label="Restore category">
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
                                No categories found.
                                @can('manage-inventory')
                                    <a href="{{ route('categories.create') }}">Add the first one</a>.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $categories->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
