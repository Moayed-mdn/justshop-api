<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant\Theme;

use App\Enums\Theme\SectionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(SectionTypeEnum::values())],
            'handle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'settings' => ['sometimes', 'array'],
            'position' => ['sometimes', 'integer'],
            'is_enabled' => ['sometimes', 'boolean'],
            'is_removable' => ['sometimes', 'boolean'],
        ];
    }
}
