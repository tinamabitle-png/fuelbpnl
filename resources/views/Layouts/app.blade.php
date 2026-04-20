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
        $metaDescription = trim($__env->yieldContent('meta_description', 'Bwiser enables fuel voucher issuance, driver finance workflows, station redemption, and settlement operations in South Africa.'));
        $metaRobots = trim($__env->yieldContent('meta_robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'));
        $canonical = trim($__env->yieldContent('canonical', url()->current()));
        $ogType = trim($__env->yieldContent('og_type', 'website'));
        $ogImage = trim($__env->yieldContent('og_image', asset('images/brand-logo.png')));
        $ogImageAlt = trim($__env->yieldContent('og_image_alt', $siteName . ' preview'));
        $locale = (string) config('seo.default_locale', 'en_ZA');
        $themeColor = (string) config('seo.theme_color', '#2563eb');
        $twitterSite = (string) config('seo.twitter_site', '@bwiser');
        $facebookAppId = trim((string) config('seo.facebook_app_id', ''));
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $metaRobots }}">
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="application-name" content="{{ $siteName }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $locale }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    @if($facebookAppId !== '')
        <meta property="fb:app_id" content="{{ $facebookAppId }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $twitterSite }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => config('seo.site_url'),
        'logo' => asset('images/brand-logo.png'),
        'email' => config('seo.support_email', 'support@bwiser.co.za'),
        'telephone' => (string) config('seo.contact_phone', ''),
        'sameAs' => (array) config('seo.same_as', []),
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
    <link rel="icon" type="image/png" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Goldman:wght@400;700&family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: "Cal Sans";
            src:
                url("{{ asset('fonts/cal-sans/cal-sans-semibold.woff2') }}") format("woff2"),
                url("{{ asset('fonts/cal-sans/cal-sans-semibold.woff') }}") format("woff");
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --ink: #0f172a;
            --slate: #1f2937;
            --fog: #6b7280;
            --mist: #f8fafc;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #38bdf8;
            --card: #ffffff;
            --glass: #ffffff;
            --line: rgba(226, 232, 240, 0.9);
            --title-font: "Goldman", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --body-font: "Cal Sans", "Outfit", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        * {
            font-family: var(--body-font);
        }

        body {
            color: #0f172a;
            background:
                radial-gradient(1200px 800px at 10% 10%, #eef2ff 0%, #f8fafc 60%),
                radial-gradient(1000px 700px at 90% 0%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                #f8fafc;
            min-height: 100vh;
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

        input,
        select,
        textarea {
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
        }

        input::placeholder,
        textarea::placeholder {
            color: #94a3b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 0.9rem;
            overflow: hidden;
        }

        th,
        td {
            padding: 0.75rem 0.9rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #475569;
            background: #f8fafc;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        .brand-font {
            font-family: var(--title-font);
        }

        .glass {
            background: var(--glass);
            border: 1px solid var(--line);
            box-shadow: 0 18px 40px -30px rgba(15, 23, 42, 0.35);
        }

        .btn-primary {
            background: linear-gradient(120deg, var(--primary), var(--primary-dark));
            color: #eff6ff;
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(37, 99, 235, 0.3);
        }

        .btn-ghost {
            border: 1px solid rgba(148, 163, 184, 0.45);
            color: #334155;
            background: #ffffff;
        }

        .btn-ghost:hover {
            border-color: rgba(59, 130, 246, 0.6);
            background: #eff6ff;
        }

        .preprod-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.22rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.62rem;
            font-weight: 900;
            letter-spacing: 0.14em;
            line-height: 1;
            background: linear-gradient(120deg, var(--primary), var(--primary-dark));
            color: #eff6ff;
            border: 1px solid rgba(37, 99, 235, 0.25);
            text-transform: uppercase;
            box-shadow: 0 10px 18px rgba(37, 99, 235, 0.22);
        }

        .surface-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 14px 28px -24px rgba(15, 23, 42, 0.35);
        }

        .bwiser-qr-stack {
            position: relative;
            isolation: isolate;
            display: inline-flex;
        }

        .hero-gradient-text {
            background: linear-gradient(95deg, var(--primary-dark) 0%, var(--primary) 52%, var(--accent) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .bw-morph-switch {
            --default: #d1d6ee;
            --hover: #cacfe6;
            --active: #275efe;
            --dot: #fff;
            --dot-shadow: rgba(0, 9, 61, .1);
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            user-select: none;
        }

        .bw-morph-switch input {
            -webkit-tap-highlight-color: transparent;
            -webkit-appearance: none;
            appearance: none;
            outline: none;
            display: block;
            border: none;
            background: var(--bw-bg, var(--default));
            width: 40px;
            height: 22px;
            padding: 0;
            margin: 0;
            border-radius: 11px;
            cursor: pointer;
            transition: background .3s linear;
        }

        .bw-morph-switch:hover input {
            --bw-bg: var(--hover);
        }

        .bw-morph-switch input:focus-visible {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .25);
        }

        .bw-morph-switch input:checked {
            --bw-bg: var(--active);
        }

        .bw-morph-switch.is-active input {
            --bw-bg: var(--active);
        }

        .bw-morph-switch.is-active input:checked {
            --bw-bg: var(--default);
        }

        .bw-morph-switch svg {
            fill: var(--dot);
            display: block;
            width: 36px;
            height: 18px;
            position: absolute;
            left: 2px;
            top: 2px;
            pointer-events: none;
            filter: drop-shadow(0 .5px .5px var(--dot-shadow));
            transition: transform .3s ease;
        }

        .bw-morph-switch input:checked + svg {
            transform: scaleX(-1);
        }

        .bw-morph-switch[data-disabled="1"] {
            opacity: .55;
            pointer-events: none;
        }

        .bw-morph-switch-label {
            font-size: .875rem;
            color: #374151;
            font-weight: 500;
        }

        .bw-morph-wrap {
            --toggle: #fff;
            --toggle-active: #275efe;
            --toggle-border: #bbc1e1;
            --toggle-border-hover: #a6accd;
            --toggle-border-active: #275efe;
            --toggle-inner: #fff;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            vertical-align: middle;
            flex: 0 0 22px;
        }

        .bw-morph-wrap input {
            position: absolute;
            inset: 0;
            margin: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .bw-morph-wrap svg {
            fill: var(--svg-fill, none);
            stroke: var(--svg-stroke, none);
            stroke-width: var(--svg-stroke-width, 0);
            stroke-linecap: round;
            stroke-linejoin: round;
            display: block;
            width: var(--svg-width, 28px);
            height: var(--svg-height, 28px);
            position: absolute;
            top: var(--svg-top, -3px);
            left: var(--svg-left, -3px);
            pointer-events: none;
            transform: scale(var(--svg-scale, 1)) translateZ(0);
            transition: stroke .3s, fill .3s, stroke-dashoffset .15s ease var(--svg-delay, 0s), transform var(--svg-transform-duration, 0s);
        }

        .bw-morph-wrap.bw-checkbox,
        .bw-morph-wrap.bw-radio {
            --svg-fill: var(--toggle);
            --svg-stroke: var(--toggle-border);
            --svg-stroke-width: 1px;
        }

        .bw-morph-wrap.bw-checkbox input:hover + .bw-frame,
        .bw-morph-wrap.bw-radio input:hover + .bw-frame {
            --svg-stroke: var(--toggle-border-hover);
        }

        .bw-morph-wrap.bw-checkbox input:checked + .bw-frame,
        .bw-morph-wrap.bw-radio input:checked + .bw-frame {
            --svg-fill: var(--toggle-active);
            --svg-stroke: var(--toggle-border-active);
        }

        .bw-morph-wrap.bw-checkbox .bw-tick {
            --svg-width: 12px;
            --svg-height: 10px;
            --svg-fill: none;
            --svg-stroke: var(--toggle-inner);
            --svg-stroke-width: 2px;
            --svg-top: 6px;
            --svg-left: 5px;
            stroke-dasharray: 14px;
            stroke-dashoffset: var(--svg-offset, 14px);
        }

        .bw-morph-wrap.bw-checkbox input:checked + .bw-frame + .bw-tick {
            --svg-offset: 0;
            --svg-delay: .15s;
        }

        .bw-morph-wrap.bw-radio {
            --svg-transform-duration: .1s;
        }

        .bw-morph-wrap.bw-radio .bw-inner {
            --svg-fill: var(--toggle-inner);
            --svg-stroke-width: 0;
            --svg-width: 14px;
            --svg-height: 14px;
            --svg-top: 4px;
            --svg-left: 4px;
            --svg-scale: 0;
        }

        .bw-morph-wrap.bw-radio input:checked + .bw-frame + .bw-inner {
            --svg-scale: 1;
        }

        .bw-morph-wrap input:focus-visible + .bw-frame {
            filter: drop-shadow(0 0 0.45rem rgba(37, 99, 235, 0.45));
        }

        .bw-morph-wrap input:disabled {
            cursor: not-allowed;
        }

        .bw-morph-wrap input:disabled + .bw-frame,
        .bw-morph-wrap input:disabled + .bw-frame + .bw-tick,
        .bw-morph-wrap input:disabled + .bw-frame + .bw-inner {
            opacity: .55;
        }

        /* Uiverse-inspired header pill animations */
        @keyframes border-glow-translate {
            0% { transform: translateX(-30%); opacity: .35; }
            100% { transform: translateX(30%); opacity: .85; }
        }

        @keyframes border-glow-scale {
            0% { transform: translateX(-50%) scaleY(.75); opacity: .25; }
            100% { transform: translateX(-50%) scaleY(1.15); opacity: .65; }
        }

        @keyframes star-rotate {
            0% { transform: rotate(-10deg); }
            100% { transform: rotate(380deg); }
        }

        @keyframes star-shine {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(.85); }
            100% { opacity: .55; transform: translate(-50%, -50%) scale(1.08); }
        }

        @media (prefers-reduced-motion: reduce) {
            .bw-association-pill,
            .bw-association-pill * {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="min-h-screen flex flex-col">
        <header class="relative isolate z-[2147483647]">
            <div class="glass">
                <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 hover:opacity-95" aria-label="Bwiser home">
                        <x-logo-shell size="md">
                            <x-brand-mark class="h-7 w-auto" />
                        </x-logo-shell>
                        <div>
                            <p class="brand-font text-lg font-semibold text-slate-900">Bwiser</p>
                            <p class="text-xs text-slate-500">Buy now, pay later for fuel</p>
                        </div>
                    </a>
                    <nav class="flex items-center gap-3 text-sm">
                        <span class="preprod-badge" aria-label="Pre-production environment">PRE-PRODUCTION</span>
                        <a
                            href="{{ route('developers.docs') }}"
                            class="inline-flex text-sm font-medium text-slate-600 hover:text-slate-900"
                            aria-label="Developers"
                        >
                            Developers
                        </a>
                        <!-- From Uiverse.io by Itskrish01 (adapted) -->
                        <button
                            type="button"
                            class="bw-association-pill group relative hidden md:inline-flex bg-neutral-200 rounded-full p-px overflow-hidden"
                            aria-label="By the fuel intercredit association"
                        >
                            <span class="absolute inset-0 rounded-full overflow-hidden">
                                <span class="inset-0 absolute pointer-events-none select-none">
                                    <span
                                        class="block -translate-x-1/2 -translate-y-1/3 size-24 blur-xl"
                                        style="background: linear-gradient(135deg, rgb(122, 105, 249), rgb(242, 99, 120), rgb(245, 131, 63));"
                                    ></span>
                                </span>
                            </span>

                            <span
                                class="inset-0 absolute pointer-events-none select-none"
                                style="animation: 10s ease-in-out 0s infinite alternate none running border-glow-translate;"
                            >
                                <span
                                    class="block z-0 h-full w-12 blur-xl -translate-x-1/2 rounded-full"
                                    style="animation: 10s ease-in-out 0s infinite alternate none running border-glow-scale;
                                           background: linear-gradient(135deg, rgb(122, 105, 249), rgb(242, 99, 120), rgb(245, 131, 63));"
                                ></span>
                            </span>

                            <span
                                class="flex items-center justify-center gap-1 relative z-[1] bg-neutral-50/90 rounded-full py-2 px-4 pl-2 w-full"
                            >
                                <span class="relative group-hover:scale-105 transition-transform group-hover:rotate-[360deg] duration-500">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="opacity-80"
                                        style="animation: 14s cubic-bezier(0.68, -0.55, 0.27, 1.55) 0s infinite alternate none running star-rotate;"
                                        aria-hidden="true"
                                        focusable="false"
                                    >
                                        <path
                                            d="M11.5268 2.29489C11.5706 2.20635 11.6383 2.13183 11.7223 2.07972C11.8062 2.02761 11.903 2 12.0018 2C12.1006 2 12.1974 2.02761 12.2813 2.07972C12.3653 2.13183 12.433 2.20635 12.4768 2.29489L14.7868 6.97389C14.939 7.28186 15.1636 7.5483 15.4414 7.75035C15.7192 7.95239 16.0419 8.08401 16.3818 8.13389L21.5478 8.88989C21.6457 8.90408 21.7376 8.94537 21.8133 9.00909C21.8889 9.07282 21.9452 9.15644 21.9758 9.2505C22.0064 9.34456 22.0101 9.4453 21.9864 9.54133C21.9627 9.63736 21.9126 9.72485 21.8418 9.79389L18.1058 13.4319C17.8594 13.672 17.6751 13.9684 17.5686 14.2955C17.4622 14.6227 17.4369 14.9708 17.4948 15.3099L18.3768 20.4499C18.3941 20.5477 18.3835 20.6485 18.3463 20.7406C18.3091 20.8327 18.2467 20.9125 18.1663 20.9709C18.086 21.0293 17.9908 21.0639 17.8917 21.0708C17.7926 21.0777 17.6935 21.0566 17.6058 21.0099L12.9878 18.5819C12.6835 18.4221 12.345 18.3386 12.0013 18.3386C11.6576 18.3386 11.3191 18.4221 11.0148 18.5819L6.3978 21.0099C6.31013 21.0563 6.2112 21.0772 6.11225 21.0701C6.0133 21.0631 5.91832 21.0285 5.83809 20.9701C5.75787 20.9118 5.69563 20.8321 5.65846 20.7401C5.62128 20.6482 5.61066 20.5476 5.6278 20.4499L6.5088 15.3109C6.567 14.9716 6.54178 14.6233 6.43534 14.2959C6.32889 13.9686 6.14441 13.672 5.8978 13.4319L2.1618 9.79489C2.09039 9.72593 2.03979 9.63829 2.01576 9.54197C1.99173 9.44565 1.99524 9.34451 2.02588 9.25008C2.05652 9.15566 2.11307 9.07174 2.18908 9.00788C2.26509 8.94402 2.3575 8.90279 2.4558 8.88889L7.6208 8.13389C7.96106 8.08439 8.28419 7.95295 8.56238 7.75088C8.84058 7.54881 9.0655 7.28216 9.2178 6.97389L11.5268 2.29489Z"
                                            fill="url(#paint0_linear_171_8212)"
                                            stroke="url(#paint1_linear_171_8212)"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        ></path>
                                        <defs>
                                            <linearGradient
                                                id="paint0_linear_171_8212"
                                                x1="-0.5"
                                                y1="9"
                                                x2="15.5"
                                                y2="-1.5"
                                                gradientUnits="userSpaceOnUse"
                                            >
                                                <stop stop-color="#7A69F9"></stop>
                                                <stop offset="0.575" stop-color="#F26378"></stop>
                                                <stop offset="1" stop-color="#F5833F"></stop>
                                            </linearGradient>
                                            <linearGradient
                                                id="paint1_linear_171_8212"
                                                x1="-0.5"
                                                y1="9"
                                                x2="15.5"
                                                y2="-1.5"
                                                gradientUnits="userSpaceOnUse"
                                            >
                                                <stop stop-color="#7A69F9"></stop>
                                                <stop offset="0.575" stop-color="#F26378"></stop>
                                                <stop offset="1" stop-color="#F5833F"></stop>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <span
                                        class="rounded-full size-11 absolute opacity-0 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 blur-lg"
                                        style="animation: 14s ease-in-out 0s infinite alternate none running star-shine;
                                               background: linear-gradient(135deg, rgb(59, 196, 242), rgb(122, 105, 249), rgb(242, 99, 120), rgb(245, 131, 63));"
                                        aria-hidden="true"
                                    ></span>
                                </span>
                                <span class="bg-gradient-to-b ml-1.5 from-neutral-950 to-neutral-950/50 bg-clip-text text-xs text-transparent group-hover:scale-105 transition transform-gpu">
                                    by the fuel intercredit association
                                </span>
                            </span>
                        </button>
	                        @auth
	                            <form method="POST" action="{{ route('logout') }}">
	                                @csrf
	                                <button type="submit" class="btn-ghost px-4 py-2 rounded-xl">Logout</button>
	                            </form>
	                        @else
                                <a
                                    href="{{ route('register') }}"
                                    class="btn-ghost px-4 py-2 rounded-xl"
                                    aria-label="Register"
                                >
                                    Register
                                </a>
	                            <a
	                                href="{{ Route::has('login') ? route('login') : '/login' }}"
	                                class="relative inline-block p-px font-semibold leading-6 text-white bg-gray-800 shadow-2xl cursor-pointer rounded-xl shadow-zinc-900 transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 group"
	                                aria-label="Login"
	                            >
	                                <span
	                                    class="absolute inset-0 rounded-xl bg-gradient-to-r from-teal-400 via-blue-500 to-purple-500 p-[2px] opacity-0 transition-opacity duration-500 group-hover:opacity-100"
	                                    aria-hidden="true"
	                                ></span>
	                                <span class="relative z-10 block px-6 py-3 rounded-xl bg-gray-950">
	                                    <span class="relative z-10 flex items-center space-x-2">
	                                        <span class="transition-all duration-500 group-hover:translate-x-1">Login</span>
	                                        <svg
	                                            class="w-6 h-6 transition-transform duration-500 group-hover:translate-x-1"
	                                            aria-hidden="true"
	                                            fill="currentColor"
	                                            viewBox="0 0 20 20"
	                                            xmlns="http://www.w3.org/2000/svg"
	                                        >
	                                            <path
	                                                clip-rule="evenodd"
	                                                d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
	                                                fill-rule="evenodd"
	                                            ></path>
	                                        </svg>
	                                    </span>
	                                </span>
	                            </a>
	                        @endauth
	                    </nav>
	                </div>
	            </div>
	        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 mt-16">
            <div class="max-w-6xl mx-auto px-6 py-8 text-sm text-slate-500">
                <div class="flex flex-col md:flex-row justify-between gap-4">
                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 hover:opacity-95" aria-label="Bwiser home">
                        <x-logo-shell size="sm">
                            <x-brand-mark class="h-5 w-auto" />
                        </x-logo-shell>
                        <span>© {{ date('Y') }} Bwiser. All rights reserved.</span>
                    </a>
                </div>
                <div class="flex items-center gap-6">
                    <span>Support: support@bwiser.co.za</span>
                    <span>Johannesburg, South Africa</span>
                </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-200/80">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Legal</p>
                    @include('partials.legal-links', ['class' => 'w-full flex flex-wrap items-center gap-2 text-sm text-slate-700'])
                </div>
            </div>
        </footer>
    </div>
    <script>
        (function initMorphControls() {
            const CHECKBOX_BASE = 'M3.5 8.49964C3.5 5.73822 5.73822 3.5 8.49964 3.5C10.3275 3.5 12.3499 3.5 14 3.5C15.6501 3.5 17.6725 3.5 19.5004 3.5C22.2618 3.5 24.5 5.73822 24.5 8.49964C24.5 10.3275 24.5 12.3499 24.5 14C24.5 15.6501 24.5 17.6725 24.5 19.5004C24.5 22.2618 22.2618 24.5 19.5004 24.5C17.6725 24.5 15.6501 24.5 14 24.5C12.3499 24.5 10.3275 24.5 8.49964 24.5C5.73822 24.5 3.5 22.2618 3.5 19.5004C3.5 17.6725 3.5 15.6501 3.5 14C3.5 12.3499 3.5 10.3275 3.5 8.49964Z';
            const CHECKBOX_PRESS = 'M3.76792 8.316C3.29262 5.68859 5.68858 3.29262 8.316 3.76792C10.2859 4.12429 12.3921 4.41667 14 4.41667C15.6079 4.41667 17.7141 4.12429 19.684 3.76792C22.3114 3.29262 24.7074 5.68858 24.2321 8.316C23.8757 10.2859 23.5833 12.3921 23.5833 14C23.5833 15.6079 23.8757 17.7141 24.2321 19.684C24.7074 22.3114 22.3114 24.7074 19.684 24.2321C17.7141 23.8757 15.6079 23.5833 14 23.5833C12.3921 23.5833 10.2859 23.8757 8.316 24.2321C5.68859 24.7074 3.29262 22.3114 3.76792 19.684C4.12429 17.7141 4.41667 15.6079 4.41667 14C4.41667 12.3921 4.12429 10.2859 3.76792 8.316Z';
            const RADIO_BASE = 'M24.5 14C24.5 19.799 19.799 24.5 14 24.5C8.20101 24.5 3.5 19.799 3.5 14C3.5 8.20101 8.20101 3.5 14 3.5C19.799 3.5 24.5 8.20101 24.5 14Z';
            const RADIO_PRESS = 'M25.5 14C25.5 19.799 19.799 23.5 14 23.5C8.20101 23.5 2.5 19.799 2.5 14C2.5 8.20101 8.20101 4.5 14 4.5C19.799 4.5 25.5 8.20101 25.5 14Z';
            const RADIO_BOUNCE = 'M23.5 14C23.5 19.799 19.799 25.5 14 25.5C8.20101 25.5 4.5 19.799 4.5 14C4.5 8.20101 8.20101 2.5 14 2.5C19.799 2.5 23.5 8.20101 23.5 14Z';
            const SHAPE_BASE = 'M18 9C18 13.9706 13.9706 18 9 18C4.02944 18 0 13.9706 0 9C0 4.02944 4.02944 0 9 0C13.9706 0 18 4.02944 18 9Z';
            const SHAPE_HOVER = 'M20 9C20 13.9706 13.9706 18 9 18C4.02944 18 0 13.9706 0 9C0 4.02944 4.02944 0 9 0C13.9706 0 20 5.02944 20 9Z';
            const SHAPE_WIDE = 'M36 9C36 15.9706 13.9706 18 9 18C4.02944 18 0 13.9706 0 9C0 4.02944 4.02944 0 9 0C13.9706 0 36 2.02944 36 9Z';
            const SHAPE_MID = 'M35.9954 9C35.9954 13.9706 31.9659 18 26.9954 18C22.0248 18 23.9954 12.9706 23.9954 9C23.9954 5.02944 22.0248 0 26.9954 0C31.9659 0 35.9954 4.02944 35.9954 9Z';
            const SHAPE_END = 'M36 9C36 13.9706 31.9706 18 27 18C22.0294 18 18 13.9706 18 9C18 4.02944 22.0294 0 27 0C31.9706 0 36 4.02944 36 9Z';

            const setPath = (path, value) => path && path.setAttribute('d', value);
            const createSvg = (className, viewBox, pathD) => {
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('viewBox', viewBox);
                if (className) svg.setAttribute('class', className);
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathD);
                svg.appendChild(path);
                return svg;
            };

            function enhanceInput(input) {
                if (!input || input.dataset.morphInit === '1') return;
                if (!['checkbox', 'radio'].includes(input.type)) return;
                if (input.closest('.bw-morph-switch')) return;
                if (input.closest('[data-no-morph]')) return;
                if (input.classList.contains('hidden') || input.classList.contains('sr-only')) return;

                const wrapper = document.createElement('span');
                wrapper.className = `bw-morph-wrap ${input.type === 'checkbox' ? 'bw-checkbox' : 'bw-radio'}`;
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);

                const frameSvg = createSvg('', '0 0 28 28', input.type === 'checkbox' ? CHECKBOX_BASE : RADIO_BASE);
                frameSvg.classList.add('bw-frame');
                wrapper.appendChild(frameSvg);

                if (input.type === 'checkbox') {
                    const tickSvg = createSvg('bw-tick', '0 0 12 10', 'M1.5 5.5L4.5 8.5L10.5 1.5');
                    wrapper.appendChild(tickSvg);
                } else {
                    const innerSvg = createSvg('bw-inner', '0 0 28 28', RADIO_BASE);
                    wrapper.appendChild(innerSvg);
                }

                const framePath = frameSvg.querySelector('path');
                const ownerLabel = input.closest('label');

                wrapper.addEventListener('pointerdown', () => {
                    if (input.disabled) return;
                    setPath(framePath, input.type === 'checkbox' ? CHECKBOX_PRESS : RADIO_PRESS);
                });

                wrapper.addEventListener('click', (event) => {
                    if (input.disabled) return;
                    if (!ownerLabel) {
                        event.preventDefault();
                        if (input.type === 'checkbox') {
                            input.checked = !input.checked;
                        } else if (!input.checked) {
                            input.checked = true;
                        }
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    setPath(framePath, input.type === 'checkbox' ? CHECKBOX_PRESS : RADIO_PRESS);
                    if (input.type === 'checkbox') {
                        setTimeout(() => setPath(framePath, CHECKBOX_BASE), 160);
                    } else {
                        setTimeout(() => setPath(framePath, RADIO_BOUNCE), 130);
                        setTimeout(() => setPath(framePath, RADIO_BASE), 410);
                    }
                });

                input.addEventListener('change', () => {
                    if (input.type === 'radio' && input.name) {
                        document.querySelectorAll(`input[type="radio"][name="${CSS.escape(input.name)}"]`).forEach((peer) => {
                            const peerPath = peer.closest('.bw-morph-wrap')?.querySelector('.bw-frame path');
                            if (peerPath) setPath(peerPath, RADIO_BASE);
                        });
                    }
                });

                input.dataset.morphInit = '1';
            }

            function mountSwitch(element) {
                if (!element || element.dataset.switchInit === '1') return;
                const input = element.querySelector('input[type="checkbox"]');
                const path = element.querySelector('path');
                if (!input || !path) return;
                element.dataset.switchInit = '1';
                if (input.disabled) element.dataset.disabled = '1';

                const morph = (shape) => path.setAttribute('d', shape);

                element.addEventListener('mouseenter', () => {
                    if (element.classList.contains('is-active')) return;
                    morph(SHAPE_HOVER);
                });

                element.addEventListener('mouseleave', () => {
                    if (element.classList.contains('is-active')) return;
                    morph(SHAPE_BASE);
                });

                element.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (input.disabled || element.classList.contains('is-active')) return;
                    element.classList.add('is-active');

                    morph(SHAPE_WIDE);
                    setTimeout(() => morph(SHAPE_MID), 130);
                    setTimeout(() => morph(SHAPE_END), 260);
                    setTimeout(() => {
                        input.checked = !input.checked;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        morph(SHAPE_BASE);
                        element.classList.remove('is-active');
                    }, 520);
                });
            }

            const mountAll = () => {
                document.querySelectorAll('.bw-morph-switch').forEach(mountSwitch);
                document.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(enhanceInput);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', mountAll);
            } else {
                mountAll();
            }

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (!(node instanceof Element)) return;
                        if (node.matches('.bw-morph-switch')) mountSwitch(node);
                        node.querySelectorAll?.('.bw-morph-switch').forEach(mountSwitch);
                        if (node.matches('input[type="checkbox"], input[type="radio"]')) enhanceInput(node);
                        node.querySelectorAll?.('input[type="checkbox"], input[type="radio"]').forEach(enhanceInput);
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        })();
    </script>
    <script>
        (function () {
            const currencyPattern = /^(.*?)(-?\d[\d,]*\.?\d*)(.*)$/;
            const currencyPrefixPattern = /^\s*(R|ZAR|KES|USD|\$|€|£)\s*[-\d]/i;

            function parseAmount(text) {
                const match = text.match(currencyPattern);
                if (!match) return null;
                const numberText = match[2];
                if (!numberText || !/[0-9]/.test(numberText)) return null;
                return {
                    prefix: match[1],
                    numberText,
                    suffix: match[3],
                };
            }

            function animateNumber(el, amountInfo, duration = 900) {
                const rawNumber = parseFloat(amountInfo.numberText.replace(/,/g, ''));
                if (!Number.isFinite(rawNumber)) return;

                const decimals = (amountInfo.numberText.split('.')[1] || '').length;
                const start = performance.now();
                const from = 0;
                const to = rawNumber;

                function tick(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = from + (to - from) * eased;
                    const formatted = current.toLocaleString(undefined, {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals,
                    });
                    el.textContent = `${amountInfo.prefix}${formatted}${amountInfo.suffix}`;
                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                }

                requestAnimationFrame(tick);
            }

            function collectCountupTargets(root) {
                const selector = 'p, span, h1, h2, h3, h4, h5, h6, td, th, li, strong, em';
                const candidates = Array.from(root.querySelectorAll(selector));
                return candidates.filter((el) => {
                    if (el.dataset.countupReady === '1') return false;
                    if (el.closest('[data-countup-skip]')) return false;
                    const explicit = el.hasAttribute('data-countup');
                    if (!explicit && el.children.length > 0) return false;
                    const text = el.textContent || '';
                    if (!explicit && !currencyPrefixPattern.test(text.trim())) return false;
                    const parsed = parseAmount(text.trim());
                    if (!parsed) return false;
                    el.dataset.countupReady = '1';
                    el.dataset.countupPrefix = parsed.prefix;
                    el.dataset.countupNumber = parsed.numberText;
                    el.dataset.countupSuffix = parsed.suffix;
                    return true;
                });
            }

            function initCountups() {
                const targets = collectCountupTargets(document.body);
                if (!targets.length) return;

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        const el = entry.target;
                        if (el.dataset.countupDone === '1') return;
                        const info = {
                            prefix: el.dataset.countupPrefix || '',
                            numberText: el.dataset.countupNumber || '',
                            suffix: el.dataset.countupSuffix || '',
                        };
                        el.dataset.countupDone = '1';
                        animateNumber(el, info);
                        observer.unobserve(el);
                    });
                }, { threshold: 0.25 });

                targets.forEach((el) => observer.observe(el));
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCountups);
            } else {
                initCountups();
            }
        })();
    </script>
    <script>
        (function initLiveValidation() {
            const forms = Array.from(document.querySelectorAll('form[data-live-validate]'));
            if (!forms.length) return;

            const sanitizeIdField = (field) => {
                if (!field || field.name !== 'id_number') return;
                const digits = (field.value || '').replace(/\D+/g, '').slice(0, 13);
                if (field.value !== digits) field.value = digits;
            };

            const clearFieldError = (field) => {
                if (!field) return;
                field.setCustomValidity('');
                const group = field.closest('div');
                const error = group ? group.querySelector('p.text-red-600') : null;
                if (error) error.remove();
            };

            forms.forEach((form) => {
                const fields = form.querySelectorAll('input, select, textarea');
                fields.forEach((field) => {
                    field.addEventListener('input', () => {
                        sanitizeIdField(field);
                        clearFieldError(field);
                    });
                    field.addEventListener('change', () => {
                        sanitizeIdField(field);
                        clearFieldError(field);
                    });
                });

                form.addEventListener('submit', () => {
                    form.querySelectorAll('input[name="id_number"]').forEach((field) => {
                        sanitizeIdField(field);
                    });
                });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
