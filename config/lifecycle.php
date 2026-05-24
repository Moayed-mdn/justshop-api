<?php

declare(strict_types=1);

return [
    'onboarding' => [
        // Recover stale first-store onboarding that was interrupted mid-transaction.
        'stale_store_creation_minutes' => (int) env('ONBOARDING_STALE_STORE_CREATION_MINUTES', 15),
    ],

    'provisioning' => [
        // Frontend polling should eventually observe a terminal state even if a worker dies silently.
        'stale_after_minutes' => (int) env('PROVISIONING_STALE_AFTER_MINUTES', 10),
        'max_retry_attempts' => (int) env('PROVISIONING_MAX_RETRY_ATTEMPTS', 5),
    ],

    'routing' => [
        'deprecated_bootstrap_sunset' => env('DEPRECATED_BOOTSTRAP_SUNSET', 'Wed, 31 Dec 2026 23:59:59 GMT'),
    ],
];
