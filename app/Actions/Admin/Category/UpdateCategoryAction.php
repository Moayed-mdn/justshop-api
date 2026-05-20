<?php

declare(strict_types=1);

namespace App\Actions\Admin\Category;

use App\DTOs\Admin\Category\UpdateCategoryDTO;
use App\Exceptions\Category\CategoryNotFoundException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Category;
use App\Models\User;
use App\Repositories\Category\CategoryRepository;
use Illuminate\Support\Facades\DB;

class UpdateCategoryAction
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function execute(
        UpdateCategoryDTO $dto,
        User $user,
    ): Category {
        if (!$user->hasRole('super_admin')
            && !$user->stores()->where('store_id', $dto->storeId)->exists()
        ) {
            throw new UnauthorizedStoreAccessException();
        }

        $category = $this->categoryRepository->findByIdOrFail(
            id:      $dto->categoryId,
            storeId: $dto->storeId,
        );

        if ($dto->parentId !== null) {
            $parent = $this->categoryRepository->findById(
                id:      $dto->parentId,
                storeId: $dto->storeId,
            );

            if ($parent === null) {
                throw new CategoryNotFoundException();
            }
        }

        return DB::transaction(
            fn() => $this->categoryRepository->update(
                category:     $category,
                slug:         $dto->slug,
                parentId:     $dto->parentId,
                sortOrder:    $dto->sortOrder,
                isActive:     $dto->isActive,
                translations: $dto->translations,
            )
        );
    }
}
