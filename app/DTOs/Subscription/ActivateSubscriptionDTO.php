<?php

namespace App\DTOs\Subscription;

use Carbon\Carbon;

class ActivateSubscriptionDTO
{
    public function __construct(
        public int $subscriptionId,
        public string $providerSubscriptionId,
        public ?string $providerStatus = null,
        public ?Carbon $currentPeriodStartsAt = null,
        public ?Carbon $currentPeriodEndsAt = null,
        public ?string $source = 'webhook',
        public ?string $reason = 'payment_successful',
        public ?int $actorUserId = null,
    ) {}

    public static function fromStripeSubscription(int $subscriptionId, array $stripeSubscription): self
    {
        return new self(
            subscriptionId: $subscriptionId,
            providerSubscriptionId: $stripeSubscription['id'],
            providerStatus: $stripeSubscription['status'],
            currentPeriodStartsAt: Carbon::createFromTimestamp($stripeSubscription['current_period_start']),
            currentPeriodEndsAt: Carbon::createFromTimestamp($stripeSubscription['current_period_end']),
        );
    }
}
