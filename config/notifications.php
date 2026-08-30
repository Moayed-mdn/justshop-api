<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Connection/queue name used for outbound FCM delivery jobs and for the
    | queued listeners that fan notifications out to recipients. Kept
    | separate from the default queue so a burst of notification sends never
    | starves unrelated background work (or vice versa).
    |
    */
    'queue_connection' => env('NOTIFICATIONS_QUEUE_CONNECTION', config('queue.default')),
    'queue' => env('NOTIFICATIONS_QUEUE', 'notifications'),

    /*
    |--------------------------------------------------------------------------
    | FCM delivery retry behaviour
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'max_tries' => (int) env('NOTIFICATIONS_FCM_MAX_TRIES', 3),
        'backoff_seconds' => (int) env('NOTIFICATIONS_FCM_BACKOFF_SECONDS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business thresholds
    |--------------------------------------------------------------------------
    |
    | Orders with a total at or above this amount (in the store's currency
    | unit, e.g. dollars) additionally notify platform admins as an
    | operational/fraud-review signal. Set to null to disable.
    |
    */
    'high_value_order_threshold' => env('NOTIFICATIONS_HIGH_VALUE_ORDER_THRESHOLD', 2000),

];
