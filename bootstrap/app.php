<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\UseMobileViews::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // SPATIE v6.24.0 middleware paths
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'tapless.partner' => \App\Http\Middleware\AuthenticateTaplessPartner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Fail-safe for intermittent session/CSRF mismatches in production.
        $exceptions->renderable(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session expired. Please refresh and try again.',
                    'code' => 419,
                ], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['_token', '_method', 'password', 'password_confirmation']))
                ->with('error', 'Your session expired. Please try again.');
        });
    })
    ->withEvents(discover: [
        __DIR__.'/../app/Listeners',
    ])
    ->create();
