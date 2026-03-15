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

    'rapidpay' => [
        'base_url' => env('RAPIDPAY_BASE_URL', 'https://api.sandbox.rapidpaycollect.co.za'),
        'username' => env('RAPIDPAY_USERNAME'),
        'password' => env('RAPIDPAY_PASSWORD'),
        'timeout' => env('RAPIDPAY_TIMEOUT', 20),
        // Endpoint can differ per account integration. Override if needed.
        'collection_path' => env('RAPIDPAY_COLLECTION_PATH', '/mandates/collect'),
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'ussd_token' => env('AFRICASTALKING_USSD_TOKEN'),
        'sms_base_url' => env('AFRICASTALKING_SMS_BASE_URL', 'https://api.africastalking.com/version1/messaging'),
        'sms_from' => env('AFRICASTALKING_SMS_FROM'),
        'sms_timeout_seconds' => env('AFRICASTALKING_SMS_TIMEOUT_SECONDS', 12),
        'sms_dry_run' => env('AFRICASTALKING_SMS_DRY_RUN', false),
        'airtime_split_enabled' => env('AFRICASTALKING_AIRTIME_SPLIT_ENABLED', false),
        'airtime_split_percent' => env('AFRICASTALKING_AIRTIME_SPLIT_PERCENT', 20),
        'airtime_split_min_amount' => env('AFRICASTALKING_AIRTIME_SPLIT_MIN_AMOUNT', 5),
        'airtime_base_url' => env('AFRICASTALKING_AIRTIME_BASE_URL', 'https://api.africastalking.com/version1/airtime'),
        'airtime_currency' => env('AFRICASTALKING_AIRTIME_CURRENCY', 'ZAR'),
        'airtime_timeout_seconds' => env('AFRICASTALKING_AIRTIME_TIMEOUT_SECONDS', 12),
        'airtime_dry_run' => env('AFRICASTALKING_AIRTIME_DRY_RUN', true),
    ],

    'google_maps' => [
        'enabled' => env('GOOGLE_MAPS_ENABLED', true),
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

    'here_maps' => [
        'key' => env('HERE_MAPS_API_KEY'),
        'user_id' => env('HERE_USER_ID'),
        'client_id' => env('HERE_CLIENT_ID'),
        'access_key_id' => env('HERE_ACCESS_KEY_ID'),
        'access_key_secret' => env('HERE_ACCESS_KEY_SECRET'),
        'token_endpoint_url' => env('HERE_TOKEN_ENDPOINT_URL', 'https://account.api.here.com/oauth2/token'),
    ],

    'bing_maps' => [
        'key' => env('BING_MAPS_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_CREDIT_MODEL', 'gpt-4o-mini'),
        'timeout' => env('OPENAI_TIMEOUT', 20),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'merchant_id' => env('FLUTTERWAVE_MERCHANT_ID'),
        'env' => env('FLUTTERWAVE_ENV', 'sandbox'),
        'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com'),
        'timeout' => env('FLUTTERWAVE_TIMEOUT', 20),
    ],

    'redemption_geofence' => [
        'enabled' => env('REDEMPTION_GEOFENCE_ENABLED', false),
        'radius_meters' => env('REDEMPTION_GEOFENCE_RADIUS_METERS', 150),
    ],

];
