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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'currency' => env('PAYSTACK_CURRENCY', 'ZAR'),
        'transfer_source' => env('PAYSTACK_TRANSFER_SOURCE', 'balance'),
        'timeout' => env('PAYSTACK_TIMEOUT', 15),
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'ussd_token' => env('AFRICASTALKING_USSD_TOKEN'),
        'airtime_split_enabled' => env('AFRICASTALKING_AIRTIME_SPLIT_ENABLED', false),
        'airtime_split_percent' => env('AFRICASTALKING_AIRTIME_SPLIT_PERCENT', 20),
        'airtime_split_min_amount' => env('AFRICASTALKING_AIRTIME_SPLIT_MIN_AMOUNT', 5),
        'airtime_base_url' => env('AFRICASTALKING_AIRTIME_BASE_URL', 'https://api.africastalking.com/version1/airtime'),
        'airtime_currency' => env('AFRICASTALKING_AIRTIME_CURRENCY', 'ZAR'),
        'airtime_timeout_seconds' => env('AFRICASTALKING_AIRTIME_TIMEOUT_SECONDS', 12),
        'airtime_dry_run' => env('AFRICASTALKING_AIRTIME_DRY_RUN', true),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'redemption_geofence' => [
        'enabled' => env('REDEMPTION_GEOFENCE_ENABLED', false),
        'radius_meters' => env('REDEMPTION_GEOFENCE_RADIUS_METERS', 150),
    ],

];
