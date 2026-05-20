<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetPublicBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(config('content.editable_locales', ['en', 'ar'])),
            ],
        ];
    }
}
