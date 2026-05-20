<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Blog\Admin;

use App\Http\Requests\Cms\Blog\ScheduleBlogPostRequest;
use Carbon\CarbonImmutable;

class ScheduleBlogPostDTO
{
    public function __construct(
        public readonly int $blogPostId,
        public readonly CarbonImmutable $publishedAt,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(ScheduleBlogPostRequest $request, int $blogPostId): self
    {
        return new self(
            blogPostId: $blogPostId,
            publishedAt: CarbonImmutable::parse($request->string('published_at')->toString()),
            updatedBy: (int) $request->user()?->id,
        );
    }
}
