<?php

declare(strict_types=1);

namespace App\Actions\Admin\Category;

use App\DTOs\Admin\Category\DeleteCategoryDTO;
use App\Exceptions\Category\CategoryHasChildrenException;
use App\Exceptions\Category\CategoryHasProductsException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\User;
use App\Repositories\Category\CategoryRepository;

class DeleteCategoryAction
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function execute(
        DeleteCategoryDTO $dto,
    ): void {
        $category = $this->categoryRepository->findByIdOrFail(
            id:      $dto->categoryId,
            storeId: $dto->storeId,
        );

        if ($this->categoryRepository->hasActiveChildren(
            id:      $category->id,
            storeId: $dto->storeId,
        )) {
            throw new CategoryHasChildrenException();
        }

        if ($this->categoryRepository->hasProducts(
            id:      $category->id,
            storeId: $dto->storeId,
        )) {
            throw new CategoryHasProductsException();
        }

        $this->categoryRepository->delete($category);
    }
}
