<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog\Admin;

use App\DTOs\Cms\Blog\Admin\GetBlogPostDTO;
use App\Models\BlogPost;
use App\Repositories\Cms\Blog\BlogPostRepository;

class GetBlogPostAction
{
    public function __construct(
        private BlogPostRepository $repository,
    ) {}

    public function execute(GetBlogPostDTO $dto, ?BlogPost $post = null): BlogPost
    {
        if ($post) {
            return $this->repository->refresh($post);
        }

        return $this->repository->findByIdOrFail($dto->blogPostId);
    }
}
