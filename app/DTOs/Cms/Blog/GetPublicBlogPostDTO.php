<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Blog;

use App\Http\Requests\Cms\Blog\GetPublicBlogPostRequest;

class GetPublicBlogPostDTO
{
    public function __construct(
        public readonly string $slug,
        public readonly string $locale,
    ) {}

    public static function fromRequest(GetPublicBlogPostRequest $request, string $slug): self
    {
        return new self(
            slug: $slug,
            locale: $request->string('locale')->toString() ?: config('content.default_locale', 'en'),
        );
    }
}
