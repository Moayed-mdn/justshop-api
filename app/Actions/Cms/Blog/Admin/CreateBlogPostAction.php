<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog\Admin;

use App\DTOs\Cms\Blog\Admin\CreateBlogPostDTO;
use App\Models\BlogPost;
use App\Repositories\Cms\Blog\BlogPostRepository;
use App\Support\BlogReadingTimeCalculator;
use Illuminate\Support\Facades\DB;

class CreateBlogPostAction
{
    public function __construct(
        private BlogPostRepository $repository,
        private BlogReadingTimeCalculator $readingTimeCalculator,
    ) {}

    public function execute(CreateBlogPostDTO $dto): BlogPost
    {
        return DB::transaction(function () use ($dto): BlogPost {
            return $this->repository->create(
                attributes: [
                    'author_id' => $dto->authorId,
                    'blog_category_id' => $dto->blogCategoryId,
                    'featured' => $dto->featured,
                    'is_published' => $dto->isPublished,
                    'published_at' => $dto->publishedAt ?? ($dto->isPublished ? now() : null),
                    'cover_image' => $dto->coverImage,
                    'reading_time' => $this->readingTimeCalculator->calculate($dto->translations),
                    'created_by' => $dto->createdBy,
                ],
                translations: $dto->translations,
                tagIds: $dto->tagIds,
            );
        });
    }
}
