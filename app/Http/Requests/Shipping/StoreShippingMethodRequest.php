<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingMethodRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:100', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0', 'gt:min_order_amount'],
            'estimated_delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'min_delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'max_delivery_days' => ['nullable', 'integer', 'min:1', 'max:365', 'gte:min_delivery_days'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The shipping method name is required.',
            'price.required' => 'The shipping price is required.',
            'price.numeric' => 'The shipping price must be a number.',
            'price.min' => 'The shipping price cannot be negative.',
            'max_order_amount.gt' => 'The maximum order amount must be greater than the minimum order amount.',
            'max_delivery_days.gte' => 'The maximum delivery days must be greater than or equal to minimum delivery days.',
        ];
    }
}
