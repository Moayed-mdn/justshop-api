<?php

declare(strict_types=1);

namespace App\DTOs\Store;

class GenerateStripeDashboardLinkDTO
{
    public function __construct(
        public int $storeId,
    ) {}
}
