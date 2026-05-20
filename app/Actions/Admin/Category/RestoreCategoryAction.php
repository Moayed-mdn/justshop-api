<?php

declare(strict_types=1);

namespace App\Actions\Admin\Category;

use App\DTOs\Admin\Category\RestoreCategoryDTO;
use App\Exceptions\Category\CategoryNotFoundException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Category;
use App\Models\User;
use App\Repositories\Category\CategoryRepository;

class RestoreCategoryAction
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function execute(
        RestoreCategoryDTO $dto,
        User $user,
    ): Category {
        if (!$user->hasRole('super_admin')
            && !$user->stores()->where('store_id', $dto->storeId)->exists()
        ) {
            throw new UnauthorizedStoreAccessException();
        }

        $category = $this->categoryRepository->findTrashedById(
            id:      $dto->categoryId,
            storeId: $dto->storeId,
        );

        if ($category === null || !$category->trashed()) {
            throw new CategoryNotFoundException();
        }

        $this->categoryRepository->restore($category);

        return $category->fresh(['translations', 'parent.translations']);
    }
}
