@extends('Layouts.app')

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
                <a href="{{ route('driver.bank-statements.create') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Upload Bank Statement</a>
                <a href="{{ route('driver.profile') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Profile</a>
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
	                                data-csrf="{{ csrf_token() }}"
	                                style="--card-bg: {{ $theme['bg'] }}; --card-fg: {{ $theme['fg'] }}; --card-accent: {{ $theme['accent'] }};"
	                            >
                                <div class="driver-vwallet-card-inner">
                                    <div class="driver-vwallet-card-top">
                                        <span class="driver-vwallet-brand">
                                            @if($logoUrl)
                                                <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" class="driver-vwallet-logo" loading="lazy">
                                            @else
                                                <span class="driver-vwallet-fallback-logo">{{ substr($brandName, 0, 1) }}</span>
                                            @endif
                                            <span class="driver-vwallet-brand-name">{{ $brandName }}</span>
                                        </span>
                                        <span class="driver-vwallet-chip" aria-hidden="true"></span>
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
	
	                                    <button type="button" class="driver-vwallet-reveal-btn" aria-label="Reveal card details">
	                                        Reveal
	                                    </button>
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
        max-width: 170px;
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
        position: absolute;
        right: 14px;
        top: 14px;
        z-index: 2;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(15, 23, 42, 0.22);
        color: var(--card-fg, #fff);
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 11px;
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
</style>
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
                    const cvv = String(data?.cvv || '').trim() || '---';

                    const panEl = cardEl.querySelector('.driver-vwallet-full');
                    const expEl = cardEl.querySelector('.driver-vwallet-exp');
                    const cvvEl = cardEl.querySelector('.driver-vwallet-cvv');

                    if (panEl && pan) panEl.textContent = pan;
                    if (expEl) expEl.textContent = `EXP ${exp}`;
                    if (cvvEl) cvvEl.textContent = `CVV ${cvv}`;

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
