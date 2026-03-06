<?php

return [
    // Absolute upper bound for any credit-limit recommendation/application.
    'system_max_limit' => (float) env('CREDIT_LIMIT_SYSTEM_THRESHOLD', 30000),
    'minimum_limit' => (float) env('CREDIT_LIMIT_MINIMUM', 1000),
];

