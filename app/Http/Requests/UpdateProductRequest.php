<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $product = $this->route('product');

        $rules['sku'] = [
            'required',
            'string',
            'max:64',
            Rule::unique('products', 'sku')->ignore($product),
        ];

        // New stock must use an active brand/category, but editing a product
        // may keep the inactive brand/category it already belongs to.
        $rules['brand_id'] = ['required', 'integer', Rule::exists('brands', 'id')->where(function ($query) use ($product) {
            $query->where('status', Brand::STATUS_ACTIVE);

            if ($product instanceof Product && $product->brand_id) {
                $query->orWhere('id', $product->brand_id);
            }
        })];

        $rules['category_id'] = ['required', 'integer', Rule::exists('categories', 'id')->where(function ($query) use ($product) {
            $query->where('status', Category::STATUS_ACTIVE);

            if ($product instanceof Product && $product->category_id) {
                $query->orWhere('id', $product->category_id);
            }
        })];

        return $rules;
    }
}
