<?php

return [
    'correlation_header' => 'X-Correlation-ID',

    'release_version' => env('APP_RELEASE_VERSION', 'dev'),

    'security_log_channel' => env('SECURITY_LOG_CHANNEL', 'security'),

    'log_redaction' => [
        'enabled' => env('OBSERVABILITY_LOG_REDACTION_ENABLED', true),
        'placeholder' => '[REDACTED]',
        'max_string_length' => 2048,
        'sensitive_keys' => [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'remember_token',
            'secret',
            'signature',
            'authorization',
            'cookie',
            'set-cookie',
            'csrf',
            'xsrf',
            'session',
            'session_id',
            'sessionid',
            'session_ownership_key',
            'sigheader',
            'webhook_secret',
        ],
        'sensitive_query_parameters' => [
            'token',
            'access_token',
            'refresh_token',
            'signature',
            'password',
            'authorization',
            'cookie',
            'session',
            'session_id',
            'xsrf-token',
            'x-xsrf-token',
        ],
    ],
];
