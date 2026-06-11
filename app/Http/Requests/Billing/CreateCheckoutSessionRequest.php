<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'plan_price_id' => [
                'required',
                'integer',
                'exists:plan_prices,id',
            ],
            'success_url' => [
                'required',
                'string',
                'url',
                'max:2048',
            ],
            'cancel_url' => [
                'required',
                'string',
                'url',
                'max:2048',
            ],
            'store_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:stores,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_price_id.required' => 'Please select a plan.',
            'plan_price_id.exists' => 'The selected plan is invalid.',
            'success_url.required' => 'Success URL is required.',
            'cancel_url.required' => 'Cancel URL is required.',
        ];
    }
}
