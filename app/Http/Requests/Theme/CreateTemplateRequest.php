<?php

declare(strict_types=1);

namespace App\Http\Requests\Theme;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTemplateRequest extends FormRequest
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
        $storeId = $this->route('store');

        return [
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-_.]+$/',
                Rule::unique('page_templates')->where(function ($query) use ($storeId) {
                    return $query->where('store_id', $storeId);
                }),
            ],
            'type' => ['required', 'string', 'in:page,product,collection,article,blog,cart'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sections' => ['required', 'array'],
            'sections.*.type' => ['required', 'string'],
            'sections.*.settings' => ['sometimes', 'array'],
            'section_order' => ['required', 'array'],
            'section_order.*' => ['required', 'string'],
            'section_settings' => ['nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'handle.regex' => 'The handle must only contain lowercase letters, numbers, hyphens, underscores, and dots.',
            'handle.unique' => 'A template with this handle already exists for this store.',
        ];
    }
}

