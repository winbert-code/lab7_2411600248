@extends('layouts.app')

@section('title', 'Transaction #'.$transaction->id)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Transaction #{{ $transaction->id }}</h1>
            <p class="text-muted mb-0">{{ $transaction->created_at?->format('F d, Y h:i A') }}</p>
        </div>
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to transactions
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card app-card h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-0">Movement details</h2>
                </div>
                <div class="card-body px-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted">Type</dt>
                        <dd class="col-sm-7">
                            <span class="stock-badge badge-{{ $transaction->type_color }}">{{ $transaction->type_label }}</span>
                        </dd>
                        <dt class="col-sm-5 text-muted">Quantity moved</dt>
                        <dd class="col-sm-7 fw-bold text-{{ $transaction->type_color }}">
                            {{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ number_format($transaction->signedQuantity()) }} units
                        </dd>
                        <dt class="col-sm-5 text-muted">Previous balance</dt>
                        <dd class="col-sm-7">{{ $transaction->previous_quantity ?? '—' }}</dd>
                        <dt class="col-sm-5 text-muted">Balance after</dt>
                        <dd class="col-sm-7 fw-bold">{{ $transaction->balance_after ?? '—' }}</dd>
                        <dt class="col-sm-5 text-muted">Reference number</dt>
                        <dd class="col-sm-7"><code>{{ $transaction->reference_number }}</code></dd>
                        <dt class="col-sm-5 text-muted">Reason</dt>
                        <dd class="col-sm-7">{{ $transaction->reason ?? '—' }}</dd>
                        <dt class="col-sm-5 text-muted">Notes</dt>
                        <dd class="col-sm-7">{{ $transaction->notes ?? '—' }}</dd>
                    </dl>
                </div>
                @can('admin')
                    <div class="card-footer bg-transparent border-0 px-4 pb-4">
                        <form method="POST" action="{{ route('transactions.destroy', $transaction) }}"
                              onsubmit="return confirm('Delete this transaction? The stock balance will be reversed.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger" type="submit">
                                <i class="bi bi-trash"></i> Delete transaction
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card app-card mb-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-0">Product</h2>
                </div>
                <div class="card-body px-4">
                    @if ($transaction->product)
                        <h3 class="h6 fw-bold mb-1">{{ $transaction->product->name }}</h3>
                        <p class="text-muted small mb-3"><code>{{ $transaction->product->sku }}</code></p>
                        <p class="mb-2">Current stock: <strong>{{ $transaction->product->quantity }}</strong></p>
                        <p class="mb-0">Category: {{ $transaction->product->category?->name ?? '—' }} &middot;
                            Brand: {{ $transaction->product->brand?->name ?? '—' }}</p>
                        <a href="{{ route('products.show', $transaction->product) }}" class="btn btn-sm btn-outline-primary mt-3">
                            View product <i class="bi bi-arrow-right"></i>
                        </a>
                    @else
                        <p class="text-muted mb-0">The related product record no longer exists.</p>
                    @endif
                </div>
            </div>

            <div class="card app-card">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-0">Performed by</h2>
                </div>
                <div class="card-body px-4">
                    <p class="mb-1 fw-semibold">{{ $transaction->user?->name ?? 'System' }}</p>
                    <p class="text-muted small mb-0">{{ $transaction->user?->email ?? 'Automated audit entry' }}</p>
                    @if ($transaction->user)
                        <span class="badge bg-secondary mt-2">{{ ucfirst($transaction->user->role) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
