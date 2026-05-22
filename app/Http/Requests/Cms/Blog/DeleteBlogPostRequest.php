<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Blog;

use Illuminate\Foundation\Http\FormRequest;

class DeleteBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
