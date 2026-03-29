@extends('Layouts.app')

@section('title', 'Driver Dashboard - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <style>
        /* Dashboard quick-link icon animations (hover + active) */
        .driver-quick-icon {
            transition: transform 180ms ease, opacity 180ms ease, stroke-dashoffset 360ms ease;
            transform-origin: 50% 50%;
        }
        .driver-quick-link:hover .driver-quick-icon,
        .driver-quick-link:focus-visible .driver-quick-icon {
            transform: translateY(-2px) scale(1.06);
        }
        .driver-quick-link[data-anim="repayments"]:hover .driver-quick-icon {
            animation: driver-wiggle 520ms ease-in-out 1;
        }
        .driver-quick-link[data-anim="upload"]:hover .driver-quick-icon {
            animation: driver-bounce 520ms cubic-bezier(0.2, 0.8, 0.2, 1) 1;
        }
        .driver-quick-link[data-anim="profile"]:hover .driver-quick-icon {
            animation: driver-pulse 620ms ease-in-out 1;
        }
        @keyframes driver-wiggle {
            0% { transform: translateY(-2px) rotate(0deg) scale(1.06); }
            25% { transform: translateY(-2px) rotate(-6deg) scale(1.06); }
            50% { transform: translateY(-2px) rotate(6deg) scale(1.06); }
            75% { transform: translateY(-2px) rotate(-3deg) scale(1.06); }
            100% { transform: translateY(-2px) rotate(0deg) scale(1.06); }
        }
        @keyframes driver-bounce {
            0% { transform: translateY(-2px) scale(1.06); }
            35% { transform: translateY(-8px) scale(1.06); }
            70% { transform: translateY(-1px) scale(1.06); }
            100% { transform: translateY(-2px) scale(1.06); }
        }
        @keyframes driver-pulse {
            0% { transform: translateY(-2px) scale(1.06); }
            40% { transform: translateY(-2px) scale(1.14); }
            100% { transform: translateY(-2px) scale(1.06); }
        }

        /* Wallet balance card (header) */
        .walletBalanceCard {
            width: fit-content;
            height: 55px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            padding: 0px 12px;
            font-family: Arial, Helvetica, sans-serif;
            /* No background fill: keep it clean and lightweight. */
            box-shadow: none;
            position: relative;
            overflow: hidden;
            background: transparent;
            border: none;
        }
        .walletBalanceCard > * { position: relative; z-index: 1; }
        .svgwrapper {
            width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 8px 18px rgba(236, 72, 153, 0.18));
        }
        .svgwrapper svg { width: 100%; }
        .balancewrapper {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            flex-direction: column;
            width: 140px;
            gap: 0px;
        }
        .balanceHeading {
            font-size: 8px;
            color: rgba(15, 23, 42, 0.72);
            font-weight: 100;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
        .balance {
            font-size: 13.5px;
            color: #0f172a;
            font-weight: 600;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .addmoney {
            padding: 1px 15px;
            border-radius: 20px;
            background: #020DFF; /* Bwiser logo blue */
            color: white;
            border: none;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            text-decoration: none;
        }
        .addmoney:hover { background: #1d29ff; color: #ffffff; }
        .plussign {
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        /* Ensure top-up modal is above all dashboard UI. */
        #walletTopupModal { z-index: 2147483647; }
    </style>
	    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
	        <div>
                @php
                    $walletBalance = (float) (auth()->user()?->wallet?->balance ?? 0);
                    $walletTopupEmail = trim((string) (
                        auth()->user()?->autopay_email
                        ?? auth()->user()?->email
                        ?? (function () {
                            $digits = preg_replace('/\\D+/', '', (string) (auth()->user()?->phone ?? ''));
                            return $digits !== '' ? ('driver' . $digits . '@bwiser.co.za') : ('driver+' . (auth()->id() ?? '0') . '@bwiser.co.za');
                        })()
                    ));
                @endphp
	            <div class="walletBalanceCard">
                    <div class="svgwrapper" aria-hidden="true">
                        <svg viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="0.539915" y="6.28937" width="21" height="4" rx="1.5" transform="rotate(-4.77865 0.539915 6.28937)" fill="#020DFF" stroke="black"></rect>
                            <path d="M2.12011 6.64507C7.75028 6.98651 12.7643 6.94947 21.935 6.58499C22.789 6.55105 23.5 7.23329 23.5 8.08585V24C23.5 24.8284 22.8284 25.5 22 25.5H2C1.17157 25.5 0.5 24.8284 0.5 24V8.15475C0.5 7.2846 1.24157 6.59179 2.12011 6.64507Z" fill="#020DFF" stroke="black"></path>
                            <path d="M16 13.5H23.5V18.5H16C14.6193 18.5 13.5 17.3807 13.5 16C13.5 14.6193 14.6193 13.5 16 13.5Z" fill="#020DFF" stroke="black"></path>
                        </svg>
                    </div>

                    <div class="balancewrapper">
                        <span class="balanceHeading">Wallet balance</span>
                        <p class="balance"><span id="currency">R</span>{{ number_format($walletBalance, 2) }}</p>
                    </div>

                    <button type="button" class="addmoney" id="walletTopupOpenBtn">
                        <span class="plussign">+</span>Add Money
                    </button>
	            </div>

                <div
                    id="walletTopupModal"
                    class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-4"
                    aria-hidden="true"
                >
                    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-blue-600">Wallet Top-up</p>
                                <p class="text-lg font-semibold text-slate-900 mt-1">Pay by Card</p>
                                <p class="text-sm text-slate-600 mt-1">A secure card form will open to complete the payment.</p>
                            </div>
                            <button type="button" id="walletTopupCloseBtn" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Close
                            </button>
                        </div>

                        <form
                            id="walletTopupForm"
                            method="POST"
                            action="{{ route('driver.wallet.topup.paystack.init') }}"
                            class="mt-4 space-y-3"
                        >
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-slate-700" for="walletTopupAmount">Amount (R)</label>
                                <input
                                    id="walletTopupAmount"
                                    name="amount"
                                    type="number"
                                    min="10"
                                    step="0.01"
                                    inputmode="decimal"
                                    required
                                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    placeholder="e.g. 250.00"
                                />
                                <p class="mt-1 text-xs text-slate-500">Minimum top-up is R 10.00.</p>
                            </div>

                            <input type="hidden" name="payer_email" value="{{ $walletTopupEmail }}">

                            <button
                                type="submit"
                                id="walletTopupSubmitBtn"
                                class="w-full rounded-xl py-3 font-semibold text-white"
                                style="background: #020DFF;"
                            >
                                Continue to Paystack
                            </button>

                            <p class="text-[11px] text-slate-500">
                                You will be redirected to the Paystack checkout (same flow used for repayments).
                            </p>
                        </form>
                    </div>
                </div>

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
	            <x-pending-approval-banner />
	        </div>
	        <div class="driver-header-actions flex flex-col items-start md:items-end gap-3">
           
            <div class="driver-header-cta flex flex-wrap gap-3 md:justify-end">
                <a href="{{ route('driver.vouchers.create') }}" class="animated-button driver-apply-voucher-btn">
                    <svg viewBox="0 0 24 24" class="arr-2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M16.1716 10.9999L10.8076 5.63589L12.2218 4.22168L20 11.9999L12.2218 19.778L10.8076 18.3638L16.1716 12.9999H4V10.9999H16.1716Z"></path>
                    </svg>
                    <span class="text">Apply for Voucher</span>
                    <span class="circle" aria-hidden="true"></span>
                    <svg viewBox="0 0 24 24" class="arr-1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M16.1716 10.9999L10.8076 5.63589L12.2218 4.22168L20 11.9999L12.2218 19.778L10.8076 18.3638L16.1716 12.9999H4V10.9999H16.1716Z"></path>
                    </svg>
                </a>

	                @php
	                    $quickLinks = [
	                        [
	                            'label' => 'Repayments',
	                            'route' => 'driver.repayments.index',
	                            'active' => 'driver.repayments.*',
	                            'anim' => 'repayments',
	                            'icon' => '
	                                <path d="M2.25 7.5A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                                <path d="M2.25 9h19.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                                <path d="M6 15.75h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                            ',
	                        ],
	                        [
	                            'label' => 'Bank Statement',
	                            'route' => 'driver.bank-statements.create',
	                            'active' => 'driver.bank-statements.*',
	                            'anim' => 'upload',
	                            'icon' => '
	                                <path d="M6 2.25h7.5L18 6.75v13.5A1.5 1.5 0 0 1 16.5 21.75H6A1.5 1.5 0 0 1 4.5 20.25V3.75A1.5 1.5 0 0 1 6 2.25Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                                <path d="M13.5 2.25V6.75H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                                <path d="M12 18V11.25" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                                <path d="M8.25 14.25 12 10.5l3.75 3.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                            ',
	                        ],
	                        [
	                            'label' => 'Profile',
	                            'route' => 'driver.profile',
	                            'active' => 'driver.profile*',
	                            'anim' => 'profile',
	                            'icon' => '
	                                <path d="M12 21.75a9.75 9.75 0 1 0-9.75-9.75 9.75 9.75 0 0 0 9.75 9.75Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                                <path d="M12 12a3.75 3.75 0 1 0-3.75-3.75A3.75 3.75 0 0 0 12 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                                <path d="M4.5 20.25a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
	                            ',
	                        ],
	                    ];
	                @endphp

	                <nav
	                    aria-label="Quick links"
	                    class="h-16 w-[320px] rounded-full bg-white border border-slate-200 shadow-sm flex items-center justify-between px-2"
	                >
                    @foreach($quickLinks as $link)
                        @if(!Route::has($link['route']))
                            @continue
                        @endif
                        @php
                            $isActive = request()->routeIs($link['active']);
                        @endphp
	                        <a
	                            href="{{ route($link['route']) }}"
	                            class="driver-quick-link group h-12 w-12 rounded-full grid place-items-center transition-transform duration-150 {{ $isActive ? 'text-fuchsia-600 -translate-y-1' : 'text-slate-700 opacity-80 hover:opacity-100 hover:text-fuchsia-600 hover:-translate-y-1' }}"
	                            title="{{ $link['label'] }}"
	                            data-anim="{{ $link['anim'] ?? '' }}"
	                        >
	                            <span class="sr-only">{{ $link['label'] }}</span>
	                            <svg viewBox="0 0 24 24" fill="none" class="driver-quick-icon h-7 w-7" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                                {!! $link['icon'] !!}
	                            </svg>
	                        </a>
	                    @endforeach
	                </nav>
            </div>
        </div>
    </div>
    @include('driver.partials.nav')

    @if(session('error'))
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

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
            <p class="mt-2 text-2xl font-semibold text-slate-900">-R {{ number_format(abs((float) $pendingRepaymentAmount), 2) }}</p>
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

    <div class="mt-6 glass rounded-2xl p-6 overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="h-24 w-56 rounded-xl bg-white/70 border border-slate-200 flex items-center justify-center overflow-hidden">
                    <img
                        src="{{ asset('images/1Voucher-Logo.webp') }}"
                        alt="1Voucher"
                        class="h-20 w-auto object-contain"
                        loading="lazy"
                    >
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Prepaid Repayments</p>
                    <h2 class="brand-font text-lg text-slate-900 mt-1">Pay a Full Week of Repayments With 1Voucher</h2>
                    <p class="text-sm text-slate-600 mt-1">No card needed. Use a 1Voucher PIN to clear due repayments for the next 7 days.</p>
                </div>
            </div>
            <a href="{{ route('driver.repayments.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800">
                View Repayments
            </a>
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
                        $cardQrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=8&ecc=H&format=png&data=' . urlencode($cardQrValue);
                    @endphp
                    <div class="driver-holo-wrap mt-4">
                        <div class="driver-holo-card rotate" id="driverHoloCard">
                            <div class="driver-holo-circles"></div>
                            <div class="driver-holo-bg"></div>
                            <div class="driver-holo-lines"></div>
                            <div class="driver-holo-logo" data-brand="BWISER"></div>
                            <div class="driver-holo-qr bwiser-qr-stack" aria-label="Voucher QR preview">
                                <img
                                    src="{{ $cardQrImage }}"
                                    alt="Voucher QR {{ $latestApprovedVoucher->code }}"
                                    loading="lazy"
                                    onerror="this.style.display='none'; const fb=this.parentElement.querySelector('.driver-holo-qr-fallback'); if(fb) fb.style.display='grid';"
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

            <div class="glass rounded-2xl p-6 driver-vwallet">
                <div class="flex items-center justify-between">
                    <h2 class="brand-font text-xl text-slate-900">Virtual Cards</h2>
                    <a href="{{ route('driver.virtual-cards.index') }}" class="text-sm text-blue-600 hover:text-blue-700">Manage</a>
                </div>

                @php
                    $brandTheme = [
                        'shell-sa' => ['bg' => '#0b1220', 'fg' => '#ffffff', 'accent' => '#f59e0b'],
                        'bp-southern-africa' => ['bg' => '#052e16', 'fg' => '#e2e8f0', 'accent' => '#22c55e'],
                        'engen' => ['bg' => '#1d4ed8', 'fg' => '#ffffff', 'accent' => '#93c5fd'],
                        'sasol' => ['bg' => '#7c3aed', 'fg' => '#ffffff', 'accent' => '#e9d5ff'],
                        'totalenergies' => ['bg' => '#111827', 'fg' => '#ffffff', 'accent' => '#60a5fa'],
                        'astron-energy' => ['bg' => '#0f172a', 'fg' => '#ffffff', 'accent' => '#38bdf8'],
                        'puma-energy' => ['bg' => '#7f1d1d', 'fg' => '#ffffff', 'accent' => '#fb7185'],
                        'vivo-energy' => ['bg' => '#0f766e', 'fg' => '#ffffff', 'accent' => '#5eead4'],
                        'mulilo' => ['bg' => '#334155', 'fg' => '#ffffff', 'accent' => '#fbbf24'],
                        'petrosa' => ['bg' => '#1f2937', 'fg' => '#ffffff', 'accent' => '#a3e635'],
                        'eskom' => ['bg' => '#0f172a', 'fg' => '#ffffff', 'accent' => '#fde047'],
                        'central-energy-fund' => ['bg' => '#111827', 'fg' => '#ffffff', 'accent' => '#f97316'],
                    ];
                @endphp

                <div class="driver-vwallet-wrap mt-4">
                    @if($virtualCards->isNotEmpty())
                        @php
                            // Render oldest at the back, newest at the front.
                            $cardsForPocket = $virtualCards->reverse()->values();
                        @endphp
	                        <div class="driver-vwallet-pocket" role="group" aria-label="Virtual card wallet">
	                            <div class="driver-vwallet-back" aria-hidden="true"></div>

	                            @foreach($cardsForPocket as $card)
                            @php
                                $slug = (string) ($card->brand ?? 'generic');
                                $theme = $brandTheme[$slug] ?? ['bg' => '#0b1220', 'fg' => '#ffffff', 'accent' => '#38bdf8'];
                                $brandName = collect((array) config('retail_brands', []))
                                    ->firstWhere('slug', $slug)['name'] ?? ($card->label ?: ucwords(str_replace('-', ' ', $slug)));
                                $logoPath = public_path('images/brands/' . $slug . '.png');
                                $logoUrl = is_file($logoPath) ? asset('images/brands/' . $slug . '.png') : null;
                                $masked = trim((string) ($card->masked_pan ?? ''));
                                $last4 = trim((string) ($card->last4 ?? ''));
                                $panHint = $masked !== '' ? $masked : ($last4 !== '' ? ('**** ' . $last4) : '**** **** **** ****');
                                $status = strtolower(trim((string) ($card->status ?? '')));
                                $statusLabel = $status === 'frozen' ? 'Frozen' : ($status === 'active' ? 'Active' : ucfirst($status));
                                $expiry = ($card->expiry_month && $card->expiry_year)
                                    ? str_pad((string) $card->expiry_month, 2, '0', STR_PAD_LEFT) . '/' . substr((string) $card->expiry_year, -2)
                                    : '--/--';
                            @endphp
	                            <div
	                                class="driver-vwallet-card slot-{{ $loop->iteration }}"
	                                data-card-id="{{ (int) $card->id }}"
	                                data-reveal-url="{{ route('driver.virtual-cards.reveal', $card) }}"
	                                data-convert-url="{{ route('driver.virtual-cards.convert-to-voucher', $card) }}"
	                                data-csrf="{{ csrf_token() }}"
	                                data-brand-slug="{{ $slug }}"
	                                data-allocated="{{ (float) ($card->allocated_amount ?? 0) }}"
	                                style="--card-bg: {{ $theme['bg'] }}; --card-fg: {{ $theme['fg'] }}; --card-accent: {{ $theme['accent'] }};"
	                            >
                                <div class="driver-vwallet-card-inner">
                                    <div class="driver-vwallet-card-top">
                                        <span class="driver-vwallet-brand">
                                            <span class="driver-vwallet-brand-name">{{ $brandName }}</span>
                                        </span>
                                        <span class="driver-vwallet-top-actions" aria-label="Virtual card actions">
                                            <span class="driver-vwallet-chip" aria-label="{{ $brandName }} card">
                                                @if($logoUrl)
                                                    <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" class="driver-vwallet-chip-logo" loading="lazy">
                                                @else
                                                    <span class="driver-vwallet-chip-fallback" aria-hidden="true">{{ substr($brandName, 0, 1) }}</span>
                                                @endif
                                            </span>
                                            <button type="button" class="driver-vwallet-reveal-btn" aria-label="Reveal card details">
                                                Reveal
                                            </button>
                                            <button type="button" class="driver-vwallet-convert-btn" aria-label="Convert allocation to voucher">
                                                Convert
                                            </button>
                                        </span>
                                    </div>

                                    <div class="driver-vwallet-card-bottom">
                                        <div>
                                            <span class="driver-vwallet-label">Status</span>
                                            <span class="driver-vwallet-value">{{ $statusLabel }}</span>
                                        </div>
	                                        <div class="driver-vwallet-pan">
	                                            <span class="driver-vwallet-hidden">{{ $last4 !== '' ? ('**** ' . $last4) : '**** ••••' }}</span>
	                                            <span class="driver-vwallet-full" data-pan-fallback="{{ $panHint }}">{{ $panHint }}</span>
	                                            <span class="driver-vwallet-exp" data-exp-fallback="{{ $expiry }}">EXP {{ $expiry }}</span>
	                                            <span class="driver-vwallet-cvv" data-cvv-fallback="---">CVV ---</span>
	                                        </div>
	                                    </div>
	                                </div>
	                            </div>
	                            @endforeach

                            <div class="driver-vwallet-pocket-front" aria-hidden="true">
                                <svg class="driver-vwallet-pocket-svg" viewBox="0 0 280 160" fill="none">
                                    <path
                                        d="M 0 20 C 0 10, 5 10, 10 10 C 20 10, 25 25, 40 25 L 240 25 C 255 25, 260 10, 270 10 C 275 10, 280 10, 280 20 L 280 120 C 280 155, 260 160, 240 160 L 40 160 C 20 160, 0 155, 0 120 Z"
                                        fill="#1e341e"
                                    ></path>
                                    <path
                                        d="M 8 22 C 8 16, 12 16, 15 16 C 23 16, 27 29, 40 29 L 240 29 C 253 29, 257 16, 265 16 C 268 16, 272 16, 272 22 L 272 120 C 272 150, 255 152, 240 152 L 40 152 C 25 152, 8 152, 8 120 Z"
                                        stroke="#3d5635"
                                        stroke-width="1.5"
                                        stroke-dasharray="6 4"
                                    ></path>
                                </svg>
                                <div class="driver-vwallet-pocket-content">
                                    <div class="driver-vwallet-balance">
                                        <div class="driver-vwallet-balance-stars">******</div>
                                        <div class="driver-vwallet-balance-real">R {{ number_format((float) $virtualCardsAllocatedTotal, 2) }}</div>
                                    </div>
                                    <div class="driver-vwallet-subtitle">Allocated to cards</div>
                                    <div class="driver-vwallet-eye" aria-hidden="true">
                                        <svg class="driver-vwallet-eye-icon driver-vwallet-eye-slash" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                            <line x1="3" y1="3" x2="21" y2="21"></line>
                                        </svg>
                                        <svg class="driver-vwallet-eye-icon driver-vwallet-eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </div>
                                </div>
                            </div>
	                        </div>
	                        <p class="driver-vwallet-hint">Hover to see balance</p>
	                        <div
	                            id="driverVwalletConvertModal"
	                            class="driver-vwallet-modal"
	                            role="dialog"
	                            aria-modal="true"
	                            aria-label="Convert virtual card allocation to voucher"
	                        >
	                            <div class="driver-vwallet-modal-card">
	                                <div class="driver-vwallet-modal-head">
	                                    <div class="driver-vwallet-modal-title">Convert To Voucher</div>
	                                    <button type="button" class="driver-vwallet-modal-close" data-close>Close</button>
	                                </div>
	                                <div class="driver-vwallet-modal-body">
	                                    <div class="text-sm text-slate-600">
	                                        This moves funds from your virtual card allocation into a wallet-funded voucher (approved instantly).
	                                    </div>
	                                    <div id="driverVwalletConvertError" class="mt-3 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
	
	                                    <div class="driver-vwallet-form-row">
	                                        <div>
	                                            <label for="driverVwalletStation">Station</label>
	                                            <select id="driverVwalletStation" class="driver-vwallet-select"></select>
	                                        </div>
	                                        <div>
	                                            <label for="driverVwalletFuelType">Fuel type</label>
	                                            <select id="driverVwalletFuelType" class="driver-vwallet-select">
	                                                <option value="petrol">Petrol</option>
	                                                <option value="diesel">Diesel</option>
	                                                <option value="super">Super</option>
	                                            </select>
	                                        </div>
	                                        <div>
	                                            <label for="driverVwalletAmount">Amount (ZAR)</label>
	                                            <input id="driverVwalletAmount" class="driver-vwallet-input" type="number" min="10" step="0.01" inputmode="decimal">
	                                            <div id="driverVwalletAmountHint" class="mt-1 text-xs text-slate-500"></div>
	                                        </div>
	                                    </div>
	                                </div>
	                                <div class="driver-vwallet-modal-actions">
	                                    <button type="button" class="driver-vwallet-btn" data-close>Cancel</button>
	                                    <button type="button" id="driverVwalletConvertSubmit" class="driver-vwallet-btn primary">Create Voucher</button>
	                                </div>
	                            </div>
	                        </div>
	                    @else
	                        <div class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
	                            No virtual cards yet. Create one to start spending.
	                        </div>
                    @endif
                </div>
            </div>

            <div class="glass rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <h2 class="brand-font text-xl text-slate-900">Recent Vouchers</h2>
                    <a href="{{ route('driver.vouchers.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View all</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($recentVouchers as $voucher)
                        @php
                            $isVirtualCardVoucher = \Illuminate\Support\Str::startsWith((string) ($voucher->transaction_reference ?? ''), 'VIRTUALCARD-');
                        @endphp
                        @if($isVirtualCardVoucher)
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-900">Virtual Card Voucher</p>
                                    <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 uppercase">{{ $voucher->status }}</span>
                                </div>
                                <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-900">{{ $voucher->fuelStation?->name ?? 'Unknown Station' }}</p>
                                        <p class="text-sm text-slate-600 mt-1">
                                            {{ ucfirst($voucher->fuel_type) }} • R {{ number_format((float) $voucher->amount, 2) }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Expires {{ optional($voucher->expires_at)->format('d M Y H:i') }}
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <div class="vc-voucher-card" tabindex="0" role="button" aria-label="Voucher {{ $voucher->code }} details">
                                            <svg class="vc-voucher-qr" shape-rendering="crispEdges" viewBox="0 -0.5 29 29" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path d="M0 0h7M8 0h2M14 0h1M16 0h5M22 0h7M0 1h1M6 1h1M13 1h1M17 1h2M22 1h1M28 1h1M0 2h1M2 2h3M6 2h1M8 2h1M11 2h4M18 2h1M20 2h1M22 2h1M24 2h3M28 2h1M0 3h1M2 3h3M6 3h1M8 3h2M11 3h1M13 3h1M15 3h5M22 3h1M24 3h3M28 3h1M0 4h1M2 4h3M6 4h1M8 4h4M13 4h1M15 4h1M19 4h1M22 4h1M24 4h3M28 4h1M0 5h1M6 5h1M9 5h1M12 5h2M17 5h4M22 5h1M28 5h1M0 6h7M8 6h1M10 6h1M12 6h1M14 6h1M16 6h1M18 6h1M20 6h1M22 6h7M9 7h1M11 7h1M15 7h6M0 8h4M6 8h1M8 8h1M13 8h2M17 8h3M21 8h1M24 8h3M28 8h1M2 9h1M4 9h2M7 9h1M9 9h1M14 9h1M16 9h1M19 9h2M22 9h3M28 9h1M0 10h5M6 10h1M8 10h1M13 10h1M16 10h1M18 10h1M20 10h1M22 10h3M26 10h2M1 11h1M3 11h2M7 11h1M11 11h4M16 11h1M18 11h1M20 11h5M28 11h1M1 12h3M5 12h2M9 12h1M11 12h1M13 12h5M19 12h1M25 12h2M0 13h2M3 13h3M8 13h1M10 13h2M14 13h1M16 13h2M19 13h2M22 13h2M26 13h3M0 14h1M2 14h1M4 14h3M9 14h2M12 14h1M14 14h1M16 14h1M19 14h3M23 14h2M26 14h3M0 15h2M3 15h2M8 15h1M12 15h1M14 15h3M20 15h1M22 15h3M27 15h1M0 16h1M2 16h3M6 16h1M10 16h2M18 16h1M20 16h2M24 16h2M27 16h1M1 17h2M4 17h1M7 17h3M12 17h1M14 17h2M18 17h1M20 17h2M23 17h1M25 17h3M0 18h1M3 18h1M6 18h1M8 18h5M15 18h2M23 18h1M26 18h1M2 19h4M12 19h1M14 19h1M16 19h2M19 19h3M26 19h1M1 20h1M3 20h1M6 20h7M14 20h2M17 20h10M8 21h3M12 21h1M18 21h1M20 21h1M24 21h5M0 22h7M9 22h6M19 22h2M22 22h1M24 22h2M27 22h1M0 23h1M6 23h1M9 23h1M13 23h3M18 23h1M20 23h1M24 23h2M27 23h1M0 24h1M2 24h3M6 24h1M10 24h1M12 24h1M14 24h4M20 24h5M26 24h3M0 25h1M2 25h3M6 25h1M8 25h1M11 25h2M15 25h2M19 25h3M24 25h2M28 25h1M0 26h1M2 26h3M6 26h1M8 26h1M10 26h2M13 26h1M21 26h1M23 26h1M26 26h1M28 26h1M0 27h1M6 27h1M8 27h1M11 27h1M14 27h1M16 27h1M18 27h3M23 27h1M25 27h1M27 27h1M0 28h7M8 28h1M14 28h3M19 28h2M25 28h1M27 28h1" stroke="#000000"></path>
	                                            </svg>
	                                            <div class="vc-voucher-prompt">
	                                                <div class="vc-voucher-token" aria-hidden="true"></div>
	                                                <div class="vc-voucher-blur"></div>
	                                                <p>Hover for voucher<br><span class="vc-voucher-bold">{{ $voucher->code }}</span></p>
	                                                <p class="vc-voucher-small">Tap to toggle on mobile</p>
	                                            </div>
                                            <div class="vc-voucher-reveal" aria-hidden="true">
                                                <div class="vc-voucher-code">{{ $voucher->code }}</div>
                                                <div class="vc-voucher-meta">{{ ucfirst($voucher->fuel_type) }} • R {{ number_format((float) $voucher->amount, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-slate-900">{{ $voucher->fuelStation?->name ?? 'Unknown Station' }}</p>
                                    <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 uppercase">{{ $voucher->status }}</span>
                                </div>
                                <p class="text-sm text-slate-600 mt-1">
                                    {{ ucfirst($voucher->fuel_type) }} • R {{ number_format((float) $voucher->amount, 2) }}
                                </p>
                            </div>
                        @endif
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
		                    <a
		                        href="{{ route('driver.repayments.index') }}"
		                        class="payshap-ad-chip"
		                        aria-label="PayShap Instant EFT"
		                        title="PayShap Instant EFT"
		                    >
		                        <span class="payshap-logo" aria-hidden="true">
		                            <span class="payshap-logo__word"><span class="payshap-logo__pay">pay</span><span class="payshap-logo__shap">shap</span></span>
		                            <img
		                                src="{{ asset('images/shap.png') }}"
		                                alt=""
		                                class="payshap-logo__mark"
		                                loading="lazy"
		                                aria-hidden="true"
		                            >
		                        </span>
		                    </a>
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
	            @if(!empty($overdueSeconds) && (int) $overdueSeconds > 0)
	                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-4 overflow-hidden">
	                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
	                        <div>
	                            <p class="text-xs uppercase tracking-[0.2em] text-rose-700">Overdue</p>
	                            <p class="text-sm font-semibold text-slate-900 mt-1">Oldest overdue repayment</p>
	                            <p class="text-xs text-slate-600 mt-1">
	                                @if(!empty($mostOverdue))
	                                    Due {{ \Illuminate\Support\Carbon::parse($mostOverdue->due_date)->format('d M Y') }}
	                                    • -R {{ number_format(abs((float) $mostOverdue->amount), 2) }}
	                                @endif
	                            </p>
	                        </div>

	                        <div class="dashboard-overdue-clock-wrap">
	                            <div class="dashboard-overdue-clock" data-overdue-seconds="{{ (int) $overdueSeconds }}" aria-label="00:00:00:00">
	                                <div class="clock__block clock__block--delay2" aria-hidden="true" data-time-group>
	                                    <div class="clock__digit-group">
	                                        <div class="clock__digits" data-time="a">00</div>
	                                        <div class="clock__digits" data-time="b">00</div>
	                                    </div>
	                                    <div class="clock__label">Days</div>
	                                </div>
	                                <div class="clock__colon" aria-hidden="true"></div>
	                                <div class="clock__block clock__block--delay1" aria-hidden="true" data-time-group>
	                                    <div class="clock__digit-group">
	                                        <div class="clock__digits" data-time="a">00</div>
	                                        <div class="clock__digits" data-time="b">00</div>
	                                    </div>
	                                    <div class="clock__label">Hours</div>
	                                </div>
	                                <div class="clock__colon" aria-hidden="true"></div>
	                                <div class="clock__block" aria-hidden="true" data-time-group>
	                                    <div class="clock__digit-group">
	                                        <div class="clock__digits" data-time="a">00</div>
	                                        <div class="clock__digits" data-time="b">00</div>
	                                    </div>
	                                    <div class="clock__label">Mins</div>
	                                </div>
	                                <div class="clock__colon" aria-hidden="true"></div>
	                                <div class="clock__block clock__block--delay2" aria-hidden="true" data-time-group>
	                                    <div class="clock__digit-group">
	                                        <div class="clock__digits" data-time="a">00</div>
	                                        <div class="clock__digits" data-time="b">00</div>
	                                    </div>
	                                    <div class="clock__label">Secs</div>
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            @endif
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
                            <p class="text-sm font-semibold {{ $isOverdue ? 'text-red-700' : ($isDueToday ? 'text-amber-700' : 'text-slate-900') }}">-R {{ number_format(abs((float) $repayment->amount), 2) }}</p>
                        </div>
                        <p class="text-xs mt-1 uppercase {{ $isOverdue ? 'text-red-700 font-semibold' : ($isDueToday ? 'text-amber-700 font-semibold' : 'text-slate-500') }}">
                            {{ $isOverdue ? 'OVERDUE' : ($isDueToday ? 'DUE TODAY' : $repayment->status) }}
                        </p>
                        @if(in_array($repayment->status, ['pending', 'overdue'], true))
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('driver.repayments.pay-now', $repayment, false) }}" class="pay-btn">
                                    <span class="btn-text">Pay Now</span>
                                    <div class="icon-container">
                                        <svg viewBox="0 0 24 24" class="icon card-icon">
                                            <path
                                                d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18C2,19.11 2.89,20 4,20H20C21.11,20 22,19.11 22,18V6C22,4.89 21.11,4 20,4Z"
                                                fill="currentColor"
                                            ></path>
                                        </svg>
                                        <svg viewBox="0 0 24 24" class="icon payment-icon">
                                            <path
                                                d="M2,17H22V21H2V17M6.25,7H9V6H6V3H18V6H15V7H17.75L19,17H5L6.25,7M9,10H15V8H9V10M9,13H15V11H9V13Z"
                                                fill="currentColor"
                                            ></path>
                                        </svg>
                                        <svg viewBox="0 0 24 24" class="icon dollar-icon">
                                            <path
                                                d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"
                                                fill="currentColor"
                                            ></path>
                                        </svg>
                                        <svg viewBox="0 0 24 24" class="icon wallet-icon default-icon">
                                            <path
                                                d="M21,18V19A2,2 0 0,1 19,21H5C3.89,21 3,20.1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5V6H12C10.89,6 10,6.9 10,8V16A2,2 0 0,0 12,18M12,16H22V8H12M16,13.5A1.5,1.5 0 0,1 14.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,12A1.5,1.5 0 0,1 16,13.5Z"
                                                fill="currentColor"
                                            ></path>
                                        </svg>
                                        <svg viewBox="0 0 24 24" class="icon check-icon">
                                            <path
                                                d="M9,16.17L4.83,12L3.41,13.41L9,19L21,7L19.59,5.59L9,16.17Z"
                                                fill="currentColor"
                                            ></path>
                                        </svg>
                                    </div>
                                </a>
                            </div>
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

    .driver-vwallet-wrap {
        display: flex;
        justify-content: center;
    }

    .driver-vwallet-pocket {
        position: relative;
        width: 280px;
        height: 240px;
        cursor: pointer;
        perspective: 1000px;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        transition: transform 0.4s ease;
        user-select: none;
    }

    /* Apply for Voucher button (Uiverse animated-button) */
    .animated-button {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 12px 22px;
        border: 4px solid transparent;
        font-size: 14px;
        background-color: transparent;
        border-radius: 999px;
        font-weight: 700;
        color: #111827; /* keep text black */
        box-shadow: 0 0 0 2px #adff2f;
        cursor: pointer;
        overflow: hidden;
        text-decoration: none;
        transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        white-space: nowrap;
    }

    .animated-button svg {
        position: absolute;
        width: 22px;
        fill: #111827;
        z-index: 2;
        transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .animated-button .arr-1 { right: 14px; }
    .animated-button .arr-2 { left: -25%; }

    .animated-button .circle {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        background-color: #adff2f;
        border-radius: 50%;
        opacity: 0;
        transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        z-index: 1;
    }

    .animated-button .text {
        position: relative;
        z-index: 2;
        transform: translateX(-12px);
        transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        color: #111827;
    }

    .animated-button:hover {
        box-shadow: 0 0 0 12px transparent;
        color: #111827;
        border-radius: 12px;
    }

    .animated-button:hover .arr-1 { right: -25%; }
    .animated-button:hover .arr-2 { left: 14px; }
    .animated-button:hover .text { transform: translateX(12px); }
    .animated-button:hover svg { fill: #111827; }

    .animated-button:active {
        transform: scale(0.95);
        box-shadow: 0 0 0 4px #adff2f;
    }

    .animated-button:hover .circle {
        width: 220px;
        height: 220px;
        opacity: 1;
    }

    @media (max-width: 640px) {
        .animated-button {
            padding: 10px 18px;
            font-size: 13px;
        }
        .animated-button svg {
            width: 20px;
        }
    }

    .driver-vwallet-hint {
        margin-top: 0.75rem;
        text-align: center;
        font-style: italic;
        color: #1d4ed8;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: underline;
    }

    @keyframes driverVwalletSlide {
        from { transform: translateY(-90px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .driver-vwallet-back {
        position: absolute;
        bottom: 0;
        width: 280px;
        height: 200px;
        background: #1e341e;
        border-radius: 22px 22px 60px 60px;
        z-index: 5;
        box-shadow:
            inset 0 25px 35px rgba(0, 0, 0, 0.4),
            inset 0 5px 15px rgba(0, 0, 0, 0.5);
    }

    .driver-vwallet-card {
        position: absolute;
        width: 260px;
        height: 140px;
        left: 10px;
        border-radius: 16px;
        padding: 18px;
        color: var(--card-fg, #fff);
        background: var(--card-bg, #0b1220);
        box-shadow:
            inset 0 1px 1px rgba(255, 255, 255, 0.24),
            0 -4px 15px rgba(0, 0, 0, 0.1);
        transition:
            transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1),
            z-index 0s;
        animation: driverVwalletSlide 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) backwards;
        overflow: hidden;
    }

    .driver-vwallet-card::before {
        content: "";
        position: absolute;
        inset: -40px;
        background: radial-gradient(circle at 20% 20%, color-mix(in srgb, var(--card-accent, #38bdf8) 30%, transparent), transparent 55%);
        opacity: 0.8;
        pointer-events: none;
        transform: rotate(-6deg);
    }

    .driver-vwallet-card-inner {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        z-index: 1;
    }

    .driver-vwallet-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        gap: 10px;
    }

    .driver-vwallet-brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .driver-vwallet-brand-name {
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
    }

    .driver-vwallet-logo {
        height: 22px;
        width: 22px;
        object-fit: contain;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 6px;
        padding: 2px;
    }

    .driver-vwallet-fallback-logo {
        height: 22px;
        width: 22px;
        display: grid;
        place-content: center;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.12);
        font-weight: 900;
        color: var(--card-fg, #fff);
    }

    .driver-vwallet-chip {
        width: 32px;
        height: 24px;
        background: rgba(255, 255, 255, 0.16);
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        overflow: hidden;
    }

    .driver-vwallet-chip-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 3px;
        background: rgba(255, 255, 255, 0.92);
    }

    .driver-vwallet-chip-fallback {
        font-size: 12px;
        font-weight: 900;
        color: var(--card-fg, #fff);
        opacity: 0.95;
    }

    .driver-vwallet-top-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex: 0 0 auto;
    }

    .driver-vwallet-card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 12px;
    }

    .driver-vwallet-label {
        font-size: 9px;
        opacity: 0.75;
        text-transform: uppercase;
        margin-bottom: 3px;
        display: block;
        letter-spacing: 1px;
    }

    .driver-vwallet-value {
        font-size: 11px;
        font-weight: 800;
    }

    .driver-vwallet-pan {
        text-align: right;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        letter-spacing: 1px;
    }

    .driver-vwallet-hidden {
        display: block;
        font-size: 14px;
    }

    .driver-vwallet-full {
        display: none;
        font-size: 13px;
    }

    .driver-vwallet-exp {
        display: block;
        margin-top: 4px;
        font-size: 10px;
        opacity: 0.78;
        font-family: inherit;
        letter-spacing: 1px;
    }

    .driver-vwallet-cvv {
        display: none;
        margin-top: 2px;
        font-size: 10px;
        opacity: 0.82;
        font-family: inherit;
        letter-spacing: 1px;
    }

    .driver-vwallet-reveal-btn {
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(15, 23, 42, 0.22);
        color: var(--card-fg, #fff);
        padding: 6px 9px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        cursor: pointer;
        backdrop-filter: blur(10px);
        transition: transform 0.2s ease, background 0.2s ease, opacity 0.2s ease;
        opacity: 0.9;
    }

    .driver-vwallet-reveal-btn:hover {
        transform: translateY(-1px);
        background: rgba(15, 23, 42, 0.32);
        opacity: 1;
    }

    .driver-vwallet-reveal-btn[disabled] {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none;
    }

    .driver-vwallet-convert-btn {
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.12);
        color: var(--card-fg, #fff);
        padding: 6px 9px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        cursor: pointer;
        backdrop-filter: blur(10px);
        transition: transform 0.2s ease, background 0.2s ease, opacity 0.2s ease;
        opacity: 0.9;
    }

    .driver-vwallet-convert-btn:hover {
        transform: translateY(-1px);
        background: rgba(255, 255, 255, 0.18);
        opacity: 1;
    }

    .driver-vwallet-convert-btn[disabled] {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none;
    }

	    .driver-vwallet-modal {
	        position: fixed;
	        inset: 0;
	        z-index: 80;
	        display: none;
	        align-items: center;
	        justify-content: center;
	        padding: 1rem;
	        background: rgba(15, 23, 42, 0.55);
	        backdrop-filter: blur(8px);
	    }

	    .driver-vwallet-modal.is-open {
	        display: flex;
	    }

	    .driver-vwallet-modal-card {
	        width: min(520px, 100%);
	        border-radius: 18px;
	        background: #ffffff;
	        border: 1px solid rgba(148, 163, 184, 0.45);
	        box-shadow: 0 24px 70px -40px rgba(15, 23, 42, 0.9);
	        overflow: hidden;
	    }

	    .driver-vwallet-modal-head {
	        padding: 14px 16px;
	        display: flex;
	        align-items: center;
	        justify-content: space-between;
	        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
	        background: linear-gradient(135deg, #eff6ff, #ffffff);
	    }

	    .driver-vwallet-modal-title {
	        font-weight: 900;
	        color: #0f172a;
	    }

	    .driver-vwallet-modal-close {
	        border: 0;
	        background: transparent;
	        color: #0f172a;
	        font-weight: 900;
	        cursor: pointer;
	        padding: 6px 10px;
	        border-radius: 10px;
	    }

	    .driver-vwallet-modal-close:hover {
	        background: rgba(15, 23, 42, 0.06);
	    }

	    .driver-vwallet-modal-body {
	        padding: 16px;
	    }

	    .driver-vwallet-form-row {
	        display: grid;
	        grid-template-columns: 1fr;
	        gap: 12px;
	        margin-top: 12px;
	    }

	    .driver-vwallet-form-row label {
	        display: block;
	        font-size: 12px;
	        font-weight: 800;
	        letter-spacing: 0.06em;
	        text-transform: uppercase;
	        color: #334155;
	        margin-bottom: 6px;
	    }

	    .driver-vwallet-input,
	    .driver-vwallet-select {
	        width: 100%;
	        border: 1px solid rgba(148, 163, 184, 0.55);
	        border-radius: 12px;
	        padding: 10px 12px;
	        font-size: 14px;
	        color: #0f172a;
	        background: #ffffff;
	    }

	    .driver-vwallet-modal-actions {
	        display: flex;
	        gap: 10px;
	        justify-content: flex-end;
	        padding: 14px 16px 16px;
	        border-top: 1px solid rgba(148, 163, 184, 0.35);
	    }

	    .driver-vwallet-btn {
	        border-radius: 12px;
	        padding: 10px 14px;
	        font-weight: 800;
	        font-size: 13px;
	        cursor: pointer;
	        border: 1px solid rgba(148, 163, 184, 0.55);
	        background: #ffffff;
	        color: #0f172a;
	    }

	    .driver-vwallet-btn.primary {
	        background: #1d4ed8;
	        border-color: #1d4ed8;
	        color: #ffffff;
	    }

	    .driver-vwallet-btn[disabled] {
	        opacity: 0.6;
	        cursor: not-allowed;
	    }

    .driver-vwallet-pocket:hover {
        transform: translateY(-5px);
    }

    .driver-vwallet-card.slot-1 {
        bottom: 90px;
        z-index: 10;
        animation-delay: 0.1s;
    }

    .driver-vwallet-card.slot-2 {
        bottom: 65px;
        z-index: 20;
        animation-delay: 0.2s;
    }

    .driver-vwallet-card.slot-3 {
        bottom: 40px;
        z-index: 30;
        animation-delay: 0.3s;
    }

    .driver-vwallet-pocket:hover .driver-vwallet-card.slot-1 {
        transform: translateY(-75px) rotate(-3deg);
        z-index: 10;
    }

    .driver-vwallet-pocket:hover .driver-vwallet-card.slot-2 {
        transform: translateY(-45px) rotate(2deg);
        z-index: 20;
    }

    .driver-vwallet-pocket:hover .driver-vwallet-card.slot-3 {
        transform: translateY(-10px);
        z-index: 30;
    }

    .driver-vwallet-card:hover {
        z-index: 100 !important;
    }

    .driver-vwallet-pocket:hover .driver-vwallet-card:hover {
        transform: translateY(-60px) scale(1.05) rotate(0);
    }

    .driver-vwallet-card:hover .driver-vwallet-hidden {
        display: none;
    }

    .driver-vwallet-card:hover .driver-vwallet-full {
        display: block;
    }

    .driver-vwallet-card:hover .driver-vwallet-cvv {
        display: block;
    }

    .driver-vwallet-card.is-revealed .driver-vwallet-hidden {
        display: none;
    }

    .driver-vwallet-card.is-revealed .driver-vwallet-full {
        display: block;
    }

    .driver-vwallet-card.is-revealed .driver-vwallet-cvv {
        display: block;
    }

    /* Keep PAN visible when opening the convert modal, without revealing CVV. */
    .driver-vwallet-card.is-peeking .driver-vwallet-hidden {
        display: none;
    }

    .driver-vwallet-card.is-peeking .driver-vwallet-full {
        display: block;
    }

    .driver-vwallet-pocket-front {
        position: absolute;
        bottom: 0;
        width: 280px;
        height: 160px;
        z-index: 40;
        filter: drop-shadow(0 15px 25px rgba(20, 40, 20, 0.4));
    }

    .driver-vwallet-pocket-content {
        position: absolute;
        top: 45px;
        width: 100%;
        text-align: center;
        z-index: 50;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        pointer-events: none;
    }

    .driver-vwallet-balance {
        position: relative;
        height: 24px;
        width: 100%;
    }

    .driver-vwallet-balance-stars {
        color: #839e7b;
        font-size: 24px;
        letter-spacing: 4px;
        transition: 0.3s;
    }

    .driver-vwallet-balance-real {
        color: #a7c59e;
        font-size: 22px;
        font-weight: 700;
        opacity: 0;
        position: absolute;
        top: 0;
        left: 50%;
        transform: translate(-50%, 10px);
        transition: 0.3s;
        white-space: nowrap;
    }

    .driver-vwallet-subtitle {
        color: #698263;
        font-size: 12px;
        font-weight: 600;
    }

    .driver-vwallet-eye {
        margin-top: 6px;
        height: 20px;
        width: 20px;
        position: relative;
        opacity: 0.35;
        transition: 0.3s;
    }

    .driver-vwallet-eye-icon {
        position: absolute;
        inset: 0;
        stroke: #3be60b;
        transition: 0.3s;
    }

    .driver-vwallet-eye-open {
        opacity: 0;
        transform: scale(0.9);
    }

    .driver-vwallet-pocket:hover .driver-vwallet-eye {
        opacity: 1;
    }

    .driver-vwallet-pocket:hover .driver-vwallet-balance-stars {
        opacity: 0;
    }

    .driver-vwallet-pocket:hover .driver-vwallet-balance-real {
        opacity: 1;
        transform: translate(-50%, 0);
    }

    .driver-vwallet-pocket:hover .driver-vwallet-eye-slash {
        opacity: 0;
        transform: scale(0.5);
    }

    .driver-vwallet-pocket:hover .driver-vwallet-eye-open {
        opacity: 1;
        transform: scale(1.1);
    }

    @media (hover: none) {
        .driver-vwallet-hint { display: none; }
        .driver-vwallet-balance-stars { opacity: 0; }
        .driver-vwallet-balance-real { opacity: 1; transform: translate(-50%, 0); }
        .driver-vwallet-eye-slash { opacity: 0; }
        .driver-vwallet-eye-open { opacity: 1; }
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

	    .payshap-ad-chip {
	        width: 132px;
	        height: 48px;
	        border-radius: 16px;
	        border: none;
	        background: rgba(255, 255, 255, 0.92);
	        display: inline-flex;
	        flex-direction: row;
	        align-items: center;
	        justify-content: center;
	        padding: 8px 10px;
	        gap: 8px;
	        box-shadow: 0 12px 24px -22px rgba(15, 23, 42, 0.28);
	        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
	        text-decoration: none;
	    }

	    .payshap-ad-chip:hover {
	        transform: translateY(-1px);
	        box-shadow: 0 16px 28px -24px rgba(37, 99, 235, 0.22);
	        background: #ffffff;
	    }

	    .payshap-logo {
	        --payshap-ink: #160b63;
	        display: inline-flex;
	        align-items: center;
	        gap: 8px;
	    }

	    .payshap-logo__word {
	        color: var(--payshap-ink);
	        font-style: italic;
	        font-size: 22px;
	        letter-spacing: -0.02em;
	        line-height: 1;
	        text-transform: lowercase;
	        white-space: nowrap;
	    }

	    .payshap-logo__pay {
	        font-weight: 500;
	    }

	    .payshap-logo__shap {
	        font-weight: 900;
	    }

	    .payshap-logo__mark {
	        width: 24px;
	        height: 24px;
	        border-radius: 6px;
	        display: block;
	        object-fit: cover;
	        box-shadow: 0 6px 12px -10px rgba(2, 6, 23, 0.55);
	        transform: translateY(-0.5px);
	        flex: 0 0 auto;
	        background: rgba(255, 255, 255, 0.08);
	    }

	    .dashboard-overdue-clock-wrap {
	        display: flex;
	        justify-content: flex-end;
	    }

	    .dashboard-overdue-clock {
	        display: flex;
	        flex-direction: row;
	        flex-wrap: wrap;
	        align-items: center;
	        gap: 10px;
	    }

	    .dashboard-overdue-clock .clock__block {
	        background: rgba(255, 255, 255, 0.8);
	        border: 1px solid rgba(251, 113, 133, 0.28);
	        border-radius: 14px;
	        box-shadow: 0 14px 34px -22px rgba(225, 29, 72, 0.35);
	        overflow: hidden;
	        text-align: center;
	        width: 84px;
	        height: 84px;
	        transition: background-color 0.3s, box-shadow 0.3s;
	    }

	    .dashboard-overdue-clock .clock__digit-group {
	        display: flex;
	        flex-direction: column-reverse;
	        height: 58px;
	        justify-content: flex-start;
	    }

	    .dashboard-overdue-clock .clock__digits {
	        display: grid;
	        place-items: center;
	        width: 100%;
	        height: 58px;
	        font-size: 28px;
	        line-height: 1;
	        font-weight: 800;
	        color: rgba(15, 23, 42, 0.92);
	        letter-spacing: 0.04em;
	    }

	    .dashboard-overdue-clock .clock__label {
	        font-size: 10px;
	        font-weight: 700;
	        letter-spacing: 0.12em;
	        text-transform: uppercase;
	        color: rgba(190, 18, 60, 0.9);
	        padding: 6px 6px 8px;
	        border-top: 1px solid rgba(251, 113, 133, 0.24);
	        background: rgba(251, 113, 133, 0.06);
	    }

	    .dashboard-overdue-clock .clock__colon {
	        display: none;
	        font-size: 2em;
	        opacity: 0.5;
	        position: relative;
	    }

	    .dashboard-overdue-clock .clock__colon:before,
	    .dashboard-overdue-clock .clock__colon:after {
	        background-color: currentColor;
	        border-radius: 50%;
	        content: "";
	        display: block;
	        position: absolute;
	        top: -0.05em;
	        left: -0.05em;
	        width: 0.1em;
	        height: 0.1em;
	        transition: background-color 0.3s;
	    }

	    .dashboard-overdue-clock .clock__colon:before { transform: translateY(-200%); }
	    .dashboard-overdue-clock .clock__colon:after { transform: translateY(200%); }

	    .dashboard-overdue-clock .clock__block--bounce {
	        animation: dashboardOverdueBounce 0.75s;
	    }

	    .dashboard-overdue-clock .clock__block--bounce .clock__digit-group {
	        animation: dashboardOverdueRoll 0.75s ease-in-out forwards;
	        transform: translateY(-50%);
	    }

	    .dashboard-overdue-clock .clock__block--delay1,
	    .dashboard-overdue-clock .clock__block--delay1 .clock__digit-group { animation-delay: 0.1s; }

	    .dashboard-overdue-clock .clock__block--delay2,
	    .dashboard-overdue-clock .clock__block--delay2 .clock__digit-group { animation-delay: 0.2s; }

	    @media (min-width: 640px) {
	        .dashboard-overdue-clock .clock__colon {
	            display: inherit;
	        }
	    }

	    @media (max-width: 420px) {
	        .dashboard-overdue-clock {
	            justify-content: flex-start;
	            width: 100%;
	        }

	        .dashboard-overdue-clock .clock__block {
	            width: 74px;
	            height: 74px;
	        }

	        .dashboard-overdue-clock .clock__digits {
	            height: 52px;
	            font-size: 24px;
	        }

	        .dashboard-overdue-clock .clock__digit-group {
	            height: 52px;
	        }
	    }

	    @keyframes dashboardOverdueBounce {
	        from,
	        to {
	            animation-timing-function: ease-in;
	            transform: translateY(0);
	        }
	        50% {
	            animation-timing-function: ease-out;
	            transform: translateY(12%);
	        }
	    }

	    @keyframes dashboardOverdueRoll {
	        from { transform: translateY(-50%); }
	        to { transform: translateY(0); }
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

    .pay-btn {
        position: relative;
        padding: 12px 24px;
        font-size: 16px;
        background: #1a1a1a;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .pay-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
    }

    .pay-btn .icon-container {
        position: relative;
        width: 24px;
        height: 24px;
    }

    .pay-btn .icon {
        position: absolute;
        top: 0;
        left: 0;
        width: 24px;
        height: 24px;
        color: #22c55e;
        opacity: 0;
        visibility: hidden;
    }

    .pay-btn .default-icon {
        opacity: 1;
        visibility: visible;
    }

    .pay-btn:hover .icon {
        animation: none;
    }

    .pay-btn:hover .wallet-icon {
        opacity: 0;
        visibility: hidden;
    }

    .pay-btn:hover .card-icon {
        animation: iconRotate 2.5s infinite;
        animation-delay: 0s;
    }

    .pay-btn:hover .payment-icon {
        animation: iconRotate 2.5s infinite;
        animation-delay: 0.5s;
    }

    .pay-btn:hover .dollar-icon {
        animation: iconRotate 2.5s infinite;
        animation-delay: 1s;
    }

    .pay-btn:hover .check-icon {
        animation: iconRotate 2.5s infinite;
        animation-delay: 1.5s;
    }

    .pay-btn:active .icon {
        animation: none;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .pay-btn:active .check-icon {
        animation: checkmarkAppear 0.6s ease forwards;
        visibility: visible;
    }

    .pay-btn .btn-text {
        font-weight: 600;
        font-family: system-ui, -apple-system, sans-serif;
    }

    @keyframes iconRotate {
        0% {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px) scale(0.5);
        }
        5% {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        15% {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        20% {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.5);
        }
        100% {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.5);
        }
    }

    @keyframes checkmarkAppear {
        0% {
            opacity: 0;
            transform: scale(0.5) rotate(-45deg);
        }
        50% {
            opacity: 0.5;
            transform: scale(1.2) rotate(0deg);
        }
        100% {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }
    }

    /* Virtual-card created vouchers: hover/tap reveal tile */
    .vc-voucher-card {
        width: 190px;
        height: 190px;
        background: rgb(22, 22, 22);
        color: white;
        border-radius: 1rem;
        padding: 1rem;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: 300ms ease;
        animation: 8s vcThumbThumb infinite;
        user-select: none;
        cursor: pointer;
        outline: none;
    }

    .vc-voucher-card svg path {
        transition: 300ms ease;
        opacity: 0;
    }

    .vc-voucher-bold {
        font-weight: 800;
        letter-spacing: .04em;
    }

	    /* Uiverse loader for virtual-card vouchers (replaces token SVG icon) */
	    .vc-voucher-token {
	        width: 3.2em;
	        height: 3.2em;
	        position: relative;
	        background: linear-gradient(-45deg, #fc00ff 0%, #00dbde 100%);
	        animation: vcVoucherSpin 3s infinite;
	        margin: 0 auto 10px;
	    }

	    .vc-voucher-token::before {
	        content: "";
	        z-index: -1;
	        position: absolute;
	        inset: 0;
	        background: linear-gradient(-45deg, #fc00ff 0%, #00dbde 100%);
	        transform: translate3d(0, 0, 0) scale(0.95);
	        filter: blur(20px);
	    }

    .vc-voucher-blur {
        position: absolute;
        inset: 0;
        width: 60px;
        margin: 0 auto;
        height: 60px;
        border-radius: 1rem;
        z-index: -1;
        opacity: 0.7;
        filter: blur(15px);
        background: linear-gradient(120deg, rgba(34, 211, 238, 0.24), rgba(59, 130, 246, 0.42), rgba(34, 211, 238, 0.20));
    }

    .vc-voucher-prompt {
        position: absolute;
        color: rgb(173, 173, 173);
        text-align: center;
        max-width: 170px;
    }

    .vc-voucher-small {
        text-align: center;
        width: 100%;
        position: absolute;
        font-size: 10px;
        margin-top: 36px;
        opacity: 0.55;
        left: 0;
    }

	    .vc-voucher-token-icon {
	        display: none;
	    }

    .vc-voucher-reveal {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: end center;
        padding-bottom: 16px;
        opacity: 0;
        transform: translateY(6px);
        transition: 300ms ease;
        pointer-events: none;
    }

    .vc-voucher-code {
        color: #0f172a;
        font-weight: 900;
        letter-spacing: .08em;
        font-size: 14px;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 999px;
        padding: 6px 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        margin-bottom: 8px;
    }

    .vc-voucher-meta {
        color: rgba(15, 23, 42, 0.8);
        font-size: 11px;
        font-weight: 600;
    }

    .vc-voucher-card:hover,
    .vc-voucher-card.is-open {
        background-color: white;
    }

    .vc-voucher-card:hover .vc-voucher-prompt,
    .vc-voucher-card.is-open .vc-voucher-prompt {
        transition: 300ms ease;
        opacity: 0;
    }

    .vc-voucher-card:hover svg path,
    .vc-voucher-card.is-open svg path {
        opacity: 1;
    }

    .vc-voucher-card:hover .vc-voucher-reveal,
    .vc-voucher-card.is-open .vc-voucher-reveal {
        opacity: 1;
        transform: translateY(0);
    }

	    @keyframes vcVoucherSpin {
	        0% {
	            transform: rotate(-45deg);
	        }
	        50% {
	            transform: rotate(-360deg);
	            border-radius: 50%;
	        }
	        100% {
	            transform: rotate(-45deg);
	        }
	    }

    @keyframes vcThumbThumb {
        0%, 10%, 100% {
            transform: scale(1);
        }
        5% {
            transform: scale(1.03);
        }
        7% {
            transform: scale(0.985);
        }
    }
</style>

<script>
    (function () {
        const cards = document.querySelectorAll('.vc-voucher-card');
        cards.forEach((el) => {
            el.addEventListener('click', () => el.classList.toggle('is-open'));
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    el.classList.toggle('is-open');
                }
            });
        });
    })();
</script>
<script>
    (function initDriverVirtualCardReveal() {
        const cards = Array.from(
            document.querySelectorAll('.driver-vwallet-card[data-reveal-url]')
        );
        if (!cards.length) return;
        const csrfMeta = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';

        const hideTimeoutByCardId = new Map();

        const formatExpiry = (month, year) => {
            const m = Number(month);
            const y = Number(year);
            if (!Number.isFinite(m) || !Number.isFinite(y) || m <= 0 || y <= 0) {
                return '--/--';
            }
            const mm = String(m).padStart(2, '0');
            const yy = String(y).slice(-2);
            return `${mm}/${yy}`;
        };

        const clearReveal = (cardEl) => {
            cardEl.classList.remove('is-revealed');
            const panEl = cardEl.querySelector('.driver-vwallet-full');
            const expEl = cardEl.querySelector('.driver-vwallet-exp');
            const cvvEl = cardEl.querySelector('.driver-vwallet-cvv');
            if (panEl) {
                panEl.textContent = panEl.getAttribute('data-pan-fallback') || '**** ••••';
            }
            if (expEl) {
                const exp = expEl.getAttribute('data-exp-fallback') || '--/--';
                expEl.textContent = `EXP ${exp}`;
            }
            if (cvvEl) {
                const cvv = cvvEl.getAttribute('data-cvv-fallback') || '---';
                cvvEl.textContent = `CVV ${cvv}`;
            }
            const btn = cardEl.querySelector('.driver-vwallet-reveal-btn');
            if (btn) btn.textContent = 'Reveal';
        };

        const scheduleHide = (cardId, cardEl) => {
            const existing = hideTimeoutByCardId.get(cardId);
            if (existing) window.clearTimeout(existing);
            hideTimeoutByCardId.set(
                cardId,
                window.setTimeout(() => clearReveal(cardEl), 15000)
            );
        };

        cards.forEach((cardEl) => {
            const btn = cardEl.querySelector('.driver-vwallet-reveal-btn');
            if (!btn) return;

            btn.addEventListener('click', async (e) => {
                e.preventDefault();

                const cardId = cardEl.getAttribute('data-card-id') || '';
                if (!cardId) return;

                if (cardEl.classList.contains('is-revealed')) {
                    clearReveal(cardEl);
                    return;
                }

                const url = cardEl.getAttribute('data-reveal-url') || '';
                if (!url) return;
                const csrf = csrfMeta || cardEl.getAttribute('data-csrf') || '';

                btn.setAttribute('disabled', 'disabled');
                btn.textContent = 'Revealing...';

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        },
                        body: JSON.stringify({}),
                    });

                    const payload = await res.json().catch(() => ({}));
                    if (!res.ok || payload?.success !== true) {
                        const msg = payload?.message || 'Could not reveal card details.';
                        throw new Error(msg);
                    }

                    const data = payload?.data || {};
                    const pan = String(data?.pan || '').trim();
                    const exp = formatExpiry(data?.expiry_month, data?.expiry_year);
                    const cvv = (data?.cvv === null || data?.cvv === undefined)
                        ? null
                        : (String(data?.cvv || '').trim() || null);

                    const panEl = cardEl.querySelector('.driver-vwallet-full');
                    const expEl = cardEl.querySelector('.driver-vwallet-exp');
                    const cvvEl = cardEl.querySelector('.driver-vwallet-cvv');

                    if (panEl && pan) panEl.textContent = pan;
                    if (expEl) expEl.textContent = `EXP ${exp}`;
                    if (cvvEl) cvvEl.textContent = `CVV ${cvv ?? 'N/A'}`;

                    if (cvv === null && data?.message) {
                        // Helpful for legacy placeholder cards or providers that do not return CVV.
                        // Avoid spamming; only show on reveal clicks.
                        window.alert(String(data.message));
                    }

                    cardEl.classList.add('is-revealed');
                    btn.textContent = 'Hide';
                    scheduleHide(cardId, cardEl);
                } catch (err) {
                    btn.textContent = 'Reveal';
                    const msg = err?.message || 'Could not reveal card details.';
                    window.alert(msg);
                } finally {
                    btn.removeAttribute('disabled');
                }
            });
        });
    })();

    (function initDriverVirtualCardConvertToVoucher() {
        const modal = document.getElementById('driverVwalletConvertModal');
        if (!modal) return;

        const stationsByBrand = @json($stationsByBrandSlug ?? []);
        const stationSelect = document.getElementById('driverVwalletStation');
        const fuelTypeSelect = document.getElementById('driverVwalletFuelType');
        const amountInput = document.getElementById('driverVwalletAmount');
        const amountHint = document.getElementById('driverVwalletAmountHint');
        const submitBtn = document.getElementById('driverVwalletConvertSubmit');
        const errorBox = document.getElementById('driverVwalletConvertError');

        let activeCardEl = null;

        const close = () => {
            modal.classList.remove('is-open');
            if (activeCardEl) {
                activeCardEl.classList.remove('is-peeking');
            }
            activeCardEl = null;
            if (errorBox) {
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
            }
        };

        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
        modal.querySelectorAll('[data-close]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                close();
            });
        });
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
        });

        const renderStations = (slug) => {
            const list = Array.isArray(stationsByBrand?.[slug]) ? stationsByBrand[slug] : [];
            stationSelect.innerHTML = '';
            if (!list.length) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'No active stations for this brand';
                stationSelect.appendChild(opt);
                stationSelect.disabled = true;
                return;
            }
            list.forEach((row) => {
                const opt = document.createElement('option');
                opt.value = String(row.id);
                opt.textContent = `${row.name}${row.city ? ' • ' + row.city : ''}`;
                stationSelect.appendChild(opt);
            });
            stationSelect.disabled = false;
        };

        const open = (cardEl) => {
            activeCardEl = cardEl;
            // Opening the modal ends :hover, so keep the PAN visible while the modal is open.
            // This does not reveal CVV (that remains reveal-only).
            activeCardEl.classList.add('is-peeking');
            const slug = cardEl.getAttribute('data-brand-slug') || '';
            const allocated = Number(cardEl.getAttribute('data-allocated') || '0') || 0;
            renderStations(slug);
            amountInput.value = allocated > 0 ? allocated.toFixed(2) : '';
            amountInput.max = allocated > 0 ? String(allocated.toFixed(2)) : '';
            if (amountHint) {
                amountHint.textContent = allocated > 0
                    ? `Max: R ${allocated.toFixed(2)} (allocated on card)`
                    : 'No allocation available on this card.';
            }
            modal.classList.add('is-open');
        };

        document.querySelectorAll('.driver-vwallet-convert-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const cardEl = btn.closest('.driver-vwallet-card');
                if (!cardEl) return;
                open(cardEl);
            });
        });

        submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            if (!activeCardEl) return;

            const url = activeCardEl.getAttribute('data-convert-url') || '';
            const csrf = activeCardEl.getAttribute('data-csrf') || '';
            const stationId = String(stationSelect.value || '').trim();
            const fuelType = String(fuelTypeSelect.value || 'petrol').trim();
            const amount = Number(amountInput.value || '0');

            if (!url) return;
            if (!stationId) {
                if (errorBox) {
                    errorBox.textContent = 'Select a station.';
                    errorBox.classList.remove('hidden');
                }
                return;
            }
            if (!Number.isFinite(amount) || amount < 10) {
                if (errorBox) {
                    errorBox.textContent = 'Enter a valid amount (min R10).';
                    errorBox.classList.remove('hidden');
                }
                return;
            }

            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.textContent = 'Creating...';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: JSON.stringify({
                        fuel_station_id: Number(stationId),
                        amount,
                        fuel_type: fuelType,
                    }),
                });

                const payload = await res.json().catch(() => ({}));
                if (!res.ok || payload?.success !== true) {
                    const msg = payload?.message || payload?.errors?.amount?.[0] || 'Could not create voucher.';
                    throw new Error(msg);
                }

                const redirect = payload?.data?.redirect_url || '';
                if (redirect) {
                    window.location.href = redirect;
                    return;
                }
                window.location.reload();
            } catch (err) {
                if (errorBox) {
                    errorBox.textContent = err?.message || 'Could not create voucher.';
                    errorBox.classList.remove('hidden');
                }
            } finally {
                submitBtn.removeAttribute('disabled');
                submitBtn.textContent = 'Create Voucher';
            }
        });
    })();

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

	    (function initDashboardOverdueClock() {
	        const els = document.querySelectorAll(".dashboard-overdue-clock[data-overdue-seconds]");
	        if (!els.length) return;
	        els.forEach((el) => new BwDashboardOverdueClock(el));

	        const pad2 = (value) => String(Math.max(0, value)).padStart(2, "0");

	        class BwDashboardOverdueClock {
	            constructor(el) {
	                this.el = el;
	                this.time = { a: [], b: [] };
	                this.rollClass = "clock__block--bounce";
	                this.digitsTimeout = null;
	                this.rollTimeout = null;

	                this.baseSeconds = parseInt(el.getAttribute("data-overdue-seconds") || "0", 10) || 0;
	                this.startedAt = Date.now();
	                this.loop();
	            }

	            loop() {
	                this.updateTime();
	                this.displayTime();
	                this.animateDigits();
	                this.tick();
	            }

	            tick() {
	                clearTimeout(this.digitsTimeout);
	                this.digitsTimeout = setTimeout(this.loop.bind(this), 1000);
	            }

	            animateDigits() {
	                const groups = this.el.querySelectorAll("[data-time-group]");
	                Array.from(groups).forEach((group, i) => {
	                    const { a, b } = this.time;
	                    if (a[i] !== b[i]) group.classList.add(this.rollClass);
	                });

	                clearTimeout(this.rollTimeout);
	                this.rollTimeout = setTimeout(() => this.removeAnimations(), 900);
	            }

	            removeAnimations() {
	                const groups = this.el.querySelectorAll("[data-time-group]");
	                Array.from(groups).forEach((group) => group.classList.remove(this.rollClass));
	            }

	            displayTime() {
	                const timeDigits = [...this.time.b];
	                this.el.ariaLabel = timeDigits.join(":");

	                Object.keys(this.time).forEach((letter) => {
	                    const letterEls = this.el.querySelectorAll(`[data-time="${letter}"]`);
	                    Array.from(letterEls).forEach((node, i) => {
	                        node.textContent = this.time[letter][i];
	                    });
	                });
	            }

	            updateTime() {
	                const elapsedSeconds = Math.floor((Date.now() - this.startedAt) / 1000);
	                const total = Math.max(0, this.baseSeconds + elapsedSeconds);

	                const days = Math.floor(total / 86400);
	                const hours = Math.floor((total % 86400) / 3600);
	                const mins = Math.floor((total % 3600) / 60);
	                const secs = Math.floor(total % 60);

	                this.time.a = [...this.time.b];
	                this.time.b = [pad2(days), pad2(hours), pad2(mins), pad2(secs)];
	                if (!this.time.a.length) this.time.a = [...this.time.b];
	            }
	        }
    })();

	</script>

    <script>
        (function () {
            const openBtn = document.getElementById('walletTopupOpenBtn');
            const modal = document.getElementById('walletTopupModal');
            const closeBtn = document.getElementById('walletTopupCloseBtn');
            const amountInput = document.getElementById('walletTopupAmount');
            const submitBtn = document.getElementById('walletTopupSubmitBtn');
            const form = document.getElementById('walletTopupForm');

            function open() {
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                setTimeout(() => amountInput && amountInput.focus(), 0);
            }

            function close() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
            }

            openBtn && openBtn.addEventListener('click', open);
            closeBtn && closeBtn.addEventListener('click', close);
            modal && modal.addEventListener('click', (e) => {
                if (e.target === modal) close();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') close();
            });

            // Basic UX: disable submit after the form actually submits (prevents blocking the submit).
            form && form.addEventListener('submit', () => {
                if (!submitBtn) return;
                submitBtn.setAttribute('disabled', 'disabled');
                submitBtn.textContent = 'Redirecting to Paystack...';
            });
        })();
    </script>
	@endsection
