<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Blog\Admin;

use App\Http\Requests\Cms\Blog\PublishBlogPostRequest;

class PublishBlogPostDTO
{
    public function __construct(
        public readonly int $blogPostId,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(PublishBlogPostRequest $request, int $blogPostId): self
    {
        return new self(
            blogPostId: $blogPostId,
            updatedBy: (int) $request->user()?->id,
        );
    }
}
