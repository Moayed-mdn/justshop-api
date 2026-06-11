<?php

namespace App\Enums\Entitlement;

use App\Enums\Subscription\SubscriptionStatusEnum;

enum EntitlementStatusEnum: string
{
    case ENTITLED      = 'entitled';
    case TRIAL         = 'trial';
    case READ_ONLY     = 'read_only';
    case RESTRICTED    = 'restricted';
    case NONE          = 'none';
    case GRANDFATHERED = 'grandfathered';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Derive entitlement status from subscription status.
     */
    public static function fromSubscriptionStatus(SubscriptionStatusEnum $status): self
    {
        return match (true) {
            $status === SubscriptionStatusEnum::TRIALING          => self::TRIAL,
            $status->grantsFullAccess()                           => self::ENTITLED,
            $status->grantsReadOnlyAccess()                       => self::READ_ONLY,
            $status === SubscriptionStatusEnum::PAUSED            => self::RESTRICTED,
            default                                               => self::NONE,
        };
    }

    public function grantsWriteAccess(): bool
    {
        return in_array($this, [self::ENTITLED, self::TRIAL, self::GRANDFATHERED], true);
    }

    public function grantsReadAccess(): bool
    {
        return in_array($this, [
            self::ENTITLED,
            self::TRIAL,
            self::READ_ONLY,
            self::GRANDFATHERED,
        ], true);
    }
}
