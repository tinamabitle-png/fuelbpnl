@extends('mobile.layouts.app')

@section('title', 'Bwiser Fuel Buy Now Pay Later')
@section('meta_description', 'Bwiser fuel finance, login, and registration platform for drivers and merchants in South Africa.')

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                'name' => 'Bwiser Fuel Buy Now Pay Later',
                'url' => url('/'),
                'description' => 'Bwiser fuel finance, login, and registration platform for drivers and merchants in South Africa.',
            ],
            [
                '@type' => 'ItemList',
                'name' => 'Primary navigation',
                'itemListElement' => array_values(array_filter([
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
                    config('services.registration.public_merchant_enabled') ? [
                        '@type' => 'SiteNavigationElement',
                        'position' => 4,
                        'name' => 'Merchant Registration',
                        'url' => route('register.merchant'),
                    ] : null,
                ])),
            ],
        ],
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
<main class="px-4 pb-8 pt-6">
    <div class="mx-auto max-w-md space-y-4">
        <section class="mobile-card p-5">
            @php
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
            <p class="text-xs uppercase tracking-[0.25em] text-blue-700">Bwiser Platform</p>
            <h1 class="mt-3 text-3xl font-semibold leading-tight text-slate-900">
                Credit, Voucher Issuance and Settlement
                <span class="mobile-gradient-text block">For Drivers, Stations and Finance Teams</span>
            </h1>
            <p class="mt-3 text-sm text-slate-600">
                Bwiser connects field users and operations teams with real-time voucher processing, credit control, and settlement workflows backed by your existing APIs.
            </p>
            <div class="mt-5 grid grid-cols-1 gap-2">
                @auth
                    @if($dashboardUrl)
                        <a href="{{ $dashboardUrl }}" class="rounded-xl bg-slate-900 px-4 py-3 text-center text-sm font-semibold text-white">
                            Go to Dashboard
                        </a>
                    @endif
                @endauth
                @guest
                    <a href="#auth-section" class="rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white">
                        Get Started
                    </a>
                    @if(config('services.registration.public_merchant_enabled'))
                        <a href="{{ route('register.merchant') }}" class="rounded-xl border border-blue-200 px-4 py-3 text-center text-sm font-semibold text-blue-700 bg-white">
                            Merchant Registration
                        </a>
                    @endif
                    <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 bg-white">
                        Login
                    </a>
                @endguest
            </div>
        </section>

        @guest
            @php
                $authLoginStep = config('services.registration.public_merchant_enabled') ? 3 : 2;
            @endphp
            <section id="auth-section" class="mobile-card p-4 scroll-mt-20">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Get Started</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">Choose your Bwiser path</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Start with registration, jump into merchant onboarding, or sign in if you already have access.
                </p>

                <div class="mt-4 grid gap-3">
                    <a href="{{ route('register') }}" class="rounded-2xl border border-blue-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-[#020DFF] bg-[#020DFF]/5 text-sm font-bold text-[#020DFF]">1</div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">Register</p>
                                <span class="mt-1 inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700">Start Here</span>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">Create your account and continue into onboarding.</p>
                            </div>
                        </div>
                    </a>

                    @if(config('services.registration.public_merchant_enabled'))
                        <a href="{{ route('register.merchant') }}" class="rounded-2xl border border-blue-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#020DFF] text-white">
                                    <svg viewBox="0 0 16 16" fill="currentColor" width="16" height="16" aria-hidden="true" focusable="false">
                                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"></path>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Merchant Registration</p>
                                    <span class="mt-1 inline-flex rounded-full bg-[#020DFF]/10 px-2.5 py-1 text-[11px] font-semibold text-[#020DFF]">Merchant Flow</span>
                                    <p class="mt-2 text-xs leading-relaxed text-slate-600">Register a station or merchant account for vouchers and settlements.</p>
                                </div>
                            </div>
                        </a>
                    @endif

                    <a href="{{ route('login') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-sm font-bold text-slate-500">{{ $authLoginStep }}</div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">Login</p>
                                <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Returning Users</span>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">Sign in to reach your dashboard, vouchers, and settlement tools.</p>
                            </div>
                        </div>
                    </a>
                </div>
            </section>
        @endguest

        <section class="mobile-card p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Trusted Network</p>
            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-700">
                @foreach (['Astron', 'BP', 'Engen', 'Sasol', 'Shell', 'Total Energies'] as $brand)
                    <span class="rounded-full border border-slate-300 bg-white px-3 py-1.5">{{ $brand }}</span>
                @endforeach
            </div>
        </section>

        <section class="mobile-card p-4">
            <div class="mt-3 relative">
	                <img
	                    src="{{ asset('images/shopping.jpg') }}"
	                    alt="Shopping"
	                    class="float-left block h-32 w-auto object-contain"
	                    loading="lazy"
	                >
            </div>
	            <div class="mt-3 flex flex-wrap items-center gap-2">
	                @foreach ([
	                    ['name' => 'Uber', 'path' => 'images/driver-platforms/uber.svg'],
	                    ['name' => 'Uber Eats', 'path' => 'images/driver-platforms/uber-eats.svg'],
	                    ['name' => 'inDrive', 'path' => 'images/driver-platforms/indrive.png'],
	                    ['name' => 'Takealot', 'path' => 'images/driver-platforms/takealot.png'],
	                    ['name' => 'Mr D', 'path' => 'images/driver-platforms/mrd.png'],
	                    ['name' => 'Sixty60', 'path' => 'images/driver-platforms/sixty60.png'],
	                ] as $p)
	                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
	                        <img src="{{ asset($p['path']) }}" alt="{{ $p['name'] }} logo" class="h-12 w-auto object-contain" loading="lazy">
	                    </div>
	                @endforeach
	            </div>
	        </section>
    </div>
</main>
@endsection
