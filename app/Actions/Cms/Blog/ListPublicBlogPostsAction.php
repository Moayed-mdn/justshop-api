<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog;

use App\DTOs\Cms\Blog\ListPublicBlogPostsDTO;
use App\Repositories\Cms\Blog\BlogPostRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPublicBlogPostsAction
{
    public function __construct(
        private BlogPostRepository $repository,
    ) {}

    public function execute(ListPublicBlogPostsDTO $dto): LengthAwarePaginator
    {
        return $this->repository->paginatePublic($dto);
    }
}
