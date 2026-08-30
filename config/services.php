<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (Push Notifications)
    |--------------------------------------------------------------------------
    |
    | Credentials for the Firebase service account used to send push
    | notifications via the FCM HTTP v1 API. Provide EITHER a path to the
    | service account JSON file (FIREBASE_CREDENTIALS_PATH) OR the JSON
    | contents base64-encoded directly in the environment
    | (FIREBASE_CREDENTIALS_JSON), which is friendlier for platforms that
    | don't allow shipping extra files (e.g. most PaaS deployments).
    | Never commit the credentials file itself to source control.
    |
    */
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),
        'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),
        'http_timeout' => (int) env('FIREBASE_HTTP_TIMEOUT', 10),
    ],

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'ecommerce_webhook_secret' => env('STRIPE_ECOMMERCE_WEBHOOK_SECRET'),
        'platform_fee_percent' => env('STRIPE_PLATFORM_FEE_PERCENT', 3.0),
        'connect_return_base_url' => env('STRIPE_CONNECT_RETURN_BASE_URL', env('FRONTEND_URL', 'http://localhost:3000')),
    ],


    /*
    |--------------------------------------------------------------------------
    | Next.js ISR Revalidation
    |--------------------------------------------------------------------------
    |
    | Configuration for on-demand ISR revalidation webhook.
    | Set FRONTEND_REVALIDATION_URL and FRONTEND_REVALIDATION_SECRET in .env
    |
    */
    'nextjs' => [
        'revalidation_url'    => env('FRONTEND_REVALIDATION_URL'),
        'revalidation_secret' => env('FRONTEND_REVALIDATION_SECRET'),
    ],
];
