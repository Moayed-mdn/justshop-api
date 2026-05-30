<?php

declare(strict_types=1);

return [
    'contract_version' => '2026-05-28',

    'supported_locales' => ['en', 'ar'],

    'legacy_passthrough_prefixes' => [
        '/login',
        '/register',
        '/cart',
        '/checkout',
        '/orders',
        '/profile',
        '/verify-email',
        '/auth',
    ],

    'cache_ttl' => [
        'route' => 300,
        'page' => 3600,
        'navigation' => 1800,
        'theme' => 3600,
    ],

    'category_product_limit' => 48,

    'theme' => [
        'tokens' => [
            'color_primary' => '#003D29',
            'color_surface' => '#ffffff',
            'color_text' => '#231F1E',
            'font_body' => 'Inter, system-ui, sans-serif',
            'font_heading' => 'Inter, system-ui, sans-serif',
        ],
        'radius' => 'md',
    ],

    'home_featured_product_limit' => 8,

    'preview' => [
        'ttl_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Controlled rollout (Phase 7)
    |--------------------------------------------------------------------------
    |
    | mode:
    | - off: runtime APIs disabled for all tenants (instant rollback when paired with kill_switch)
    | - internal: only internal_tenant_keys
    | - pilot: internal + pilot_tenant_keys
    | - full: all resolved tenants
    */
    'rollout' => [
        'mode' => env('STOREFRONT_RUNTIME_ROLLOUT_MODE', 'full'),
        'kill_switch' => filter_var(env('STOREFRONT_RUNTIME_KILL_SWITCH', false), FILTER_VALIDATE_BOOL),
        'internal_tenant_keys' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('STOREFRONT_RUNTIME_INTERNAL_TENANT_KEYS', 'justshop-demo,demo.justshop.test')),
        ))),
        'pilot_tenant_keys' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('STOREFRONT_RUNTIME_PILOT_TENANT_KEYS', '')),
        ))),
    ],
];
