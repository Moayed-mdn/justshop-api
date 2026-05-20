<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Documentation\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateDocumentSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.*' => ['required', 'string'],
            'slug' => ['required', 'array'],
            'slug.*' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:cms_document_sections,id'],
            'version' => ['nullable', 'string'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
