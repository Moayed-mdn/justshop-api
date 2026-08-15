<?php

namespace App\Http\Requests\Billing;

use App\Enums\Billing\InvoiceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(InvoiceStatusEnum::values())],
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => __('validation.in', ['attribute' => 'status']),
            'year.integer' => __('validation.integer', ['attribute' => 'year']),
            'year.min' => __('validation.min.numeric', ['attribute' => 'year', 'min' => 2000]),
            'year.max' => __('validation.max.numeric', ['attribute' => 'year', 'max' => date('Y') + 1]),
            'per_page.integer' => __('validation.integer', ['attribute' => 'per_page']),
            'per_page.min' => __('validation.min.numeric', ['attribute' => 'per_page', 'min' => 1]),
            'per_page.max' => __('validation.max.numeric', ['attribute' => 'per_page', 'max' => 100]),
        ];
    }
}
