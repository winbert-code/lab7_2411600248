<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSalesOrderRequest extends StoreSalesOrderRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['order_number'] = [
            'nullable',
            'string',
            'max:32',
            Rule::unique('sales_orders', 'order_number')->ignore($this->route('salesOrder')),
        ];

        return $rules;
    }
}
