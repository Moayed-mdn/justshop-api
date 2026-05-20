<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Blog\Admin;

use App\Http\Requests\Cms\Blog\ListBlogPostsRequest;

class ListBlogPostsDTO
{
    public function __construct(
        public readonly ?string $locale,
        public readonly ?string $publishState,
        public readonly ?int $authorId,
        public readonly ?int $blogCategoryId,
        public readonly ?bool $featured,
        public readonly ?string $search,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(ListBlogPostsRequest $request): self
    {
        return new self(
            locale: $request->string('locale')->toString() ?: null,
            publishState: $request->string('status')->toString() ?: null,
            authorId: $request->filled('author_id') ? $request->integer('author_id') : null,
            blogCategoryId: $request->filled('blog_category_id') ? $request->integer('blog_category_id') : null,
            featured: $request->has('featured') ? $request->boolean('featured') : null,
            search: $request->string('search')->toString() ?: null,
            perPage: $request->integer('per_page', 15),
        );
    }
}
