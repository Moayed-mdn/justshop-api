<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreAddressSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by policy in controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'allowed_countries' => ['sometimes', 'array'],
            'allowed_countries.*' => ['string', 'size:2', 'uppercase'],
            'required_fields' => ['sometimes', 'array'],
            'required_fields.*' => ['string', 'in:first_name,last_name,company,address_line_1,address_line_2,city,state,postal_code,country,phone'],
            'validation_rules' => ['nullable', 'array'],
            'require_phone' => ['sometimes', 'boolean'],
            'require_company' => ['sometimes', 'boolean'],
            'allow_po_boxes' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'allowed_countries.array' => 'Allowed countries must be an array.',
            'allowed_countries.*.size' => 'Each country code must be exactly 2 characters.',
            'allowed_countries.*.uppercase' => 'Country codes must be in uppercase (e.g., US, GB).',
            'required_fields.*.in' => 'Invalid field name in required fields list.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert country codes to uppercase
        if ($this->has('allowed_countries') && is_array($this->allowed_countries)) {
            $this->merge([
                'allowed_countries' => array_map('strtoupper', $this->allowed_countries),
            ]);
        }
    }
}
