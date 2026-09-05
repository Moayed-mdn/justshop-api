<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Orders;

class CancelPlatformOrderDTO
{
    public function __construct(
        public int $orderId,
    ) {}
}
