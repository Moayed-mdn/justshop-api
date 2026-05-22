<?php

declare(strict_types=1);

namespace App\Actions\Admin\Category;

use App\DTOs\Admin\Category\ListCategoriesDTO;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\User;
use App\Repositories\Category\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCategoriesAction
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function execute(
        ListCategoriesDTO $dto,
    ): LengthAwarePaginator {
        return $this->categoryRepository->paginate(
            storeId:  $dto->storeId,
            parentId: $dto->parentId,
            isActive: $dto->isActive,
            perPage:  $dto->perPage,
        );
    }
}
