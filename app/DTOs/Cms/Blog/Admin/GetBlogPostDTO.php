<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Blog\Admin;

use App\Http\Requests\Cms\Blog\ShowBlogPostRequest;

class GetBlogPostDTO
{
    public function __construct(
        public readonly int $blogPostId,
    ) {}

    public static function fromRequest(ShowBlogPostRequest $request, int $blogPostId): self
    {
        return new self(blogPostId: $blogPostId);
    }
}
