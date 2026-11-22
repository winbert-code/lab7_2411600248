<div class="card app-card h-100">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
        <div>
            <h2 class="h5 fw-bold mb-0">Recent purchase orders</h2>
            <p class="text-muted small mb-0">Latest procurement activity.</p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="small text-decoration-none">View all</a>
    </div>
    <div class="list-group list-group-flush px-4 pb-3">
        @forelse ($orders as $order)
            <a href="{{ $order['url'] }}" class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center">
                <span>
                    <span class="fw-semibold d-block">{{ $order['reference_number'] }}</span>
                    <span class="small text-muted">{{ $order['supplier'] }} · {{ $order['order_date'] }}</span>
                </span>
                <span class="badge bg-{{ $order['status_color'] }}">{{ $order['status_label'] }}</span>
            </a>
        @empty
            <p class="text-muted small py-3 mb-0">No purchase orders yet.</p>
        @endforelse
    </div>
</div>
