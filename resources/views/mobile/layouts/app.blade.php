<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = (string) config('seo.site_name', 'Bwiser');
        $metaTitle = trim($__env->yieldContent('title', $siteName.' Mobile'));
        if (!str_contains(strtolower($metaTitle), strtolower($siteName))) {
            $metaTitle .= ' | '.$siteName;
        }
        $metaDescription = trim($__env->yieldContent('meta_description', 'Bwiser mobile web experience for fuel finance and voucher operations.'));
        $metaRobots = trim($__env->yieldContent('meta_robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'));
        $canonical = trim($__env->yieldContent('canonical', url()->current()));
        $ogImage = trim($__env->yieldContent('og_image', asset('images/brand-logo.png')));
        $locale = (string) config('seo.default_locale', 'en_ZA');
        $themeColor = (string) config('seo.theme_color', '#2563eb');
        $twitterSite = (string) config('seo.twitter_site', '@bwiser');
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $metaRobots }}">
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="application-name" content="{{ $siteName }} Mobile">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $locale }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $twitterSite }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => config('seo.site_url'),
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
    <link rel="icon" type="image/png" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Goldman:wght@400;700&family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @php
        $hasViteAssets = is_file(public_path('hot')) || is_file(public_path('build/manifest.json'));
    @endphp
    @if($hasViteAssets)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <style>
        @font-face {
            font-family: "Cal Sans";
            src:
                url("{{ asset('fonts/cal-sans/cal-sans-semibold.woff2') }}") format("woff2"),
                url("{{ asset('fonts/cal-sans/cal-sans-semibold.woff') }}") format("woff");
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --mobile-bg:
                radial-gradient(900px 550px at 10% 0%, #eef2ff 0%, #f8fafc 58%),
                radial-gradient(700px 450px at 95% -10%, rgba(59, 130, 246, 0.18) 0%, transparent 52%),
                #f8fafc;
            --panel: rgba(255, 255, 255, 0.95);
            --panel-border: rgba(226, 232, 240, 0.9);
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #38bdf8;
            --text-strong: #0f172a;
            --text-muted: #475569;
            --title-font: "Goldman", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --body-font: "Cal Sans", "Outfit", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body.mobile-shell {
            margin: 0;
            min-height: 100svh;
            background: var(--mobile-bg);
            color: var(--text-strong);
            font-family: var(--body-font);
        }

        .mobile-card {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 1rem;
            box-shadow: 0 18px 40px -32px rgba(15, 23, 42, 0.35);
        }

        .mobile-gradient-text {
            background: linear-gradient(95deg, var(--primary-dark) 0%, var(--primary) 52%, var(--accent) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .mobile-brand-font {
            font-family: var(--title-font);
        }

        .staging-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.18rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.6rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            line-height: 1;
            background: rgba(245, 158, 11, 0.18);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.35);
            text-transform: uppercase;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: var(--title-font);
        }
    </style>
</head>
<body class="mobile-shell">
    <header class="px-4 pt-4">
        <div class="mx-auto max-w-md mobile-card px-4 py-3">
            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}"
                    alt="Bwiser logo"
                    class="h-10 w-10 rounded-xl object-cover"
                >
                <div>
                    <div class="flex items-center gap-2">
                        <p class="mobile-brand-font text-base font-semibold text-slate-900 leading-tight">Bwiser</p>
                        @if(app()->environment('staging'))
                            <span class="staging-badge" aria-label="Staging environment">STAGING</span>
                        @endif
                    </div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-blue-700">Control Platform</p>
                </div>
            </div>
        </div>
    </header>
    <div class="min-h-screen flex flex-col">
        <main class="flex-1">
            @yield('content')
        </main>
        <footer class="px-4 pb-6 pt-2">
            <div class="mx-auto max-w-md mobile-card px-4 py-3 space-y-2">
                <p class="text-[11px] text-slate-500">© {{ date('Y') }} Bwiser</p>
                <div class="pt-2 mt-2 border-t border-slate-200/80">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500 mb-1.5">Legal</p>
                    @include('partials.legal-links', ['class' => 'w-full flex flex-wrap items-center gap-2 text-[11px] text-slate-700'])
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
