<?php

namespace App\Enums\Billing;

enum InvoiceStatusEnum: string
{
    case DRAFT         = 'draft';
    case OPEN          = 'open';
    case PAID          = 'paid';
    case VOID          = 'void';
    case UNCOLLECTIBLE = 'uncollectible';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
