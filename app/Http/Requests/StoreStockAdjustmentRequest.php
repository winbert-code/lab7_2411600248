<?php

namespace App\Http\Requests;

use App\Models\InventoryTransaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'physical_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason' => ['required', 'string', 'in:'.implode(',', array_keys(InventoryTransaction::ADJUSTMENT_REASONS))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'physical_count' => 'physical count',
        ];
    }
}
