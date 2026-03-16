<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Investor Portal')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --title-font: "BBH Bartle", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

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

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: var(--title-font);
        }
    </style>
    @php
        $hasViteAssets = is_file(public_path('hot')) || is_file(public_path('build/manifest.json'));
    @endphp
    @if($hasViteAssets)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/brand-logo.png') }}" alt="Bwiser" class="h-8 w-8 rounded-lg object-cover">
                    <div>
                        <p class="font-semibold">Bwiser Investor</p>
                        <p class="text-xs text-slate-500">@yield('page-description', 'Investor portal')</p>
                    </div>
                </div>
                <nav class="flex items-center gap-2">
                    @if(Route::has('investor.dashboard'))
                        <a href="{{ route('investor.dashboard') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('investor.dashboard') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">Dashboard</a>
                    @endif
                    @if(Route::has('investor.investments'))
                        <a href="{{ route('investor.investments') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('investor.investments*') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">Investments</a>
                    @endif
                    @if(Route::has('investor.opportunities'))
                        <a href="{{ route('investor.opportunities') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('investor.opportunities*') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">Opportunities</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Logout</button>
                    </form>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section class="max-w-7xl mx-auto px-6 pt-8">
                <h1 class="text-2xl md:text-3xl font-bold">@yield('page-title', 'Investor Dashboard')</h1>
                @if (session('success'))
                    <div class="mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                        {{ session('error') }}
                    </div>
                @endif
            </section>

            <section class="max-w-7xl mx-auto px-6 pt-6">
                @yield('stats')
            </section>

            <section class="max-w-7xl mx-auto px-0 md:px-6 pb-10">
                @yield('content')
            </section>
        </main>
    </div>
</body>
</html>
