<?php

declare(strict_types=1);

namespace App\Enums\System;

enum ApiDomainEnum: string
{
    case MERCHANT_AUTH = 'merchant_auth';
    case MERCHANT_ADMIN = 'merchant_admin';
    case STOREFRONT = 'storefront';
    case CUSTOMER_IDENTITY = 'customer_identity';
    case PLATFORM_ADMIN = 'platform_admin';
    case CMS = 'cms';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
