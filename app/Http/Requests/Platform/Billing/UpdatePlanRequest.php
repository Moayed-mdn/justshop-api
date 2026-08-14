<?php

namespace App\Http\Requests\Platform\Billing;

use App\Enums\Entitlement\FeatureKeyEnum;
use App\Enums\Subscription\BillingCycleEnum;
use App\Enums\Subscription\PlanTierEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by platform.authority:platform_admin middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'array'],
            'name.en' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.en' => ['sometimes', 'nullable', 'string'],
            'tier' => ['sometimes', 'string', 'in:' . implode(',', PlanTierEnum::values())],
            'tier_rank' => ['sometimes', 'integer', 'min:1'],
            'is_public' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'trial_days' => ['sometimes', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            
            'features' => ['sometimes', 'array', 'min:1'],
            'features.*.feature_key' => ['required_with:features', 'string', 'in:' . implode(',', FeatureKeyEnum::values())],
            'features.*.value_type' => ['required_with:features', 'string', 'in:limit,boolean,unlimited'],
            'features.*.limit_value' => ['nullable', 'integer', 'min:0'],
            'features.*.boolean_value' => ['nullable', 'boolean'],
            
            'prices' => ['sometimes', 'array', 'min:1'],
            'prices.*.billing_cycle' => ['required_with:prices', 'string', 'in:' . implode(',', BillingCycleEnum::values())],
            'prices.*.currency' => ['required_with:prices', 'string', 'size:3'],
            'prices.*.amount_cents' => ['required_with:prices', 'integer', 'min:0'],
            'prices.*.provider' => ['nullable', 'string', 'in:stripe'],
        ];
    }

    public function messages(): array
    {
        return [
            'tier.in' => 'Tier must be one of: ' . implode(', ', PlanTierEnum::values()),
            'tier_rank.min' => 'Tier rank must be at least 1',
            'features.*.feature_key.in' => 'Invalid feature key',
            'features.min' => 'At least one feature is required',
            'prices.min' => 'At least one price is required',
        ];
    }
}
