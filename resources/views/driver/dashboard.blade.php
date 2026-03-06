@extends('layouts.app')

@section('title', 'Driver Dashboard - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Drive r Dashboard</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">Driver Dashboard</h1>
            <p class="text-slate-600 mt-3">Apply for vouchers, locate stations, and manage repayments.</p>
            <div class="driver-greet-card mt-4">
                <div class="driver-greet-loader">
                    <p>Welcome</p>
                    <div class="driver-greet-words">
                        <span class="driver-greet-word">{{ auth()->user()->name ?? 'Driver' }}</span>
                        <span class="driver-greet-word">to Bwiser</span>
                        <span class="driver-greet-word">Driver</span>
                        <span class="driver-greet-word"> {{ auth()->user()->name ?? 'Driver' }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="driver-header-actions flex flex-col items-start md:items-end gap-3">
           
            <div class="driver-header-cta flex flex-wrap gap-3 md:justify-end">
                <a href="{{ route('driver.vouchers.create') }}" class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold">Apply for Voucher</a>
                <a href="{{ route('driver.repayments.index') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">View Repayments</a>
                <a href="{{ route('driver.profile') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Profile</a>
            </div>
        </div>
    </div>
    @include('driver.partials.nav')

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="driver-active-voucher-card-wrap">
            <article class="driver-active-voucher-card">
                <div class="main-content">
                    <div class="header">
                        <p class="heading">Active Vouchers</p>
                    </div>
                    <p class="driver-active-voucher-count">{{ $activeVoucherCount }}</p>
                </div>
                <p class="footer">Available now</p>
            </article>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Pending Repayments</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $pendingRepayments }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Amount Due</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format($pendingRepaymentAmount, 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Active Stations</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $activeStationCount }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Redeemed Vouchers</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $redeemedVoucherCount }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $redeemedVoucherToday }} today</p>
        </div>
    </div>

    @php
        $saBrands = [
            ['name' => 'Shell', 'slug' => 'shell-sa'],
            ['name' => 'BP', 'slug' => 'bp-southern-africa'],
            ['name' => 'Engen', 'slug' => 'engen'],
            ['name' => 'Sasol', 'slug' => 'sasol'],
            ['name' => 'TotalEnergies', 'slug' => 'totalenergies'],
            ['name' => 'Astron Energy', 'slug' => 'astron-energy'],
        ];
        $saBrands = collect($saBrands)->filter(function ($brand) {
            return file_exists(public_path('images/brands/' . $brand['slug'] . '.png'));
        })->values();
    @endphp

    <div class="glass rounded-2xl p-6 mt-8 overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-blue-600">Top Brands</p>
                <h2 class="brand-font text-xl text-slate-900 mt-2">South African Fuel and Energy Partners</h2>
            </div>
            <p class="text-sm text-slate-500">Operators, oil corporations, and energy groups.</p>
        </div>

        <div class="relative mt-5">
            <div class="absolute left-0 top-0 bottom-0 w-12 bg-gradient-to-r from-white/95 to-transparent z-10 pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-12 bg-gradient-to-l from-white/95 to-transparent z-10 pointer-events-none"></div>
            <div class="driver-brand-ticker-track">
                @if($saBrands->count())
                    @for ($i = 0; $i < 2; $i++)
                        @foreach ($saBrands as $brand)
                            <div class="driver-brand-chip">
                                <img
                                    src="{{ asset('images/brands/' . $brand['slug'] . '.png') }}"
                                    alt="{{ $brand['name'] }} logo"
                                    loading="lazy"
                                >
                                <span class="text-sm font-medium text-slate-700 whitespace-nowrap">{{ $brand['name'] }}</span>
                            </div>
                        @endforeach
                    @endfor
                @else
                    <p class="text-sm text-slate-500 px-2">No popular brand logos configured.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-6">
            <div class="glass rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <h2 class="brand-font text-xl text-slate-900">Latest Approved Voucher</h2>
                    <a href="{{ route('driver.vouchers.index', ['status' => 'approved']) }}" class="text-sm text-blue-600 hover:text-blue-700">View approved</a>
                </div>
                @if($latestApprovedVoucher)
                    @php
                        $cardQrValue = $latestApprovedVoucher->qr_code ?: $latestApprovedVoucher->code;
                        $cardQrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=8&data=' . urlencode($cardQrValue);
                    @endphp
                    <div class="driver-holo-wrap mt-4">
                        <div class="driver-holo-card rotate" id="driverHoloCard">
                            <div class="driver-holo-circles"></div>
                            <div class="driver-holo-bg"></div>
                            <div class="driver-holo-lines"></div>
                            <div class="driver-holo-logo" data-brand="BWISER"></div>
                            <div class="driver-holo-qr" aria-label="Voucher QR preview">
                                <img
                                    src="{{ $cardQrImage }}"
                                    alt="Voucher QR {{ $latestApprovedVoucher->code }}"
                                    loading="lazy"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                                >
                                <span class="driver-holo-qr-fallback" style="display:none;">
                                    {{ $latestApprovedVoucher->code ?? 'QR' }}
                                </span>
                            </div>
                            <div class="driver-holo-meta driver-holo-meta-top">
                                {{ \Illuminate\Support\Str::limit($latestApprovedVoucher->fuelStation?->name ?? 'Station', 28) }}
                            </div>
                            <div class="driver-holo-meta driver-holo-meta-bottom">
                                R {{ number_format((float) $latestApprovedVoucher->amount, 2) }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-900">
                                {{ $latestApprovedVoucher->fuelStation?->name ?? 'Unknown Station' }}
                            </p>
                            <span class="text-xs px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 uppercase">
                                {{ $latestApprovedVoucher->status }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-600 mt-1">
                            {{ ucfirst($latestApprovedVoucher->fuel_type) }} •
                            R {{ number_format((float) $latestApprovedVoucher->amount, 2) }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Code {{ $latestApprovedVoucher->code ?? ('#' . $latestApprovedVoucher->id) }}
                            @if($latestApprovedVoucher->expires_at)
                                • Expires {{ \Illuminate\Support\Carbon::parse($latestApprovedVoucher->expires_at)->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                @else
                    <p class="text-sm text-slate-500 mt-4">No approved voucher yet. Apply and wait for approval to see it here.</p>
                @endif
            </div>

            <div class="glass rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <h2 class="brand-font text-xl text-slate-900">Recent Vouchers</h2>
                    <a href="{{ route('driver.vouchers.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View all</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($recentVouchers as $voucher)
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-slate-900">{{ $voucher->fuelStation?->name ?? 'Unknown Station' }}</p>
                                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 uppercase">{{ $voucher->status }}</span>
                            </div>
                            <p class="text-sm text-slate-600 mt-1">
                                {{ ucfirst($voucher->fuel_type) }} • R {{ number_format((float) $voucher->amount, 2) }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No vouchers yet. Start by applying for one.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <h2 class="brand-font text-xl text-slate-900">Upcoming Repayments</h2>
                <div class="flex items-center gap-2">
                    <a href="{{ route('driver.repayments.index') }}" class="text-sm text-blue-600 hover:text-blue-700">Open repayments</a>
                    <a href="{{ route('driver.repayments.upcoming.export-pdf') }}" class="download-button" title="Export upcoming repayments to PDF">
                        <span class="docs">
                            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            PDF
                        </span>
                        <span class="download">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
            @if($nextRepaymentCountdownTarget)
                <div class="next-repayment-clock-wrap mt-4">
                    <div class="clock-container next-repayment-clock" data-target-ms="{{ $nextRepaymentCountdownTarget }}">
                        <div class="clock-col">
                            <p class="clock-day clock-timer"></p>
                            <p class="clock-label">Days</p>
                        </div>
                        <div class="clock-col">
                            <p class="clock-hours clock-timer"></p>
                            <p class="clock-label">Hours</p>
                        </div>
                        <div class="clock-col">
                            <p class="clock-minutes clock-timer"></p>
                            <p class="clock-label">Minutes</p>
                        </div>
                        <div class="clock-col">
                            <p class="clock-seconds clock-timer"></p>
                            <p class="clock-label">Seconds</p>
                        </div>
                    </div>
                    <p id="nextRepaymentHint" class="next-repayment-hint">
                        Countdown to next repayment due date
                        @if($nextRepayment?->due_date)
                            ({{ \Illuminate\Support\Carbon::parse($nextRepayment->due_date)->format('d M Y') }}).
                        @else
                            .
                        @endif
                    </p>
                </div>
            @endif
            <div class="mt-4 space-y-3">
                @forelse($upcomingRepayments as $repayment)
                    @php
                        $dueDate = $repayment->due_date ? \Illuminate\Support\Carbon::parse($repayment->due_date) : null;
                        $isDueToday = $dueDate?->isToday() ?? false;
                        $isOverdue = ($repayment->status === 'overdue') || (($dueDate?->isPast() ?? false) && !$isDueToday && $repayment->status !== 'paid');
                    @endphp
                    <div class="rounded-xl border px-4 py-3 {{ $isOverdue ? 'border-red-200 bg-red-50/70' : ($isDueToday ? 'border-amber-200 bg-amber-50/70' : 'border-slate-200 bg-white') }}">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-900">Due {{ \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y') }}</p>
                            <p class="text-sm font-semibold {{ $isOverdue ? 'text-red-700' : ($isDueToday ? 'text-amber-700' : 'text-slate-900') }}">R {{ number_format((float) $repayment->amount, 2) }}</p>
                        </div>
                        <p class="text-xs mt-1 uppercase {{ $isOverdue ? 'text-red-700 font-semibold' : ($isDueToday ? 'text-amber-700 font-semibold' : 'text-slate-500') }}">
                            {{ $isOverdue ? 'OVERDUE' : ($isDueToday ? 'DUE TODAY' : $repayment->status) }}
                        </p>
                        @if(in_array($repayment->status, ['pending', 'overdue'], true))
                            <form method="POST" action="{{ route('payments.paystack.repayment', $repayment) }}" class="mt-3 flex flex-wrap gap-2">
                                @csrf
                                <input type="hidden" name="payment_intent" value="force_now">
                                <button name="payment_method" value="card" class="btn-primary pay-now-btn px-3 py-1.5 rounded-lg text-xs font-semibold">
                                    Pay Now
                                </button>
                            </form>
                            <p class="text-[11px] text-slate-500 mt-2">One-time override. Auto-pay still runs on future due repayments.</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No pending repayments.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8">
        @include('components.user-feedback-card')
    </div>
</section>

<style>
    .driver-wisdom-card {
        width: min(100%, 320px);
        min-height: 156px;
        background: transparent;
        position: relative;
        border-radius: 12px;
        padding: .2rem 0 .15rem;
        overflow: hidden;
    }

    .driver-wisdom-card--header {
        min-height: 146px;
    }

    .driver-wisdom-title {
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        letter-spacing: .04em;
        margin: 0;
    }

    .driver-wisdom-quote {
        color: #cbd5e1;
        line-height: 1;
        margin-top: .15rem;
    }

    .driver-wisdom-body {
        margin: .15rem 0 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.35;
        max-width: 17rem;
    }

    .driver-wisdom-author {
        margin-top: .55rem;
        font-weight: 700;
        font-size: .75rem;
        color: #64748b;
        opacity: 0;
        transition: opacity .45s ease;
    }

    .driver-wisdom-card:hover .driver-wisdom-author {
        opacity: 1;
    }

    .driver-header-actions {
        width: 100%;
    }

    .driver-header-cta {
        width: 100%;
    }

    .driver-header-cta > a {
        flex: 1 1 160px;
        text-align: center;
    }

    .redeemed-pattern-card {
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }

    .redeemed-pattern-card > * {
        position: relative;
        z-index: 2;
    }

    .redeemed-pattern-card::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        opacity: 0.24;
        pointer-events: none;
        --s: 60px;
        --c1: #180a22;
        --c2: #5b42f3;
        --_g: radial-gradient(25% 25% at 25% 25%, var(--c1) 99%, rgba(0, 0, 0, 0) 101%);
        background: var(--_g) var(--s) var(--s) / calc(2 * var(--s)) calc(2 * var(--s)),
            var(--_g) 0 0 / calc(2 * var(--s)) calc(2 * var(--s)),
            radial-gradient(50% 50%, var(--c2) 98%, rgba(0, 0, 0, 0)) 0 0 / var(--s) var(--s),
            repeating-conic-gradient(var(--c2) 0 50%, var(--c1) 0 100%) calc(0.5 * var(--s)) 0 / calc(2 * var(--s)) var(--s);
    }

    .driver-active-voucher-card-wrap {
        display: flex;
        align-items: stretch;
    }

    .driver-active-voucher-card {
        --card-accent-a: #1d4ed8;
        --card-accent-b: #0ea5e9;
        --card-accent-c: #16a34a;
        width: 100%;
        max-width: 320px;
        min-height: 260px;
        padding: 20px;
        color: #0f172a;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 55%, #eef2ff 100%);
        border: 2px solid color-mix(in srgb, var(--card-accent-a) 55%, #dbeafe);
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        cursor: pointer;
        transform-origin: center center;
        transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 14px 28px rgba(2, 6, 23, 0.2);
    }

    .driver-active-voucher-card .main-content {
        flex: 1;
    }

    .driver-active-voucher-card .header {
        margin-bottom: 24px;
    }

    .driver-active-voucher-card .heading {
        font-size: 32px;
        font-weight: 400;
        line-height: 1.2;
        margin: 0;
        color: #0f172a;
    }

    .driver-active-voucher-count {
        margin: 0;
        font-size: 4rem;
        line-height: 1;
        font-weight: 700;
        color: #0f172a;
    }

    .driver-active-voucher-card .footer {
        font-weight: 400;
        margin-right: 4px;
        margin-bottom: 0;
        opacity: 0.88;
        color: #334155;
    }

    .driver-active-voucher-card:hover {
        border-radius: 12px;
        border-color: color-mix(in srgb, var(--card-accent-b) 70%, #ffffff);
        background: linear-gradient(135deg, var(--card-accent-a) 0%, var(--card-accent-b) 56%, var(--card-accent-c) 100%);
        color: #ffffff;
        scale: 0.95;
        rotate: 8deg;
        box-shadow: 0px 3px 187.5px 7.5px rgba(29, 78, 216, 0.28);
    }

    .driver-active-voucher-card:hover .heading,
    .driver-active-voucher-card:hover .driver-active-voucher-count,
    .driver-active-voucher-card:hover .footer {
        color: #ffffff;
    }

    .driver-greet-card {
        background: transparent;
        padding: 0;
        border-radius: 0;
        width: auto;
    }

    .driver-greet-loader {
        color: #0f172a;
        font-family: "Poppins", "Outfit", sans-serif;
        font-weight: 500;
        font-size: 1.1rem;
        box-sizing: content-box;
        height: 28px;
        padding: 4px 0;
        display: flex;
        border-radius: 8px;
        line-height: 28px;
    }

    .driver-greet-words {
        overflow: hidden;
        position: relative;
    }

    .driver-greet-words::after {
        content: none;
    }

    .driver-greet-word {
        display: block;
        height: 100%;
        padding-left: 6px;
        color: #0f172a;
        animation: driverGreetSpin 4s infinite;
        white-space: nowrap;
    }

    @keyframes driverGreetSpin {
        10% { transform: translateY(-102%); }
        25% { transform: translateY(-100%); }
        35% { transform: translateY(-202%); }
        50% { transform: translateY(-200%); }
        60% { transform: translateY(-302%); }
        75% { transform: translateY(-300%); }
        85% { transform: translateY(-402%); }
        100% { transform: translateY(-400%); }
    }

    @media (min-width: 768px) {
        .driver-header-actions {
            width: auto;
            min-width: 330px;
        }

        .driver-header-cta {
            width: auto;
            justify-content: flex-end;
        }

        .driver-header-cta > a {
            flex: 0 0 auto;
        }
    }

    .driver-brand-ticker-track {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: max-content;
        animation: driverBrandTickerScroll 36s linear infinite;
    }

    .driver-brand-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.6rem 0.95rem;
        border-radius: 999px;
        border: 1px solid rgba(203, 213, 225, 0.9);
        background: #fff;
        box-shadow: 0 8px 24px -20px rgba(15, 23, 42, 0.5);
    }

    .driver-brand-chip img {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        background: #f8fafc;
        border: 1px solid rgba(226, 232, 240, 0.9);
    }

    .driver-brand-chip img {
        object-fit: contain;
        padding: 0.15rem;
        background: #fff;
    }

    @keyframes driverBrandTickerScroll {
        from {
            transform: translateX(0);
        }
        to {
            transform: translateX(-50%);
        }
    }

    @media (max-width: 768px) {
        .driver-brand-ticker-track {
            animation-duration: 28s;
        }
    }

    .driver-holo-wrap {
        perspective: 28rem;
    }

    .driver-holo-card {
        position: relative;
        width: 100%;
        max-width: 330px;
        aspect-ratio: 3.126 / 1.95;
        border-radius: 1.35rem;
        overflow: hidden;
        --ratio-x: 1.2;
        --ratio-y: 1.2;
        --correction: 28%;
        --holo-accent: #60a5fa;
        transform-style: preserve-3d;
        transition: transform .2s linear;
        transform: rotateY(calc(-13deg * var(--ratio-x))) rotateX(calc(11deg * var(--ratio-y)));
        background: linear-gradient(145deg, #0f172a, #1e293b);
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 18px 38px -26px rgba(15, 23, 42, 0.85);
    }

    .driver-holo-circles,
    .driver-holo-bg,
    .driver-holo-lines,
    .driver-holo-logo {
        position: absolute;
        inset: 0;
        border-radius: inherit;
    }

    .driver-holo-logo {
        display: grid;
        place-items: center;
        font-weight: 800;
        font-size: clamp(1.25rem, 4.8cqw, 2rem);
        letter-spacing: .12em;
        color: #ffffff;
        text-shadow: 0 2px 10px rgba(15, 23, 42, 0.45);
    }

    .driver-holo-logo::before,
    .driver-holo-logo::after {
        content: attr(data-brand);
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        letter-spacing: .12em;
    }

    .driver-holo-logo::before {
        color: rgba(0, 0, 0, 0.35);
        transform: translateY(2px);
        z-index: 0;
    }

    .driver-holo-logo::after {
        color: #e2e8f0;
        z-index: 1;
        transform-style: preserve-3d;
        transition: transform .2s linear;
        transform: perspective(120px)
            translateZ(calc(0.08rem + 0.5rem * var(--ratio-x)))
            translate(
                calc(0rem + var(--ratio-x) * -0.8rem),
                calc(0rem + var(--ratio-y) * -0.7rem)
            )
            rotateY(calc(-9deg * var(--ratio-x)))
            rotateX(calc(9deg * var(--ratio-y)));
    }

    .driver-holo-meta {
        position: absolute;
        z-index: 2;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        color: rgba(226, 232, 240, 0.95);
        text-transform: uppercase;
        text-shadow: 0 1px 6px rgba(15, 23, 42, .6);
    }

    .driver-holo-meta-top {
        top: .8rem;
        left: .9rem;
        max-width: 70%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .driver-holo-meta-bottom {
        right: .9rem;
        bottom: .72rem;
    }

    .driver-holo-qr {
        position: absolute;
        right: .75rem;
        top: .72rem;
        width: 58px;
        height: 58px;
        z-index: 3;
        border-radius: .68rem;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, .72);
        box-shadow: 0 8px 18px -14px rgba(15, 23, 42, .95);
        background: rgba(255, 255, 255, .97);
    }

    .driver-holo-qr::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(145deg, rgba(255,255,255,.35), transparent 62%);
        mix-blend-mode: screen;
    }

    .driver-holo-qr img,
    .driver-holo-qr-fallback {
        width: 100%;
        height: 100%;
        display: block;
    }

    .driver-holo-qr img {
        object-fit: contain;
        padding: 3px;
        filter: contrast(1.08);
    }

    .driver-holo-qr-fallback {
        place-items: center;
        display: none;
        padding: .3rem;
        font-size: .47rem;
        line-height: 1.2;
        text-align: center;
        font-weight: 700;
        color: #334155;
    }

    .driver-holo-bg {
        background:
            radial-gradient(
                ellipse at calc(90% - var(--ratio-x) * 20%) calc(0% - var(--ratio-y) * 20%),
                rgba(255, 255, 255, 0.65),
                #93c5fd 1%,
                rgba(37, 99, 235, .82) 20%,
                transparent
            ),
            linear-gradient(
                112deg,
                #2563eb calc(10% - var(--ratio-x) * 20%),
                #60a5fa calc(20% - var(--ratio-x) * 20%),
                #1d4ed8 calc(30% - var(--ratio-x) * 20%),
                rgba(96, 165, 250, 0.72) calc(60% - var(--ratio-x) * 20%),
                transparent calc(100% - var(--ratio-x) * 20%),
                transparent
            );
        mix-blend-mode: hard-light;
        opacity: .72;
        transition: all .2s linear, opacity .8s ease;
        z-index: 0;
    }

    .driver-holo-lines {
        pointer-events: none;
        z-index: 1;
        opacity: .52;
        background-image:
            repeating-linear-gradient(
                110deg,
                rgba(59, 130, 246, .48) 0px,
                rgba(147, 197, 253, .4) 3px,
                transparent 6px,
                transparent 10px
            );
        background-position: calc(var(--ratio-x) * 12%) calc(var(--ratio-y) * 10%);
        transition: all .2s linear;
    }

    .driver-holo-circles {
        overflow: hidden;
        opacity: .45;
        transition: all .8s ease;
        z-index: 0;
    }

    .driver-holo-circles::before,
    .driver-holo-circles::after {
        content: "";
        position: absolute;
        inset: 0;
        aspect-ratio: 1/1;
        background: radial-gradient(
            ellipse at 50% 50%,
            rgba(125, 211, 252, .42) 0.23rem,
            transparent 0.23rem,
            transparent
        ) repeat;
        background-size: 1rem 1rem;
        background-position: left top;
    }

    .driver-holo-circles::before {
        transform: translate(-50%, -50%) rotate(45deg);
    }

    .driver-holo-circles::after {
        transform: translate(50%, 100%) rotate(45deg);
    }

    .driver-holo-card.rotate {
        animation: driverCardRotate 10s ease-in-out infinite;
    }

    @keyframes driverCardRotate {
        from { --ratio-x: 1.2; --ratio-y: 1.2; }
        30% { --ratio-x: -1.4; --ratio-y: 0.2; }
        50% { --ratio-x: 0.45; --ratio-y: 0.2; }
        70% { --ratio-x: -1.35; --ratio-y: -1.1; }
        to { --ratio-x: 1.2; --ratio-y: 1.2; }
    }

    .next-repayment-clock-wrap {
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 1rem;
        padding: 0.95rem 0.9rem 0.75rem;
        background: linear-gradient(140deg, #1d4ed8 0%, #2563eb 55%, #38bdf8 100%);
    }

    .next-repayment-clock {
        --timer-day: '00';
        --timer-hours: '00';
        --timer-minutes: '00';
        --timer-seconds: '00';
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.7rem;
    }

    .next-repayment-clock .clock-col {
        text-align: center;
        min-width: 56px;
        position: relative;
    }

    .next-repayment-clock .clock-col:not(:last-child)::before,
    .next-repayment-clock .clock-col:not(:last-child)::after {
        content: "";
        width: 4px;
        height: 4px;
        border-radius: 50%;
        position: absolute;
        right: -0.5rem;
        background: rgba(255, 255, 255, 0.55);
    }

    .next-repayment-clock .clock-col:not(:last-child)::before {
        top: 34%;
    }

    .next-repayment-clock .clock-col:not(:last-child)::after {
        top: 55%;
    }

    .next-repayment-clock .clock-day::before { content: var(--timer-day); }
    .next-repayment-clock .clock-hours::before { content: var(--timer-hours); }
    .next-repayment-clock .clock-minutes::before { content: var(--timer-minutes); }
    .next-repayment-clock .clock-seconds::before { content: var(--timer-seconds); }

    .next-repayment-clock .clock-timer::before {
        color: #ffffff;
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1;
        letter-spacing: 0.02em;
    }

    .next-repayment-clock .clock-label {
        margin-top: 0.4rem;
        font-size: 0.62rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(255, 255, 255, 0.75);
    }

    .next-repayment-hint {
        margin-top: 0.5rem;
        font-size: 0.72rem;
        color: rgba(239, 246, 255, 0.92);
    }

    .download-button {
        position: relative;
        border-width: 0;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: 0.45rem;
        z-index: 1;
        text-decoration: none;
        flex-shrink: 0;
    }

    .download-button .docs {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.45rem;
        min-height: 34px;
        padding: 0 0.6rem;
        border-radius: 0.45rem;
        z-index: 1;
        background: linear-gradient(120deg, #1d4ed8, #2563eb);
        border: solid 1px rgba(147, 197, 253, 0.45);
        transition: all 0.5s cubic-bezier(0.77, 0, 0.175, 1);
    }

    .download-button:hover {
        box-shadow:
            rgba(30, 64, 175, 0.32) 0px 20px 24px,
            rgba(30, 64, 175, 0.12) 0px -8px 16px,
            rgba(56, 189, 248, 0.2) 0px 5px 12px;
    }

    .download-button .download {
        position: absolute;
        inset: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 90%;
        margin: 0 auto;
        z-index: -1;
        border-radius: 0.45rem;
        transform: translateY(0%);
        background: linear-gradient(120deg, #38bdf8, #93c5fd);
        border: solid 1px rgba(56, 189, 248, 0.4);
        color: #0f172a;
        transition: all 0.5s cubic-bezier(0.77, 0, 0.175, 1);
    }

    .download-button:hover .download {
        transform: translateY(100%);
    }

    .download-button .download svg polyline,
    .download-button .download svg line {
        animation: docs 1s infinite;
    }

    @keyframes docs {
        0% { transform: translateY(0%); }
        50% { transform: translateY(-15%); }
        100% { transform: translateY(0%); }
    }

    @media (max-width: 460px) {
        .next-repayment-clock {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem 0.5rem;
        }

        .next-repayment-clock .clock-col::before,
        .next-repayment-clock .clock-col::after {
            display: none;
        }
    }

    .pay-now-btn,
    .pay-now-btn:hover,
    .pay-now-btn:focus {
        box-shadow: none !important;
    }
</style>
<script>
    (function initDriverVoucherCard() {
        const card = document.getElementById('driverHoloCard');
        if (!card) return;

        const updatePointerPosition = ({ x, y }) => {
            card.classList.remove('rotate');
            const rect = card.getBoundingClientRect();
            const halfWidth = rect.width / 2;
            const halfHeight = rect.height / 2;
            const ratioX = (x - (rect.x + halfWidth)) / halfWidth;
            const ratioY = (y - (rect.y + halfHeight)) / halfHeight;
            card.style.setProperty('--ratio-x', ratioX.toFixed(4));
            card.style.setProperty('--ratio-y', ratioY.toFixed(4));
        };

        card.addEventListener('pointermove', updatePointerPosition);
        card.addEventListener('pointerleave', () => {
            card.style.setProperty('--ratio-x', '0');
            card.style.setProperty('--ratio-y', '0');
            card.classList.add('rotate');
        });
    })();

    (function initNextRepaymentCountdown() {
        const clock = document.querySelector('.next-repayment-clock');
        if (!clock) return;

        const hint = document.getElementById('nextRepaymentHint');
        const targetMs = Number(clock.dataset.targetMs || 0);
        if (!targetMs || Number.isNaN(targetMs)) return;

        const pad = (value) => String(Math.max(0, value)).padStart(2, '0');

        const render = () => {
            const nowMs = Date.now();
            let deltaMs = targetMs - nowMs;
            const overdue = deltaMs < 0;
            deltaMs = Math.abs(deltaMs);

            const days = Math.floor(deltaMs / (1000 * 60 * 60 * 24));
            const hours = Math.floor((deltaMs / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((deltaMs / (1000 * 60)) % 60);
            const seconds = Math.floor((deltaMs / 1000) % 60);

            clock.style.setProperty('--timer-day', `'${pad(days)}'`);
            clock.style.setProperty('--timer-hours', `'${pad(hours)}'`);
            clock.style.setProperty('--timer-minutes', `'${pad(minutes)}'`);
            clock.style.setProperty('--timer-seconds', `'${pad(seconds)}'`);

            if (hint) {
                if (overdue) {
                    hint.textContent = `Repayment overdue by ${days} day(s).`;
                } else if (days === 0) {
                    hint.textContent = 'Repayment is due today.';
                } else {
                    hint.textContent = 'Countdown to next repayment due date.';
                }
            }
        };

        render();
        window.setInterval(render, 1000);
    })();

</script>
@endsection
