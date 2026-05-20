<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Blog;

use App\Http\Requests\Cms\Blog\ListPublicBlogPostsRequest;

class ListPublicBlogPostsDTO
{
    public function __construct(
        public readonly string $locale,
        public readonly ?string $category,
        public readonly ?string $tag,
        public readonly ?bool $featured,
        public readonly bool $latest,
        public readonly ?string $search,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(ListPublicBlogPostsRequest $request): self
    {
        return new self(
            locale: $request->string('locale')->toString() ?: config('content.default_locale', 'en'),
            category: $request->string('category')->toString() ?: null,
            tag: $request->string('tag')->toString() ?: null,
            featured: $request->has('featured') ? $request->boolean('featured') : null,
            latest: $request->boolean('latest', true),
            search: $request->string('search')->toString() ?: null,
            perPage: $request->integer('per_page', 10),
        );
    }
}
