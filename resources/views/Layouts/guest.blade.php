<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = (string) config('seo.site_name', 'Bwiser');
        $metaTitle = trim($__env->yieldContent('title', $siteName));
        if (!str_contains(strtolower($metaTitle), strtolower($siteName))) {
            $metaTitle .= ' | '.$siteName;
        }
        $metaDescription = trim($__env->yieldContent('meta_description', 'Secure onboarding and access portal for the Bwiser platform.'));
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
    <meta name="application-name" content="{{ $siteName }}">
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: "BBH Bartle";
            src:
                url("{{ asset('fonts/bbh-bartle/bbh-bartle-regular.woff2') }}") format("woff2"),
                url("{{ asset('fonts/bbh-bartle/bbh-bartle-regular.woff') }}") format("woff");
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: "BBH Bartle";
            src:
                url("{{ asset('fonts/bbh-bartle/bbh-bartle-bold.woff2') }}") format("woff2"),
                url("{{ asset('fonts/bbh-bartle/bbh-bartle-bold.woff') }}") format("woff");
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --title-font: "BBH Bartle", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        * {
            font-family: "Outfit", sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: var(--title-font);
        }

        body {
            background:
                radial-gradient(1200px 800px at 12% 8%, #eef2ff 0%, #f8fafc 58%),
                radial-gradient(1000px 700px at 100% 0%, rgba(59, 130, 246, 0.12) 0%, transparent 52%),
                #f8fafc;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a,
        button,
        input,
        select,
        textarea {
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900">
    @if (session('success'))
        <div class="max-w-4xl mx-auto mt-4 px-4">
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-4xl mx-auto mt-4 px-4">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="min-h-screen flex flex-col">
        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="max-w-4xl mx-auto px-4 py-5 space-y-2">
                <p class="text-xs text-slate-500">© {{ date('Y') }} Bwiser. Johannesburg, South Africa.</p>
                <div class="pt-2 border-t border-slate-200/80">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 mb-2">Legal</p>
                    @include('partials.legal-links', ['class' => 'w-full flex flex-wrap items-center gap-2 text-xs text-slate-700'])
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
