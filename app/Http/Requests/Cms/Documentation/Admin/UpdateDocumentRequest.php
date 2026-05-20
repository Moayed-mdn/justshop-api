<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Documentation\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
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
            'content' => ['required', 'array'],
            'content.*' => ['required', 'string'],
            'section_id' => ['nullable', 'exists:cms_document_sections,id'],
            'parent_id' => ['nullable', 'exists:cms_documents,id'],
            'version' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'array'],
            'excerpt.*' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'array'],
            'meta_description' => ['nullable', 'array'],
            'canonical_url' => ['nullable', 'array'],
            'og_image' => ['nullable', 'array'],
            'robots' => ['nullable', 'array'],
            'index_controls' => ['nullable', 'array'],
        ];
    }
}
