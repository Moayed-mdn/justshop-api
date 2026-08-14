<?php

namespace App\Http\Requests\Platform\Billing;

use Illuminate\Foundation\Http\FormRequest;

class MigrateSubscribersRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by platform.authority:platform_admin middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'from_plan_id' => ['required', 'integer', 'exists:plans,id'],
            'to_plan_id' => ['required', 'integer', 'exists:plans,id', 'different:from_plan_id'],
            'billing_account_ids' => ['required', 'array', 'min:1'],
            'billing_account_ids.*' => ['integer', 'exists:billing_accounts,id'],
            'grandfather_existing' => ['sometimes', 'boolean'],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_plan_id.required' => 'Source plan ID is required',
            'to_plan_id.required' => 'Target plan ID is required',
            'to_plan_id.different' => 'Target plan must be different from source plan',
            'billing_account_ids.required' => 'At least one billing account is required',
            'billing_account_ids.min' => 'At least one billing account is required',
        ];
    }
}
