@extends('layouts.app')

@section('title', $editing ? 'Edit Purchase Order' : 'New Purchase Order')

@section('content')
@php
    $rows = old('items');
    if (! is_array($rows) || $rows === []) {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'product_id' => $item->product_id ?? '',
                'quantity' => $item->quantity ?? 1,
                'unit_cost' => $item->unit_cost ?? '',
            ];
        }
    }
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $editing ? 'Edit Purchase Order' : 'New Purchase Order' }}</h1>
        <p class="text-muted mb-0">Unit prices are frozen at order time; stock changes only when goods are received.</p>
    </div>
    <a href="{{ $editing ? route('purchase-orders.show', $order) : route('purchase-orders.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<form method="POST" action="{{ $action }}" id="order-form">
    @csrf
    @method($method)

    <div class="card app-card mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="reference_number" class="form-label">Reference number</label>
                    <input type="text" id="reference_number" name="reference_number"
                           class="form-control @error('reference_number') is-invalid @enderror"
                           value="{{ old('reference_number', $order->reference_number) }}" maxlength="32">
                    @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                        <option value="">Choose a supplier…</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $order->supplier_id) === (string) $supplier->id)>
                                {{ $supplier->company_name }} · {{ $supplier->lead_time_days }}d · {{ $supplier->payment_terms }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="order_date" class="form-label">Order date <span class="text-danger">*</span></label>
                    <input type="date" id="order_date" name="order_date"
                           class="form-control @error('order_date') is-invalid @enderror"
                           value="{{ old('order_date', $order->order_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    @error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="expected_delivery_date" class="form-label">Expected delivery</label>
                    <input type="date" id="expected_delivery_date" name="expected_delivery_date"
                           class="form-control @error('expected_delivery_date') is-invalid @enderror"
                           value="{{ old('expected_delivery_date', $order->expected_delivery_date?->format('Y-m-d')) }}">
                    @error('expected_delivery_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="tax_rate" class="form-label">Tax rate (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01"
                           class="form-control @error('tax_rate') is-invalid @enderror"
                           value="{{ old('tax_rate', $order->tax_rate ?? config('replenishment.tax_rate')) }}">
                    @error('tax_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="terms" class="form-label">Terms</label>
                    <input type="text" id="terms" name="terms" class="form-control"
                           value="{{ old('terms', $order->terms) }}" placeholder="Net 30">
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $order->notes) }}</textarea>
                </div>
                @unless ($editing)
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="submit_for_approval" name="submit_for_approval"
                                   @checked(old('submit_for_approval'))>
                            <label class="form-check-label" for="submit_for_approval">
                                Submit for approval immediately (otherwise saved as draft)
                            </label>
                        </div>
                    </div>
                @endunless
            </div>
        </div>
    </div>

    <div class="card app-card mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <h2 class="h5 fw-bold mb-0">Order items</h2>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-item"><i class="bi bi-plus-lg"></i> Add item</button>
        </div>
        <div class="card-body px-4 pb-4">
            @error('items') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
            <div id="items-container">
                @foreach ($rows as $index => $row)
                    <div class="row g-2 align-items-end item-row mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Product</label>
                            <select name="items[{{ $index }}][product_id]" class="form-select form-select-sm product-select @error("items.$index.product_id") is-invalid @enderror" required>
                                <option value="">Choose a product…</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-cost="{{ $product->cost_price }}"
                                        @selected((string) old("items.$index.product_id", $row['product_id']) === (string) $product->id)>
                                        {{ $product->name }} — {{ $product->sku }}
                                    </option>
                                @endforeach
                            </select>
                            @error("items.$index.product_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Quantity</label>
                            <input type="number" name="items[{{ $index }}][quantity]" min="1" max="100000"
                                   class="form-control form-control-sm @error("items.$index.quantity") is-invalid @enderror"
                                   value="{{ old("items.$index.quantity", $row['quantity']) }}" required>
                            @error("items.$index.quantity") <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Unit price (₱)</label>
                            <input type="number" name="items[{{ $index }}][unit_cost]" min="0" step="0.01"
                                   class="form-control form-control-sm unit-price @error("items.$index.unit_cost") is-invalid @enderror"
                                   value="{{ old("items.$index.unit_cost", $row['unit_cost']) }}" required>
                            @error("items.$index.unit_cost") <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item w-100" title="Remove"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>

            <template id="item-template">
                <div class="row g-2 align-items-end item-row mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Product</label>
                        <select name="items[__INDEX__][product_id]" class="form-select form-select-sm product-select" required>
                            <option value="">Choose a product…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-cost="{{ $product->cost_price }}">{{ $product->name }} — {{ $product->sku }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Quantity</label>
                        <input type="number" name="items[__INDEX__][quantity]" min="1" max="100000" class="form-control form-control-sm" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Unit price (₱)</label>
                        <input type="number" name="items[__INDEX__][unit_cost]" min="0" step="0.01" class="form-control form-control-sm unit-price" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item w-100" title="Remove"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle"></i> {{ $editing ? 'Save changes' : 'Create purchase order' }}</button>
        <a href="{{ $editing ? route('purchase-orders.show', $order) : route('purchase-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const container = document.getElementById('items-container');
    const template = document.getElementById('item-template');
    let nextIndex = container.querySelectorAll('.item-row').length;

    document.getElementById('add-item').addEventListener('click', function () {
        container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
    });

    container.addEventListener('change', function (event) {
        if (!event.target.classList.contains('product-select')) return;
        const option = event.target.selectedOptions[0];
        const row = event.target.closest('.item-row');
        if (option && option.dataset.cost) {
            row.querySelector('.unit-price').value = option.dataset.cost;
        }
    });

    container.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-item');
        if (!button) return;
        if (container.querySelectorAll('.item-row').length === 1) {
            alert('A purchase order needs at least one item.');
            return;
        }
        button.closest('.item-row').remove();
    });
})();
</script>
@endpush
