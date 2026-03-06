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
        if (app()->environment('production') && env('FORCE_HTTPS', true)) {
            $host = request()->getHost();
            $isLocalHost = in_array($host, ['localhost', '127.0.0.1'], true)
                || str_starts_with($host, '192.168.')
                || str_starts_with($host, '10.')
                || preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host) === 1;

            if (!$isLocalHost) {
                URL::forceScheme('https');
            }
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
