<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog\Admin;

use App\DTOs\Cms\Blog\Admin\PublishBlogPostDTO;
use App\Models\BlogPost;
use App\Repositories\Cms\Blog\BlogPostRepository;
use Illuminate\Support\Facades\DB;

class PublishBlogPostAction
{
    public function __construct(
        private BlogPostRepository $repository,
    ) {}

    public function execute(PublishBlogPostDTO $dto, ?BlogPost $post = null): BlogPost
    {
        $post = $post ?? $this->repository->findByIdOrFail($dto->blogPostId);

        return DB::transaction(function () use ($dto, $post): BlogPost {
            return $this->repository->publish($post, now());
        });
    }
}
