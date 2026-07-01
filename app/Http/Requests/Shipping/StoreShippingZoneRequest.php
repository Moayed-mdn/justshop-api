<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingZoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'countries' => ['required', 'array', 'min:1'],
            'countries.*' => ['string', 'size:2', 'uppercase'],
            'regions' => ['nullable', 'array'],
            'postal_code_patterns' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The shipping zone name is required.',
            'countries.required' => 'At least one country is required.',
            'countries.min' => 'At least one country must be selected.',
            'countries.*.size' => 'Each country code must be exactly 2 characters.',
            'countries.*.uppercase' => 'Country codes must be in uppercase (e.g., US, GB).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert country codes to uppercase
        if ($this->has('countries') && is_array($this->countries)) {
            $this->merge([
                'countries' => array_map('strtoupper', $this->countries),
            ]);
        }
    }
}
