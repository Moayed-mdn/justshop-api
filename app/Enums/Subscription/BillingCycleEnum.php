<?php

namespace App\Enums\Subscription;

enum BillingCycleEnum: string
{
    case MONTHLY = 'monthly';
    case ANNUAL  = 'annual';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function intervalDays(): int
    {
        return match ($this) {
            self::MONTHLY => 30,
            self::ANNUAL  => 365,
        };
    }
}
