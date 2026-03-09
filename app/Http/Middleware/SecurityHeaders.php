<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Apply baseline security headers to all responses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        $host = (string) $request->getHost();
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1'], true)
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.')
            || preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host) === 1;

        $cspEnabled = (bool) env('SECURITY_CSP_ENABLED', true);

        if ($cspEnabled) {
            // Keep production strict, but allow local HMR/dev sockets on localhost/LAN.
            $csp = $isLocalHost
                ? "default-src 'self' http: https: data: blob:; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' http: https:; " .
                    "style-src 'self' 'unsafe-inline' http: https:; " .
                    "img-src 'self' data: blob: http: https:; " .
                    "font-src 'self' data: http: https:; " .
                    "connect-src 'self' http: https: ws: wss:; " .
                    "frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
                : "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://maps.googleapis.com https://maps.gstatic.com https://cdn.tailwindcss.com https://js.api.here.com https://www.bing.com https://unpkg.com; " .
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://js.api.here.com https://unpkg.com; " .
                    "img-src 'self' data: blob: https:; " .
                    "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
                    "connect-src 'self' https: wss:; " .
                    "frame-ancestors 'self'; base-uri 'self'; form-action 'self'";

            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
