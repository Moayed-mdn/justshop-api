<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog\Admin;

use App\Models\BlogPost;
use App\Repositories\Cms\Blog\BlogPostRepository;
use Illuminate\Support\Facades\DB;

class DeleteBlogPostAction
{
    public function __construct(
        private BlogPostRepository $repository,
    ) {}

    public function execute(int $blogPostId, ?BlogPost $post = null): void
    {
        $post = $post ?? $this->repository->findByIdOrFail($blogPostId);

        DB::transaction(function () use ($post): void {
            $this->repository->delete($post);
        });
    }
}
