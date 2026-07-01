<?php

return [
    'paths' => ['api/*', 'graphql', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
        'http://localhost:4000',
        'http://localhost:8000',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:3002',
        'http://127.0.0.1:4000',
        'http://127.0.0.1:8000',
        'http://laratenant.local',
        'https://laratenant.local',
        'http://www.laratenant.local',
        'https://www.laratenant.local',
        'http://app.laratenant.local',
        'https://app.laratenant.local',
        'http://my-store.laratenant.local',
        'https://my-store.laratenant.local',
        'http://demo.justshop.test:3000',
        'https://demo.justshop.test:3000',
    ],

    'allowed_origins_patterns' => [
        '#^https?://[a-z0-9-]+\.justshop\.test(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 0,

    'supports_credentials' => true,
];