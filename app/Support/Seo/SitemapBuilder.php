<?php

namespace App\Support\Seo;

use Carbon\CarbonImmutable;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class SitemapBuilder
{
    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
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
                'lastmod' => $this->lastModForRoute($route, $uri),
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

    private function lastModForRoute(Route $route, string $uri): string
    {
        $timestamps = array_filter([
            $this->baseTimestamp(),
            $this->configTimestampForRoute($uri),
            ...array_map(
                fn (string $path): int => $this->fileTimestamp($path),
                $this->viewPathsForRoute($uri)
            ),
        ]);

        $latest = max($timestamps ?: [time()]);

        return CarbonImmutable::createFromTimestamp($latest)->toAtomString();
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

    /**
     * @return array<int, string>
     */
    private function viewPathsForRoute(string $uri): array
    {
        $capitalGuestLayout = base_path('resources/views/Layouts/guest.blade.php');
        $capitalAppLayout = base_path('resources/views/Layouts/app.blade.php');
        $mobileAppLayout = base_path('resources/views/mobile/layouts/app.blade.php');

        return match (true) {
            $uri === '/' => [
                base_path('resources/views/welcome.blade.php'),
                base_path('resources/views/mobile/welcome.blade.php'),
                $capitalAppLayout,
                $mobileAppLayout,
            ],
            $uri === '/login' => [
                base_path('resources/views/auth/login.blade.php'),
                base_path('resources/views/mobile/auth/login.blade.php'),
                $capitalGuestLayout,
                $mobileAppLayout,
            ],
            $uri === '/register',
            $uri === '/register/driver' => [
                base_path('resources/views/auth/driver/register.blade.php'),
                base_path('resources/views/mobile/auth/driver/register.blade.php'),
                base_path('resources/views/auth/driver/partials/agreement-modal.blade.php'),
                $capitalGuestLayout,
                $mobileAppLayout,
            ],
            $uri === '/register/merchant' => [
                base_path('resources/views/auth/merchant/register.blade.php'),
                base_path('resources/views/mobile/auth/merchant/register.blade.php'),
                $capitalGuestLayout,
                $mobileAppLayout,
            ],
            str_starts_with($uri, '/legal/') => [
                $this->legalViewPathForUri($uri),
                $capitalGuestLayout,
            ],
            $uri === '/drivers' => [
                base_path('resources/views/seo/drivers.blade.php'),
                $capitalGuestLayout,
            ],
            $uri === '/merchants' => [
                base_path('resources/views/seo/merchants.blade.php'),
                $capitalGuestLayout,
            ],
            str_starts_with($uri, '/drivers/') => [
                base_path('resources/views/seo/drivers-city.blade.php'),
                $capitalGuestLayout,
            ],
            str_starts_with($uri, '/merchants/') => [
                base_path('resources/views/seo/merchants-city.blade.php'),
                $capitalGuestLayout,
            ],
            str_starts_with($uri, '/intent/') => [
                base_path('resources/views/seo/intent.blade.php'),
                $capitalGuestLayout,
            ],
            $uri === '/blog' => [
                base_path('resources/views/blog/index.blade.php'),
                $capitalGuestLayout,
            ],
            str_starts_with($uri, '/blog/') => [
                base_path('resources/views/blog/show.blade.php'),
                $capitalGuestLayout,
            ],
            default => [],
        };
    }

    private function legalViewPathForUri(string $uri): string
    {
        $slug = trim(substr($uri, strlen('/legal/')), '/');
        $map = [
            'aml-kyc' => 'aml',
            'paia-manual' => 'paia',
            'security-compliance' => 'security',
        ];

        $view = $map[$slug] ?? $slug;

        return base_path("resources/views/legal/{$view}.blade.php");
    }

    private function configTimestampForRoute(string $uri): int
    {
        return match (true) {
            $uri === '/blog' => max(
                $this->fileTimestamp(config_path('blog_posts.php')),
                $this->latestBlogPostTimestamp()
            ),
            str_starts_with($uri, '/blog/') => max(
                $this->fileTimestamp(config_path('blog_posts.php')),
                $this->blogPostTimestampForUri($uri)
            ),
            str_starts_with($uri, '/intent/') => $this->fileTimestamp(config_path('intent_pages.php')),
            default => 0,
        };
    }

    private function latestBlogPostTimestamp(): int
    {
        $timestamps = array_map(
            fn (array $post): int => $this->postDateTimestamp((string) ($post['date'] ?? '')),
            (array) config('blog_posts', [])
        );

        return max(array_filter($timestamps) ?: [0]);
    }

    private function blogPostTimestampForUri(string $uri): int
    {
        $slug = trim(substr($uri, strlen('/blog/')), '/');

        foreach ((array) config('blog_posts', []) as $post) {
            if (!is_array($post) || (string) ($post['slug'] ?? '') !== $slug) {
                continue;
            }

            return $this->postDateTimestamp((string) ($post['date'] ?? ''));
        }

        return 0;
    }

    private function postDateTimestamp(string $date): int
    {
        if ($date === '') {
            return 0;
        }

        try {
            return CarbonImmutable::parse($date, config('app.timezone'))->endOfDay()->timestamp;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function baseTimestamp(): int
    {
        return $this->fileTimestamp(config_path('seo.php'));
    }

    private function fileTimestamp(string $path): int
    {
        return is_file($path) ? (int) filemtime($path) : 0;
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
