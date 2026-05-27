<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = (int) $this->route('category');

        return [
            'slug'                       => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'parent_id'                  => ['sometimes', 'nullable', 'integer', 'exists:categories,id', Rule::notIn([$categoryId])],
            'sort_order'                 => ['sometimes', 'integer', 'min:0'],
            'is_active'                  => ['sometimes', 'boolean'],
            'translations'               => ['required', 'array', 'min:1'],
            'translations.*.locale'      => ['required', 'string', 'in:en,ar'],
            'translations.*.name'        => ['required', 'string', 'max:255'],
            'translations.*.slug'        => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'distinct',
                Rule::unique('category_translations', 'slug')
                    ->where(fn($q) => $q->whereNot('category_id', $categoryId)),
            ],
        ];
    }
}
