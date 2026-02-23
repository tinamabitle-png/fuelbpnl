<?php

return [
    'currency' => env('FUEL_PRICE_CURRENCY', 'ZAR'),

    'defaults' => [
        'petrol' => (float) env('FUEL_PRICE_DEFAULT_PETROL', 24.75),
        'diesel' => (float) env('FUEL_PRICE_DEFAULT_DIESEL', 23.95),
        'super' => (float) env('FUEL_PRICE_DEFAULT_SUPER', 25.10),
    ],

    'api' => [
        'enabled' => (bool) env('FUEL_PRICE_API_ENABLED', false),
        'url' => env('FUEL_PRICE_API_URL'),
        'token' => env('FUEL_PRICE_API_TOKEN'),
        'timeout' => (int) env('FUEL_PRICE_API_TIMEOUT', 5),
        'cache_seconds' => (int) env('FUEL_PRICE_API_CACHE_SECONDS', 300),
    ],
];
