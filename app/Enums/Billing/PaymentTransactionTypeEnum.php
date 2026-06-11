<?php

namespace App\Enums\Billing;

enum PaymentTransactionTypeEnum: string
{
    case CHARGE     = 'charge';
    case REFUND     = 'refund';
    case ADJUSTMENT = 'adjustment';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
