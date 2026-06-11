<?php

namespace App\DTOs\Subscription;

final readonly class CancelSubscriptionDTO
{
    public function __construct(
        public int $billingAccountId,
        public bool $cancelImmediately = false,
        public ?string $reason = null,
        public ?int $actorUserId = null,
    ) {}
}
