<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Local HTTPS reverse-proxy support (e.g. https://localhost:8443 -> http://127.0.0.1:8000).
        // Without this, Laravel may generate http:// links/redirects based on APP_URL, causing mixed content
        // and "broken" pages under the proxy.
        if (!app()->runningInConsole() && app()->environment('local')) {
            $r = request();
            $xfp = strtolower((string) $r->header('x-forwarded-proto', ''));
            if ($xfp === 'https' && $r->getHost() === 'localhost') {
                URL::forceScheme('https');
                URL::forceRootUrl('https://' . $r->getHttpHost());
            }
        }

        if (app()->environment('production') && config('app.force_https')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('ussd', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
