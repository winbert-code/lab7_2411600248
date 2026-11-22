/*
 * Shared helpers for the stock movement and physical adjustment forms.
 * Shows the selected product's current stock and constrains stock-out inputs.
 */
(function () {
    'use strict';

    const numberFormat = new Intl.NumberFormat('en-US');

    function selectedOption(select) {
        return select && select.options[select.selectedIndex];
    }

    function stockOf(option) {
        return option ? Number(option.dataset.stock || 0) : 0;
    }

    // Record movement form ---------------------------------------------------
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const stockHint = document.getElementById('stockHint');
    const quantityHint = document.getElementById('quantityHint');

    function selectedType() {
        const checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'in';
    }

    function updateMovementForm() {
        if (!productSelect || !quantityInput) {
            return;
        }

        const option = selectedOption(productSelect);
        const stock = stockOf(option);
        const type = selectedType();

        if (stockHint) {
            stockHint.textContent = option && option.value
                ? `Current stock for ${option.dataset.sku || 'this product'}: ${numberFormat.format(stock)} units. Reorder alerts are sent to managers automatically.`
                : 'Select a product to see its current stock level.';
        }

        quantityInput.max = type === 'out' ? String(stock) : '100000';

        if (type === 'out') {
            quantityHint.textContent = `Maximum stock-out quantity: ${numberFormat.format(stock)} units.`;
            if (option && option.value && Number(quantityInput.value) > stock) {
                quantityInput.setCustomValidity(`Only ${stock} units are available.`);
            } else {
                quantityInput.setCustomValidity('');
            }
        } else if (type === 'adjustment') {
            quantityHint.textContent = 'Enter the new absolute stock quantity for this product.';
            quantityInput.setCustomValidity('');
        } else {
            quantityHint.textContent = 'Units to add to the selected product.';
            quantityInput.setCustomValidity('');
        }
    }

    if (productSelect) {
        productSelect.addEventListener('change', updateMovementForm);
    }
    if (quantityInput) {
        quantityInput.addEventListener('input', updateMovementForm);
    }
    document.querySelectorAll('input[name="type"]').forEach((radio) => {
        radio.addEventListener('change', updateMovementForm);
    });
    updateMovementForm();

    // Physical adjustment form ----------------------------------------------
    const adjustSelect = document.getElementById('adjust_product_id');
    const systemQuantity = document.getElementById('system_quantity');
    const physicalCount = document.getElementById('physical_count');
    const differenceHint = document.getElementById('adjustDifference');

    function updateAdjustForm() {
        if (!adjustSelect || !systemQuantity || !physicalCount) {
            return;
        }

        const option = selectedOption(adjustSelect);
        const stock = stockOf(option);
        systemQuantity.value = option && option.value ? numberFormat.format(stock) + ' units' : '—';

        const physical = Number(physicalCount.value || 0);
        const difference = option && option.value ? physical - stock : 0;
        const sign = difference > 0 ? '+' : '';

        if (differenceHint) {
            differenceHint.textContent = option && option.value && physicalCount.value !== ''
                ? `Difference: ${sign}${numberFormat.format(difference)} units`
                : 'Difference will be calculated automatically.';
        }
    }

    if (adjustSelect) {
        adjustSelect.addEventListener('change', updateAdjustForm);
    }
    if (physicalCount) {
        physicalCount.addEventListener('input', updateAdjustForm);
    }
    updateAdjustForm();
})();
