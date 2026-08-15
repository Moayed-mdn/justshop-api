<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Analytics;

class GetPlatformAnalyticsDTO
{
    public function __construct(
        public readonly int $days = 30,
        public readonly ?string $metric = null,
    ) {}
}
