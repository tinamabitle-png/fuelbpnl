<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000', // React admin panel
        'capacitor://localhost', // Flutter iOS
        'http://localhost', // Flutter Android
        env('MOBILE_APP_URL', 'http://localhost'),
        env('ADMIN_PANEL_URL', 'http://localhost:3000'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Idempotency-Key', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 0,
    'supports_credentials' => true,
];