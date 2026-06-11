<?php

namespace App\Enums\Billing;

enum PaymentStatusEnum: string
{
    case PENDING    = 'pending';
    case PROCESSING = 'processing';
    case SUCCEEDED  = 'succeeded';
    case FAILED     = 'failed';
    case REFUNDED   = 'refunded';
    case CANCELED   = 'canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
