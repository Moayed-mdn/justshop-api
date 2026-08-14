<?php

namespace App\Http\Requests\Platform\Billing;

use App\Enums\Entitlement\FeatureKeyEnum;
use App\Enums\Subscription\BillingCycleEnum;
use App\Enums\Subscription\PlanTierEnum;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by platform.authority:platform_admin middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'tier' => ['required', 'string', 'in:' . implode(',', PlanTierEnum::values())],
            'tier_rank' => ['required', 'integer', 'min:1'],
            'is_public' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            
            'features' => ['required', 'array', 'min:1'],
            'features.*.feature_key' => ['required', 'string', 'in:' . implode(',', FeatureKeyEnum::values())],
            'features.*.value_type' => ['required', 'string', 'in:limit,boolean,unlimited'],
            'features.*.limit_value' => ['nullable', 'integer', 'min:0'],
            'features.*.boolean_value' => ['nullable', 'boolean'],
            
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.billing_cycle' => ['required', 'string', 'in:' . implode(',', BillingCycleEnum::values())],
            'prices.*.currency' => ['required', 'string', 'size:3'],
            'prices.*.amount_cents' => ['required', 'integer', 'min:0'],
            'prices.*.provider' => ['nullable', 'string', 'in:stripe'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Plan code is required',
            'tier.in' => 'Tier must be one of: ' . implode(', ', PlanTierEnum::values()),
            'tier_rank.min' => 'Tier rank must be at least 1',
            'features.*.feature_key.in' => 'Invalid feature key',
            'features.min' => 'At least one feature is required',
            'prices.min' => 'At least one price is required',
        ];
    }
}
