<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Lead;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class GetLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::SUPER_ADMIN->value) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
