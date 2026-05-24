<?php

declare(strict_types=1);

namespace App\DTOs\Store;

class SlugAvailabilityDTO
{
    public function __construct(
        public bool $available,
        public ?string $reason,
    ) {}
}
