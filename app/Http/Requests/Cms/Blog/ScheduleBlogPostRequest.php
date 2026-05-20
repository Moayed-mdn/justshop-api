<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Blog;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('blogPost');
        return (bool) $this->user()?->can('schedule', $post);
    }

    public function rules(): array
    {
        return [
            'published_at' => ['required', 'date', 'after:now'],
        ];
    }
}
