<div class="card app-card h-100">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
        <div>
            <h2 class="h5 fw-bold mb-0">Reorder alerts</h2>
            <p class="text-muted small mb-0">Calculated from usage, lead time and safety stock.</p>
        </div>
        <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
    </div>
    <div class="list-group list-group-flush px-4 pb-3">
        @forelse ($alerts as $alert)
            <a href="{{ route('products.show', $alert['product_id']) }}" class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center">
                <span>
                    <span class="fw-semibold d-block">{{ $alert['product_name'] }}</span>
                    <span class="small text-muted">{{ $alert['current_stock'] }} / {{ $alert['reorder_point'] }} units · suggest {{ number_format($alert['suggested_quantity']) }}</span>
                </span>
                <span class="badge bg-warning text-dark">{{ $alert['supplier'] }}</span>
            </a>
        @empty
            <p class="text-muted small py-3 mb-0">All products are above their reorder points.</p>
        @endforelse
    </div>
</div>
