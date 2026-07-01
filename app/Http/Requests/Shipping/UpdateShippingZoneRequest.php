<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingZoneRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'countries' => ['sometimes', 'array', 'min:1'],
            'countries.*' => ['string', 'size:2', 'uppercase'],
            'regions' => ['nullable', 'array'],
            'postal_code_patterns' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
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
