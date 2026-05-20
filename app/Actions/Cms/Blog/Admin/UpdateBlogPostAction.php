<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog\Admin;

use App\DTOs\Cms\Blog\Admin\UpdateBlogPostDTO;
use App\Models\BlogPost;
use App\Repositories\Cms\Blog\BlogPostRepository;
use App\Support\BlogReadingTimeCalculator;
use Illuminate\Support\Facades\DB;

class UpdateBlogPostAction
{
    public function __construct(
        private BlogPostRepository $repository,
        private BlogReadingTimeCalculator $readingTimeCalculator,
    ) {}

    public function execute(UpdateBlogPostDTO $dto, ?BlogPost $post = null): BlogPost
    {
        $post = $post ?? $this->repository->findByIdOrFail($dto->blogPostId);

        return DB::transaction(function () use ($dto, $post): BlogPost {
            return $this->repository->update(
                post: $post,
                attributes: [
                    'author_id' => $dto->authorId,
                    'blog_category_id' => $dto->blogCategoryId,
                    'featured' => $dto->featured,
                    'is_published' => $dto->isPublished,
                    'published_at' => $dto->publishedAt ?? ($dto->isPublished ? $post->published_at ?? now() : null),
                    'cover_image' => $dto->coverImage,
                    'reading_time' => $this->readingTimeCalculator->calculate($dto->translations),
                    'updated_by' => $dto->updatedBy,
                ],
                translations: $dto->translations,
                tagIds: $dto->tagIds,
            );
        });
    }
}
