<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Blog;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBlogPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', BlogPost::class);
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
            'status' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['draft', 'published', 'scheduled', 'all']),
            ],
            'author_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'blog_category_id' => ['sometimes', 'nullable', 'integer', 'exists:blog_categories,id'],
            'featured' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
