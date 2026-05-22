<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

class MerchantRegistered extends DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
