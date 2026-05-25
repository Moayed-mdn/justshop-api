<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use Illuminate\Support\Facades\Log;

class StoreCreated extends DomainEvent
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $ownerId,
        public readonly string $slug,
        public readonly string $name,
    ) {
        parent::__construct();

        Log::info('Event: StoreCreated dispatched', [
            'store_id' => $this->storeId,
            'owner_id' => $this->ownerId,
            'slug'     => $this->slug,
        ]);
    }
}
