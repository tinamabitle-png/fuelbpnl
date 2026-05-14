@extends('Layouts.app')

@section('title', 'Bwiser Fuel Buy Now Pay Later')
@section('meta_description', 'Bwiser is a South African fuel finance and payments platform for drivers, stations, vouchers, and settlements.')
@section('canonical', url('/'))
@php
    $welcomeOgImage = 'images/NalediTsunke.png';
    $welcomeOgImagePath = public_path($welcomeOgImage);
    $welcomeOgImageUrl = asset($welcomeOgImage);
    $welcomeNavigation = [
        [
            '@type' => 'SiteNavigationElement',
            'position' => 1,
            'name' => 'Login',
            'url' => route('login'),
        ],
        [
            '@type' => 'SiteNavigationElement',
            'position' => 2,
            'name' => 'Register',
            'url' => route('register'),
        ],
        [
            '@type' => 'SiteNavigationElement',
            'position' => 3,
            'name' => 'Developers',
            'url' => route('developers.docs'),
        ],
    ];

    if (config('services.registration.public_merchant_enabled')) {
        $welcomeNavigation[] = [
            '@type' => 'SiteNavigationElement',
            'position' => 4,
            'name' => 'Merchant Registration',
            'url' => route('register.merchant'),
        ];
    }

    if (is_file($welcomeOgImagePath)) {
        $welcomeOgImageUrl .= '?v=' . filemtime($welcomeOgImagePath);
    }
@endphp

@section('og_image', $welcomeOgImageUrl)
@section('og_image_alt', 'Bwiser Fuel Buy Now Pay Later')

@push('head')
    <link rel="stylesheet" href="{{ asset('vendor/flyonui/flyonui.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
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
            [
                '@type' => 'ItemList',
                'name' => 'Primary navigation',
                'itemListElement' => $welcomeNavigation,
            ],
        ],
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

	@section('content')
	<section class="max-w-7xl mx-auto px-6 pt-16 pb-20" data-scroll-nav-root>
	    <div class="glass welcome-hero-surface rounded-3xl p-8 md:p-12">
	        <div class="welcome-hero-image" aria-hidden="true"></div>
	        @php
	            $recentDrivers = collect((array) (($welcomeStats ?? [])['recent_drivers'] ?? []))->take(4);
	            $dashboardUrl = null;
	            $dashboardUserName = null;
	            if (auth()->check()) {
                $user = auth()->user();
                $dashboardUserName = trim((string) ($user->name ?? $user->email ?? 'User'));

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
                            @if($dashboardUserName)
                                <span class="bw-dashboard-user" aria-label="Signed in as {{ $dashboardUserName }}">
                                    <span class="bw-dashboard-user__dot" aria-hidden="true"></span>
                                    <span class="bw-dashboard-user__label">Signed in</span>
                                    <span class="bw-dashboard-user__name">{{ $dashboardUserName }}</span>
                                </span>
                            @endif
                        @endif
                    @endauth
                    @guest
                        <a
                            class="super-button"
                            href="{{ route('register') }}"
                            data-auth-scroll-trigger
                            data-auth-scroll-target="auth-section"
                        >
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

    @guest
        @php
            $authLoginStep = config('services.registration.public_merchant_enabled') ? 3 : 2;
        @endphp
        <div id="auth-section" class="auth-stepper-box glass rounded-2xl p-6 md:p-8 mt-8 scroll-mt-24">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="brand-font text-2xl font-semibold text-slate-900">Choose your Bwiser path</h2>
                        <div class="rounded-md flex items-center bg-slate-100 py-0.5 px-2.5 border border-transparent text-sm text-slate-600 transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 mr-1.5" aria-hidden="true" focusable="false">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.735 14c.618 0 1.093-.561.872-1.139a6.002 6.002 0 0 0-11.215 0c-.22.578.254 1.139.872 1.139h9.47Z" />
                            </svg>
                            driver or merchant
                        </div>
                    </div>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">
                        Start with registration, jump into merchant onboarding, or sign in if you already have access.
                    </p>
                </div>
                <a href="{{ route('login') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">
                    Already have an account? Sign in
                </a>
            </div>

            <div class="auth-stepper-track mt-6">
                <a href="{{ route('register') }}" class="stepper-step stepper-active">
                    <div class="stepper-circle">1</div>
                    <div class="stepper-line"></div>
                    <div class="stepper-content">
                        <div class="stepper-title">Register</div>
                        <div class="stepper-status">Start Here</div>
                        <div class="stepper-time">Create your account and continue into onboarding.</div>
                    </div>
                </a>

                @if(config('services.registration.public_merchant_enabled'))
                    <a href="{{ route('register.merchant') }}" class="stepper-step stepper-completed stepper-step--merchant">
                        <div class="stepper-circle">2</div>
                        <div class="stepper-line"></div>
                        <div class="stepper-content">
                            <div class="stepper-title">Merchant Registration</div>
                            <div class="stepper-status">Merchant Process</div>
                            <div class="stepper-time">Register a station or merchant account for vouchers and settlements.</div>
                        </div>
                    </a>
                @endif

                <a href="{{ route('login') }}" class="stepper-step stepper-pending">
                    <div class="stepper-circle">{{ $authLoginStep }}</div>
                    <div class="stepper-content">
                        <div class="stepper-title">Login</div>
                        <div class="stepper-status">Returning Users</div>
                        <div class="stepper-time">Sign in to reach your dashboard, vouchers, and settlement tools.</div>
                    </div>
                </a>
            </div>
        </div>
    @endguest

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
        <div class="space-y-4">
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
                        <div class="mt-4 inline-flex max-w-sm items-center gap-3 rounded-2xl border border-slate-200 bg-white/85 px-3 py-3 shadow-sm">
                            <div class="h-10 w-10 overflow-hidden rounded-full ring-2 ring-blue-100">
                                <img src="{{ asset('images/tony.jpg') }}" alt="Tony" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">Finance Lead</p>
                                <p class="text-[11px] leading-5 text-slate-600">Talk to Tony about funding, settlements, and repayments.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $bwiserFuelImage = 'images/bwiserpngvoucher.png';
                $bwiserFuelImagePath = public_path($bwiserFuelImage);
                $bwiserFuelImageUrl = asset($bwiserFuelImage);
                if (is_file($bwiserFuelImagePath)) {
                    $bwiserFuelImageUrl .= '?v=' . filemtime($bwiserFuelImagePath);
                }
            @endphp
            <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden welcome-media-shell" data-welcome-media>
                <img
                    src="{{ $bwiserFuelImageUrl }}"
                    alt="Bwiser fuel preview"
                    class="finance-lead-preview welcome-media-shell__media"
                    data-welcome-media-target
                    loading="lazy"
                />
            </div>
        </div>

        <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden welcome-media-shell" data-welcome-media>
            <img
                src="{{ asset('images/bench_sign.png') }}"
                alt="Bwiser preview"
                class="block w-full h-full object-cover rounded-2xl welcome-media-shell__media"
                data-welcome-media-target
                loading="lazy"
            >
        </div>
    </div>

    <div class="grid gap-8 md:grid-cols-2 mt-8 items-start">
        <div class="space-y-6">
            <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden welcome-tween-card">
                <img
                    src="{{ asset('images/NalediTsunke.png') }}"
                    alt="Bwiser preview"
                    class="welcome-tween-image is-in rounded-2xl"
                    loading="lazy"
                    data-welcome-tween="slide-in"
                    onerror="this.classList.add('is-in'); this.style.opacity='1'; this.style.transform='none';"
                >
            </div>

            <div class="glass rounded-2xl p-4 md:p-6 welcome-tween-card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#020DFF]">How Bwiser works</p>
                        <h3 class="mt-1 text-base md:text-lg font-semibold text-slate-900 leading-tight">From voucher to repayment</h3>
                    </div>
                    <span class="shrink-0 rounded-full bg-[#020DFF]/8 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-[#020DFF]">
                        4 steps
                    </span>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="relative overflow-hidden rounded-2xl bg-white/80 p-4 shadow-[0_10px_30px_-24px_rgba(2,13,255,0.45)]">
                        <div class="absolute left-[1.15rem] top-12 bottom-0 w-px bg-gradient-to-b from-[#020DFF]/25 via-slate-200 to-transparent"></div>
                        <div class="relative flex gap-3">
                            <div class="stepper-circle repayment-flow-circle">01</div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-900">Create voucher</p>
                                <p class="mt-1 text-[12px] leading-relaxed text-slate-600">
                                    Issue a Bwiser voucher for a driver or lease with the amount, station, and due items, then share the voucher ID or QR.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-2xl bg-slate-50/85 p-4">
                        <div class="absolute left-[1.15rem] top-12 bottom-0 w-px bg-gradient-to-b from-[#020DFF]/25 via-slate-200 to-transparent"></div>
                        <div class="relative flex gap-3">
                            <div class="stepper-circle repayment-flow-circle">02</div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-900">Validate at station</p>
                                <p class="mt-1 text-[12px] leading-relaxed text-slate-600">
                                    The station validates the voucher with QR or USSD geofence on the POS, prints a receipt, and settles the payment.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-2xl bg-white/80 p-4">
                        <div class="absolute left-[1.15rem] top-12 bottom-0 w-px bg-gradient-to-b from-[#020DFF]/25 via-slate-200 to-transparent"></div>
                        <div class="relative flex gap-3">
                            <div class="stepper-circle repayment-flow-circle">03</div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-900">Repayment runs</p>
                                <p class="mt-1 text-[12px] leading-relaxed text-slate-600">
                                    Repayments appear on the driver dashboard, and auto-pay can settle balances on due dates while sending confirmation emails.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-2xl bg-slate-50/85 p-4">
                        <div class="relative flex gap-3">
                            <div class="stepper-circle repayment-flow-circle">04</div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-900">Track performance</p>
                                <p class="mt-1 text-[12px] leading-relaxed text-slate-600">
                                    Monitor settlement reporting, due items, and performance across drivers, leases, and stations from one process.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-gradient-to-r from-emerald-50 via-white to-[#020DFF]/5 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-700">Success</p>
                    <p class="mt-2 text-sm font-medium leading-relaxed text-slate-800">
                        Fuel payments stay tracked end-to-end with simple vouchers, predictable repayments, and clean reporting.
                    </p>
                </div>

                <div class="mt-5 flex justify-start">
                    <a href="{{ asset('images/BWISER.jpg') }}" class="welcome-entry-button welcome-entry-button--type-c" data-tapless-open aria-haspopup="dialog">
                        <div class="welcome-entry-button__line"></div>
                        <div class="welcome-entry-button__line"></div>
                        <span class="welcome-entry-button__text">Tapless payments</span>
                        <div class="welcome-entry-button__drow1"></div>
                        <div class="welcome-entry-button__drow2"></div>
                    </a>
                </div>

                <div class="mt-5 rounded-2xl bg-white/85 p-4 shadow-[0_12px_30px_-24px_rgba(2,13,255,0.35)]">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#020DFF]">Case study</p>
                    <h4 class="mt-2 text-sm font-semibold text-slate-900">A founder story behind tapless payments</h4>
                    <p class="mt-2 text-[12px] leading-relaxed text-slate-600">
                        After a difficult experience with an earlier payments initiative that Tlhologelo Mabitle felt left him unfairly treated, he decided to build something new for the Flowdosi Merchant Group instead of walking away from the problem.
                    </p>
                    <p class="mt-2 text-[12px] leading-relaxed text-slate-600">
                        That decision led to a novel approach: USSD geofenced tapless payments designed for merchant environments, turning a frustrating setback into an unexpected breakthrough for Bwiser.
                    </p>
                    <p class="mt-2 text-[12px] leading-relaxed text-slate-700">
                        In many ways, it was a happy accident: a reaction to a tough moment that opened the path to a first-of-its-kind payment experience.
                    </p>
                </div>

            </div>

            <div class="glass rounded-2xl p-4 md:p-6 welcome-tween-card">
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#020DFF]">FAQ</p>
                <h4 class="mt-2 text-sm font-semibold text-slate-900">How USSD tapless payments work</h4>
                <div class="mt-4 rounded-2xl bg-white/80 p-4 space-y-3">
                    <div class="chat chat-receiver max-w-full">
                        <div class="chat-avatar avatar">
                            <div class="h-7 w-7 shrink-0 overflow-hidden rounded-full">
                                <img src="{{ asset('images/ask.jpg') }}" alt="Question asker" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                        </div>
                        <div class="chat-header text-base-content text-[11px] font-medium">
                            Merchant
                            <time class="text-base-content/50 ml-1 text-[10px]">12:45</time>
                        </div>
                        <div class="chat-bubble max-w-[15rem] rounded-2xl px-3 py-2 text-[11px] leading-relaxed shadow-sm">
                            How do USSD tapless payments work if there is no physical card tap?
                        </div>
                        <div class="chat-footer text-base-content/50 text-[10px]">
                            Delivered
                        </div>
                    </div>

                    <div class="chat chat-sender max-w-full">
                        <div class="chat-avatar avatar">
                            <div class="h-7 w-7 shrink-0 overflow-hidden rounded-full">
                                <img src="{{ asset('images/ans.jpg') }}" alt="Answer" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                        </div>
                        <div class="chat-header text-base-content text-[11px] font-medium">
                            Bwiser
                            <time class="text-base-content/50 ml-1 text-[10px]">12:46</time>
                        </div>
                        <div class="chat-bubble max-w-[15rem] rounded-2xl bg-[#020DFF] px-3 py-2 text-[11px] leading-relaxed text-white shadow-sm">
                            The driver or merchant starts the payment process with USSD, and Bwiser checks the voucher, the user, and the geofenced station context before the transaction is approved.
                        </div>
                        <div class="chat-footer text-base-content/50 text-[10px]">
                            Seen
                        </div>
                    </div>

                    <div class="chat chat-receiver max-w-full">
                        <div class="chat-avatar avatar">
                            <div class="h-7 w-7 shrink-0 overflow-hidden rounded-full">
                                <img src="{{ asset('images/ask.jpg') }}" alt="Question asker" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                        </div>
                        <div class="chat-header text-base-content text-[11px] font-medium">
                            Merchant
                            <time class="text-base-content/50 ml-1 text-[10px]">12:46</time>
                        </div>
                        <div class="chat-bubble max-w-[15rem] rounded-2xl px-3 py-2 text-[11px] leading-relaxed shadow-sm">
                            Wow that is quite novel, it's a really fresh take on frictionless payments.
                        </div>
                        <div class="chat-footer text-base-content/50 text-[10px]">
                            Delivered
                        </div>
                    </div>

                    <div class="chat chat-receiver max-w-full">
                        <div class="chat-avatar avatar">
                            <div class="h-7 w-7 shrink-0 overflow-hidden rounded-full">
                                <img src="{{ asset('images/ask.jpg') }}" alt="Question asker" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                        </div>
                        <div class="chat-header text-base-content text-[11px] font-medium">
                            Merchant
                            <time class="text-base-content/50 ml-1 text-[10px]">12:47</time>
                        </div>
                        <div class="chat-bubble max-w-[15rem] rounded-2xl px-3 py-2 text-[11px] leading-relaxed shadow-sm">
                            So what makes it secure?
                        </div>
                        <div class="chat-footer text-base-content/50 text-[10px]">
                            Delivered
                        </div>
                    </div>

                    <div class="chat chat-sender max-w-full">
                        <div class="chat-avatar avatar">
                            <div class="h-7 w-7 shrink-0 overflow-hidden rounded-full">
                                <img src="{{ asset('images/ans.jpg') }}" alt="Answer" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                        </div>
                        <div class="chat-header text-base-content text-[11px] font-medium">
                            Bwiser
                            <time class="text-base-content/50 ml-1 text-[10px]">12:48</time>
                        </div>
                        <div class="chat-bubble max-w-[15rem] rounded-2xl bg-[#020DFF] px-3 py-2 text-[11px] leading-relaxed text-white shadow-sm">
                            Security comes from combining voucher validation, location awareness, merchant rules, and repayment tracking instead of relying only on a plastic card tap.
                        </div>
                        <div class="chat-footer text-base-content/50 text-[10px]">
                            Seen
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden welcome-tween-card">
            <div class="rounded-[22px] bg-gradient-to-br from-[#020DFF]/20 via-cyan-400/18 to-[#020DFF]/8 p-[1px] shadow-[0_18px_40px_-30px_rgba(2,13,255,0.28)]">
                <div class="rounded-[21px] bg-gradient-to-br from-white via-white to-blue-50/70 p-4 md:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#020DFF]">Merchant onboarding</p>
                        <h3 class="mt-1 text-base md:text-lg font-semibold text-slate-900 leading-tight">Get live in 4 quick steps</h3>
                    </div>
                    <span class="shrink-0 rounded-full border border-[#020DFF]/15 bg-[#020DFF]/8 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-[#020DFF]">
                        Fast setup
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-gradient-to-br from-[#020DFF]/28 via-cyan-400/22 to-[#020DFF]/10 p-[1px] shadow-[0_10px_24px_-22px_rgba(2,13,255,0.45)]">
                        <div class="rounded-[15px] bg-white p-4">
                            <div class="stepper-circle merchant-onboarding-step-number">01</div>
                            <p class="mt-3 text-sm font-semibold text-slate-900">Register</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-slate-600">Capture merchant, station, and business details to start onboarding.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-[#020DFF]/22 via-cyan-400/18 to-slate-200/50 p-[1px]">
                        <div class="rounded-[15px] bg-slate-50/85 p-4">
                            <div class="stepper-circle merchant-onboarding-step-number">02</div>
                            <p class="mt-3 text-sm font-semibold text-slate-900">Verify</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-slate-600">Submit KYC and onboarding documents so the account can be approved.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-cyan-400/18 via-[#020DFF]/18 to-slate-200/50 p-[1px]">
                        <div class="rounded-[15px] bg-slate-50/85 p-4">
                            <div class="stepper-circle merchant-onboarding-step-number">03</div>
                            <p class="mt-3 text-sm font-semibold text-slate-900">Install</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-slate-600">Set up the POS, train staff, and configure voucher validation for the site.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-[#020DFF]/24 via-cyan-400/18 to-emerald-300/35 p-[1px] shadow-[0_10px_24px_-22px_rgba(15,23,42,0.28)]">
                        <div class="rounded-[15px] bg-white p-4">
                            <div class="stepper-circle merchant-onboarding-step-number">04</div>
                            <p class="mt-3 text-sm font-semibold text-slate-900">Go live</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-slate-600">Start accepting Bwiser vouchers with reporting and support already in place.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-gradient-to-r from-[#020DFF]/20 via-cyan-400/18 to-[#020DFF]/10 p-[1px]">
                    <div class="rounded-[15px] bg-white/90 px-4 py-3">
                        <p class="text-[11px] font-medium text-slate-700">
                            Merchants move from signup to live voucher acceptance in one guided process.
                        </p>
                    </div>
                </div>
                </div>
            </div>

            <img
                src="{{ asset('images/pos4.jpg') }}"
                alt="Bwiser preview"
                class="welcome-tween-image is-in rounded-2xl"
                loading="lazy"
                data-welcome-tween="slide-in"
                onerror="this.classList.add('is-in'); this.style.opacity='1'; this.style.transform='none';"
            >

            <div class="prov545-plan mt-4">
                <div class="prov545-plan__head">
                    <div>
                        <h4 class="prov545-plan__title">Bwiser Pro</h4>
                    </div>
                    <span class="prov545-plan__chip">Available</span>
                </div>

                <div class="prov545-pricing-card" aria-label="Bwiser Pro pricing">
                    <div class="prov545-pricing-card__content">
                        <div class="prov545-pricing-card__top">
                            <span class="prov545-pricing-card__index">Buy now</span>
                            <p>On sale</p>
                        </div>

                        <div class="prov545-pricing-card__bottom">
                            <div>
                                <p class="prov545-pricing-card__amount">R 1,900</p>
                                <p class="prov545-pricing-card__note">Own the device</p>
                            </div>
                            <svg viewBox="0 -960 960 960" aria-hidden="true">
                                <path d="M734-160q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19ZM480-160q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19ZM226-160q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Zm508-254q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19ZM480-414q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Zm-254 0q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Zm508-254q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19ZM480-668q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Zm-254 0q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="prov545-pricing-card__image" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18C2,19.11 2.89,20 4,20H20C21.11,20 22,19.11 22,18V6C22,4.89 21.11,4 20,4Z"></path>
                        </svg>
                    </div>
                </div>

                <div class="prov545-plan__features">
                    <p class="prov545-plan__features-title">Key features</p>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-center space-x-3">
                            <span class="bg-primary/20 text-primary flex items-center justify-center rounded-full p-1">
                                <span class="icon-[tabler--arrow-right] size-4 rtl:rotate-180"></span>
                            </span>
                            <span class="text-base-content/80">USSD geofenced payments</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <span class="bg-primary/20 text-primary flex items-center justify-center rounded-full p-1">
                                <span class="icon-[tabler--arrow-right] size-4 rtl:rotate-180"></span>
                            </span>
                            <span class="text-base-content/80">Built-in receipt printing</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <span class="bg-primary/20 text-primary flex items-center justify-center rounded-full p-1">
                                <span class="icon-[tabler--arrow-right] size-4 rtl:rotate-180"></span>
                            </span>
                            <span class="text-base-content/80">Portable, battery-powered terminal</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <span class="bg-primary/20 text-primary flex items-center justify-center rounded-full p-1">
                                <span class="icon-[tabler--arrow-right] size-4 rtl:rotate-180"></span>
                            </span>
                            <span class="text-base-content/80">Wi‑Fi + 4G connectivity</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <span class="bg-primary/20 text-primary flex items-center justify-center rounded-full p-1">
                                <span class="icon-[tabler--arrow-right] size-4 rtl:rotate-180"></span>
                            </span>
                            <span class="text-base-content/80">Fast settlement reporting</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <span class="bg-primary/20 text-primary flex items-center justify-center rounded-full p-1">
                                <span class="icon-[tabler--arrow-right] size-4 rtl:rotate-180"></span>
                            </span>
                            <span class="text-base-content/80">Ready for Bwiser voucher payments</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-4 flex justify-end">
                    <a
                        href="mailto:support@bwiser.co.za?subject=Bwiser%20Pro%20Purchase%20Request"
                        class="button-86"
                        role="button"
                        aria-label="Buy Bwiser Pro"
                    >
                        Buy now
                    </a>
                </div>
            </div>

            <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden mt-4">
                <div class="bw-merchant-story-card">
                    <div class="bw-merchant-story-card__details">
                        <div class="bw-merchant-story-card__header">Merchant-ready</div>
                        <div class="bw-merchant-story-card__text">
                            Built for onboarding, payments, and settlement operations so stations can go live with less friction and stronger control from day one.
                        </div>
                        <a href="{{ route('register.merchant') }}" class="bw-merchant-story-card__button">Go live now</a>
                    </div>
                </div>
            </div>

            <div class="glass rounded-2xl p-3 md:p-4 overflow-hidden mt-4 welcome-media-shell" data-welcome-media>
                <img
                    src="{{ asset('images/pos6.jpg') }}"
                    alt="Bwiser merchant preview"
                    class="block w-full h-full rounded-2xl object-cover welcome-media-shell__media"
                    data-welcome-media-target
                    loading="lazy"
                >
            </div>

        </div>
    </div>

    <div class="mt-8 glass rounded-2xl p-4 md:p-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h4 class="mt-1 text-sm font-semibold text-slate-900">Countries where Bwiser operates</h4>
            </div>
            <span class="rounded-full bg-[#020DFF]/8 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-[#020DFF]">
                South Africa
            </span>
        </div>
        <p class="mt-2 text-[12px] leading-relaxed text-slate-600">
            Bwiser is currently active in South Africa, shown here in the wider global payments map.
        </p>
        <div class="mt-4 overflow-hidden rounded-2xl bg-transparent">
            <div id="bwiser-countries-datamap" class="h-[280px] w-full md:h-[340px]"></div>
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

    <div class="grid gap-8 lg:grid-cols-2 mt-8 items-start">
        <div class="glass rounded-2xl p-6 overflow-hidden">
            <p class="text-xs uppercase tracking-[1px] text-blue-600 mb-4">Latest Drivers</p>
            <div class="welcome-driver-stack-wrap">
                <ul class="welcome-driver-stack" aria-label="Last 4 drivers">
                    @forelse($recentDrivers as $index => $driver)
                        @php
                            $rawDriverName = trim((string) ($driver['name'] ?? ''));
                            $driverNameParts = preg_split('/\s+/', $rawDriverName) ?: [];
                            $driverFirstName = trim((string) ($driverNameParts[0] ?? ''));
                            $driverFirstName = $driverFirstName !== ''
                                ? mb_convert_case(mb_strtolower($driverFirstName, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
                                : 'Driver';
                        @endphp
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
                                <h3>{{ $driverFirstName }}</h3>
                                <p>{{ $driverFirstName }} is now driving wiser</p>
                            </div>
                        </li>
                    @empty
                        @foreach([
                            ['name' => 'Aphiwe Dlamini', 'initials' => 'AD'],
                            ['name' => 'Naledi Mokoena', 'initials' => 'NM'],
                            ['name' => 'Thabo Maseko', 'initials' => 'TM'],
                            ['name' => 'Lerato Nkosi', 'initials' => 'LN'],
                        ] as $index => $driver)
                            @php
                                $rawDriverName = trim((string) ($driver['name'] ?? ''));
                                $driverNameParts = preg_split('/\s+/', $rawDriverName) ?: [];
                                $driverFirstName = trim((string) ($driverNameParts[0] ?? ''));
                                $driverFirstName = $driverFirstName !== ''
                                    ? mb_convert_case(mb_strtolower($driverFirstName, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
                                    : 'Driver';
                            @endphp
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
                                    <h3>{{ $driverFirstName }}</h3>
                                    <p>{{ $driverFirstName }} is now driving wiser</p>
                                </div>
                            </li>
                        @endforeach
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="flex justify-center">
            <img
                src="{{ asset('images/cups.png') }}"
                alt="Bwiser kiosk"
                class="welcome-kiosk-image"
                loading="lazy"
            >
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

<div class="scroll-nav-rail" data-scroll-nav-rail hidden aria-hidden="true">
    <span class="scroll-nav-rail__thumb" data-scroll-nav-thumb></span>
</div>

<button
    type="button"
    class="scroll-nav-fab"
    data-scroll-nav-button
    aria-label="Scroll to next section"
    title="Scroll to next section"
    hidden
>
    <svg class="scroll-nav-fab__icon" data-scroll-nav-icon viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M12 16.4a1 1 0 0 1-.7-.29l-6-6a1 1 0 1 1 1.4-1.42L12 13.99l5.3-5.3a1 1 0 1 1 1.4 1.42l-6 6a1 1 0 0 1-.7.29Z" fill="currentColor"></path>
    </svg>
</button>

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
        html {
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        html::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        body {
            overflow-y: scroll;
        }

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

        .scroll-nav-fab {
            position: fixed;
            right: clamp(1rem, 2vw, 1.5rem);
            bottom: calc(var(--scroll-nav-bottom, 1.25rem) + env(safe-area-inset-bottom, 0px));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 999px;
            border: 1px solid rgba(2, 13, 255, 0.16);
            background: rgba(255, 255, 255, 0.92);
            color: #020dff;
            box-shadow: 0 22px 36px -24px rgba(2, 13, 255, 0.42);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 2147483600;
            cursor: pointer;
            pointer-events: auto;
            touch-action: manipulation;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .scroll-nav-fab:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 40px -22px rgba(2, 13, 255, 0.5);
            background: rgba(255, 255, 255, 0.98);
        }

        .scroll-nav-fab[hidden] {
            display: none;
        }

        .scroll-nav-rail {
            position: fixed;
            top: clamp(7rem, 10vh, 8.75rem);
            right: 0.45rem;
            bottom: calc(var(--scroll-nav-bottom, 1.25rem) + env(safe-area-inset-bottom, 0px));
            width: 0.7rem;
            border-radius: 999px;
            background: rgba(226, 232, 240, 0.9);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.18);
            z-index: 2147483590;
            pointer-events: auto;
            cursor: pointer;
        }

        .scroll-nav-rail[hidden] {
            display: none;
        }

        .scroll-nav-rail__thumb {
            display: block;
            width: 100%;
            min-height: 2.75rem;
            border-radius: inherit;
            background: linear-gradient(180deg, #020dff 0%, #38bdf8 100%);
            box-shadow: 0 12px 20px -16px rgba(2, 13, 255, 0.72);
            transform: translateY(0);
            will-change: transform;
            transition: height 0.16s ease;
            cursor: grab;
            touch-action: none;
        }

        .scroll-nav-rail.is-dragging,
        .scroll-nav-rail.is-dragging .scroll-nav-rail__thumb {
            cursor: grabbing;
        }

        body.scroll-nav-dragging {
            user-select: none;
        }

        .scroll-nav-fab__icon {
            width: 1.4rem;
            height: 1.4rem;
            animation: scroll-nav-bounce 1.8s ease-in-out infinite;
            transition: transform 0.25s ease;
        }

        .scroll-nav-fab.is-returning .scroll-nav-fab__icon {
            animation: none;
            transform: rotate(180deg);
        }

        @keyframes scroll-nav-bounce {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(4px);
            }
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

    .bw-animated-border {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.32);
        backdrop-filter: blur(8px);
    }

	    .bw-animated-border::before {
	        content: "";
	        position: absolute;
	        top: -50%;
	        left: -50%;
	        width: 200%;
	        height: 200%;
	        background: conic-gradient(from 0deg, #00ffff, #ff00ff, #00ffff);
	        animation: rotate 4s linear infinite;
	        z-index: 0;
	        opacity: 0.9;
	        pointer-events: none;
	    }

		    .bw-animated-border::after {
		        content: "";
		        position: absolute;
		        inset: 2px;
		        /* Opaque inner fill so the conic gradient only reads as a border. */
		        background: rgba(255, 255, 255, 0.98);
		        border-radius: inherit;
		        z-index: 1;
		        pointer-events: none;
		    }

	    .bw-animated-border > * {
	        position: relative;
	        z-index: 2;
	    }

    @media (prefers-reduced-motion: reduce) {
        .bw-animated-border::before {
            animation: none;
        }
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

    .welcome-media-shell {
        position: relative;
        isolation: isolate;
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 48%),
                linear-gradient(135deg, rgba(226, 232, 240, 0.78), rgba(248, 250, 252, 0.94));
        }

        .welcome-media-shell::before,
        .welcome-media-shell::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            transition: opacity 320ms ease;
        }

        .welcome-media-shell::before {
            z-index: 0;
            background:
                linear-gradient(110deg, rgba(255, 255, 255, 0) 12%, rgba(255, 255, 255, 0.68) 32%, rgba(255, 255, 255, 0) 52%),
                linear-gradient(135deg, rgba(191, 219, 254, 0.55), rgba(226, 232, 240, 0.45), rgba(255, 255, 255, 0.6));
            background-size: 220% 100%, 100% 100%;
            animation: welcomeSkeletonShimmer 1.35s linear infinite;
            opacity: 1;
        }

        .welcome-media-shell::after {
            z-index: 1;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.12), rgba(15, 23, 42, 0.06));
            opacity: 0.9;
        }

        .welcome-media-shell.is-media-loaded::before,
        .welcome-media-shell.is-media-loaded::after {
            opacity: 0;
            animation: none;
        }

        .welcome-media-shell__media,
        .welcome-video__poster {
            position: relative;
            z-index: 2;
        }

        .welcome-kiosk-image {
            display: block;
            width: 150%;
            height: auto;
            object-fit: contain;
            -webkit-border-radius: 180px;
            -webkit-border-bottom-right-radius: 10px;
            -moz-border-radius: 180px;
            -moz-border-radius-bottomright: 10px;
            border-radius: 180px;
            border-bottom-right-radius: 10px;
        }

        @keyframes welcomeSkeletonShimmer {
            0% {
                background-position: 120% 0, 0 0;
            }
            100% {
                background-position: -120% 0, 0 0;
            }
        }

        .auth-stepper-box {
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.95)),
                radial-gradient(circle at top left, rgba(2, 13, 255, 0.08), transparent 42%);
            border: 1px solid rgba(191, 219, 254, 0.9);
            box-shadow: 0 20px 40px -32px rgba(15, 23, 42, 0.24);
        }

        .auth-stepper-track {
            display: grid;
            gap: 1rem;
        }

        .stepper-step {
            position: relative;
            display: flex;
            gap: 1rem;
            text-decoration: none;
            padding: 1.1rem 1rem;
            border-radius: 1.1rem;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 10px 24px -24px rgba(15, 23, 42, 0.35);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .stepper-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 32px -26px rgba(2, 13, 255, 0.22);
        }

        .stepper-line {
            display: none;
        }

        .stepper-circle {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.1rem;
            z-index: 2;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .stepper-content {
            flex: 1;
            min-width: 0;
        }

        .stepper-title {
            font-weight: 700;
            margin-bottom: 0.35rem;
            line-height: 1.2;
        }

        .stepper-status {
            font-size: 12px;
            display: inline-block;
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            margin-top: 0.15rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .stepper-time {
            font-size: 13px;
            line-height: 1.55;
            margin-top: 0.65rem;
        }

        .stepper-step--merchant {
            overflow: hidden;
            background: rgba(2, 13, 255, 0.6);
            border-color: rgba(103, 232, 249, 0.22);
            box-shadow: 0 20px 38px -30px rgba(2, 13, 255, 0.42);
        }

        .stepper-step--merchant::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(35.36% 35.36% at 100% 25%, transparent 66%, #c79013 68% 70%, transparent 72%) 32px 32px / 64px 64px,
                radial-gradient(35.36% 35.36% at 0 75%, transparent 66%, #c79013 68% 70%, transparent 72%) 32px 32px / 64px 64px,
                radial-gradient(35.36% 35.36% at 100% 25%, transparent 66%, #c79013 68% 70%, transparent 72%) 0 0 / 64px 64px,
                radial-gradient(35.36% 35.36% at 0 75%, transparent 66%, #c79013 68% 70%, transparent 72%) 0 0 / 64px 64px,
                repeating-conic-gradient(rgba(7, 28, 141, 0.88) 0 25%, transparent 0 50%) 0 0 / 64px 64px,
                radial-gradient(transparent 66%, #c79013 68% 70%, transparent 72%) 0 16px / 32px 32px #06208a;
            opacity: 0.52;
            pointer-events: none;
        }

        .stepper-step--merchant > * {
            position: relative;
            z-index: 1;
        }

        .stepper-completed .stepper-circle {
            background-color: #020dff;
            color: white;
        }

        .stepper-active {
            border-color: rgba(2, 13, 255, 0.22);
            box-shadow: 0 18px 34px -28px rgba(2, 13, 255, 0.32);
        }

        .stepper-active .stepper-circle {
            border: 2px solid #020dff;
            color: #020dff;
            background: rgba(2, 13, 255, 0.06);
        }

        .stepper-pending .stepper-circle,
        .merchant-onboarding-step-number {
            border: 2px solid #cbd5e1;
            color: #64748b;
            background: #ffffff;
        }

        .stepper-completed .stepper-title,
        .stepper-active .stepper-title {
            color: #0f172a;
        }

        .stepper-pending .stepper-title {
            color: #334155;
        }

        .stepper-completed .stepper-status {
            background-color: rgba(2, 13, 255, 0.1);
            color: #020dff;
        }

        .stepper-step--merchant .stepper-circle {
            background: #67e8f9;
            color: #020617;
        }

        .repayment-flow-circle {
            margin-top: 0;
            background: #67e8f9;
            color: #020617;
            box-shadow: 0 12px 24px -18px rgba(6, 182, 212, 0.75);
        }

        .stepper-step--merchant .stepper-title,
        .stepper-step--merchant .stepper-time {
            color: rgba(255, 255, 255, 0.96);
        }

        .stepper-step--merchant .stepper-status {
            background: rgba(103, 232, 249, 0.16);
            color: #cffafe;
        }

        .stepper-active .stepper-status {
            background-color: rgba(14, 165, 233, 0.12);
            color: #0369a1;
        }

        .stepper-pending .stepper-status {
            background-color: #f1f5f9;
            color: #64748b;
        }

        .stepper-time {
            color: #64748b;
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

    .bw-dashboard-user {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0 0.9rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.34);
        background: rgba(255, 255, 255, 0.82);
        color: #0f172a;
        box-shadow: 0 12px 26px -22px rgba(15, 23, 42, 0.42);
        backdrop-filter: blur(12px);
    }

    .bw-dashboard-user__dot {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 999px;
        background: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.16);
        flex: 0 0 auto;
    }

    .bw-dashboard-user__label {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #64748b;
    }

    .bw-dashboard-user__name {
        max-width: 11rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.9rem;
        font-weight: 900;
        color: #0f172a;
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

    .finance-lead-preview {
        display: block;
        width: 100%;
        height: auto;
        border-radius: 5px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        box-shadow: 0 16px 32px -24px rgba(15, 23, 42, 0.45);
        object-fit: cover;
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

    .bw-merchant-story-card {
        position: relative;
        width: 100%;
        min-height: 260px;
        border-radius: 18px;
        padding: 1rem;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        background: rgba(2, 13, 255, 0.58);
        color: #fff;
        transition: transform 0.5s ease, border-radius 0.5s ease;
        box-shadow: 0 20px 42px -28px rgba(2, 13, 255, 0.45);
    }

    .bw-merchant-story-card::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(35.36% 35.36% at 100% 25%, transparent 66%, #c79013 68% 70%, transparent 72%) 32px 32px / 64px 64px,
            radial-gradient(35.36% 35.36% at 0 75%, transparent 66%, #c79013 68% 70%, transparent 72%) 32px 32px / 64px 64px,
            radial-gradient(35.36% 35.36% at 100% 25%, transparent 66%, #c79013 68% 70%, transparent 72%) 0 0 / 64px 64px,
            radial-gradient(35.36% 35.36% at 0 75%, transparent 66%, #c79013 68% 70%, transparent 72%) 0 0 / 64px 64px,
            repeating-conic-gradient(rgba(7, 28, 141, 0.88) 0 25%, transparent 0 50%) 0 0 / 64px 64px,
            radial-gradient(transparent 66%, #c79013 68% 70%, transparent 72%) 0 16px / 32px 32px #06208a;
        opacity: 0.52;
        transition: opacity 0.3s ease;
    }

    .bw-merchant-story-card:hover {
        transform: scale(1.03);
        border-radius: 22px;
    }

    .bw-merchant-story-card:hover::after {
        opacity: 0.28;
    }

    .bw-merchant-story-card__details {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        transform: translateY(58%);
        transition: transform 0.5s ease;
    }

    .bw-merchant-story-card:hover .bw-merchant-story-card__details {
        transform: translateY(0);
        transition-delay: 0.22s;
    }

    .bw-merchant-story-card__header {
        position: relative;
        width: max-content;
        font-size: 0.9rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .bw-merchant-story-card__header::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: -4px;
        width: calc(100% + 1rem);
        height: 2.5px;
        background: #67e8f9;
        opacity: 0;
        transform: translateX(calc(-100% - 1rem));
        transition: transform 0.5s ease, opacity 0.5s ease;
    }

    .bw-merchant-story-card:hover .bw-merchant-story-card__header::after {
        opacity: 1;
        transform: translateX(-1rem);
    }

    .bw-merchant-story-card__text {
        max-width: 28rem;
        font-size: 0.92rem;
        line-height: 1.65;
        color: rgba(255, 255, 255, 0.92);
    }

    .bw-merchant-story-card__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: max-content;
        border-radius: 999px;
        background: #67e8f9;
        color: #020617;
        padding: 0.3rem 0.7rem;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .bw-merchant-story-card {
            min-height: 220px;
        }

        .bw-merchant-story-card__details {
            transform: translateY(0);
        }

        .bw-merchant-story-card__header::after {
            opacity: 1;
            transform: translateX(-1rem);
        }
    }

    .welcome-entry-button {
        --welcome_entry_line_color: #00135c;
        --welcome_entry_back_color: #defffa;
        position: relative;
        z-index: 0;
        display: inline-block;
        width: min(240px, 100%);
        height: 56px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        color: var(--welcome_entry_line_color);
        letter-spacing: 2px;
        transition: all 0.3s ease;
    }

    .welcome-entry-button__text {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100%;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .welcome-entry-button::before,
    .welcome-entry-button::after,
    .welcome-entry-button__text::before,
    .welcome-entry-button__text::after {
        content: "";
        position: absolute;
        height: 3px;
        border-radius: 2px;
        background: var(--welcome_entry_line_color);
        transition: all 0.5s ease;
    }

    .welcome-entry-button::before {
        top: 0;
        left: 54px;
        width: calc(100% - 56px * 2 - 16px);
    }

    .welcome-entry-button::after {
        top: 0;
        right: 54px;
        width: 8px;
    }

    .welcome-entry-button__text::before {
        bottom: 0;
        right: 54px;
        width: calc(100% - 56px * 2 - 16px);
    }

    .welcome-entry-button__text::after {
        bottom: 0;
        left: 54px;
        width: 8px;
    }

    .welcome-entry-button__line {
        position: absolute;
        top: 0;
        width: 56px;
        height: 100%;
        overflow: hidden;
    }

    .welcome-entry-button__line::before {
        content: "";
        position: absolute;
        top: 0;
        width: 150%;
        height: 100%;
        box-sizing: border-box;
        border-radius: 300px;
        border: solid 3px var(--welcome_entry_line_color);
    }

    .welcome-entry-button__line:nth-child(1),
    .welcome-entry-button__line:nth-child(1)::before {
        left: 0;
    }

    .welcome-entry-button__line:nth-child(2),
    .welcome-entry-button__line:nth-child(2)::before {
        right: 0;
    }

    .welcome-entry-button:hover {
        letter-spacing: 4px;
    }

    .welcome-entry-button:hover::before,
    .welcome-entry-button:hover .welcome-entry-button__text::before {
        width: 8px;
    }

    .welcome-entry-button:hover::after,
    .welcome-entry-button:hover .welcome-entry-button__text::after {
        width: calc(100% - 56px * 2 - 16px);
    }

    .welcome-entry-button__drow1,
    .welcome-entry-button__drow2 {
        position: absolute;
        z-index: -1;
        border-radius: 16px;
        transform-origin: 16px 16px;
    }

    .welcome-entry-button__drow1 {
        top: -16px;
        left: 40px;
        width: 32px;
        height: 0;
        transform: rotate(30deg);
    }

    .welcome-entry-button__drow2 {
        top: 44px;
        left: 77px;
        width: 32px;
        height: 0;
        transform: rotate(-127deg);
    }

    .welcome-entry-button__drow1::before,
    .welcome-entry-button__drow1::after,
    .welcome-entry-button__drow2::before,
    .welcome-entry-button__drow2::after {
        content: "";
        position: absolute;
    }

    .welcome-entry-button__drow1::before {
        bottom: 0;
        left: 0;
        width: 0;
        height: 32px;
        border-radius: 16px;
        transform-origin: 16px 16px;
        transform: rotate(-60deg);
    }

    .welcome-entry-button__drow1::after {
        top: -10px;
        left: 45px;
        width: 0;
        height: 32px;
        border-radius: 16px;
        transform-origin: 16px 16px;
        transform: rotate(69deg);
    }

    .welcome-entry-button__drow2::before {
        bottom: 0;
        left: 0;
        width: 0;
        height: 32px;
        border-radius: 16px;
        transform-origin: 16px 16px;
        transform: rotate(-146deg);
    }

    .welcome-entry-button__drow2::after {
        bottom: 26px;
        left: -40px;
        width: 0;
        height: 32px;
        border-radius: 16px;
        transform-origin: 16px 16px;
        transform: rotate(-262deg);
    }

    .welcome-entry-button__drow1,
    .welcome-entry-button__drow1::before,
    .welcome-entry-button__drow1::after,
    .welcome-entry-button__drow2,
    .welcome-entry-button__drow2::before,
    .welcome-entry-button__drow2::after {
        background: var(--welcome_entry_back_color);
    }

    .welcome-entry-button:hover .welcome-entry-button__drow1 {
        animation: welcome-entry-drow1 ease-in 0.06s;
        animation-fill-mode: forwards;
    }

    .welcome-entry-button:hover .welcome-entry-button__drow1::before {
        animation: welcome-entry-drow2 linear 0.08s 0.06s;
        animation-fill-mode: forwards;
    }

    .welcome-entry-button:hover .welcome-entry-button__drow1::after {
        animation: welcome-entry-drow3 linear 0.03s 0.14s;
        animation-fill-mode: forwards;
    }

    .welcome-entry-button:hover .welcome-entry-button__drow2 {
        animation: welcome-entry-drow4 linear 0.06s 0.2s;
        animation-fill-mode: forwards;
    }

    .welcome-entry-button:hover .welcome-entry-button__drow2::before {
        animation: welcome-entry-drow3 linear 0.03s 0.26s;
        animation-fill-mode: forwards;
    }

    .welcome-entry-button:hover .welcome-entry-button__drow2::after {
        animation: welcome-entry-drow5 linear 0.06s 0.32s;
        animation-fill-mode: forwards;
    }

    @keyframes welcome-entry-drow1 {
        0% { height: 0; }
        100% { height: 100px; }
    }

    @keyframes welcome-entry-drow2 {
        0% { width: 0; opacity: 0; }
        10% { opacity: 0; }
        11% { opacity: 1; }
        100% { width: 120px; }
    }

    @keyframes welcome-entry-drow3 {
        0% { width: 0; }
        100% { width: 80px; }
    }

    @keyframes welcome-entry-drow4 {
        0% { height: 0; }
        100% { height: 120px; }
    }

    @keyframes welcome-entry-drow5 {
        0% { width: 0; }
        100% { width: 124px; }
    }

    .prov545-plan {
        border-radius: 1rem;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: rgba(255, 255, 255, 0.88);
        padding: 1rem;
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.32);
    }

    .prov545-plan__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .prov545-plan__title {
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.1;
        font-weight: 900;
        letter-spacing: -0.02em;
        color: #0f172a;
    }

    .prov545-plan__chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.18rem 0.6rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        color: #166534;
        background: rgba(34, 197, 94, 0.16);
        border: 1px solid rgba(34, 197, 94, 0.28);
        white-space: nowrap;
    }

    .prov545-pricing-card {
        width: 100%;
        position: relative;
        overflow: hidden;
        border-radius: 2rem;
        padding: 1.35rem;
        background: rgba(2, 13, 255, 0.58);
        color: #111827;
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        box-shadow: 0 18px 40px -28px rgba(202, 138, 4, 0.55);
    }

    .prov545-pricing-card:hover {
        transform: scale(0.98);
        box-shadow: 0 22px 46px -30px rgba(202, 138, 4, 0.6);
    }

    .prov545-pricing-card:active {
        transform: scale(0.94);
    }

    .prov545-pricing-card__content {
        position: relative;
        z-index: 2;
        display: flex;
        min-height: 175px;
        flex-direction: column;
        justify-content: space-between;
        gap: 3.5rem;
        transition: transform 0.4s ease;
    }

    .prov545-pricing-card:hover .prov545-pricing-card__content {
        transform: scale(0.97);
    }

    .prov545-pricing-card__top,
    .prov545-pricing-card__bottom {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }

    .prov545-pricing-card__top p,
    .prov545-pricing-card__bottom p {
        margin: 0;
        font-weight: 700;
    }

    .prov545-pricing-card__index {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .prov545-pricing-card__bottom {
        align-items: flex-end;
    }

    .prov545-pricing-card__amount {
        font-size: 1.4rem;
        font-weight: 900;
        letter-spacing: -0.04em;
        line-height: 1;
    }

    .prov545-pricing-card__note {
        margin-top: 0.3rem !important;
        font-size: 0.82rem;
        font-weight: 700;
        color: rgba(17, 24, 39, 0.72);
    }

    .prov545-pricing-card__bottom svg,
    .prov545-pricing-card__image svg {
        fill: currentColor;
    }

    .prov545-pricing-card__bottom svg {
        width: 1.9rem;
        height: 1.9rem;
        color: rgba(17, 24, 39, 0.72);
        transition: transform 0.4s ease;
    }

    .prov545-pricing-card__image {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        pointer-events: none;
    }

    .prov545-pricing-card__image svg {
        width: 4rem;
        height: 4rem;
        color: rgba(17, 24, 39, 0.18);
        transition: transform 0.4s ease;
    }

    .prov545-pricing-card:hover .prov545-pricing-card__image svg,
    .prov545-pricing-card:hover .prov545-pricing-card__bottom svg {
        transform: scale(1.05);
    }

    .prov545-plan__features {
        margin-top: 1rem;
    }

    .prov545-plan__features-title {
        margin: 0 0 0.55rem 0;
        font-size: 0.85rem;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.01em;
    }

    .prov545-plan__features-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 0.4rem;
    }

    .prov545-plan__features-list li {
        display: flex;
        gap: 0.55rem;
        font-size: 0.85rem;
        color: rgba(51, 65, 85, 0.95);
        line-height: 1.4;
    }

    .prov545-plan__features-list li::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 999px;
        margin-top: 0.4rem;
        flex: 0 0 auto;
        background: rgba(47, 100, 216, 0.14);
        border: 1px solid rgba(47, 100, 216, 0.3);
    }

    .steps-main .bw-steps-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .steps-main .bw-step-tab {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1 1 220px;
        min-height: 56px;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.65);
        color: #0f172a;
        text-align: left;
        cursor: default;
        user-select: none;
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease, background-color 180ms ease;
    }

    .steps-main .bw-step-tab::after {
        content: "";
        position: absolute;
        top: 50%;
        right: -10px;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
        border-left: 10px solid rgba(226, 232, 240, 0.95);
        opacity: 0.8;
    }

    .steps-main .bw-step-tab::before {
        content: "";
        position: absolute;
        top: 50%;
        right: -9px;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-top: 9px solid transparent;
        border-bottom: 9px solid transparent;
        border-left: 9px solid rgba(255, 255, 255, 0.65);
        opacity: 0.95;
    }

    .steps-main .bw-step-tab.is-last::after,
    .steps-main .bw-step-tab.is-last::before {
        display: none;
    }

    .steps-main .bw-step-tab.is-active {
        border-color: rgba(2, 13, 255, 0.35);
        background: linear-gradient(135deg, rgba(2, 13, 255, 0.12), rgba(0, 221, 235, 0.08));
        box-shadow: 0 14px 30px -26px rgba(2, 13, 255, 0.55);
    }

    .steps-main .bw-step-tab.is-incomplete {
        opacity: 0.88;
    }

    .steps-main .bw-step-ico {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: rgba(2, 13, 255, 0.1);
        color: #020DFF;
        flex: 0 0 auto;
    }

    .steps-main .bw-step-tab.is-incomplete .bw-step-ico {
        background: rgba(148, 163, 184, 0.2);
        color: rgba(51, 65, 85, 0.85);
    }

    .steps-main .bw-step-ico i {
        font-size: 18px;
        line-height: 1;
        display: block;
    }

    .steps-main .bw-step-title {
        font-weight: 900;
        font-size: 0.92rem;
        letter-spacing: -0.01em;
        line-height: 1.15;
    }

    .steps-main .bw-step-text p {
        margin: 2px 0 0 0;
        font-size: 0.72rem;
        color: rgba(71, 85, 105, 0.95);
        line-height: 1.25;
    }

    @media (max-width: 768px) {
        .steps-main .bw-step-tab::after,
        .steps-main .bw-step-tab::before {
            display: none;
        }
    }

    .button-86 {
        all: unset;
        width: 100px;
        height: 30px;
        font-size: 16px;
        background: transparent;
        border: none;
        position: relative;
        color: #f0f0f0;
        cursor: pointer;
        z-index: 1;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        user-select: none;
        -webkit-user-select: none;
        touch-action: manipulation;
    }

    .button-86::after,
    .button-86::before {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        z-index: -99999;
        transition: all .4s;
    }

    .button-86::before {
        transform: translate(0%, 0%);
        width: 100%;
        height: 100%;
        background: #28282d;
        border-radius: 10px;
    }

    .button-86::after {
        transform: translate(10px, 10px);
        width: 35px;
        height: 35px;
        background: #ffffff15;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        border-radius: 50px;
    }

    .button-86:hover::before {
        transform: translate(5%, 20%);
        width: 110%;
        height: 110%;
    }

    .button-86:hover::after {
        border-radius: 10px;
        transform: translate(0, 0);
        width: 100%;
        height: 100%;
    }

    .button-86:active::after {
        transition: 0s;
        transform: translate(0, 5%);
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
        z-index: 2;
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
        z-index: 3;
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
        z-index: 3;
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
        z-index: 3;
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

        .auth-stepper-track {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            align-items: stretch;
        }

        .stepper-step {
            min-height: 100%;
        }

    }

    @media (max-width: 767px) {
        .voucher-split-card__media {
            min-height: 260px;
        }

        .stepper-step {
            padding: 1rem 0.95rem;
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
        <script>
            (function initScrollNavigator() {
                const boot = () => {
                    const button = document.querySelector('[data-scroll-nav-button]');
                    const root = document.querySelector('[data-scroll-nav-root]');
                    const rail = document.querySelector('[data-scroll-nav-rail]');
                    const thumb = document.querySelector('[data-scroll-nav-thumb]');
                    if (!button || !root || !rail || !thumb) return;

                    const cookieBar = document.getElementById('cookieConsentBar');
                    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    const isCookieBarVisible = () => cookieBar && !cookieBar.classList.contains('hidden') && window.getComputedStyle(cookieBar).display !== 'none';
                    const getMaxScroll = () => Math.max(document.documentElement.scrollHeight - window.innerHeight, 0);

                    const setBottomOffset = () => {
                        let offset = window.innerWidth < 768 ? 16 : 20;

                        if (isCookieBarVisible()) {
                            offset += cookieBar.getBoundingClientRect().height + 12;
                        }

                        button.style.setProperty('--scroll-nav-bottom', `${offset}px`);
                        rail.style.setProperty('--scroll-nav-bottom', `${offset}px`);
                    };

                    const hasOverflow = () => getMaxScroll() > 120;
                    const isNearBottom = () => window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 140;
                    let updateFrame = null;
                    let dragPointerId = null;
                    let dragThumbOffset = 0;

                    const scrollToTop = () => {
                        window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                    };

                    const scrollToProgress = (progress, smooth = false) => {
                        window.scrollTo({
                            top: progress * getMaxScroll(),
                            behavior: smooth && !prefersReducedMotion ? 'smooth' : 'auto',
                        });
                    };

                    const scrollToNextSection = () => {
                        const nextSection = Array.from(root.children).find((section) => section.getBoundingClientRect().top > 110);

                        if (!nextSection) {
                            window.scrollTo({ top: document.documentElement.scrollHeight, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                            return;
                        }

                        const top = Math.max(window.scrollY + nextSection.getBoundingClientRect().top - 24, 0);
                        window.scrollTo({ top, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                    };

                    const updateThumb = () => {
                        const railHeight = rail.clientHeight;
                        const maxScroll = getMaxScroll();
                        const visibleRatio = document.documentElement.scrollHeight > 0
                            ? window.innerHeight / document.documentElement.scrollHeight
                            : 1;
                        const thumbHeight = maxScroll > 0
                            ? Math.max(Math.round(railHeight * visibleRatio), 44)
                            : railHeight;
                        const maxTravel = Math.max(railHeight - thumbHeight, 0);
                        const progress = maxScroll > 0 ? window.scrollY / maxScroll : 0;

                        thumb.style.height = `${thumbHeight}px`;
                        thumb.style.transform = `translate3d(0, ${maxTravel * progress}px, 0)`;
                    };

                    const update = () => {
                        const hideNav = !hasOverflow();
                        button.hidden = hideNav;
                        rail.hidden = hideNav;
                        button.classList.toggle('is-returning', isNearBottom());

                        const label = isNearBottom() ? 'Back to top' : 'Scroll to next section';
                        button.setAttribute('aria-label', label);
                        button.title = label;

                        setBottomOffset();
                        updateThumb();
                    };

                    const setScrollFromClientY = (clientY) => {
                        const rect = rail.getBoundingClientRect();
                        const thumbHeight = thumb.getBoundingClientRect().height;
                        const maxTravel = Math.max(rect.height - thumbHeight, 1);
                        const thumbTop = Math.min(
                            Math.max(clientY - rect.top - dragThumbOffset, 0),
                            maxTravel
                        );

                        scrollToProgress(thumbTop / maxTravel, false);
                    };

                    const scheduleUpdate = () => {
                        if (updateFrame !== null) return;

                        updateFrame = window.requestAnimationFrame(() => {
                            updateFrame = null;
                            update();
                        });
                    };

                    button.addEventListener('click', () => {
                        if (isNearBottom()) {
                            scrollToTop();
                            return;
                        }

                        scrollToNextSection();
                    });

                    rail.addEventListener('pointerdown', (event) => {
                        if (button.hidden) return;

                        const thumbRect = thumb.getBoundingClientRect();
                        const clickedThumb = thumb.contains(event.target);
                        dragPointerId = event.pointerId;
                        dragThumbOffset = clickedThumb
                            ? Math.min(Math.max(event.clientY - thumbRect.top, 0), thumbRect.height)
                            : thumbRect.height / 2;

                        rail.classList.add('is-dragging');
                        document.body.classList.add('scroll-nav-dragging');

                        if (typeof rail.setPointerCapture === 'function') {
                            rail.setPointerCapture(event.pointerId);
                        }

                        setScrollFromClientY(event.clientY);
                        event.preventDefault();
                    });

                    rail.addEventListener('pointermove', (event) => {
                        if (dragPointerId !== event.pointerId) return;
                        setScrollFromClientY(event.clientY);
                        event.preventDefault();
                    });

                    const stopDragging = (event) => {
                        if (dragPointerId === null || dragPointerId !== event.pointerId) return;

                        if (typeof rail.hasPointerCapture === 'function' && rail.hasPointerCapture(event.pointerId)) {
                            rail.releasePointerCapture(event.pointerId);
                        }

                        dragPointerId = null;
                        rail.classList.remove('is-dragging');
                        document.body.classList.remove('scroll-nav-dragging');
                    };

                    rail.addEventListener('pointerup', stopDragging);
                    rail.addEventListener('pointercancel', stopDragging);

                    window.addEventListener('scroll', scheduleUpdate, { passive: true });
                    window.addEventListener('resize', scheduleUpdate);

                    if (cookieBar && 'ResizeObserver' in window) {
                        new ResizeObserver(scheduleUpdate).observe(cookieBar);
                    }

                    if (cookieBar && 'MutationObserver' in window) {
                        new MutationObserver(scheduleUpdate).observe(cookieBar, {
                            attributes: true,
                            attributeFilter: ['class', 'style'],
                        });
                    }

                    update();
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', boot);
                } else {
                    boot();
                }
            })();
        </script>
        @php
            $chartLocal = 'vendor/chart.js/Chart.min.js';
            $chartLocalPath = public_path($chartLocal);
        $chartSrc = is_file($chartLocalPath)
            ? asset($chartLocal).'?v='.filemtime($chartLocalPath)
            : 'https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js';
    @endphp
	    <script src="{{ $chartSrc }}"></script>
        <script src="{{ asset('vendor/datamaps/d3.min.js') }}"></script>
        <script src="{{ asset('vendor/datamaps/topojson.min.js') }}"></script>
        <script src="{{ asset('vendor/datamaps/datamaps.world.min.js') }}"></script>
        <script>
            (function initAuthSectionScroll() {
                const boot = () => {
                    const triggers = document.querySelectorAll('[data-auth-scroll-trigger]');
                    if (!triggers.length) return;

                    triggers.forEach((trigger) => {
                        trigger.addEventListener('click', (event) => {
                            const targetId = trigger.getAttribute('data-auth-scroll-target');
                            if (!targetId) return;

                            const target = document.getElementById(targetId);
                            if (!target) return;

                            event.preventDefault();
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
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
                const markMediaLoaded = () => container.classList.add('is-media-loaded');

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
                        markMediaLoaded();
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
                video.addEventListener('canplay', () => {
                    setLoading(false);
                    markMediaLoaded();
                });
                video.addEventListener('loadeddata', markMediaLoaded, { once: true });

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

        (function initWelcomeMediaSkeletons() {
            const boot = () => {
                const shells = Array.from(document.querySelectorAll('[data-welcome-media]'));
                shells.forEach((shell) => {
                    const media = shell.querySelector('[data-welcome-media-target]');
                    if (!media) return;

                    const done = () => shell.classList.add('is-media-loaded');

                    media.addEventListener('error', done, { once: true });

                    if (media.complete) {
                        if (typeof media.decode === 'function') {
                            media.decode().then(done).catch(done);
                        } else {
                            done();
                        }
                        return;
                    }

                    media.addEventListener('load', () => {
                        if (typeof media.decode === 'function') {
                            media.decode().then(done).catch(done);
                        } else {
                            done();
                        }
                    }, { once: true });
                });
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
            (function initBwiserOperatingMap() {
                const boot = () => {
                    const el = document.getElementById('bwiser-countries-datamap');
                    if (!el || typeof Datamap === 'undefined') return;
                    if (el.dataset.loaded === 'true') return;

                    const data = {
                        ZAF: {
                            fillKey: 'LIVE',
                            description: 'Bwiser is currently operating in South Africa.'
                        }
                    };

                    const map = new Datamap({
                        element: el,
                        projection: 'mercator',
                        responsive: true,
                        fills: {
                            defaultFill: '#E2E8F0',
                            LIVE: '#020DFF'
                        },
                        data,
                        geographyConfig: {
                            borderColor: 'rgba(148, 163, 184, 0.45)',
                            highlightFillColor: '#1D4ED8',
                            highlightBorderColor: '#020DFF',
                            popupTemplate: function (geo, countryData) {
                                const name = geo && geo.properties ? geo.properties.name : 'Country';
                                const message = countryData && countryData.description
                                    ? countryData.description
                                    : 'Bwiser is preparing for rollout in this market.';

                                return `
                                    <div style="min-width:180px;border-radius:14px;background:#ffffff;padding:12px;box-shadow:0 18px 42px -24px rgba(15,23,42,0.35);border:1px solid rgba(226,232,240,0.9);">
                                        <p style="margin:0 0 4px;font-size:10px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#020DFF;">Bwiser</p>
                                        <p style="margin:0 0 6px;font-size:14px;font-weight:600;color:#0f172a;">${name}</p>
                                        <p style="margin:0;font-size:12px;line-height:1.55;color:#475569;">${message}</p>
                                    </div>
                                `;
                            }
                        }
                    });

                    el.dataset.loaded = 'true';
                    window.addEventListener('resize', function () {
                        map.resize();
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
	    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
	    <script>
	        (function initTaplessPaymentsModal() {
	            const openers = Array.from(document.querySelectorAll('[data-tapless-open]'));
	            if (!openers.length) return;

	            const imageUrl = @json(asset('images/BWISER.jpg'));
	            const modalHtml = `
	                <div style="text-align:left;">
	                    <img src="${imageUrl}" alt="Bwiser tapless payments" style="display:block;width:100%;height:100%;min-height:260px;max-height:48vh;object-fit:cover;border-radius:14px;margin-bottom:12px;">
	                    <p style="margin:0 0 10px;font-size:13px;line-height:1.6;color:#475569;">
	                        Bwiser tapless payments let merchants validate and process voucher-linked payments without a physical card tap, using USSD, geofencing, and voucher verification to confirm the right user at the right place.
	                    </p>
	                    <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#475569;">
	                        In practice, that means faster checkout, less hardware dependency, and a payment experience built for stations, drivers, and merchant environments where traditional card processes are not always the best fit.
	                    </p>
	                    <div style="border-radius:14px;background:#eff6ff;padding:12px;">
	                        <p style="margin:0 0 5px;font-size:10px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#1d4ed8;">What it means</p>
	                        <p style="margin:0;font-size:13px;line-height:1.6;color:#334155;">
	                            A secure, location-aware payment process that feels lightweight to the user but still gives merchants strong control and clear reporting.
	                        </p>
	                    </div>
	                </div>
	            `;

	            openers.forEach((btn) => {
	                btn.addEventListener('click', (e) => {
	                    e.preventDefault();

	                    if (typeof Swal !== 'undefined') {
	                        Swal.fire({
	                            title: 'Tapless payments',
	                            html: modalHtml,
	                            width: window.innerWidth >= 768 ? '32rem' : '90vw',
	                            padding: '0.875rem',
	                            confirmButtonText: 'Close',
	                            confirmButtonColor: '#020DFF',
	                            background: '#ffffff',
	                            backdrop: 'rgba(15, 23, 42, 0.58)',
	                            customClass: {
	                                popup: 'rounded-[24px]',
	                                title: 'text-slate-900',
	                                htmlContainer: '!m-0',
	                            },
	                            didOpen: () => {
	                                const container = Swal.getContainer();
	                                if (container) {
	                                    container.style.backdropFilter = 'blur(14px)';
	                                    container.style.webkitBackdropFilter = 'blur(14px)';
	                                }
	                                const popup = Swal.getPopup();
	                                if (popup) {
	                                    popup.style.maxHeight = '90vh';
	                                    popup.style.overflowY = 'auto';
	                                }
	                            },
	                        });
	                        return;
	                    }

	                    window.open(btn.getAttribute('href') || imageUrl, '_blank', 'noopener');
	                });
	            });
	        })();
	    </script>
@endpush
