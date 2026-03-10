<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs that should be excluded from CSRF verification.
     *
     * Keep this list minimal. External webhooks should live under `routes/api.php`
     * (stateless) whenever possible.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Example:
        // 'webhooks/*',
    ];
}

