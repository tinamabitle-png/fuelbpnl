@extends('Layouts.app')

@section('title', 'Bwiser Control Platform')
@section('meta_description', 'Bwiser is a South African fuel finance and payments platform for drivers, stations, vouchers, and settlements.')
@section('canonical', url('/'))
@section('og_image', asset('images/bwsr.png'))

@section('content')
<section class="max-w-7xl mx-auto px-6 pt-16 pb-20">
    <div class="glass rounded-3xl p-8 md:p-12">
        @php
            $recentDrivers = collect((array) (($welcomeStats ?? [])['recent_drivers'] ?? []))->take(4);
        @endphp
        <div class="max-w-4xl">
            <div class="min-w-0">
                <h1 class="brand-font text-4xl md:text-6xl font-semibold text-slate-900 mt-4 leading-tight">
                    Fuel Infrastructure Finance and Voucher Payments, Low Late Fees,
                    <span class="hero-gradient-text block">Built for Real-Time Operations</span>
                </h1>
                <p class="text-slate-600 mt-5 max-w-3xl text-medium">
                    Bwiser connects drivers, stations, and finance teams on one buy now pay later process.
                    We approve fuel financing, issue secure vouchers, redeem instantly at station level, and settle to bank with full audit visibility.
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a class="super-button" href="{{ Route::has('login') ? route('login') : '/login' }}">
                        <span>Get Started</span>
                    </a>
                    {{-- Play Store button hidden until the mobile app is published. --}}
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
                src="{{ asset('images/bwsr.png') }}"
                alt="Bwiser preview"
                class="block w-full h-full object-cover rounded-2xl"
                loading="lazy"
            >
        </div>
    </div>

    <div class="glass rounded-2xl p-3 md:p-4 mt-8 overflow-hidden welcome-tween-card">
        <img
            src="{{ asset('images/MsPaballoTsunke.jpg') }}"
            alt="Ms Paballo Tsunke"
            class="welcome-tween-image is-in rounded-2xl"
            loading="lazy"
            data-welcome-tween="slide-in"
            onerror="this.classList.add('is-in'); this.style.opacity='1'; this.style.transform='none';"
        >
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

</section>
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
        background-color: #000;
        padding: 0.625rem 1.5rem;
        text-align: center;
        color: #fff;
        outline: 0;
        transition: all .2s ease;
        text-decoration: none;
    }

    .playstore-button:hover {
        background-color: transparent;
        color: #000;
    }

    .playstore-button .playstore-icon {
        height: 1.5rem;
        width: 1.5rem;
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
        const target = document.querySelector(selector);
        if (!target) return;

        const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const reveal = () => target.classList.add('is-in');
        const resetForTween = () => {
            if (reduceMotion) return;
            target.classList.remove('is-in');
            // Force style flush so re-adding triggers the transition reliably.
            void target.offsetHeight;
        };

        const ready = () => {
            if (target.dataset.tweenDone === '1') return;
            if (!('IntersectionObserver' in window)) {
                resetForTween();
                window.addEventListener('load', () => {
                    reveal();
                    target.dataset.tweenDone = '1';
                }, { once: true });
                return;
            }
            resetForTween();
            const io = new IntersectionObserver((entries) => {
                for (const e of entries) {
                    if (!e.isIntersecting) continue;
                    reveal();
                    target.dataset.tweenDone = '1';
                    io.disconnect();
                    break;
                }
            }, { threshold: 0.18 });
            io.observe(target);
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
@endpush
