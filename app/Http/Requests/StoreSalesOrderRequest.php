<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'order_number' => ['nullable', 'string', 'max:32', 'unique:sales_orders,order_number'],
            'customer' => ['required', 'string', 'max:128'],
            'order_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $productIds = collect($this->input('items', []))
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->all();

            if ($productIds === []) {
                return;
            }

            $outOfStock = Product::query()
                ->whereIn('id', $productIds)
                ->where('quantity', '<=', 0)
                ->pluck('name');

            if ($outOfStock->isNotEmpty()) {
                $validator->errors()->add(
                    'items',
                    'Out-of-stock products cannot be added to a sales order: '.$outOfStock->join(', ').'.',
                );
            }
        });
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'order_number' => 'order number',
            'order_date' => 'order date',
            'due_date' => 'due date',
            'items.*.product_id' => 'product',
            'items.*.quantity' => 'quantity',
            'items.*.unit_price' => 'unit price',
        ];
    }
}
