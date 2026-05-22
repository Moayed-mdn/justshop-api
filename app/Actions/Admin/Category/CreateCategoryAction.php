<?php

declare(strict_types=1);

namespace App\Actions\Admin\Category;

use App\DTOs\Admin\Category\CreateCategoryDTO;
use App\Exceptions\Category\CategoryNotFoundException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Category;
use App\Models\User;
use App\Repositories\Category\CategoryRepository;
use Illuminate\Support\Facades\DB;

class CreateCategoryAction
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function execute(
        CreateCategoryDTO $dto,
    ): Category {
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
            fn() => $this->categoryRepository->create(
                storeId:      $dto->storeId,
                slug:         $dto->slug,
                parentId:     $dto->parentId,
                sortOrder:    $dto->sortOrder,
                isActive:     $dto->isActive,
                translations: $dto->translations,
            )
        );
    }
}
