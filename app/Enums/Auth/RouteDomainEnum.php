<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum RouteDomainEnum: string
{
    case MERCHANT_USERS = 'merchant_users';
    case MERCHANT_ADMIN = 'merchant_admin';
    case CUSTOMER_ACCOUNT = 'customer_account';
    case STOREFRONT = 'storefront';
    case STOREFRONT_COMMERCE = 'storefront_commerce';
    case PLATFORM = 'platform';
    case SUPPORT = 'support';
    case PUBLIC = 'public';
    case SHARED_TRANSITIONAL = 'shared_transitional';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
