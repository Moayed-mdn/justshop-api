<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum OperationalContextEnum: string
{
    case MERCHANT_ONBOARDING = 'merchant_onboarding';
    case MERCHANT_OPERATIONAL = 'merchant_operational';
    case CUSTOMER_ACCOUNT = 'customer_account';
    case PLATFORM_ADMIN = 'platform_admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
