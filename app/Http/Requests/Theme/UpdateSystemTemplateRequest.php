<?php

declare(strict_types=1);

namespace App\Http\Requests\Theme;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'section_ids' => ['sometimes', 'array'],
            'section_ids.*' => ['integer', 'exists:theme_sections,id'],
            'section_overrides' => ['sometimes', 'array'],
            'section_overrides.*' => ['array'],
            'section_visibility' => ['sometimes', 'array'],
            'section_visibility.*' => ['boolean'],
            'settings' => ['sometimes', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
