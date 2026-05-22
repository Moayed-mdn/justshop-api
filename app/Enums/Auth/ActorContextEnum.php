<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum ActorContextEnum: string
{
    case MERCHANT = 'merchant';
    case CUSTOMER = 'customer';
    case SUPER_ADMIN = 'super_admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
