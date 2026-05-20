<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog;

use App\DTOs\Cms\Blog\GetPublicBlogPostDTO;
use App\Models\BlogPost;
use App\Repositories\Cms\Blog\BlogPostRepository;

class GetPublicBlogPostAction
{
    public function __construct(
        private BlogPostRepository $repository,
    ) {}

    public function execute(GetPublicBlogPostDTO $dto): BlogPost
    {
        return $this->repository->findPublishedBySlug($dto->locale, $dto->slug);
    }
}
