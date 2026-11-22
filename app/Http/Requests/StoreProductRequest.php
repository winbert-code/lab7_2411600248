<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : null,
            'sku' => is_string($this->input('sku')) ? Str::upper(trim($this->input('sku'))) : null,
            'supplier' => is_string($this->input('supplier')) && trim($this->input('supplier')) !== ''
                ? trim($this->input('supplier'))
                : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')],
            'description' => ['nullable', 'string', 'max:1000'],
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')->where('status', Brand::STATUS_ACTIVE)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('status', Category::STATUS_ACTIVE)],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'supplier' => ['nullable', 'string', 'max:128'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A product name is required.',
            'sku.required' => 'A SKU is required.',
            'sku.unique' => 'This SKU is already used by another product.',
            'brand_id.required' => 'Choose a brand.',
            'brand_id.exists' => 'Choose an active brand.',
            'category_id.required' => 'Choose a category.',
            'category_id.exists' => 'Choose an active category.',
            'quantity.min' => 'Quantity cannot be negative.',
            'reorder_level.min' => 'Reorder level cannot be negative.',
            'unit_price.min' => 'Unit price cannot be negative.',
            'unit_price.numeric' => 'Unit price must be a number.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'unit_price' => 'unit price',
            'reorder_level' => 'reorder level',
        ];
    }
}
