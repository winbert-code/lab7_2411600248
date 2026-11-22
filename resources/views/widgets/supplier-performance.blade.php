<div class="card app-card h-100">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
        <div>
            <h2 class="h5 fw-bold mb-0">Supplier performance</h2>
            <p class="text-muted small mb-0">On-time delivery rate by vendor.</p>
        </div>
        <a href="{{ route('supplier-performance.index') }}" class="small text-decoration-none">Report</a>
    </div>
    <div class="list-group list-group-flush px-4 pb-3">
        @forelse ($suppliers as $row)
            <div class="list-group-item px-0">
                <div class="d-flex justify-content-between small">
                    <a href="{{ $row['url'] }}" class="text-decoration-none fw-semibold">{{ $row['company_name'] }}</a>
                    <span class="fw-bold">{{ $row['metrics']['on_time_percentage'] }}%</span>
                </div>
                <div class="progress mt-1" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar"
                         style="width: {{ $row['metrics']['on_time_percentage'] }}%"
                         aria-valuenow="{{ $row['metrics']['on_time_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        @empty
            <p class="text-muted small py-3 mb-0">No suppliers to measure.</p>
        @endforelse
    </div>
</div>
