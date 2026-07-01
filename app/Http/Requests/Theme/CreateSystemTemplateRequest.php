<?php

declare(strict_types=1);

namespace App\Http\Requests\Theme;

use App\Enums\Theme\TemplateTypeEnum;
use App\Models\Theme\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSystemTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $theme = $this->route('theme');
        $themeId = $theme instanceof Theme
            ? $theme->getKey()
            : 0;

        $systemTypes = array_values(array_filter(
            TemplateTypeEnum::values(),
            fn(string $type) => TemplateTypeEnum::from($type)->isSystemPage()
        ));

        return [
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-_.]+$/',
                Rule::unique('theme_templates')->where(function ($query) use ($themeId) {
                    return $query->where('theme_id', $themeId);
                }),
            ],
            'type' => ['required', 'string', Rule::in($systemTypes)],
            'description' => ['nullable', 'string', 'max:1000'],
            'section_ids' => ['sometimes', 'array'],
            'section_ids.*' => ['integer', 'exists:theme_sections,id'],
            'settings' => ['sometimes', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'handle.regex' => 'The handle may only contain lowercase letters, numbers, hyphens, underscores, and dots.',
            'handle.unique' => 'A template with this handle already exists for this theme.',
            'type.in' => 'The selected type is not a valid system page type.',
        ];
    }
}
