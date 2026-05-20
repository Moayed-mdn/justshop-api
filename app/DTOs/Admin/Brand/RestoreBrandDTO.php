<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Brand;

class RestoreBrandDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $brandId,
    ) {}
}
