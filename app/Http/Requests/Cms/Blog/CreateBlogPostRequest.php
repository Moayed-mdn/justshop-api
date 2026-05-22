<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'author_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'blog_category_id' => ['sometimes', 'nullable', 'integer', 'exists:blog_categories,id'],
            'featured' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tag_ids' => ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:blog_tags,id'],
            'translations' => ['required', 'array'],
        ];

        foreach (config('content.editable_locales', ['en', 'ar']) as $locale) {
            $rules["translations.$locale.title"] = ['required', 'string', 'max:255'];
            $rules["translations.$locale.slug"] = [
                'required',
                'string',
                'max:255',
                Rule::unique('blog_post_translations', 'slug')->where(
                    fn ($query) => $query->where('locale', $locale)
                ),
            ];
            $rules["translations.$locale.excerpt"] = ['sometimes', 'nullable', 'string'];
            $rules["translations.$locale.content"] = ['required', 'string'];
            $rules["translations.$locale.meta_title"] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules["translations.$locale.meta_description"] = ['sometimes', 'nullable', 'string'];
            $rules["translations.$locale.canonical_url"] = ['sometimes', 'nullable', 'url', 'max:2048'];
            $rules["translations.$locale.og_image"] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules["translations.$locale.robots"] = ['sometimes', 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $status = $this->input('status');
            $publishedAt = $this->input('published_at');

            if ($status === 'scheduled') {
                if (!$publishedAt || strtotime((string) $publishedAt) <= now()->getTimestamp()) {
                    $validator->errors()->add('published_at', __('blog.schedule_requires_future_date'));
                }
            }
        });
    }
}
