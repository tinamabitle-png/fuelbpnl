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

        body.mobile-shell {
            overflow-y: scroll;
        }

        [data-scroll-nav-root] .mobile-card,
        [data-scroll-nav-root] [class*="-card"],
        [data-scroll-nav-root] [class*="__card"],
        [data-scroll-nav-root] [class*="-shell"],
        [data-scroll-nav-root] [class*="-panel"],
        [data-scroll-nav-root] [class*="-box"],
        [data-scroll-nav-root] [class*="-tab"] {
            border-radius: 1.5rem;
        }

        [data-scroll-nav-root] img,
        [data-scroll-nav-root] video,
        [data-scroll-nav-root] canvas,
        [data-scroll-nav-root] iframe {
            border-radius: inherit;
        }

        .mobile-auth-step--merchant {
            position: relative;
            overflow: hidden;
            background: rgba(2, 13, 255, 0.6);
            border-color: rgba(103, 232, 249, 0.22);
            box-shadow: 0 20px 38px -30px rgba(2, 13, 255, 0.42);
        }

        .mobile-auth-step--merchant::before {
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

        .mobile-auth-step--merchant > * {
            position: relative;
            z-index: 1;
        }

        .scroll-nav-fab {
            position: fixed;
            right: 1rem;
            bottom: calc(var(--scroll-nav-bottom, 1rem) + env(safe-area-inset-bottom, 0px));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.15rem;
            height: 3.15rem;
            border-radius: 999px;
            border: 1px solid rgba(2, 13, 255, 0.16);
            background: rgba(255, 255, 255, 0.94);
            color: #020dff;
            box-shadow: 0 22px 36px -24px rgba(2, 13, 255, 0.42);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 2147483600;
            pointer-events: auto;
            touch-action: manipulation;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .scroll-nav-fab[hidden] {
            display: none;
        }

        .scroll-nav-rail {
            position: fixed;
            top: 6rem;
            right: 0.45rem;
            bottom: calc(var(--scroll-nav-bottom, 1rem) + env(safe-area-inset-bottom, 0px));
            width: 0.6rem;
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
            min-height: 2.25rem;
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
            width: 1.3rem;
            height: 1.3rem;
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
    </style>
@endpush

@section('content')
<main class="px-4 pb-8 pt-6">
    <div class="mx-auto max-w-md space-y-4" data-scroll-nav-root>
        @if(session('success') || session('error') || $errors->any())
            <div class="mobile-card border {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900' }} p-4 text-sm font-semibold">
                {{ session('success') ?: (session('error') ?: $errors->first()) }}
            </div>
        @endif

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
                    <a
                        href="{{ route('register') }}"
                        class="rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white"
                        data-auth-scroll-trigger
                        data-auth-scroll-target="auth-section"
                    >
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

        @php($financeTeamPlans = \App\Models\FinanceTeamSubscription::plans())
	        <section id="finance-team-pricing" class="mobile-card bg-[#fff0fa] p-4 scroll-mt-20">
	            <p class="text-xs font-bold uppercase tracking-[0.18em] text-pink-500">Finance Teams</p>
	            <h2 class="mt-2 text-xl font-semibold text-[#170b37]">Loan-book subscription plans</h2>

	            <div class="mt-4 space-y-4">
                @foreach($financeTeamPlans as $plan)
                    @php
                        $highlight = (bool) ($plan['highlight'] ?? false);
                        $oldPlan = old('plan_slug');
                        $prefillCompany = $oldPlan === $plan['slug'] ? old('company_name') : '';
                        $prefillEmail = $oldPlan === $plan['slug'] ? old('email') : (auth()->user()?->email ?? '');
                    @endphp
	                    <div class="{{ $highlight ? 'bg-[#ff35b6] text-slate-950' : 'bg-white text-slate-900' }} rounded-3xl p-4 shadow-sm">
	                        <div class="flex items-center justify-between gap-3">
	                            <span class="{{ $highlight ? 'border-slate-950/30 bg-white/80 text-slate-950' : 'border-pink-300 text-pink-500' }} rounded-full border px-3 py-1 text-xs font-bold">{{ $plan['name'] }}</span>
	                            <span class="{{ $highlight ? 'bg-white/80 text-slate-950' : 'bg-pink-50 text-pink-500' }} rounded-full px-2.5 py-1 text-[11px] font-bold">{{ number_format((int) $plan['loan_book_limit']) }} leases</span>
	                        </div>
	                        <div class="mt-4">
	                            <span class="text-3xl font-black">R {{ number_format((float) $plan['amount'], 0) }}</span>
	                            <span class="{{ $highlight ? 'text-slate-950/75' : 'text-slate-500' }} text-xs">/ month</span>
	                        </div>
	                        <p class="{{ $highlight ? 'text-slate-950/85' : 'text-slate-600' }} mt-2 text-xs leading-relaxed">{{ $plan['description'] }}</p>

                        <form method="POST" action="{{ route('finance-team-subscriptions.paystack.start') }}" class="mt-4 space-y-2">
                            @csrf
                            <input type="hidden" name="plan_slug" value="{{ $plan['slug'] }}">
                            <input name="company_name" value="{{ $prefillCompany }}" required placeholder="Finance company" class="w-full rounded-2xl border border-white/30 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400">
                            <input name="email" type="email" value="{{ $prefillEmail }}" required placeholder="Work email" class="w-full rounded-2xl border border-white/30 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400">
                            <button type="submit" class="{{ $highlight ? 'bg-slate-950 text-white' : 'border border-pink-400 bg-pink-500 text-white' }} w-full rounded-full px-4 py-2.5 text-sm font-bold">
                                Pay
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        @guest
            @php
                $authLoginStep = config('services.registration.public_merchant_enabled') ? 3 : 2;
            @endphp
            <section id="auth-section" class="mobile-card p-4 scroll-mt-20">
                <h2 class="text-xl font-semibold text-slate-900">Choose your Bwiser path</h2>
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
                        <a href="{{ route('register.merchant') }}" class="mobile-auth-step--merchant rounded-2xl border border-blue-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-300 text-slate-950">2</div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white">Merchant Registration</p>
                                    <span class="mt-1 inline-flex rounded-full bg-cyan-300/15 px-2.5 py-1 text-[11px] font-semibold text-cyan-100">Merchant Process</span>
                                    <p class="mt-2 text-xs leading-relaxed text-white/90">Register a station or merchant account for vouchers and settlements.</p>
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

                const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const getMaxScroll = () => Math.max(document.documentElement.scrollHeight - window.innerHeight, 0);

                const hasOverflow = () => getMaxScroll() > 120;
                const isNearBottom = () => window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 140;
                let updateFrame = null;
                let dragPointerId = null;
                let dragThumbOffset = 0;
                const scrollToProgress = (progress, smooth = false) => {
                    window.scrollTo({
                        top: progress * getMaxScroll(),
                        behavior: smooth && !prefersReducedMotion ? 'smooth' : 'auto',
                    });
                };

                const updateThumb = () => {
                    const railHeight = rail.clientHeight;
                    const maxScroll = getMaxScroll();
                    const visibleRatio = document.documentElement.scrollHeight > 0
                        ? window.innerHeight / document.documentElement.scrollHeight
                        : 1;
                    const thumbHeight = maxScroll > 0
                        ? Math.max(Math.round(railHeight * visibleRatio), 36)
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
                        window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                        return;
                    }

                    const nextSection = Array.from(root.children).find((section) => section.getBoundingClientRect().top > 96);

                    if (!nextSection) {
                        window.scrollTo({ top: document.documentElement.scrollHeight, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                        return;
                    }

                    const top = Math.max(window.scrollY + nextSection.getBoundingClientRect().top - 16, 0);
                    window.scrollTo({ top, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
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
                update();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
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
@endpush
