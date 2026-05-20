<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog\Admin;

use App\DTOs\Cms\Blog\Admin\ListBlogPostsDTO;
use App\Repositories\Cms\Blog\BlogPostRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListBlogPostsAction
{
    public function __construct(
        private BlogPostRepository $repository,
    ) {}

    public function execute(ListBlogPostsDTO $dto): LengthAwarePaginator
    {
        return $this->repository->paginateAdmin($dto);
    }
}
