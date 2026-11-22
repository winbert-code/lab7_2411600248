@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Suppliers</h1>
        <p class="text-muted mb-0">Manage vendors, lead times and payment terms.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('supplier-performance.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-bar-chart"></i> Performance report
        </a>
        @can('create', App\Models\Supplier::class)
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Supplier</a>
        @endcan
    </div>
</div>

<div class="card app-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('suppliers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold" for="search">Search</label>
                <input type="search" name="search" id="search" value="{{ $search }}"
                       class="form-control" placeholder="Company, contact or email">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold" for="status">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold" for="lead_time">Max lead time</label>
                <select name="lead_time" id="lead_time" class="form-select">
                    <option value="0">Any</option>
                    @foreach ([3, 7, 14, 30] as $days)
                        <option value="{{ $days }}" @selected($leadTime === $days)>{{ $days }} days</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold" for="sort">Sort by</label>
                <select name="sort" id="sort" class="form-select">
                    <option value="">Newest</option>
                    <option value="name" @selected($sort === 'name')>Company name</option>
                    <option value="lead_time" @selected($sort === 'lead_time')>Lead time</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card app-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Code / Company</th>
                    <th>Contact</th>
                    <th>Terms</th>
                    <th class="text-center">Lead time</th>
                    <th class="text-center">Orders</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>
                        <a href="{{ route('suppliers.show', $supplier) }}" class="text-decoration-none fw-semibold">{{ $supplier->company_name }}</a>
                        <span class="d-block small text-muted"><code>{{ $supplier->code }}</code></span>
                    </td>
                    <td>{{ $supplier->contact_person }}<span class="d-block small text-muted">{{ $supplier->email }}</span></td>
                    <td>{{ $supplier->payment_terms }}</td>
                    <td class="text-center">{{ $supplier->lead_time_days }} d</td>
                    <td class="text-center">{{ $supplier->purchase_orders_count }}</td>
                    <td class="text-center">
                        <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($supplier->status) }}</span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                        @can('update', $supplier)
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                        @endcan
                        @can('delete', $supplier)
                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="d-inline" onsubmit="return confirm('Soft-delete this supplier?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No suppliers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-transparent">{{ $suppliers->links() }}</div>
</div>
@endsection
