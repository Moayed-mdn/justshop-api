<?php

return [
    'spam' => [
        'throttle_max_attempts' => (int) env('LEAD_THROTTLE_MAX_ATTEMPTS', 5),
        'throttle_decay_minutes' => (int) env('LEAD_THROTTLE_DECAY_MINUTES', 1),
        'duplicate_window_minutes' => (int) env('LEAD_DUPLICATE_WINDOW_MINUTES', 10),
    ],
];
