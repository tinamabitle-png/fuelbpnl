<?php

namespace App\Support\Seo;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class SitemapBuilder
{
    /**
     * @return array<int, array{loc: string, changefreq: string, priority: string}>
     */
    public function build(): array
    {
        $pages = [];
        $seen = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (!$this->isIndexable($route)) {
                continue;
            }

            $uri = '/' . ltrim($route->uri(), '/');
            if ($uri === '//') {
                $uri = '/';
            }

            $loc = url($uri);
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;

            $pages[] = [
                'loc' => $loc,
                'changefreq' => $this->changeFreqForUri($uri),
                'priority' => $this->priorityForUri($uri),
            ];
        }

        usort($pages, static fn (array $a, array $b) => strcmp($a['loc'], $b['loc']));

        // Ensure homepage appears first.
        usort($pages, static function (array $a, array $b): int {
            if (rtrim($a['loc'], '/') === rtrim(url('/'), '/')) {
                return -1;
            }
            if (rtrim($b['loc'], '/') === rtrim(url('/'), '/')) {
                return 1;
            }
            return strcmp($a['loc'], $b['loc']);
        });

        return $pages;
    }

    private function isIndexable(Route $route): bool
    {
        $methods = $route->methods();
        if (!in_array('GET', $methods, true)) {
            return false;
        }

        $uri = ltrim($route->uri(), '/');
        if (str_contains($uri, '{')) {
            return false;
        }

        $name = (string) $route->getName();
        if ($name !== '' && $this->isExcludedName($name)) {
            return false;
        }

        if ($this->isExcludedUri($uri)) {
            return false;
        }

        $middleware = $route->gatherMiddleware();
        if (in_array('auth', $middleware, true) || in_array('signed', $middleware, true)) {
            return false;
        }

        return true;
    }

    private function isExcludedName(string $name): bool
    {
        $excluded = (array) config('seo.sitemap.excluded_route_names', []);
        if (in_array($name, $excluded, true)) {
            return true;
        }

        foreach ((array) config('seo.sitemap.excluded_name_prefixes', []) as $prefix) {
            if ($prefix !== '' && str_starts_with($name, (string) $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedUri(string $uri): bool
    {
        foreach ((array) config('seo.sitemap.excluded_uri_prefixes', []) as $prefix) {
            $prefix = trim((string) $prefix, '/');
            if ($prefix !== '' && (str_starts_with($uri, $prefix . '/') || $uri === $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function priorityForUri(string $uri): string
    {
        if ($uri === '/') {
            return '1.0';
        }

        if (str_starts_with($uri, '/legal/')) {
            return '0.5';
        }

        if (str_contains($uri, 'register')) {
            return '0.7';
        }

        return '0.6';
    }

    private function changeFreqForUri(string $uri): string
    {
        if ($uri === '/') {
            return 'daily';
        }

        if (str_starts_with($uri, '/legal/')) {
            return 'monthly';
        }

        return 'weekly';
    }
}

