<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Dashboard;

class GetPlatformDashboardStatsDTO
{
    public function __construct(
        public readonly int $trendDays = 30,
    ) {}
}
