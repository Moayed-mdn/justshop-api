<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Documentation\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PublishDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_published' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
