<?php

namespace App\DTOs\Subscription;

class SyncSubscriptionDTO
{
    public function __construct(
        public int $subscriptionId,
    ) {}
}
