<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">
    @php
        $siteName = 'Bwiser';
        $metaTitle = trim($__env->yieldContent('title', $siteName.' Mobile'));
        if (!str_contains(strtolower($metaTitle), strtolower($siteName))) {
            $metaTitle .= ' | '.$siteName;
        }
        $metaDescription = trim($__env->yieldContent('meta_description', 'Bwiser mobile web experience for fuel finance and voucher operations.'));
        $metaRobots = trim($__env->yieldContent('meta_robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'));
        $canonical = trim($__env->yieldContent('canonical', url()->current()));
        $ogImage = trim($__env->yieldContent('og_image', asset('images/brand-logo.png')));
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $metaRobots }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="icon" type="image/png" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @php
        $viteManifestPath = public_path('build/manifest.json');
        $hasViteManifest = is_file($viteManifestPath);
    @endphp
    @if($hasViteManifest)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <style>
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
        }

        body.mobile-shell {
            margin: 0;
            min-height: 100svh;
            background: var(--mobile-bg);
            color: var(--text-strong);
            font-family: "Outfit", sans-serif;
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
            font-family: "Space Grotesk", sans-serif;
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
                    <p class="mobile-brand-font text-base font-semibold text-slate-900 leading-tight">Bwiser</p>
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
                @include('partials.legal-links', ['class' => 'w-full flex flex-wrap items-center gap-2 text-[11px] text-slate-700'])
            </div>
        </footer>
    </div>
</body>
</html>
