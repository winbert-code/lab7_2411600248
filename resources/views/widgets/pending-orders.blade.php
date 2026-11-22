<div class="card app-card h-100">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
        <div>
            <h2 class="h5 fw-bold mb-0">Pending approvals</h2>
            <p class="text-muted small mb-0">Orders waiting for a decision.</p>
        </div>
        <i class="bi bi-hourglass-split fs-3 text-warning"></i>
    </div>
    <div class="card-body px-4">
        <h2 class="display-6 fw-bold mb-0">{{ $count }}</h2>
        <p class="text-muted small mb-2">
            @if ($awaitingFinal > 0) {{ $awaitingFinal }} awaiting administrator approval @else Awaiting manager approval @endif
        </p>
        <a href="{{ route('purchase-orders.index', ['status' => 'pending']) }}" class="small text-decoration-none">Review orders <i class="bi bi-arrow-right"></i></a>
        <p class="text-muted small mt-2 mb-0">Pending value: ₱{{ number_format($totalValue, 2) }}</p>
    </div>
</div>
