<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class AssignMethodToZoneRequest extends FormRequest
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
            'method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'method_id.required' => 'The shipping method is required.',
            'method_id.exists' => 'The selected shipping method does not exist.',
            'price_override.numeric' => 'The price override must be a number.',
            'price_override.min' => 'The price override cannot be negative.',
        ];
    }
}
