<?php

namespace App\Http\Requests\Platform\Billing;

use App\Enums\Subscription\BillingCycleEnum;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by platform.authority:platform_admin middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'billing_cycle' => ['required', 'string', 'in:' . implode(',', BillingCycleEnum::values())],
            'currency' => ['required', 'string', 'size:3'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'provider' => ['sometimes', 'string', 'in:stripe'],
        ];
    }

    public function messages(): array
    {
        return [
            'billing_cycle.required' => 'Billing cycle is required',
            'billing_cycle.in' => 'Invalid billing cycle',
            'currency.required' => 'Currency is required',
            'currency.size' => 'Currency must be a 3-letter code',
            'amount_cents.required' => 'Amount is required',
            'amount_cents.min' => 'Amount must be 0 or greater',
        ];
    }
}
