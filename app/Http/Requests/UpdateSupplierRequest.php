<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('supplier'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('supplier')?->id;

        return [
            'code' => ['nullable', 'string', 'max:20', Rule::unique('suppliers', 'code')->ignore($id)],
            'company_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($id)],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+\-\s()]{7,40}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['required', Rule::in(['COD', 'Net 15', 'Net 30', 'Net 45', 'Net 60'])],
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:180'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'contact_person' => 'contact person',
            'lead_time_days' => 'lead time (days)',
            'minimum_order_amount' => 'minimum order amount',
        ];
    }
}
