@extends('Layouts.app')

@section('title', 'Bwiser Control Platform')
@section('meta_description', 'Bwiser is a South African fuel finance and payments platform for drivers, stations, vouchers, and settlements.')
@section('canonical', url('/'))

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="glass rounded-3xl p-8 md:p-12">
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
            <a class="playstore-button" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="playstore-icon" viewBox="0 0 512 512" aria-hidden="true">
                    <path d="M99.617 8.057a50.191 50.191 0 00-38.815-6.713l230.932 230.933 74.846-74.846L99.617 8.057zM32.139 20.116c-6.441 8.563-10.148 19.077-10.148 30.199v411.358c0 11.123 3.708 21.636 10.148 30.199l235.877-235.877L32.139 20.116zM464.261 212.087l-67.266-37.637-81.544 81.544 81.548 81.548 67.273-37.64c16.117-9.03 25.738-25.442 25.738-43.908s-9.621-34.877-25.749-43.907zM291.733 279.711L60.815 510.629c3.786.891 7.639 1.371 11.492 1.371a50.275 50.275 0 0027.31-8.07l266.965-149.372-74.849-74.847z"></path>
                </svg>
                <span class="texts">
                    <span class="text-1">GET IT ON</span>
                    <span class="text-2">Google Play</span>
                </span>
            </a>
        </div>
        <!-- <div class="welcome-driver-market mt-8">
            <img
                src="{{ asset('images/illustrations/fuel-delivery-banner.png') }}"
                alt="Fuel delivery illustration"
                class="welcome-driver-market-img"
                loading="lazy"
            >
        </div> -->
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
        <p class="text-xs uppercase tracking-[0.2em] text-blue-600">Trusted Retail Network</p>
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
        .trusted-ticker-track {
            animation-duration: 24s;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .trusted-ticker-track {
            animation: none;
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
@endsection
