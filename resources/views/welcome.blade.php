@extends('Layouts.app')

@section('title', 'Bwiser Fuel Buy Now Pay Later')
@section('meta_description', 'Bwiser is a South African fuel finance and payments platform for drivers, stations, vouchers, and settlements.')
@section('canonical', url('/'))
@php
    $welcomeOgImage = 'images/tsunkebwiser1.jpg';
    $welcomeOgImagePath = public_path($welcomeOgImage);
    $welcomeOgImageUrl = asset($welcomeOgImage);

    if (is_file($welcomeOgImagePath)) {
        $welcomeOgImageUrl .= '?v=' . filemtime($welcomeOgImagePath);
    }
@endphp

@section('og_image', $welcomeOgImageUrl)
@section('og_image_alt', 'Bwiser Fuel Buy Now Pay Later')

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                'name' => 'Bwiser Fuel Buy Now Pay Later',
                'url' => url('/'),
                'description' => 'Bwiser is a South African fuel finance and payments platform for drivers, stations, vouchers, and settlements.',
            ],
            [
                '@type' => 'MobileApplication',
                'name' => 'BWiser Driver App',
                'operatingSystem' => 'ANDROID',
                'applicationCategory' => 'BusinessApplication',
                'url' => 'https://bwiser.co.za',
                'installUrl' => 'https://play.google.com/store/apps/details?id=za.bwiser.driverapp',
                'downloadUrl' => 'https://play.google.com/store/apps/details?id=za.bwiser.driverapp',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

	@section('content')
	<section class="max-w-7xl mx-auto px-6 pt-16 pb-20">
	    <div class="glass welcome-hero-surface rounded-3xl p-8 md:p-12">
	        <div class="welcome-hero-image" aria-hidden="true"></div>
	        @php
	            $recentDrivers = collect((array) (($welcomeStats ?? [])['recent_drivers'] ?? []))->take(4);
	            $dashboardUrl = null;
	            if (auth()->check()) {
                $user = auth()->user();

                if ($user?->hasAnyRole(['super_admin', 'admin', 'employee'])) {
                    $dashboardUrl = route('employee.dashboard');
                } elseif ($user?->hasRole('merchant')) {
                    $dashboardUrl = route('merchant.dashboard');
                } elseif ($user?->hasRole('driver')) {
                    $dashboardUrl = route('driver.dashboard');
                } else {
                    $dashboardUrl = Route::has('login') ? route('login') : '/login';
                }
            }
        @endphp
        <div class="max-w-4xl relative z-[1]">
            <div class="min-w-0">
                <h1 class="brand-font text-4xl md:text-6xl font-semibold text-slate-900 mt-4 leading-tight">
                    <span class="shine">Fuel Infrastructure Finance and Voucher Payments,</span>
                    <br>
                    <span class="text-white italic">easy as padel</span>
                    <span class="hero-gradient-text block"><span class="inline-block rounded-full bg-white px-3 py-1" style="color: #2563eb;">Built for Real-Time Operations</span></span>
                </h1>
                <div class="mt-7 flex flex-wrap gap-3">
                    @auth
                        @if($dashboardUrl)
                            <a class="bw-dashboard-button" href="{{ $dashboardUrl }}">
                                <svg class="bw-dashboard-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M3 13.5h8V3H3v10.5zM13 21h8V10.5h-8V21zM3 21h8v-6H3v6zM13 3v6h8V3h-8z"></path>
                                </svg>
                                <span class="bw-dashboard-label">Go to Dashboard</span>
                                <span class="bw-dashboard-arrow" aria-hidden="true">›</span>
                            </a>
                        @endif
                    @endauth
                    @guest
                        <a class="super-button" href="{{ Route::has('login') ? route('login') : '/login' }}">
                            <span>Get Started</span>
                        </a>
                    @endguest
                    <a
                        class="playstore-button"
                        href="https://play.google.com/store/apps/details?id=za.bwiser.driverapp"
                        target="_blank"
                        rel="noopener"
                        aria-label="Get BWiser on Google Play"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="icon" viewBox="0 0 512 512" aria-hidden="true" focusable="false">
                            <path d="M99.617 8.057a50.191 50.191 0 00-38.815-6.713l230.932 230.933 74.846-74.846L99.617 8.057zM32.139 20.116c-6.441 8.563-10.148 19.077-10.148 30.199v411.358c0 11.123 3.708 21.636 10.148 30.199l235.877-235.877L32.139 20.116zM464.261 212.087l-67.266-37.637-81.544 81.544 81.548 81.548 67.273-37.64c16.117-9.03 25.738-25.442 25.738-43.908s-9.621-34.877-25.749-43.907zM291.733 279.711L60.815 510.629c3.786.891 7.639 1.371 11.492 1.371a50.275 50.275 0 0027.31-8.07l266.965-149.372-74.849-74.847z"></path>
                        </svg>
                        <span class="texts">
                            <span class="text-1">GET IT ON</span>
                            <span class="text-2">Google Play</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        $popularBrands = collect([
            ['name' => 'Astron', 'slug' => 'astron-energy'],
            ['name' => 'BP', 'slug' => 'bp-southern-africa'],
            ['name' => 'Engen', 'slug' => 'engen'],
            ['name' => 'Sasol', 'slug' => 'sasol'],
            ['name' => 'Shell', 'slug' => 'shell-sa'],
            ['name' => 'Total Energies', 'slug' => 'totalenergies'],
        ]);
    @endphp
    <div class="glass rounded-2xl p-6 mt-8">
        <p class="text-xs uppercase tracking-[1px] text-blue-600">Trusted Retail Network</p>
        <div class="trusted-ticker-wrap mt-4">
            <div class="trusted-ticker-edge trusted-ticker-edge--left" aria-hidden="true"></div>
            <div class="trusted-ticker-edge trusted-ticker-edge--right" aria-hidden="true"></div>
            <div class="trusted-ticker-track">
                @for($i = 0; $i < 2; $i++)
                    @foreach($popularBrands as $brand)
                        <div class="trusted-brand-pill">
                            <img src="{{ asset('images/brands/' . $brand['slug'] . '.png') }}" alt="{{ $brand['name'] }} logo" class="h-6 w-6 object-contain" loading="lazy">
                            <span class="text-sm font-medium text-slate-700">{{ $brand['name'] }}</span>
                        </div>
                    @endforeach
                @endfor
            </div>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8 hidden">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <div class="relative">
	                    <img
	                        src="{{ asset('images/shopping.jpg') }}"
	                        alt="Shopping"
	                        class="float-left block h-40 md:h-44 lg:h-48 w-auto object-contain"
	                        loading="lazy"
	                    >
                </div>
            </div>
            <div>
		                @php
		                    $mobilityPartners = [
		                        ['name' => 'Uber', 'path' => 'images/driver-platforms/uber.svg'],
		                        ['name' => 'Uber Eats', 'path' => 'images/driver-platforms/uber-eats.svg'],
		                        ['name' => 'inDrive', 'path' => 'images/driver-platforms/indrive.png'],
		                        ['name' => 'Takealot', 'path' => 'images/driver-platforms/takealot.png'],
		                        ['name' => 'Mr D', 'path' => 'images/driver-platforms/mrd.png'],
		                        ['name' => 'Sixty60', 'path' => 'images/driver-platforms/sixty60.png'],
		                    ];

		                    $mrdSpecialCandidates = [
		                        'images/mrd-special.webp',
		                        'images/mrd-special.jpg',
		                        'images/mrd-special.png',
		                    ];
		                    $mrdSpecialRelPath = null;
		                    foreach ($mrdSpecialCandidates as $candidate) {
		                        if (is_file(public_path($candidate))) {
		                            $mrdSpecialRelPath = $candidate;
		                            break;
		                        }
		                    }
		                    $mrdSpecialUrl = null;
		                    if ($mrdSpecialRelPath) {
		                        $abs = public_path($mrdSpecialRelPath);
		                        $mrdSpecialUrl = asset($mrdSpecialRelPath) . '?v=' . filemtime($abs);
		                    }

		                    // Bottom row of the "Place Order Here" grid should show pay options (p1-p3),
		                    // instead of repeating the first mobility logo (Uber).
		                    $payPartners = [
		                        ['name' => 'Apple Pay', 'path' => 'images/applepay.svg'],
		                        ['name' => 'GPay', 'path' => 'images/p2.svg'],
		                        ['name' => 'Diners Club', 'path' => 'images/p1.svg'],
	                    ];

	                    $gridPartners = collect(array_merge($mobilityPartners, $payPartners))->take(9)->values();
		                @endphp

		                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
		                    <div class="flex items-center justify-center lg:justify-start w-full lg:w-auto">
		                        <ul class="bw-mrd-cards" aria-label="Mr D special">
		                            <li>
		                                <a href="javascript:void(0)" class="bw-mrd-card" aria-label="Mr D special">
		                                    <span class="bw-mrd-card__loader" aria-hidden="true"></span>
		                                    <img
		                                        src="{{ $mrdSpecialUrl ?: asset('images/driver-platforms/mrd.png') }}"
		                                        class="bw-mrd-card__image"
		                                        alt="Mr D special"
		                                        data-bw-mrd-img
		                                        loading="lazy"
		                                    />
		                                    <div class="bw-mrd-card__overlay">
		                                        <div class="bw-mrd-card__header">
		                                            <svg class="bw-mrd-card__arc" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
		                                                <path />
		                                            </svg>
		                                            <img class="bw-mrd-card__thumb" src="{{ asset('images/driver-platforms/mrd.png') }}" alt="Mr D logo" loading="lazy" />
		                                            <div class="bw-mrd-card__header-text">
		                                                <h3 class="bw-mrd-card__title">Mr D Special</h3>
		                                                <span class="bw-mrd-card__status">Bwiser shoppers: Pay in 4</span>
		                                            </div>
		                                        </div>
		                                        <p class="bw-mrd-card__description">
		                                            Bwiser BNPL lets shoppers split checkout into 4 repayments with participating drivers.
		                                        </p>
		                                    </div>
		                                </a>
		                            </li>
		                        </ul>
		                    </div>

		                    <div class="flex justify-center lg:justify-end">
		                        <button
		                            class="bw-order-grid"
	                            type="button"
	                            data-coming-soon-open
	                            aria-haspopup="dialog"
	                            aria-controls="comingSoonModal"
	                            aria-label="Place order here, coming soon"
	                        >
	                            <span class="bw-order-grid__cards" aria-hidden="true">
	                                @foreach($gridPartners as $p)
	                                    <span class="bw-order-grid__card">
	                                        <img
                                            src="{{ asset($p['path']) }}"
                                            alt=""
                                            class="bw-order-grid__logo"
                                            loading="lazy"
                                        >
                                    </span>
                                @endforeach
                            </span>
	                            <span class="bw-order-grid__text">
	                                PLACE<br><br>ORDER<br><br>HERE
	                            </span>
	                            <span class="bw-order-grid__back" aria-hidden="true"></span>
	                        </button>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>

    <div class="grid gap-8 lg:grid-cols-2 mt-8 items-stretch">
        <div class="glass rounded-2xl p-6">
            <div class="flex items-start gap-5">
                <div class="slack-loader-shell" aria-hidden="true">
                    <div class="slack-loader"></div>
                </div>
                <div class="flex-1">
                    <h3 class="brand-font text-xl md:text-2xl font-semibold text-slate-900">Request your place in the Bwiser merchant Slack</h3>
                    <p class="text-sm text-slate-600 mt-2 max-w-2xl">
                        Merchants can request early access to the Bwiser Slack workspace for rollout updates, onboarding help, and support coordination.
                    </p>
                    <div class="mt-5">
                        <a
                            href="mailto:support@bwiser.co.za?subject=Slack%20Access%20Request&body=Hi%20Bwiser%2C%20I%20would%20like%20merchant%20Slack%20access.%0A%0ABusiness%20name%3A%0AContact%20name%3A%0AContact%20number%3A%0A"
                            class="super-button whitespace-nowrap"
                        >
                            <span>Request Slack Access</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden">
            <img
                src="{{ asset('images/bench_sign.png') }}"
                alt="Bwiser preview"
                class="block w-full h-full object-cover rounded-2xl"
                loading="lazy"
            >
        </div>
    </div>

    <div class="grid gap-8 md:grid-cols-2 mt-8 items-stretch">
        <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden welcome-tween-card">
            <img
                src="{{ asset('images/tsunkebwiser1.jpg') }}"
                alt="Bwiser preview"
                class="welcome-tween-image is-in rounded-2xl"
                loading="lazy"
                data-welcome-tween="slide-in"
                onerror="this.classList.add('is-in'); this.style.opacity='1'; this.style.transform='none';"
            >
        </div>

        <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden welcome-tween-card">
            <img
                src="{{ asset('images/box.png') }}"
                alt="Bwiser preview"
                class="welcome-tween-image is-in rounded-2xl"
                loading="lazy"
                data-welcome-tween="slide-in"
                onerror="this.classList.add('is-in'); this.style.opacity='1'; this.style.transform='none';"
            >
        </div>
    </div>

    @php
        $stats = (array) ($welcomeStats ?? []);
        $totals = (array) ($stats['totals'] ?? []);
        $growth = (array) ($stats['voucher_growth'] ?? []);
        $series = (array) ($stats['series'] ?? []);
        $totalVouchers = (int) ($totals['vouchers'] ?? 0);
        $voucherPct = (int) ($growth['pct'] ?? 0);
        $voucherPctAbs = abs($voucherPct);
        $voucherUp = $voucherPct >= 0;
        $showVoucherPct = (bool) ($growth['show_pct'] ?? false);
    @endphp

    <div class="glass rounded-2xl p-3 md:p-4 mt-8 overflow-hidden">
        <div class="welcome-video" data-welcome-video data-poster-seconds="22">
            <img class="welcome-video__poster" alt="Badserve preview" loading="lazy">
            <video
                class="welcome-video__media"
                muted
                loop
                playsinline
                preload="metadata"
            >
                <source src="{{ asset('images/badserve.mp4') }}" type="video/mp4">
            </video>
            <button class="welcome-video__play" type="button" aria-label="Play video">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M8 5v14l11-7z" fill="currentColor"></path>
                </svg>
            </button>
            <button class="welcome-video__sound" type="button" aria-label="Unmute video" title="Unmute">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M11 5L6.8 8.5H4a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.8L11 19V5z" fill="currentColor"></path>
                    <path class="welcome-video__sound-waves" d="M14.5 8.5a1 1 0 0 1 1.4 0 5 5 0 0 1 0 7.1 1 1 0 1 1-1.4-1.4 3 3 0 0 0 0-4.2 1 1 0 0 1 0-1.5z" fill="currentColor"></path>
                    <path class="welcome-video__sound-waves" d="M16.8 6.2a1 1 0 0 1 1.4 0 8 8 0 0 1 0 11.3 1 1 0 1 1-1.4-1.4 6 6 0 0 0 0-8.5 1 1 0 0 1 0-1.4z" fill="currentColor"></path>
                    <path class="welcome-video__sound-mute" d="M20 9l-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"></path>
                    <path class="welcome-video__sound-mute" d="M14 9l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"></path>
                </svg>
            </button>
            <div class="welcome-video__loader" aria-hidden="true"></div>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-2 mt-8 items-start">
        <div class="glass rounded-2xl p-6 overflow-hidden">
            <p class="text-xs uppercase tracking-[1px] text-blue-600 mb-4">Latest Drivers</p>
            <div class="welcome-driver-stack-wrap">
                <ul class="welcome-driver-stack" aria-label="Last 4 drivers">
                    @forelse($recentDrivers as $index => $driver)
                        <li style="--i: {{ $index + 1 }};">
                            <div class="welcome-driver-avatar" aria-hidden="true">
                                <img
                                    src="{{ asset($driver['platform_logo_path'] ?? ('images/driver-platforms/' . ($driver['platform_logo'] ?? 'uber.svg'))) }}"
                                    alt="{{ $driver['platform_name'] ?? 'Driver platform' }}"
                                    class="welcome-driver-avatar-logo"
                                    loading="lazy"
                                >
                            </div>
                            <div class="content">
                                <h3>{{ $driver['name'] }}</h3>
                                <p>{{ $driver['name'] }} is now driving wiser</p>
                            </div>
                        </li>
                    @empty
                        @foreach([
                            ['name' => 'Aphiwe Dlamini', 'initials' => 'AD'],
                            ['name' => 'Naledi Mokoena', 'initials' => 'NM'],
                            ['name' => 'Thabo Maseko', 'initials' => 'TM'],
                            ['name' => 'Lerato Nkosi', 'initials' => 'LN'],
                        ] as $index => $driver)
                            <li style="--i: {{ $index + 1 }};">
                                @php
                                    $fallbackLogos = ['sixty60.png', 'uber-eats.svg', 'uber.svg', 'indrive.png'];
                                    $fallbackLogo = $fallbackLogos[$index % count($fallbackLogos)];
                                @endphp
                                <div class="welcome-driver-avatar" aria-hidden="true">
                                    <img
                                        src="{{ asset('images/driver-platforms/' . $fallbackLogo) }}"
                                        alt="Driver platform"
                                        class="welcome-driver-avatar-logo"
                                        loading="lazy"
                                    >
                                </div>
                                <div class="content">
                                    <h3>{{ $driver['name'] }}</h3>
                                    <p>{{ $driver['name'] }} is now driving wiser</p>
                                </div>
                            </li>
                        @endforeach
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[1px] text-blue-600">Site Stats</p>
                    <h3 class="brand-font leading-5 text-base md:text-xl font-bold text-slate-900 mt-2">Operations Overview</h3>
                </div>
                <div class="flex items-center justify-between lg:justify-start mt-2 md:mt-4 lg:mt-0">
                    <div class="flex items-center">
                        <button type="button" class="welcome-stats-btn welcome-stats-btn--ghost" data-series="drivers">Drivers</button>
                        <button type="button" class="welcome-stats-btn welcome-stats-btn--active" data-series="vouchers">Vouchers</button>
                    </div>
                    <div class="lg:ml-6">
                        <div class="bg-slate-100 ease-in duration-150 hover:bg-slate-200 pb-2 pt-1 px-3 rounded-sm">
                            <span class="text-xs text-slate-600">Last 12 months</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs uppercase tracking-[1px] text-slate-500">Total vouchers</p>
                    <div class="flex items-end mt-3">
                        <h3 class="text-blue-600 leading-5 text-xl md:text-3xl font-semibold">{{ number_format($totalVouchers) }}</h3>
                        @if($showVoucherPct)
                            <div class="flex items-center md:ml-4 ml-2 {{ $voucherUp ? 'text-emerald-700' : 'text-rose-700' }}">
                                <p class="text-xs md:text-base font-semibold">{{ $voucherUp ? '+' : '-' }}{{ $voucherPctAbs }}%</p>
                                <svg role="img" class="ml-1" aria-label="trend" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                    <path d="M6 2.5V9.5" stroke="currentColor" stroke-width="0.75" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M8 4.5L6 2.5" stroke="currentColor" stroke-width="0.75" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M4 4.5L6 2.5" stroke="currentColor" stroke-width="0.75" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-2">30-day change vs previous 30 days.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs uppercase tracking-[1px] text-slate-500">Drivers</p>
                    <p class="text-2xl md:text-3xl font-semibold text-slate-900 mt-3">{{ number_format((int) ($totals['drivers'] ?? 0)) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Registered drivers on the network.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs uppercase tracking-[1px] text-slate-500">Stations</p>
                    <p class="text-2xl md:text-3xl font-semibold text-slate-900 mt-3">{{ number_format((int) ($totals['stations'] ?? 0)) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Fuel stations available for redemption.</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
                <canvas
                    id="welcomeStatsChart"
                    height="120"
                    role="img"
                    aria-label="Activity chart"
                    data-labels='@json((array) ($series["labels"] ?? []))'
                    data-series-vouchers='@json((array) ($series["vouchers"] ?? []))'
                    data-series-drivers='@json((array) ($series["drivers"] ?? []))'
                ></canvas>
            </div>
        </div>
    </div>

    @php
        $groceriesImages = collect(glob(public_path('images/groceries*')) ?: [])
            ->sort()
            ->map(fn ($path) => asset('images/'.basename($path)))
            ->values();
    @endphp
    <div class="voucher-split-card mt-5">
        <div class="voucher-split-card__grid">
            <div class="voucher-split-card__media">
                <div class="voucher-split-slider" data-voucher-slider>
                    @foreach($groceriesImages as $index => $image)
                        <img
                            src="{{ $image }}"
                            alt="Groceries voucher preview {{ $index + 1 }}"
                            class="voucher-split-slide{{ $index === 0 ? ' is-active' : '' }}"
                            data-slide
                            loading="lazy"
                        >
                    @endforeach
                </div>
            </div>
            <div class="voucher-split-card__content">
                <p class="voucher-split-card__eyebrow">Voucher Split</p>
                <h3 class="voucher-split-card__title">How Bwiser splits every voucher between fuel and kiosk value</h3>
                <div class="voucher-split-card__body">
                    <p>Bwiser treats voucher design as an operational tool, not just a payment token. When a voucher is issued, the platform can divide that value into two coordinated balances: one balance for fuel and one balance for kiosk use. This matters because drivers, merchants, and funding partners do not experience value in a single flat way. Fuel is the core operating requirement, but convenience spend still shapes the real economics of a station visit. By structuring the voucher into defined buckets, Bwiser makes the product more flexible without reducing control. The result is a system that feels practical for drivers, useful for merchants, and auditable for finance teams.</p>
                    <div class="voucher-split-card__break" aria-hidden="true"></div>
                </div>
            </div>
        </div>
    </div>

</section>

<div id="comingSoonModal" class="hidden fixed inset-0 z-[9999] items-center justify-center p-4" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="absolute inset-0 bg-black/55 backdrop-blur-sm" data-coming-soon-close></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/10">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Bwiser</p>
                <h3 class="mt-2 text-xl font-bold text-slate-900">Coming soon</h3>
            </div>
            <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-coming-soon-close>
                Close
            </button>
        </div>
        <p class="mt-3 text-sm text-slate-600">
            Ordering is launching soon. This button will open the marketplace once it is live.
        </p>
        <div class="mt-6 flex justify-end">
            <button type="button" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" data-coming-soon-close>
                OK
            </button>
        </div>
    </div>
</div>

<div id="cookieConsentBar" class="cookie-bar hidden" role="dialog" aria-live="polite" aria-label="Cookie consent">
	    <div class="cookie-bar__inner">
	        <div class="cookie-bar__copy">
	            <p class="cookie-bar__title">We use cookies</p>
            <p class="cookie-bar__text">
                We use essential cookies to keep BWiser secure and working properly. By continuing, you accept cookies used for core
                platform functionality.
            </p>
        </div>
        <div class="cookie-bar__actions">
            <button type="button" id="acceptCookiesBtn" class="cookie-bar__button" onclick="return window.bwiserAcceptCookies && window.bwiserAcceptCookies();">
                Accept Cookies
            </button>
        </div>
    </div>
</div>

	<style>
	    .welcome-hero-surface {
	        position: relative;
	        overflow: hidden;
	        background: linear-gradient(135deg, rgba(255, 251, 235, 0.78), rgba(239, 246, 255, 0.82));
	        border-color: rgba(148, 163, 184, 0.2);
	        box-shadow: 0 24px 48px -34px rgba(148, 163, 184, 0.45);
	    }

	    .welcome-hero-image {
	        position: absolute;
	        inset: 0;
	        pointer-events: none;
	    }

	    .welcome-hero-image {
	        background-image: url("{{ asset('images/tennis.jpg') }}");
	        background-position: center;
	        background-size: cover;
	        opacity: 0.9;
	    }

	    /* Shine headline */
	    .shine {
	        display: inline-block;
	        color: #efefef;
	    }

	    @supports ((-webkit-background-clip: text) or (background-clip: text)) {
	        .shine {
	            background-color: #efefef;
	            background-image: linear-gradient(
	                -40deg,
	                transparent 0%,
	                transparent 40%,
	                #020DFF 50%,
	                transparent 60%,
	                transparent 100%
	            );
	            background-repeat: no-repeat;
	            background-size: 56px 100%;
	            background-position: -200px 0;
	            -webkit-background-clip: text;
	            background-clip: text;
	            color: transparent;
	            -webkit-text-fill-color: transparent;
	            animation: zezzz 5s infinite;
	        }
	    }

	    @keyframes zezzz {
	        0%,
	        10% {
	            background-position: -200px 0;
	        }
	        20% {
	            background-position: 0 0;
	        }
	        100% {
	            background-position: calc(100% + 200px) 0;
	        }
	    }

	    @media (prefers-reduced-motion: reduce) {
	        .shine {
	            animation: none;
	            background-position: 0 0;
	        }
	    }

    .super-button {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 14px 28px;
        background: linear-gradient(145deg, #0f0f0f, #1c1c1c);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 100px;
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.5px;
        cursor: pointer;
        overflow: hidden;
        transition: all 0.4s ease-in-out;
        box-shadow: 0 0 20px rgba(0, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        z-index: 1;
    }

    .super-button::before {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg, #00ffff, #ff00ff, #00ffff);
        animation: rotate 4s linear infinite;
        z-index: -2;
    }

    .super-button::after {
        content: "";
        position: absolute;
        inset: 2px;
        background: #1d4ed8;
        border-radius: inherit;
        z-index: -1;
    }

    .super-button:hover {
        transform: scale(1.05);
        box-shadow: 0 0 40px rgba(0, 255, 255, 0.2);
    }

    .playstore-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #000;
        border-radius: 9999px;
        background-color: rgba(0, 0, 0, 1);
        padding: 0.625rem 1.5rem;
        text-align: center;
        color: rgba(255, 255, 255, 1);
        outline: 0;
        transition: all .2s ease;
        text-decoration: none;
    }

    .playstore-button:hover {
        background-color: transparent;
        color: rgba(0, 0, 0, 1);
    }

	    .playstore-button .icon {
	        height: 1.5rem;
	        width: 1.5rem;
	    }

	    /* Mr D Special hover-reveal card (scoped). */
	    .bw-mrd-cards {
	        --surface-color: #ffffff;
	        --curve: 40;
	        display: grid;
	        grid-template-columns: 1fr;
	        gap: 0;
	        margin: 0;
	        padding: 0;
	        list-style-type: none;
	        width: min(26rem, 100%);
	    }

	    .bw-mrd-card {
	        position: relative;
	        display: block;
	        height: 12.8em;
	        border-radius: calc(var(--curve) * 1px);
	        overflow: hidden;
	        text-decoration: none;
	        box-shadow: 0 18px 42px -30px rgba(15, 23, 42, 0.7);
	    }

	    .bw-mrd-card__loader {
	        position: absolute;
	        inset: 0;
	        z-index: 0;
	        background:
	            linear-gradient(110deg, rgba(226, 232, 240, 0.25) 8%, rgba(148, 163, 184, 0.25) 18%, rgba(226, 232, 240, 0.25) 33%),
	            linear-gradient(145deg, rgba(37, 99, 235, 0.25), rgba(217, 70, 239, 0.18), rgba(244, 114, 182, 0.18));
	        background-size: 250% 100%, 100% 100%;
	        animation: bw-mrd-shimmer 1.25s infinite linear;
	        opacity: 1;
	        transition: opacity 0.35s ease;
	    }

	    .bw-mrd-card__image {
	        width: 100%;
	        height: 100%;
	        object-fit: cover;
	        display: block;
	        position: relative;
	        z-index: 1;
	        opacity: 0;
	        filter: blur(16px) saturate(1.05);
	        transform: scale(1.02);
	        transition: opacity 0.45s ease, filter 0.55s ease, transform 0.55s ease;
	    }

	    .bw-mrd-card__overlay {
	        position: absolute;
	        bottom: 0;
	        left: 0;
	        right: 0;
	        z-index: 1;
	        border-radius: calc(var(--curve) * 1px);
	        background-color: rgba(255, 255, 255, 0.96);
	        transform: translateY(100%);
	        transition: 0.2s ease-in-out;
	    }

	    .bw-mrd-card.is-loaded .bw-mrd-card__image {
	        opacity: 1;
	        filter: none;
	        transform: none;
	    }

	    .bw-mrd-card.is-loaded .bw-mrd-card__loader {
	        opacity: 0;
	        animation: none;
	    }

	    .bw-mrd-card:hover .bw-mrd-card__overlay {
	        transform: translateY(0);
	    }

	    .bw-mrd-card__header {
	        position: relative;
	        display: flex;
	        align-items: center;
	        gap: 1.25em;
	        padding: 1.25em 1.25em 1em;
	        border-radius: calc(var(--curve) * 1px) 0 0 0;
	        background-color: rgba(255, 255, 255, 0.96);
	        transform: translateY(-100%);
	        transition: 0.2s ease-in-out;
	    }

	    .bw-mrd-card__arc {
	        width: 80px;
	        height: 80px;
	        position: absolute;
	        bottom: 100%;
	        right: 0;
	        z-index: 1;
	    }

	    .bw-mrd-card__arc path {
	        fill: rgba(255, 255, 255, 0.96);
	        d: path("M 40 80 c 22 0 40 -22 40 -40 v 40 Z");
	    }

	    .bw-mrd-card:hover .bw-mrd-card__header {
	        transform: translateY(0);
	    }

	    .bw-mrd-card__thumb {
	        flex-shrink: 0;
	        width: 46px;
	        height: 46px;
	        border-radius: 9999px;
	        background: #fff;
	        object-fit: contain;
	        padding: 8px;
	        border: 1px solid rgba(226, 232, 240, 0.9);
	    }

	    .bw-mrd-card__title {
	        font-size: 1em;
	        margin: 0 0 0.25em;
	        color: #0f172a;
	        font-weight: 800;
	    }

	    .bw-mrd-card__status {
	        font-size: 0.85em;
	        color: rgba(100, 116, 139, 0.9);
	        font-weight: 700;
	    }

	    .bw-mrd-card__description {
	        padding: 0 1.25em 1.25em;
	        margin: 0;
	        color: rgba(100, 116, 139, 0.92);
	        display: -webkit-box;
	        -webkit-box-orient: vertical;
	        -webkit-line-clamp: 3;
	        overflow: hidden;
	    }

	    @media (hover: none) {
	        .bw-mrd-card__overlay {
	            transform: translateY(0);
	        }
	        .bw-mrd-card__header {
	            transform: translateY(0);
	        }
	    }

	    @keyframes bw-mrd-shimmer {
	        0% { background-position: 100% 0, 0 0; }
	        100% { background-position: -100% 0, 0 0; }
	    }

		    .bw-order-grid {
		        position: relative;
		        display: inline-flex;
	        align-items: center;
	        justify-content: center;
	        width: 14em;
	        height: 14em;
	        text-decoration: none;
	        border: 0;
	        background: transparent;
	        padding: 0;
	        cursor: pointer;
	        font: inherit;
	        border-radius: 14px;
	        outline: none;
	        -webkit-tap-highlight-color: transparent;
	    }

    .bw-order-grid:focus-visible {
        box-shadow: 0 0 0 4px rgba(2, 13, 255, 0.25);
    }

    .bw-order-grid__back {
        position: absolute;
        top: 50%;
        left: 50%;
        border-radius: 12px;
        transform: translate(-50%, -50%) rotate(90deg);
        width: 11em;
        height: 11em;
        background: linear-gradient(270deg, #020DFF, #cc39a4, #ffb5d2);
        z-index: 0;
        box-shadow: inset 0 0 180px 5px rgba(255, 255, 255, 0.9);
    }

    .bw-order-grid__cards {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        width: 14em;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    .bw-order-grid__card {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-top-left-radius: 10px;
        transition: 0.4s ease-in-out, 0.2s background-color ease-in-out, 0.2s background-image ease-in-out;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(5px);
        border: 1px solid transparent;
        -webkit-backdrop-filter: blur(5px);
    }

    .bw-order-grid__logo {
        width: 34px;
        height: 34px;
        object-fit: contain;
        opacity: 0;
        transform: scale(0.96);
        transition: 0.2s ease-in-out;
        filter: drop-shadow(0 8px 14px rgba(15, 23, 42, 0.15));
    }

    .bw-order-grid__card:nth-child(2),
    .bw-order-grid__card:nth-child(4),
    .bw-order-grid__card:nth-child(5),
    .bw-order-grid__card:nth-child(6),
    .bw-order-grid__card:nth-child(8) {
        border-radius: 0;
    }

    .bw-order-grid__card:nth-child(3) {
        border-top-right-radius: 10px;
        border-top-left-radius: 0;
    }

    .bw-order-grid__card:nth-child(7) {
        border-bottom-left-radius: 10px;
        border-top-left-radius: 0;
    }

    .bw-order-grid__card:nth-child(9) {
        border-bottom-right-radius: 10px;
        border-top-left-radius: 0;
    }

    .bw-order-grid__text {
        position: absolute;
        font-size: 0.72em;
        transition: 0.4s ease-in-out;
        color: #0f172a;
        text-align: center;
        font-weight: 800;
        letter-spacing: 0.33em;
        z-index: 2;
        text-transform: uppercase;
        line-height: 1.1;
        padding-left: 0.33em;
    }

    .bw-order-grid:hover .bw-order-grid__card {
        margin: 0.2em;
        border-radius: 10px;
        box-shadow: 0 4px 30px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.24);
    }

    .bw-order-grid:hover .bw-order-grid__logo {
        opacity: 1;
        transform: scale(1);
    }

    .bw-order-grid:hover .bw-order-grid__text {
        opacity: 0;
    }

    .bw-order-grid:hover .bw-order-grid__back {
        opacity: 0;
    }

    .bw-order-grid:focus .bw-order-grid__card,
    .bw-order-grid:active .bw-order-grid__card {
        margin: 0.2em;
        border-radius: 10px;
        box-shadow: 0 4px 30px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.24);
    }

    .bw-order-grid:focus .bw-order-grid__logo,
    .bw-order-grid:active .bw-order-grid__logo {
        opacity: 1;
        transform: scale(1);
    }

    .bw-order-grid:focus .bw-order-grid__text,
    .bw-order-grid:active .bw-order-grid__text {
        opacity: 0;
    }

    .bw-order-grid:focus .bw-order-grid__back,
    .bw-order-grid:active .bw-order-grid__back {
        opacity: 0;
    }

    @media (prefers-reduced-motion: reduce) {
        .bw-order-grid__card,
        .bw-order-grid__logo,
        .bw-order-grid__text,
        .bw-order-grid__back {
            transition: none !important;
        }
    }

    .playstore-button .texts {
        margin-left: 1rem;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        line-height: 1;
    }

    .playstore-button .text-1 {
        margin-bottom: 0.25rem;
        font-size: 0.75rem;
        line-height: 1rem;
    }

    .playstore-button .text-2 {
        font-weight: 600;
    }

    .bw-dashboard-button {
        width: min(260px, 100%);
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        padding: 0 18px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.9));
        color: rgba(255, 255, 255, 0.98);
        text-decoration: none;
        position: relative;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
        backdrop-filter: blur(10px);
    }

    .bw-dashboard-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.24);
    }

    .bw-dashboard-button:focus-visible {
        outline: 3px solid rgba(37, 99, 235, 0.45);
        outline-offset: 2px;
    }

    .bw-dashboard-icon {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        color: rgb(96, 165, 250);
        fill: currentColor;
    }

    .bw-dashboard-label {
        font-weight: 700;
        font-size: 14px;
        letter-spacing: 0.2px;
        white-space: nowrap;
    }

    .bw-dashboard-arrow {
        position: absolute;
        right: 12px;
        width: 28px;
        height: 100%;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.85;
    }

    .bw-dashboard-button:hover .bw-dashboard-arrow {
        animation: bw-slide-right .6s ease-out both;
    }

    @keyframes bw-slide-right {
        0% {
            transform: translateX(-10px);
            opacity: 0;
        }

        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .bw-dashboard-button:active {
        transform: translate(1px, 1px);
    }

    .slack-loader-shell {
        position: relative;
        width: 88px;
        height: 88px;
        border-radius: 24px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.9));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 18px 36px rgba(15, 23, 42, 0.18);
        flex: 0 0 88px;
    }

    .slack-loader {
        position: absolute;
        top: calc(50% - 1.25em);
        left: calc(50% - 1.25em);
        width: 2.5em;
        height: 2.5em;
        transform: rotate(165deg);
    }

    .slack-loader::before,
    .slack-loader::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        display: block;
        width: 0.5em;
        height: 0.5em;
        border-radius: 0.25em;
        transform: translate(-50%, -50%);
    }

    .slack-loader::before {
        animation: slack-loader-before 2s infinite;
    }

    .slack-loader::after {
        animation: slack-loader-after 2s infinite;
    }

    @keyframes slack-loader-before {
        0% {
            width: 0.5em;
            box-shadow: 1em -0.5em rgba(225, 20, 98, 0.75), -1em 0.5em rgba(111, 202, 220, 0.75);
        }

        35% {
            width: 2.5em;
            box-shadow: 0 -0.5em rgba(225, 20, 98, 0.75), 0 0.5em rgba(111, 202, 220, 0.75);
        }

        70% {
            width: 0.5em;
            box-shadow: -1em -0.5em rgba(225, 20, 98, 0.75), 1em 0.5em rgba(111, 202, 220, 0.75);
        }

        100% {
            box-shadow: 1em -0.5em rgba(225, 20, 98, 0.75), -1em 0.5em rgba(111, 202, 220, 0.75);
        }
    }

    @keyframes slack-loader-after {
        0% {
            height: 0.5em;
            box-shadow: 0.5em 1em rgba(61, 184, 143, 0.75), -0.5em -1em rgba(233, 169, 32, 0.75);
        }

        35% {
            height: 2.5em;
            box-shadow: 0.5em 0 rgba(61, 184, 143, 0.75), -0.5em 0 rgba(233, 169, 32, 0.75);
        }

        70% {
            height: 0.5em;
            box-shadow: 0.5em -1em rgba(61, 184, 143, 0.75), -0.5em 1em rgba(233, 169, 32, 0.75);
        }

        100% {
            box-shadow: 0.5em 1em rgba(61, 184, 143, 0.75), -0.5em -1em rgba(233, 169, 32, 0.75);
        }
    }

    .welcome-driver-stack-wrap {
        position: relative;
        min-height: 420px;
        width: 100%;
        max-width: 380px;
        justify-self: center;
    }

    .welcome-driver-stack {
        position: relative;
        transform-style: preserve-3d;
        perspective: 500px;
        display: flex;
        flex-direction: column;
        gap: 0;
        transition: 500ms;
    }

    .welcome-driver-stack:hover {
        gap: 20px;
    }

    .welcome-driver-stack li {
        position: relative;
        list-style: none;
        width: 100%;
        min-height: 96px;
        padding: 16px;
        background: #fff;
        border-radius: 18px;
        display: flex;
        gap: 18px;
        justify-content: flex-start;
        align-items: center;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        border: 1px solid rgba(148, 163, 184, 0.18);
        transition: 500ms;
        transition-delay: calc(var(--i) * 50ms);
    }

    .welcome-driver-stack li:nth-child(1) {
        transform: translateZ(-75px) translateY(20px);
        opacity: .6;
        filter: blur(4px);
    }

    .welcome-driver-stack li:nth-child(2) {
        opacity: .8;
        filter: blur(2px);
    }

    .welcome-driver-stack li:nth-child(3) {
        transform: translateZ(65px) translateY(-30px);
    }

    .welcome-driver-stack li:nth-child(4) {
        transform: translateZ(125px) translateY(-68px);
        filter: blur(1px);
    }

    .welcome-driver-stack:hover li {
        opacity: 1;
        filter: blur(0);
        transform: translateZ(0) translateY(0);
    }

    .welcome-driver-avatar {
        width: 64px;
        height: 64px;
        flex: 0 0 64px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: #ffffff;
        padding: 0.45rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.35);
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .welcome-driver-avatar-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }

    .welcome-driver-stack .content {
        width: 100%;
    }

    .welcome-driver-stack .content h3 {
        font-weight: 700;
        margin-bottom: 8px;
        line-height: 1.1;
        color: #0f172a;
    }

    .welcome-driver-stack .content p {
        color: rgba(15, 23, 42, 0.68);
        line-height: 1.2;
        font-size: 0.95rem;
    }

    .welcome-stats-btn {
        appearance: none;
        border: 1px solid rgba(148, 163, 184, 0.55);
        background: #ffffff;
        color: #334155;
        padding: 0.55rem 0.9rem;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
    }

    .welcome-stats-btn + .welcome-stats-btn {
        margin-left: 0.5rem;
    }

    .welcome-stats-btn--ghost:hover {
        border-color: rgba(37, 99, 235, 0.5);
        background: rgba(239, 246, 255, 0.85);
        transform: translateY(-1px);
    }

    .welcome-stats-btn--active {
        border-color: rgba(29, 78, 216, 0.95);
        background: linear-gradient(120deg, #1d4ed8, #2563eb);
        color: #ffffff;
        box-shadow: 0 14px 26px -18px rgba(37, 99, 235, 0.55);
    }

    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .trusted-ticker-wrap {
        position: relative;
        overflow: hidden;
        border-radius: 0.9rem;
    }

    .welcome-driver-market {
        position: relative;
        width: 100%;
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid #dbeafe;
        background: radial-gradient(circle at 20% 20%, #eff6ff, #dbeafe 55%, #bfdbfe);
        box-shadow: 0 16px 34px -26px rgba(37, 99, 235, 0.5);
    }

    .welcome-driver-market::before,
    .welcome-driver-market::after {
        content: "";
        position: absolute;
        top: 0;
        bottom: 0;
        width: clamp(2.2rem, 8vw, 5.5rem);
        z-index: 2;
        pointer-events: none;
    }

    .welcome-driver-market::before {
        left: 0;
        background: linear-gradient(to right, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0));
    }

    .welcome-driver-market::after {
        right: 0;
        background: linear-gradient(to left, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0));
    }

    .welcome-driver-market-img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: cover;
    }

    .trusted-ticker-track {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 0.75rem;
        width: max-content;
        animation: trustedBrandTicker 30s linear infinite;
    }

    .trusted-brand-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.55rem 0.85rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 10px 22px -18px rgba(15, 23, 42, 0.4);
        white-space: nowrap;
    }

    .trusted-ticker-edge {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 3rem;
        z-index: 2;
        pointer-events: none;
    }

    .trusted-ticker-edge--left {
        left: 0;
        background: linear-gradient(to right, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0));
    }

    .trusted-ticker-edge--right {
        right: 0;
        background: linear-gradient(to left, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0));
    }

    .cookie-bar {
        position: fixed;
        inset: auto 1.5rem 1.5rem;
        z-index: 60;
        display: flex;
        justify-content: center;
    }

    .cookie-bar__inner {
        width: min(980px, 100%);
        background: rgba(15, 23, 42, 0.92);
        color: #f8fafc;
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 20px 50px -30px rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(148, 163, 184, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        backdrop-filter: blur(12px);
    }

    .cookie-bar__title {
        font-weight: 700;
        letter-spacing: 0.02em;
        margin-bottom: 0.25rem;
    }

    .cookie-bar__text {
        font-size: 0.95rem;
        color: rgba(226, 232, 240, 0.9);
    }

    .cookie-bar__actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .cookie-bar__button {
        background: linear-gradient(135deg, #38bdf8, #2563eb);
        color: #fff;
        border: none;
        padding: 0.65rem 1.4rem;
        border-radius: 999px;
        font-weight: 600;
        letter-spacing: 0.01em;
        box-shadow: 0 12px 22px -16px rgba(56, 189, 248, 0.8);
        cursor: pointer;
    }

    .cookie-bar__button:hover {
        filter: brightness(1.05);
    }

    @media (max-width: 720px) {
        .cookie-bar {
            inset: auto 1rem 1rem;
        }

        .cookie-bar__inner {
            flex-direction: column;
            align-items: flex-start;
        }

        .cookie-bar__actions {
            width: 100%;
            justify-content: flex-end;
        }
    }

    @keyframes trustedBrandTicker {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }

    @media (max-width: 768px) {
        .welcome-driver-stack-wrap {
            max-width: none;
            justify-self: stretch;
        }

        .trusted-ticker-track {
            animation-duration: 24s;
        }

        .welcome-driver-stack-wrap {
            min-height: auto;
        }

        .welcome-driver-stack {
            gap: 14px;
            perspective: none;
        }

        .welcome-driver-stack li,
        .welcome-driver-stack li:nth-child(1),
        .welcome-driver-stack li:nth-child(2),
        .welcome-driver-stack li:nth-child(3),
        .welcome-driver-stack li:nth-child(4) {
            transform: none;
            opacity: 1;
            filter: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .trusted-ticker-track {
            animation: none;
        }
    }

    .welcome-tween-card {
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 18px 40px -30px rgba(15, 23, 42, 0.35);
    }

    .welcome-video {
        position: relative;
        border-radius: 1rem;
        overflow: hidden;
        background: #0b1220;
        aspect-ratio: 16 / 9;
    }

    .welcome-video__poster,
    .welcome-video__media {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .welcome-video__media {
        opacity: 0;
        transition: opacity 220ms ease;
    }

    .welcome-video.is-playing .welcome-video__media {
        opacity: 1;
    }

    .welcome-video.is-playing .welcome-video__poster {
        opacity: 0;
        transition: opacity 180ms ease;
    }

    .welcome-video__play {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        border: 0;
        background: transparent;
        cursor: pointer;
        color: #ffffff;
    }

    .welcome-video__play::before {
        content: "";
        position: absolute;
        width: 84px;
        height: 84px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 18px 40px -26px rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        transition: transform 180ms ease, background-color 180ms ease;
    }

    .welcome-video__play svg {
        position: relative;
        width: 34px;
        height: 34px;
        transform: translateX(2px);
        filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.45));
    }

    .welcome-video__play:hover::before {
        transform: scale(1.05);
        background: rgba(15, 23, 42, 0.84);
    }

    .welcome-video.is-playing .welcome-video__play {
        opacity: 0;
        pointer-events: none;
    }

    .welcome-video__sound {
        position: absolute;
        right: 14px;
        top: 14px;
        width: 44px;
        height: 44px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(15, 23, 42, 0.6);
        color: #ffffff;
        display: grid;
        place-items: center;
        cursor: pointer;
        opacity: 0;
        pointer-events: none;
        transition: opacity 180ms ease, transform 180ms ease, background-color 180ms ease;
        backdrop-filter: blur(10px);
    }

    .welcome-video.is-playing .welcome-video__sound {
        opacity: 1;
        pointer-events: auto;
    }

    .welcome-video__sound:hover {
        transform: scale(1.03);
        background: rgba(15, 23, 42, 0.72);
    }

    .welcome-video__sound svg {
        width: 22px;
        height: 22px;
    }

    .welcome-video.is-muted .welcome-video__sound-waves {
        opacity: 0;
    }

    .welcome-video:not(.is-muted) .welcome-video__sound-mute {
        opacity: 0;
    }

    .welcome-video__loader {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 44px;
        height: 44px;
        border-radius: 999px;
        border: 3px solid rgba(255, 255, 255, 0.24);
        border-top-color: rgba(255, 255, 255, 0.95);
        transform: translate(-50%, -50%);
        animation: welcomeVideoSpin 1s linear infinite;
        opacity: 0;
        pointer-events: none;
    }

    .welcome-video.is-loading .welcome-video__loader {
        opacity: 1;
    }

    @keyframes welcomeVideoSpin {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .voucher-split-card {
        margin-top: 20px;
        border-radius: 1.75rem;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.94)),
            linear-gradient(135deg, rgba(219, 234, 254, 0.28), rgba(254, 240, 138, 0.12));
        box-shadow: 0 24px 48px -32px rgba(15, 23, 42, 0.28);
        overflow: hidden;
    }

    .voucher-split-card__grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }

    .voucher-split-card__media {
        position: relative;
        min-height: 340px;
        background: linear-gradient(135deg, #dbeafe, #eff6ff 40%, #f8fafc);
    }

    .voucher-split-slider {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .voucher-split-slide {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0;
        transform: scale(1.025);
        transition: opacity 700ms ease, transform 1200ms ease;
    }

    .voucher-split-slide.is-active {
        opacity: 1;
        transform: scale(1);
    }

    .voucher-split-card__content {
        padding: 2rem 1.5rem;
    }

    .voucher-split-card__eyebrow {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #2563eb;
    }

    .voucher-split-card__title {
        margin-top: 0.85rem;
        font-size: clamp(1.75rem, 2vw, 2.25rem);
        line-height: 1.1;
        color: #0f172a;
    }

    .voucher-split-card__body {
        margin-top: 1.5rem;
        color: #334155;
        font-size: 0.98rem;
        line-height: 1.78;
        display: grid;
        gap: 1rem;
    }

    .voucher-split-card__body p {
        margin: 0;
    }

    .voucher-split-card__break {
        position: relative;
        margin-top: 1.75rem;
        padding-top: 1.5rem;
    }

    .voucher-split-card__break::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, rgba(37, 99, 235, 0), rgba(37, 99, 235, 0.55), rgba(14, 165, 233, 0));
    }


    .welcome-tween-image {
        width: 100%;
        height: auto;
        display: block;
        object-fit: cover;
        opacity: 0;
        transform: translateX(-14px) translateY(8px) scale(0.992);
        filter: saturate(1.02) contrast(1.02) blur(6px);
        transition:
            opacity 980ms cubic-bezier(0.16, 1, 0.3, 1),
            transform 1100ms cubic-bezier(0.16, 1, 0.3, 1),
            filter 1100ms cubic-bezier(0.16, 1, 0.3, 1);
        will-change: transform, opacity;
    }

    .welcome-tween-image.is-in {
        opacity: 1;
        transform: translateX(0) translateY(0) scale(1);
        filter: saturate(1.02) contrast(1.02) blur(0);
    }

    @media (prefers-reduced-motion: reduce) {
        .welcome-tween-image {
            opacity: 1;
            transform: none;
            transition: none;
        }

        .voucher-split-slide {
            transition: none;
        }
    }

    @media (min-width: 992px) {
        .voucher-split-card__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .voucher-split-card__content {
            padding: 2.4rem 2.2rem;
        }
    }

    @media (max-width: 767px) {
        .voucher-split-card__media {
            min-height: 260px;
        }
    }
</style>
<script>
    (function () {
        const key = 'bwiser_cookie_consent_v1';
        window.bwiserAcceptCookies = function () {
            const bar = document.getElementById('cookieConsentBar');
            if (!bar) return false;
            localStorage.setItem(key, 'accepted');
            document.cookie = "bwiser_cookie_consent=accepted; path=/; max-age=31536000; SameSite=Lax";
            bar.classList.add('hidden');
            bar.style.display = 'none';
            bar.remove();
            return false;
        };
        const ready = () => {
            const bar = document.getElementById('cookieConsentBar');
            const button = document.getElementById('acceptCookiesBtn');
            if (!button || !bar) return;
            if (localStorage.getItem(key) === 'accepted') return;
            bar.classList.remove('hidden');
            bar.style.display = 'flex';
            button.addEventListener('click', function () {
                window.bwiserAcceptCookies();
            });
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ready);
        } else {
            ready();
        }
    })();
</script>
    <script>
        (function () {
            const selector = '[data-welcome-tween="slide-in"]';
            const targets = Array.from(document.querySelectorAll(selector));
            if (!targets.length) return;

            const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) return;

            const resetForTween = (target) => {
                if (target.dataset.tweenDone === '1') return;
                target.classList.remove('is-in');
                // Force style flush so re-adding triggers the transition reliably.
                void target.offsetHeight;
            };

            const ready = () => {
                if (!('IntersectionObserver' in window)) {
                    window.addEventListener('load', () => {
                        targets.forEach((t) => {
                            resetForTween(t);
                            t.classList.add('is-in');
                            t.dataset.tweenDone = '1';
                        });
                    }, { once: true });
                    return;
                }

                targets.forEach(resetForTween);

                const io = new IntersectionObserver((entries) => {
                    for (const e of entries) {
                        if (!e.isIntersecting) continue;
                        const target = e.target;
                        target.classList.add('is-in');
                        target.dataset.tweenDone = '1';
                        io.unobserve(target);
                    }
                }, { threshold: 0.18 });

                targets.forEach((t) => io.observe(t));
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', ready);
            } else {
            ready();
        }
    })();
</script>
@endsection

@push('scripts')
    @php
        $chartLocal = 'vendor/chart.js/Chart.min.js';
        $chartLocalPath = public_path($chartLocal);
        $chartSrc = is_file($chartLocalPath)
            ? asset($chartLocal).'?v='.filemtime($chartLocalPath)
            : 'https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js';
    @endphp
	    <script src="{{ $chartSrc }}"></script>
	    <script>
	        (function initVoucherSplitSlider() {
	            const boot = () => {
                const slider = document.querySelector('[data-voucher-slider]');
                if (!slider) return;

                const slides = Array.from(slider.querySelectorAll('[data-slide]'));
                if (slides.length <= 1) {
                    slides.forEach((slide, index) => slide.classList.toggle('is-active', index === 0));
                    return;
                }

                const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (reduceMotion) {
                    slides.forEach((slide, index) => slide.classList.toggle('is-active', index === 0));
                    return;
                }

                let activeIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
                if (activeIndex < 0) activeIndex = 0;

                window.setInterval(() => {
                    slides[activeIndex].classList.remove('is-active');
                    activeIndex = (activeIndex + 1) % slides.length;
                    slides[activeIndex].classList.add('is-active');
                }, 3000);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();

        (function initWelcomeVideo() {
            const boot = () => {
                const container = document.querySelector('[data-welcome-video]');
                if (!container) return;

                const video = container.querySelector('video');
                const poster = container.querySelector('.welcome-video__poster');
                const play = container.querySelector('.welcome-video__play');
                const sound = container.querySelector('.welcome-video__sound');
                if (!video || !poster || !play || !sound) return;

                const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const posterSeconds = Number(container.getAttribute('data-poster-seconds') || '22');

                const setLoading = (loading) => {
                    container.classList.toggle('is-loading', Boolean(loading));
                };

                const capturePoster = () => {
                    if (reduceMotion) return;
                    if (!video.videoWidth || !video.videoHeight) return;

                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) return;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    try {
                        poster.src = canvas.toDataURL('image/jpeg', 0.86);
                    } catch (e) {
                        // Some browsers can block toDataURL in edge cases; just keep the default background.
                    }
                };

                let posterCaptured = false;
                const seekForPoster = () => {
                    if (posterCaptured || reduceMotion) return;
                    const duration = Number.isFinite(video.duration) ? video.duration : 0;
                    const target = duration > 0 ? Math.min(Math.max(0.1, posterSeconds), Math.max(0.1, duration - 0.1)) : posterSeconds;

                    try {
                        video.currentTime = target;
                    } catch (e) {
                        // Ignore; poster will just stay as background.
                    }
                };

                const onSeeked = () => {
                    if (posterCaptured) return;
                    capturePoster();
                    posterCaptured = true;
                    setLoading(false);
                    // Rewind so playback starts normally on user click.
                    try { video.currentTime = 0; } catch (e) {}
                    video.removeEventListener('seeked', onSeeked);
                };

                setLoading(true);
                video.addEventListener('loadedmetadata', seekForPoster, { once: true });
                video.addEventListener('seeked', onSeeked);

                video.addEventListener('loadstart', () => setLoading(true));
                video.addEventListener('waiting', () => setLoading(true));
                video.addEventListener('playing', () => setLoading(false));
                video.addEventListener('canplay', () => setLoading(false));

                play.addEventListener('click', async () => {
                    container.classList.add('is-playing');
                    setLoading(true);
                    try {
                        video.muted = false;
                        video.volume = 1;
                        container.classList.remove('is-muted');
                        await video.play();
                    } catch (e) {
                        container.classList.remove('is-playing');
                        setLoading(false);
                    }
                });

                const syncMuteUi = () => {
                    container.classList.toggle('is-muted', Boolean(video.muted));
                    sound.setAttribute('aria-label', video.muted ? 'Unmute video' : 'Mute video');
                    sound.setAttribute('title', video.muted ? 'Unmute' : 'Mute');
                };

                syncMuteUi();

                sound.addEventListener('click', () => {
                    video.muted = !video.muted;
                    if (!video.muted && video.volume === 0) video.volume = 1;
                    syncMuteUi();
                });

                video.addEventListener('volumechange', syncMuteUi);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();

        (function initWelcomeStatsChart() {
            const boot = () => {
                const canvas = document.getElementById('welcomeStatsChart');
                if (!canvas || typeof Chart === 'undefined') return;

                const labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
                const vouchers = JSON.parse(canvas.getAttribute('data-series-vouchers') || '[]');
                const drivers = JSON.parse(canvas.getAttribute('data-series-drivers') || '[]');

                const seriesMap = {
                    vouchers: { label: 'Vouchers', color: '#2563eb', points: vouchers },
                    drivers: { label: 'Drivers', color: '#0f766e', points: drivers },
                };

                let activeKey = 'vouchers';
                const ctx = canvas.getContext('2d');
                const makeDataset = (key) => {
                    const s = seriesMap[key] || seriesMap.vouchers;
                    return {
                        label: s.label,
                        borderColor: s.color,
                        pointBackgroundColor: s.color,
                        data: Array.isArray(s.points) ? s.points : [],
                        fill: false,
                        borderWidth: 3,
                        pointBorderWidth: 4,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 8,
                        pointHoverBorderColor: 'rgba(37, 99, 235, 0.18)',
                        tension: 0.35,
                    };
                };

                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [makeDataset(activeKey)],
                    },
                    options: {
                        legend: { display: false },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                        },
                        hover: {
                            mode: 'nearest',
                            intersect: true,
                        },
                        scales: {
                            yAxes: [{
                                gridLines: { display: false },
                                ticks: { beginAtZero: true },
                            }],
                            xAxes: [{
                                gridLines: { display: false },
                            }],
                        },
                    },
                });

                const buttons = Array.from(document.querySelectorAll('.welcome-stats-btn[data-series]'));
                const setActive = (key) => {
                    activeKey = key in seriesMap ? key : 'vouchers';
                    buttons.forEach((btn) => {
                        const isActive = btn.getAttribute('data-series') === activeKey;
                        btn.classList.toggle('welcome-stats-btn--active', isActive);
                        btn.classList.toggle('welcome-stats-btn--ghost', !isActive);
                    });
                    chart.data.datasets = [makeDataset(activeKey)];
                    chart.update();
                };

                buttons.forEach((btn) => {
                    btn.addEventListener('click', () => setActive(btn.getAttribute('data-series') || 'vouchers'));
                });

                setActive(activeKey);
            };

	            if (document.readyState === 'loading') {
	                document.addEventListener('DOMContentLoaded', boot);
	            } else {
	                boot();
	            }
	        })();
	    </script>
	    <script>
	        (function initMrdSpecialLoader() {
	            const boot = () => {
	                const imgs = Array.from(document.querySelectorAll('img[data-bw-mrd-img]'));
	                imgs.forEach((img) => {
	                    const card = img.closest('.bw-mrd-card');
	                    if (!card) return;

	                    const done = () => card.classList.add('is-loaded');

	                    // Stop the loader even if the image fails.
	                    img.addEventListener('error', done, { once: true });

	                    if (img.complete) {
	                        if (img.decode) {
	                            img.decode().then(done).catch(done);
	                        } else {
	                            done();
	                        }
	                        return;
	                    }

	                    img.addEventListener('load', done, { once: true });
	                });
	            };

	            if (document.readyState === 'loading') {
	                document.addEventListener('DOMContentLoaded', boot);
	            } else {
	                boot();
	            }
	        })();
	    </script>
	    <script>
	        (function initComingSoonModal() {
	            const modal = document.getElementById('comingSoonModal');
	            if (!modal) return;

	            const openers = Array.from(document.querySelectorAll('[data-coming-soon-open]'));
	            const closers = Array.from(modal.querySelectorAll('[data-coming-soon-close]'));

	            const open = () => {
	                modal.classList.remove('hidden');
	                modal.classList.add('flex');
	                modal.setAttribute('aria-hidden', 'false');
	                document.body.style.overflow = 'hidden';
	            };

	            const close = () => {
	                modal.classList.add('hidden');
	                modal.classList.remove('flex');
	                modal.setAttribute('aria-hidden', 'true');
	                document.body.style.overflow = '';
	            };

	            openers.forEach((btn) => {
	                btn.addEventListener('click', (e) => {
	                    e.preventDefault();
	                    open();
	                });
	            });

	            closers.forEach((btn) => btn.addEventListener('click', close));

	            document.addEventListener('keydown', (e) => {
	                if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') close();
	            });
	        })();
	    </script>
@endpush
