@extends('Layouts.app')

@section('title', 'Bwiser Control Platform')
@section('meta_description', 'Bwiser is a South African fuel finance and payments platform for drivers, stations, vouchers, and settlements.')
@section('canonical', url('/'))

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="glass rounded-3xl p-8 md:p-12">
        <h1 class="brand-font text-4xl md:text-6xl font-semibold text-slate-900 mt-4 leading-tight">
            Fuel Infrastructure Finance and Payments for Vouchers, and Settlements
            <span class="hero-gradient-text block">Built for Real-Time Operations</span>
        </h1>
        <p class="text-slate-600 mt-5 max-w-3xl text-medium">
            Bwiser connects drivers, stations, and finance teams on one production-ready platform.
            Approve fuel credit, issue secure vouchers, redeem instantly at station level, and settle to bank with full audit visibility.
        </p>
        <div class="mt-7 flex flex-wrap gap-3">
            <a class="super-button" href="{{ Route::has('login') ? route('login') : '/login' }}">
                <span>Get Started</span>
            </a>
            <button type="button" id="acceptCookiesBtn" class="btn-ghost px-5 py-3 rounded-xl text-sm font-semibold hidden">
                Accept Cookies
            </button>
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
        background: #0a0a0a;
        border-radius: inherit;
        z-index: -1;
    }

    .super-button:hover {
        transform: scale(1.05);
        box-shadow: 0 0 40px rgba(0, 255, 255, 0.2);
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
        const button = document.getElementById('acceptCookiesBtn');
        if (!button) return;
        if (localStorage.getItem(key) === 'accepted') return;
        button.classList.remove('hidden');
        button.addEventListener('click', function () {
            localStorage.setItem(key, 'accepted');
            document.cookie = "bwiser_cookie_consent=accepted; path=/; max-age=31536000; SameSite=Lax";
            button.classList.add('hidden');
        });
    })();
</script>
@endsection
