<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Lead;

use App\Enums\Lead\LeadStatusEnum;
use App\Enums\Lead\LeadTypeEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::SUPER_ADMIN->value) === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', 'string', Rule::in(LeadTypeEnum::values())],
            'status' => ['sometimes', 'nullable', 'string', Rule::in([...LeadStatusEnum::values(), 'all'])],
            'email' => ['sometimes', 'nullable', 'string', 'max:255'],
            'created_from' => ['sometimes', 'nullable', 'date'],
            'created_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:created_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
