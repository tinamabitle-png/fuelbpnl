<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeSessionDomain
{
    /**
     * Ensure session/XSRF cookies work across bwiser.co.za and www.bwiser.co.za.
     *
     * This is intentionally defensive: if VPS env vars are missing/mis-set, the
     * app still behaves correctly instead of producing repeated 419 "Page expired".
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower((string) $request->getHost());

        // Only normalize for our production domain.
        if ($host === 'bwiser.co.za' || str_ends_with($host, '.bwiser.co.za')) {
            // Leading dot makes cookies valid for subdomains.
            config([
                'session.domain' => '.bwiser.co.za',
                // Honor HTTPS behind Cloudflare / reverse proxies.
                'session.secure' => $request->isSecure(),
                'session.same_site' => config('session.same_site') ?: 'lax',
            ]);
        }

        return $next($request);
    }
}
