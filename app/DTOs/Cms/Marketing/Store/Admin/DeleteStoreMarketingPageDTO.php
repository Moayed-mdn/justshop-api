<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Marketing\Store\Admin;

class DeleteStoreMarketingPageDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $storeId,
    ) {}
}
