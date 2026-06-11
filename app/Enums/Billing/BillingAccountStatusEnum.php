<?php

namespace App\Enums\Billing;

enum BillingAccountStatusEnum: string
{
    case ACTIVE    = 'active';
    case SUSPENDED = 'suspended';
    case CLOSED    = 'closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
