<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Orders;

class GetPlatformOrderDTO
{
    public function __construct(
        public int $orderId,
    ) {}
}
