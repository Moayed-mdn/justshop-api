<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform\Audit;

use Illuminate\Foundation\Http\FormRequest;

class GetAuditLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'event' => ['nullable', 'string', 'max:255'],
            'actor_id' => ['nullable', 'integer'],
            'store_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => __('error.end_date_must_be_after_start_date'),
            'per_page.max' => __('error.per_page_max_100'),
        ];
    }
}
