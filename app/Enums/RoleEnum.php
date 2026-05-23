<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN  = 'super_admin';
    case SUPPORT      = 'support';
    case STORE_ADMIN  = 'store_admin';
    case STAFF        = 'staff';
    case CUSTOMER     = 'customer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
