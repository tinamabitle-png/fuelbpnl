<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UseMobileViews
{
    public function handle(Request $request, Closure $next): SymfonyResponse|View
    {
        $response = $next($request);

        if (! $this->shouldUseMobileView($request)) {
            return $response;
        }

        $view = $this->extractView($response);
        if (! $view) {
            return $response;
        }

        $viewName = method_exists($view, 'getName') ? $view->getName() : null;
        if (! $viewName || str_starts_with($viewName, 'mobile.')) {
            return $response;
        }

        $mobileViewName = 'mobile.'.$viewName;
        if (! view()->exists($mobileViewName)) {
            return $response;
        }

        $mobileView = view($mobileViewName, $view->getData());

        if ($response instanceof View) {
            return $mobileView;
        }

        if ($response instanceof Response) {
            $response->setContent($mobileView->render());
        }

        return $response;
    }

    private function shouldUseMobileView(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->expectsJson()) {
            return false;
        }

        if ($request->boolean('force_desktop')) {
            return false;
        }

        if ($request->boolean('force_mobile')) {
            return true;
        }

        $userAgent = strtolower((string) $request->userAgent());
        if ($userAgent === '') {
            return false;
        }

        return (bool) preg_match('/android|iphone|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent);
    }

    private function extractView(mixed $response): ?View
    {
        if ($response instanceof View) {
            return $response;
        }

        if ($response instanceof Response) {
            $original = $response->getOriginalContent();
            if ($original instanceof View) {
                return $original;
            }
        }

        return null;
    }
}
