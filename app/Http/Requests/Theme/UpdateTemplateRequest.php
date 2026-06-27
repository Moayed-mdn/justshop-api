<?php

declare(strict_types=1);

namespace App\Http\Requests\Theme;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sections' => ['sometimes', 'array'],
            'sections.*.type' => ['required_with:sections', 'string'],
            'sections.*.settings' => ['sometimes', 'array'],
            'section_order' => ['sometimes', 'array'],
            'section_order.*' => ['required_with:section_order', 'string'],
            'section_settings' => ['nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

