@extends('layouts.app')

@section('title', 'Record Stock Movement')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Record Stock Movement</h1>
            <p class="text-muted mb-0">Every movement updates stock automatically and writes an audit record.</p>
        </div>
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to transactions
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card app-card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('transactions.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="product_id" class="form-label fw-semibold">Product</label>
                            <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">Choose a product…</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-stock="{{ $product->quantity }}" data-sku="{{ $product->sku }}"
                                        @selected(old('product_id', request('product_id')) == $product->id)>
                                        {{ $product->name }} — {{ $product->sku }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div id="stockHint" class="form-text">Select a product to see its current stock level.</div>
                        </div>

                        <div class="mb-3">
                            <span class="form-label fw-semibold d-block">Transaction type</span>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($types as $value => $label)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input @error('type') is-invalid @enderror" type="radio"
                                               name="type" id="type_{{ $value }}" value="{{ $value }}"
                                               @checked(old('type', 'in') === $value)>
                                        <label class="form-check-label" for="type_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="quantity" class="form-label fw-semibold">Quantity moved</label>
                                <input type="number" id="quantity" name="quantity"
                                       class="form-control @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity') }}" min="1" max="100000" required>
                                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text" id="quantityHint">Units to add to the selected product.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="reference" class="form-label fw-semibold">Reference number</label>
                                <input type="text" id="reference" name="reference"
                                       class="form-control @error('reference') is-invalid @enderror"
                                       value="{{ old('reference') }}" maxlength="128"
                                       placeholder="e.g. PO-2026-001 / SO-1042">
                                @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="reason" class="form-label fw-semibold">Reason <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" id="reason" name="reason"
                                   class="form-control @error('reason') is-invalid @enderror"
                                   value="{{ old('reason') }}" maxlength="64" placeholder="e.g. Purchase order received">
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      maxlength="1000" placeholder="Additional context for the audit trail…">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check2-circle"></i> Record movement
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
