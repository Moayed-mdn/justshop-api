<?php

namespace App\Enums\Subscription;

enum SubscriptionStatusEnum: string
{
    case INCOMPLETE   = 'incomplete';    // initial payment pending
    case TRIALING     = 'trialing';      // free trial active
    case ACTIVE       = 'active';        // paid and current
    case PAST_DUE     = 'past_due';      // payment failed, retrying
    case GRACE_PERIOD = 'grace_period';  // retries exhausted, access window before suspension
    case PAUSED       = 'paused';        // collection paused
    case CANCELED     = 'canceled';      // user-canceled, may retain access until period end
    case EXPIRED      = 'expired';       // trial ended without conversion OR terminal state

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * States where the merchant has full write access.
     */
    public function grantsFullAccess(): bool
    {
        return in_array($this, [
            self::TRIALING,
            self::ACTIVE,
            self::CANCELED, // access until period end
        ], true);
    }

    /**
     * States where merchant has read-only access (no writes, storefront still live).
     */
    public function grantsReadOnlyAccess(): bool
    {
        return in_array($this, [
            self::PAST_DUE,
            self::GRACE_PERIOD,
        ], true);
    }

    /**
     * States that grant any form of access (full or read-only).
     */
    public function grantsAccess(): bool
    {
        return $this->grantsFullAccess() || $this->grantsReadOnlyAccess();
    }

    /**
     * States that fully block all paid feature access.
     */
    public function isBlocked(): bool
    {
        return in_array($this, [
            self::PAUSED,
            self::EXPIRED,
        ], true);
    }

    /**
     * Terminal state — new subscription required.
     */
    public function isTerminal(): bool
    {
        return $this === self::EXPIRED;
    }

    /**
     * Valid transitions enforced by SubscriptionStateMachine service.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::INCOMPLETE    => [self::TRIALING, self::ACTIVE, self::EXPIRED],
            self::TRIALING      => [self::ACTIVE, self::PAST_DUE, self::CANCELED, self::EXPIRED],
            self::ACTIVE        => [self::PAST_DUE, self::GRACE_PERIOD, self::PAUSED, self::CANCELED],
            self::PAST_DUE      => [self::ACTIVE, self::GRACE_PERIOD, self::CANCELED],
            self::GRACE_PERIOD  => [self::ACTIVE, self::EXPIRED, self::CANCELED],
            self::PAUSED        => [self::ACTIVE, self::CANCELED],
            self::CANCELED      => [self::ACTIVE, self::EXPIRED],  // reactivation
            self::EXPIRED       => [self::TRIALING, self::ACTIVE], // resubscribe
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
