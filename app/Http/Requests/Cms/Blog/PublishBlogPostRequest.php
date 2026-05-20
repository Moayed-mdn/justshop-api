<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Blog;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;

class PublishBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('blogPost');
        return (bool) $this->user()?->can('publish', $post);
    }

    public function rules(): array
    {
        return [];
    }
}
