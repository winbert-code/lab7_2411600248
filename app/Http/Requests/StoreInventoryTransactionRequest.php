<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInventoryTransactionRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reference' => ['nullable', 'string', 'max:128'],
            'reason' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('type') !== 'out') {
                return;
            }

            $product = Product::query()->find($this->integer('product_id'));

            if ($product === null) {
                return;
            }

            if ((int) $this->input('quantity') > $product->quantity) {
                $validator->errors()->add(
                    'quantity',
                    "Insufficient stock. Only {$product->quantity} unit(s) of {$product->name} are available."
                );
            }
        });
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'reference' => 'reference number',
        ];
    }
}
