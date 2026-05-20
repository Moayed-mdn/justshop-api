<?php

declare(strict_types=1);

namespace App\DTOs\Cms\MarketingPage\Admin;

class GetMarketingPageDTO
{
    public function __construct(
        public readonly int $id,
    ) {}
}
