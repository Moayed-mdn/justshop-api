<?php

declare(strict_types=1);

namespace App\Actions\Admin\Category;

use App\DTOs\Admin\Category\ShowCategoryDTO;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Category;
use App\Models\User;
use App\Repositories\Category\CategoryRepository;

class ShowCategoryAction
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function execute(
        ShowCategoryDTO $dto,
        User $user,
    ): Category {
        if (!$user->hasRole('super_admin')
            && !$user->stores()->where('store_id', $dto->storeId)->exists()
        ) {
            throw new UnauthorizedStoreAccessException();
        }

        return $this->categoryRepository->findByIdOrFail(
            id:      $dto->categoryId,
            storeId: $dto->storeId,
        );
    }
}
