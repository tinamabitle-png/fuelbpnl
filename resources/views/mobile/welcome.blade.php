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
                    <a href="{{ route('register') }}" class="rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white">
                        Register
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
