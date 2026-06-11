<?php

namespace App\Enums\Entitlement;

enum FeatureKeyEnum: string
{
    case STORES_MAX         = 'stores.max';
    case PRODUCTS_MAX       = 'products.max';
    case USERS_MAX          = 'users.max';
    case ANALYTICS_ADVANCED = 'analytics.advanced';
    case API_ACCESS         = 'api.access';
    case CUSTOM_DOMAIN      = 'custom_domain.enabled';
    case PRIORITY_SUPPORT   = 'support.priority';
    case WEBHOOKS_ENABLED   = 'webhooks.enabled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
