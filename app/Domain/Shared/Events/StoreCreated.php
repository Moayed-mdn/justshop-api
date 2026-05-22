<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

class StoreCreated extends DomainEvent
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $ownerId,
        public readonly string $slug,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
