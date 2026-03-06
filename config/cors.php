<?php

$configuredOrigins = array_values(array_filter(array_map(
    static fn ($origin) => trim((string) $origin),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

$defaultOrigins = [
    'http://localhost:3000',
    'http://localhost',
    'http://127.0.0.1',
    'capacitor://localhost',
    (string) env('MOBILE_APP_URL', 'http://localhost'),
    (string) env('ADMIN_PANEL_URL', 'http://localhost:3000'),
];

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique(array_merge($defaultOrigins, $configuredOrigins))),
    'allowed_origins_patterns' => [
        '/^https?:\/\/localhost(:\d+)?$/',
        '/^https?:\/\/127\.0\.0\.1(:\d+)?$/',
        '/^https?:\/\/192\.168\.\d{1,3}\.\d{1,3}(:\d+)?$/',
        '/^https?:\/\/10\.\d{1,3}\.\d{1,3}\.\d{1,3}(:\d+)?$/',
        '/^https?:\/\/172\.(1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3}(:\d+)?$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Idempotency-Key', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 0,
    'supports_credentials' => true,
];
