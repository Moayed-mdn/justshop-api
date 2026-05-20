<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Category;

class RestoreCategoryDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $categoryId,
    ) {}
}
