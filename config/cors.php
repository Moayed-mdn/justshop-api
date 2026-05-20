<?php

return [
    'paths' => ['api/*', 'graphql', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:8000',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://laratenant.local',
        'https://laratenant.local',
        'http://www.laratenant.local',
        'https://www.laratenant.local',
        'http://app.laratenant.local',
        'https://app.laratenant.local',
        'http://my-store.laratenant.local',
        'https://my-store.laratenant.local',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 0,

    'supports_credentials' => true,
];