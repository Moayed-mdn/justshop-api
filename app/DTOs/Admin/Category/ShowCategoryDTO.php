<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Category;

class ShowCategoryDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $categoryId,
    ) {}
}
