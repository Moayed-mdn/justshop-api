<?php

declare(strict_types=1);

return [
    'contract_version' => '2026-05-28',

    'supported_locales' => ['en', 'ar'],

    /*
    |--------------------------------------------------------------------------
    | Storefront Base Domain
    |--------------------------------------------------------------------------
    | New stores get a subdomain derived from their slug: <slug>.<base_domain>
    | e.g. test.justshop.test for a store with slug "test".
    */
    'base_domain' => env('STOREFRONT_BASE_DOMAIN', 'justshop.test'),

    'legacy_passthrough_prefixes' => [
        '/cart',
        '/checkout',
        '/orders',
        '/profile',
    ],

    'cache_ttl' => [
        'route' => env('STOREFRONT_RUNTIME_CACHE_TTL_ROUTE', 300),
        'page' => env('STOREFRONT_RUNTIME_CACHE_TTL_PAGE', 3600),
        'navigation' => env('STOREFRONT_RUNTIME_CACHE_TTL_NAVIGATION', 1800),
        'theme' => env('STOREFRONT_RUNTIME_CACHE_TTL_THEME', 3600),
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
