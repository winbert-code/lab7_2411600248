@extends('layouts.app')

@section('title', $editing ? 'Edit Sales Order' : 'New Sales Order')

@section('content')
    @php
        $rows = old('items');
        if (! is_array($rows) || $rows === []) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = [
                    'product_id' => $item->product_id ?? '',
                    'quantity' => $item->quantity ?? 1,
                    'unit_price' => $item->unit_price ?? '',
                ];
            }
        }
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $editing ? 'Edit Sales Order' : 'New Sales Order' }}</h1>
            <p class="text-muted mb-0">Only in-stock products can be ordered. An admin must approve the order before stock is deducted.</p>
        </div>
        <a href="{{ $editing ? route('sales-orders.show', $order) : route('sales-orders.index') }}" class="btn btn-outline-secondary">
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
                        <label for="order_number" class="form-label">Order number</label>
                        <input type="text" id="order_number" name="order_number"
                               class="form-control @error('order_number') is-invalid @enderror"
                               value="{{ old('order_number', $order->order_number) }}" maxlength="32">
                        @error('order_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="customer" class="form-label">Customer</label>
                        <input type="text" id="customer" name="customer"
                               class="form-control @error('customer') is-invalid @enderror"
                               value="{{ old('customer', $order->customer) }}" maxlength="128" required>
                        @error('customer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="order_date" class="form-label">Order date</label>
                        <input type="date" id="order_date" name="order_date"
                               class="form-control @error('order_date') is-invalid @enderror"
                               value="{{ old('order_date', $order->order_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                        @error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="due_date" class="form-label">Due date <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="date" id="due_date" name="due_date"
                               class="form-control @error('due_date') is-invalid @enderror"
                               value="{{ old('due_date', $order->due_date?->format('Y-m-d')) }}">
                        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label for="notes" class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" id="notes" name="notes"
                               class="form-control @error('notes') is-invalid @enderror"
                               value="{{ old('notes', $order->notes) }}" maxlength="1000">
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card app-card mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold mb-0">Order items</h2>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-item">
                    <i class="bi bi-plus-lg"></i> Add item
                </button>
            </div>
            <div class="card-body px-4 pb-4">
                @error('items') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

                <div id="items-container">
                    @foreach ($rows as $index => $row)
                        <div class="row g-2 align-items-end item-row mb-2">
                            <div class="col-md-5">
                                <label class="form-label small">Product</label>
                                <select name="items[{{ $index }}][product_id]" class="form-select form-select-sm product-select @error("items.$index.product_id") is-invalid @enderror" required>
                                    <option value="">Choose a product…</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" data-stock="{{ $product->quantity }}"
                                            @selected((string) old("items.$index.product_id", $row['product_id']) === (string) $product->id)>
                                            {{ $product->name }} — {{ $product->sku }} ({{ $product->quantity }} in stock)
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
                                <input type="number" name="items[{{ $index }}][unit_price]" min="0" step="0.01"
                                       class="form-control form-control-sm unit-price @error("items.$index.unit_price") is-invalid @enderror"
                                       value="{{ old("items.$index.unit_price", $row['unit_price']) }}" required>
                                @error("items.$index.unit_price") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-item w-100" title="Remove item">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template id="item-template">
                    <div class="row g-2 align-items-end item-row mb-2">
                        <div class="col-md-5">
                            <label class="form-label small">Product</label>
                            <select name="items[__INDEX__][product_id]" class="form-select form-select-sm product-select" required>
                                <option value="">Choose a product…</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" data-stock="{{ $product->quantity }}">
                                        {{ $product->name }} — {{ $product->sku }} ({{ $product->quantity }} in stock)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Quantity</label>
                            <input type="number" name="items[__INDEX__][quantity]" min="1" max="100000"
                                   class="form-control form-control-sm" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Unit price (₱)</label>
                            <input type="number" name="items[__INDEX__][unit_price]" min="0" step="0.01"
                                   class="form-control form-control-sm unit-price" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item w-100" title="Remove item">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle"></i> {{ $editing ? 'Save changes' : 'Create sales order' }}
            </button>
            <a href="{{ $editing ? route('sales-orders.show', $order) : route('sales-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
                const html = template.innerHTML.replaceAll('__INDEX__', nextIndex++);
                container.insertAdjacentHTML('beforeend', html);
            });

            container.addEventListener('change', function (event) {
                if (!event.target.classList.contains('product-select')) return;
                const option = event.target.selectedOptions[0];
                if (!option) return;
                const row = event.target.closest('.item-row');
                row.querySelector('.unit-price').value = option.dataset.price || '';
                const quantity = row.querySelector('input[name*="[quantity]"]');
                if (quantity) {
                    quantity.max = option.dataset.stock || '1';
                }
            });

            container.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-item');
                if (!button) return;
                if (container.querySelectorAll('.item-row').length === 1) {
                    alert('A sales order needs at least one item.');
                    return;
                }
                button.closest('.item-row').remove();
            });
        })();
    </script>
@endpush
