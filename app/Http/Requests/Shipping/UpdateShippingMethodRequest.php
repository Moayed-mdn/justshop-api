<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingMethodRequest extends FormRequest
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
            'code' => ['sometimes', 'string', 'max:100', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'min_delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'max_delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
