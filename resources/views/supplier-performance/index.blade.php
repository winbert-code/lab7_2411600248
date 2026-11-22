@extends('layouts.app')

@section('title', 'Supplier Performance')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Supplier Performance Report</h1>
        <p class="text-muted mb-0">Delivery reliability, accuracy and purchasing value by vendor.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @can('export-reports')
            <a href="{{ route('exports.pdf', 'suppliers') }}" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            <a href="{{ route('supplier-performance.export') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv"></i> CSV</a>
        @endcan
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-primary"><i class="bi bi-truck"></i> Suppliers</a>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ([
        ['Suppliers measured', $rows->count(), 'bi-people'],
        ['Total orders', $totals['orders'], 'bi-cart-check'],
        ['Avg. on-time rate', $totals['average_on_time'].'%', 'bi-clock-history'],
        ['Purchased value', '₱'.number_format($totals['value'], 0), 'bi-cash-stack'],
    ] as $card)
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body">
                <p class="stat-label mb-1">{{ $card[0] }}</p>
                <h2 class="h4 mb-0"><i class="bi {{ $card[2] }} text-success"></i> {{ $card[1] }}</h2>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card app-card h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-0">Delivery trends</h2></div>
            <div class="card-body px-4 pb-4"><canvas id="performanceChart" height="280"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card app-card h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h2 class="h5 fw-bold mb-0">Top performers</h2>
                <p class="text-muted small mb-0">Highest on-time delivery rates.</p>
            </div>
            <div class="list-group list-group-flush px-4 pb-3">
                @forelse ($top as $row)
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <a href="{{ route('suppliers.show', $row['supplier']) }}" class="text-decoration-none fw-semibold">{{ $row['supplier']->company_name }}</a>
                        <span class="badge bg-success">{{ $row['metrics']['on_time_percentage'] }}%</span>
                    </div>
                @empty
                    <p class="text-muted small py-3 mb-0">No data available.</p>
                @endforelse
            </div>
            <div class="card-header bg-transparent border-0 px-4 pb-2"><h2 class="h6 fw-bold mb-0">Needs attention</h2></div>
            <div class="list-group list-group-flush px-4 pb-3">
                @forelse ($bottom as $row)
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <a href="{{ route('suppliers.show', $row['supplier']) }}" class="text-decoration-none">{{ $row['supplier']->company_name }}</a>
                        <span class="badge bg-warning text-dark">{{ $row['metrics']['on_time_percentage'] }}%</span>
                    </div>
                @empty
                    <p class="text-muted small py-3 mb-0">No data available.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card app-card mt-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h2 class="h5 fw-bold mb-0">Performance summary</h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr>
                <th>Supplier</th><th class="text-center">Orders</th><th class="text-center">On time</th>
                <th class="text-center">Avg. delivery</th><th class="text-center">Accuracy</th>
                <th class="text-end">Value</th><th>Rating</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><a href="{{ route('suppliers.show', $row['supplier']) }}" class="text-decoration-none fw-semibold">{{ $row['supplier']->company_name }}</a></td>
                    <td class="text-center">{{ $row['metrics']['total_orders'] }}</td>
                    <td class="text-center">{{ $row['metrics']['on_time_orders'] }}/{{ $row['metrics']['received_orders'] }} ({{ $row['metrics']['on_time_percentage'] }}%)</td>
                    <td class="text-center">{{ $row['metrics']['average_delivery_days'] }} days</td>
                    <td class="text-center">{{ $row['metrics']['order_accuracy'] }}%</td>
                    <td class="text-end">₱{{ number_format($row['metrics']['total_value'], 2) }}</td>
                    <td><span class="badge bg-{{ $row['metrics']['on_time_percentage'] >= 90 ? 'success' : ($row['metrics']['on_time_percentage'] >= 70 ? 'warning' : 'danger') }}">{{ number_format($row['metrics']['on_time_percentage'], 1) }}%</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No suppliers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const performanceData = {{ Js::from($chart) }};
    new Chart(document.getElementById('performanceChart'), {
        type: 'bar',
        data: {
            labels: performanceData.labels,
            datasets: [
                { label: 'On-time %', data: performanceData.onTime, backgroundColor: '#1DBF04', borderRadius: 5 },
                { label: 'Accuracy %', data: performanceData.accuracy, backgroundColor: '#A7F205', borderRadius: 5 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100 } },
        },
    });
</script>
@endpush
