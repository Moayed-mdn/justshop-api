<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum AuthDomainEnum: string
{
    case MERCHANT = 'merchant';
    case CUSTOMER = 'customer';
    case PLATFORM = 'platform';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
