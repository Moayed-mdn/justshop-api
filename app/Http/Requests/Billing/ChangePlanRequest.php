<?php

namespace App\Http\Requests\Billing;

use App\Enums\Subscription\BillingCycleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by controller policy
    }

    public function rules(): array
    {
        return [
            'plan_code' => ['required', 'string', Rule::exists('plans', 'code')],
            'billing_cycle' => ['required', 'string', Rule::in(BillingCycleEnum::values())],
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
        ];
    }
}
