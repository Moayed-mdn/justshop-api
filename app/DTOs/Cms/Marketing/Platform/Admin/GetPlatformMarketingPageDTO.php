<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Marketing\Platform\Admin;

class GetPlatformMarketingPageDTO
{
    public function __construct(
        public readonly int $id,
    ) {}
}
