<?php

namespace App\Enums\Subscription;

enum PlanTierEnum: string
{
    case STARTER    = 'starter';
    case GROWTH     = 'growth';
    case ENTERPRISE = 'enterprise';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function tier(): int
    {
        return match ($this) {
            self::STARTER    => 1,
            self::GROWTH     => 2,
            self::ENTERPRISE => 3,
        };
    }

    public function isUpgradeTo(self $target): bool
    {
        return $target->tier() > $this->tier();
    }

    public function defaultStoreLimit(): ?int
    {
        return match ($this) {
            self::STARTER    => 1,
            self::GROWTH     => 3,
            self::ENTERPRISE => null, // unlimited
        };
    }
}
