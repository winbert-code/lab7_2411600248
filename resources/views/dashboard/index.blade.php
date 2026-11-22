@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div id="dashboard-root"
         data-stats-url="{{ route('api.dashboard.stats') }}"
         data-low-stock-url="{{ route('api.products.low-stock') }}"
         data-recent-url="{{ route('api.transactions.recent') }}"
         data-poll-interval="30000">
        @if (auth()->user()->isStaff())
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-shield-check"></i>
                <strong>Staff view:</strong> you can review records and prepare purchase or sales orders.
                Receiving stock, completing orders, editing the catalog and managing users are restricted to managers and admins.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Dashboard</h1>
                <p class="text-muted mb-0">Live overview of your computer parts inventory.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2" id="liveIndicator">
                    <i class="bi bi-broadcast"></i> Live &middot; updated <span id="lastUpdated">{{ now()->format('h:i:s A') }}</span>
                </span>
                @can('manage-inventory')
                    <a href="{{ route('transactions.create') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left-right"></i> Record movement
                    </a>
                    <a href="{{ route('products.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Product
                    </a>
                @endcan
                @can('create-orders')
                    <a href="{{ route('sales-orders.create') }}" class="btn btn-primary">
                        <i class="bi bi-cart-plus"></i> New Sales Order
                    </a>
                @endcan
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="stat-label mb-1">Active products</p>
                                <h2 class="stat-value mb-0" id="stat-products">{{ number_format($stats->total_products) }}</h2>
                            </div>
                            <span class="stat-icon icon-primary"><i class="bi bi-box-seam"></i></span>
                        </div>
                        <a href="{{ route('products.index') }}" class="stat-link">View inventory <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="stat-label mb-1">Low stock items</p>
                                <h2 class="stat-value mb-0" id="stat-low">{{ number_format($stats->low_stock) }}</h2>
                            </div>
                            <span class="stat-icon icon-warning"><i class="bi bi-exclamation-triangle"></i></span>
                        </div>
                        <span class="stat-link text-warning">At or below reorder level</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="stat-label mb-1">Out of stock</p>
                                <h2 class="stat-value mb-0" id="stat-out">{{ number_format($stats->out_of_stock) }}</h2>
                            </div>
                            <span class="stat-icon icon-danger"><i class="bi bi-x-octagon"></i></span>
                        </div>
                        <span class="stat-link text-danger">Needs restocking</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="stat-label mb-1">Inventory value</p>
                                <h2 class="stat-value mb-0" id="stat-value">&#8369;{{ number_format((float) $stats->inventory_value, 2) }}</h2>
                            </div>
                            <span class="stat-icon icon-success"><i class="bi bi-graph-up"></i></span>
                        </div>
                        <span class="stat-link text-success">Based on current stock</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card app-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-1">Total movements</p>
                            <h3 class="h4 mb-0" id="stat-transactions">—</h3>
                        </div>
                        <span class="stat-icon icon-primary"><i class="bi bi-arrow-left-right"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card app-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-1">Units received today</p>
                            <h3 class="h4 mb-0" id="stat-today">—</h3>
                        </div>
                        <span class="stat-icon icon-success"><i class="bi bi-box-arrow-in-down"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card app-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-1">Reports</p>
                            <h3 class="h4 mb-0"><a href="{{ route('reports.index') }}" class="text-decoration-none">Open reports <i class="bi bi-arrow-up-right"></i></a></h3>
                        </div>
                        <span class="stat-icon icon-warning"><i class="bi bi-file-earmark-bar-graph"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h5 fw-bold mt-4 mb-3"><i class="bi bi-truck"></i> Procurement overview</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">@include('widgets.pending-orders')</div>
            <div class="col-12 col-md-6 col-xl-3">@include('widgets.recent-orders')</div>
            <div class="col-12 col-md-6 col-xl-3">@include('widgets.supplier-performance')</div>
            <div class="col-12 col-md-6 col-xl-3">@include('widgets.reorder-alerts')</div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card app-card h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h2 class="h5 fw-bold mb-0">Products per category</h2>
                        <p class="text-muted small mb-0">Distribution of stocked computer parts.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <canvas id="categoryChart" height="260" aria-label="Products per category chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card app-card h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
                        <div>
                            <h2 class="h5 fw-bold mb-0">Low stock alerts</h2>
                            <p class="text-muted small mb-0">Reorder these items soon.</p>
                        </div>
                        <a href="{{ route('products.index', ['status' => 'active']) }}" class="small text-decoration-none">See all</a>
                    </div>
                    <div class="list-group list-group-flush px-4" id="lowStockList">
                        @forelse ($lowStockProducts as $product)
                            <a href="{{ route('products.show', $product) }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                <span>
                                    <span class="fw-semibold d-block">{{ $product->name }}</span>
                                    <span class="small text-muted">{{ $product->sku }} &middot; {{ $product->quantity }} / {{ $product->reorder_level }}</span>
                                </span>
                                <x-stock-badge :status="$product->availabilityStatus()" />
                            </a>
                        @empty
                            <p class="text-muted px-0 py-3 mb-0">No low stock items. Everything is well stocked.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card app-card">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 fw-bold mb-0">Recent stock movements</h2>
                            <p class="text-muted small mb-0">The latest entries in the inventory audit trail.</p>
                        </div>
                        <a href="{{ route('transactions.index') }}" class="small text-decoration-none">View all</a>
                    </div>
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
                                </tr>
                            </thead>
                            <tbody id="recentTransactionsBody">
                                @forelse ($recentTransactions as $transaction)
                                    <tr>
                                        <td class="text-nowrap">{{ $transaction->created_at?->format('M d, Y h:i A') }}</td>
                                        <td>
                                            <a href="{{ route('products.show', $transaction->product) }}" class="text-decoration-none fw-semibold">
                                                {{ $transaction->product?->name ?? '—' }}
                                            </a>
                                        </td>
                                        <td><span class="stock-badge badge-{{ $transaction->type_color }}">{{ $transaction->type_label }}</span></td>
                                        <td class="text-end fw-semibold text-{{ $transaction->type_color }}">
                                            {{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ number_format($transaction->signedQuantity()) }}
                                        </td>
                                        <td><code>{{ $transaction->reference_number }}</code></td>
                                        <td>{{ $transaction->user?->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4" id="noTransactionsRow">No stock movements recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card app-card">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
                        <div>
                            <h2 class="h5 fw-bold mb-0">Recent activity</h2>
                            <p class="text-muted small mb-0">The latest actions recorded in the system.</p>
                        </div>
                        <a href="{{ route('activity-logs.index') }}" class="small text-decoration-none">See all</a>
                    </div>
                    <div class="list-group list-group-flush px-4">
                        @forelse ($recentActivity as $log)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>
                                    <span class="fw-semibold d-block">{{ $log->description }}</span>
                                    <span class="small text-muted">
                                        {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at?->diffForHumans() }}
                                    </span>
                                </span>
                                <span class="badge {{ $log->badgeClass() }}">{{ $log->action }}</span>
                            </div>
                        @empty
                            <p class="text-muted px-0 py-3 mb-0">No activity recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}?v=1"></script>
    <script>
        const categoryData = {{ Js::from($chart) }};

        window.categoryChart = new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: categoryData.labels,
                datasets: [{
                    label: 'Products',
                    data: categoryData.values,
                    backgroundColor: '#1DBF04',
                    hoverBackgroundColor: '#A7F205',
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e0e0e0' } },
                    x: { grid: { display: false } },
                },
            },
        });
    </script>
@endpush
