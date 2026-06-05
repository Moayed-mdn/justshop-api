<?php

declare(strict_types=1);

namespace App\DTOs\Admin\HeroBanner;

class DeleteHeroBannerDTO
{
    public function __construct(
        public int $storeId,
        public int $bannerId,
    ) {}
}
