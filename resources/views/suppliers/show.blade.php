@extends('layouts.app')

@section('title', 'Supplier Details')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $supplier->company_name }}</h1>
        <p class="text-muted mb-0">
            <code>{{ $supplier->code }}</code> &middot;
            <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($supplier->status) }}</span>
            @if ($supplier->trashed()) <span class="badge bg-danger">Soft-deleted</span> @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        @if ($supplier->trashed())
            @can('restore', $supplier)
                <form method="POST" action="{{ route('suppliers.restore', $supplier) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                </form>
            @endcan
        @else
            @can('update', $supplier)
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card app-card h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-0">Company details</h2></div>
            <div class="card-body px-4">
                <dl class="row mb-0 small">
                    <dt class="col-4">Contact</dt><dd class="col-8">{{ $supplier->contact_person }}</dd>
                    <dt class="col-4">Email</dt><dd class="col-8"><a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a></dd>
                    <dt class="col-4">Phone</dt><dd class="col-8">{{ $supplier->phone }}</dd>
                    <dt class="col-4">Address</dt><dd class="col-8">{{ $supplier->address }}{{ $supplier->city ? ', '.$supplier->city : '' }}</dd>
                    <dt class="col-4">Terms</dt><dd class="col-8">{{ $supplier->payment_terms }}</dd>
                    <dt class="col-4">Lead time</dt><dd class="col-8">{{ $supplier->lead_time_days }} days</dd>
                    <dt class="col-4">Min. order</dt><dd class="col-8">₱{{ number_format($supplier->minimum_order_amount, 2) }}</dd>
                </dl>
                @if ($supplier->notes)
                    <hr><p class="small text-muted mb-0">{{ $supplier->notes }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            @foreach ([
                ['Total orders', $metrics['total_orders'], 'bi-cart'],
                ['On-time delivery', $metrics['on_time_percentage'].'%', 'bi-clock-history'],
                ['Avg. delivery', $metrics['average_delivery_days'].' d', 'bi-calendar2-check'],
                ['Order accuracy', $metrics['order_accuracy'].'%', 'bi-check2-circle'],
                ['Order value', '₱'.number_format($metrics['total_value'], 0), 'bi-cash-stack'],
            ] as $metric)
                <div class="col-6 col-md-4">
                    <div class="card app-card h-100"><div class="card-body">
                        <p class="stat-label mb-1">{{ $metric[0] }}</p>
                        <h3 class="h5 mb-0"><i class="bi {{ $metric[2] }} text-success"></i> {{ $metric[1] }}</h3>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="card app-card mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
                <h2 class="h5 fw-bold mb-0">Recent purchase orders</h2>
                <a href="{{ route('purchase-orders.index', ['supplier' => $supplier->id]) }}" class="small text-decoration-none">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Reference</th><th>Date</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('purchase-orders.show', $order) }}" class="text-decoration-none fw-semibold">{{ $order->reference_number }}</a></td>
                            <td>{{ $order->order_date?->format('M d, Y') }}</td>
                            <td class="text-end">₱{{ number_format($order->total, 2) }}</td>
                            <td><span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No purchase orders yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card app-card">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-0">Products supplied</h2></div>
            <div class="card-body px-4">
                @forelse ($products as $product)
                    <a href="{{ route('products.show', $product) }}" class="badge bg-light text-dark border text-decoration-none me-1 mb-1">
                        {{ $product->name }} ({{ $product->sku }})
                    </a>
                @empty
                    <p class="text-muted mb-0">No products linked to this supplier.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
