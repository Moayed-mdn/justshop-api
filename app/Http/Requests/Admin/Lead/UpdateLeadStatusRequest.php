<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Lead;

use App\Enums\Lead\LeadStatusEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::SUPER_ADMIN->value) === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(LeadStatusEnum::class)],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
