@extends('layouts.app')

@section('title', 'Stock Adjustment')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Physical Stock Adjustment</h1>
            <p class="text-muted mb-0">Enter the physical count; the difference is recorded as an adjustment.</p>
        </div>
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to transactions
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card app-card">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('transactions.adjust.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="product_id" class="form-label fw-semibold">Product</label>
                            <select id="adjust_product_id" name="product_id" class="form-select" required>
                                <option value="">Choose a product…</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-stock="{{ $product->quantity }}"
                                        @selected(old('product_id') == $product->id)>
                                        {{ $product->name }} — {{ $product->sku }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="system_quantity" class="form-label fw-semibold">System quantity</label>
                                <input type="text" id="system_quantity" class="form-control" value="—" readonly>
                                <div class="form-text">Read from the current product record.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="physical_count" class="form-label fw-semibold">Physical count</label>
                                <input type="number" id="physical_count" name="physical_count" class="form-control"
                                       value="{{ old('physical_count') }}" min="0" max="100000" required>
                                <div class="form-text" id="adjustDifference">Difference will be calculated automatically.</div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="reason" class="form-label fw-semibold">Reason for adjustment</label>
                            <select id="reason" name="reason" class="form-select" required>
                                <option value="">Choose a reason…</option>
                                @foreach ($reasons as $value => $label)
                                    <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea id="notes" name="notes" rows="3" class="form-control" maxlength="1000"
                                      placeholder="Explain the discrepancy…">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-sliders2"></i> Apply adjustment
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/transactions.js') }}?v=1"></script>
@endpush
