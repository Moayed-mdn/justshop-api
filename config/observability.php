<?php

return [
    'correlation_header' => 'X-Correlation-ID',

    'release_version' => env('APP_RELEASE_VERSION', 'dev'),

    'security_log_channel' => env('SECURITY_LOG_CHANNEL', 'security'),
];
