@extends('Layouts.app')

@section('title', 'Merchant Dashboard - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="merchant-brand-mark" aria-label="Merchant branding">
                @if(($branding['mode'] ?? 'brand') === 'upload' && !empty($branding['upload_logo_url']))
                    <img src="{{ $branding['upload_logo_url'] }}" alt="Merchant dashboard logo" class="merchant-brand-mark-img" loading="lazy">
                @elseif(!empty($branding['brand_logo_url']))
                    <img src="{{ $branding['brand_logo_url'] }}" alt="{{ $branding['brand_name'] ?: 'Fuel brand' }} logo" class="merchant-brand-mark-img" loading="lazy">
                @elseif(!empty($branding['brand_name']))
                    <span class="merchant-brand-mark-text">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($branding['brand_name'], 0, 2)) }}</span>
                @else
                    <x-fuel-station-icon size="42" />
                @endif
            </div>
            <div>
           
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Merchant Station Console</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">Merchant Dashboard</h1>
            @if(!empty($branding['brand_name']))
                <p class="text-xs uppercase tracking-[0.16em] text-slate-500 mt-1">Brand Theme: {{ $branding['brand_name'] }}</p>
            @endif
            <p class="text-slate-600 mt-3">Live voucher visibility, manual redemption control, and direct bank deposit ready totals.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @if(Route::has('merchant.vouchers.index'))
                <a href="{{ route('merchant.vouchers.index') }}" class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold">View All Vouchers</a>
            @endif
            @if(Route::has('merchant.vouchers.stream'))
                <a href="{{ route('merchant.vouchers.stream') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold" target="_blank" rel="noopener">Open Stream JSON</a>
            @endif
            <span id="wsStatus" class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600">
                Realtime: connecting
            </span>
        </div>
    </div>

    @include('merchant.partials.nav')

    @if(session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-6 bw-error-alert" data-error-alert>
            <button type="button" aria-label="close-error" class="bw-error-alert-close" data-alert-close>
                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
            <p class="bw-error-alert-text">
                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="28" width="28" xmlns="http://www.w3.org/2000/svg">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                </svg>
                {{ session('error') }}
            </p>
        </div>
    @endif

    @if($errors->any())
        <div class="mt-6 bw-error-alert" data-error-alert>
            <button type="button" aria-label="close-error" class="bw-error-alert-close" data-alert-close>
                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
            <p class="bw-error-alert-text">
                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="28" width="28" xmlns="http://www.w3.org/2000/svg">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                </svg>
                {{ $errors->first() }}
            </p>
        </div>
    @endif

    @if(!$station)
        <div class="glass rounded-2xl p-6 mt-8">
            <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                No station is linked to this merchant account yet. Every station must be assigned to a merchant user.
            </p>
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-5">
                <article class="bwiser-station-card">
                    <div class="bwiser-station-card-content">
                        <p class="bwiser-station-eyebrow">Bwiser Merchant Station</p>
                        <h3 class="bwiser-station-title">{{ $station->name }}</h3>
                        <h4
                            id="bwiserStationSubtitle"
                            class="bwiser-station-subtitle"
                            data-text="Verified station profile connected to real-time vouchers, direct bank deposits, and live redemption controls."
                        ></h4>
                        <div class="bwiser-station-meta">
                            <span>{{ $station->city }}, {{ $station->country }}</span>
                            @if(!empty($station->contact_phone))
                                <span>{{ $station->contact_phone }}</span>
                            @endif
                            @if(!empty($station->contact_email))
                                <span>{{ $station->contact_email }}</span>
                            @endif
                        </div>
                    </div>
                   
                </article>
            </div>
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="glass rounded-2xl p-5">
                    <p class="text-base text-slate-500">Issued</p>
                    <p id="issuedCount" class="mt-2 text-4xl font-semibold text-slate-900">{{ $summary['issued'] }}</p>
                    <p class="text-sm text-slate-500 mt-1">R {{ number_format((float) ($summary['issued_amount'] ?? 0), 2) }}</p>
                </div>
                <div class="glass rounded-2xl p-5">
                    <p class="text-base text-slate-500">Approved</p>
                    <p id="approvedCount" class="mt-2 text-4xl font-semibold text-slate-900">{{ $summary['approved'] }}</p>
                    <p class="text-sm text-slate-500 mt-1">R {{ number_format((float) ($summary['approved_amount'] ?? 0), 2) }}</p>
                </div>
                <div class="glass rounded-2xl p-5">
                    <p class="text-base text-slate-500">Redeemed</p>
                    <p id="redeemedCount" class="mt-2 text-4xl font-semibold text-slate-900">{{ $summary['redeemed'] }}</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $summary['today_redeemed'] }} today • R {{ number_format((float) ($summary['redeemed_amount'] ?? 0), 2) }}</p>
                </div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="glass rounded-2xl p-5">
                <p class="text-sm text-slate-500">Total Voucher Value</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) ($financial['total_voucher_value'] ?? 0), 2) }}</p>
            </div>
            <div class="glass rounded-2xl p-5">
                <p class="text-sm text-slate-500">Pending Direct Bank Deposit</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) ($financial['pending_settlement_amount'] ?? 0), 2) }}</p>
            </div>
            <div class="glass rounded-2xl p-5">
                <p class="text-sm text-slate-500">Completed Direct Bank Deposits</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) ($financial['completed_settlement_amount'] ?? 0), 2) }}</p>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 mt-8">
            <div class="flex items-center justify-between">
                <h2 class="brand-font text-xl text-slate-900">Approved Vouchers</h2>
                @if(Route::has('merchant.vouchers.index'))
                    <a href="{{ route('merchant.vouchers.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View full history</a>
                @endif
            </div>
            <div class="mt-5 bwiser-approved-grid">
                @if($approvedVouchers->count())
                    @foreach($approvedVouchers as $voucher)
                        @php
                            $driverName = trim((string) ($voucher->user?->name ?? 'Unknown Driver'));
                            $issuedAt = $voucher->issued_at;
                            $expiresAt = $voucher->expires_at;
                            $qrValue = (string) ($voucher->qr_code ?: $voucher->code ?: $voucher->id);
                            $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=8&data=' . urlencode($qrValue);
                            $voucherStatus = (string) ($voucher->status ?? 'approved');
                        @endphp

                        <article class="av-card">
                            <div class="av-card-content">
                                <div class="av-card-top">
                                    <span class="av-card-title">{{ $loop->iteration < 10 ? '0' . $loop->iteration : $loop->iteration }}.</span>
                                    <p>{{ $voucher->code }}</p>
                                </div>
                                <div class="av-card-bottom">
                                    <div>
                                        <p>{{ ucfirst((string) ($voucher->fuel_type ?: 'Fuel')) }} • R {{ number_format((float) $voucher->amount, 2) }}</p>
                                        <p class="av-card-meta">Driver: {{ $driverName }} • Expires {{ optional($expiresAt)->format('d M Y') ?: '-' }}</p>
                                    </div>
                                    <svg width="28" viewBox="0 -960 960 960" height="28" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M226-160q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Zm254 0q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Zm254 0q-28 0-47-19t-19-47q0-28 19-47t47-19q28 0 47 19t19 47q0 28-19 47t-47 19Z"></path></svg>
                                </div>
                            </div>
                            <div class="av-card-image">
                                <div class="av-qr-card">
                                    <svg class="av-qr-stroke" viewBox="0 0 98 132" aria-hidden="true">
                                        <rect x="2.5" y="2.5" width="93" height="127" rx="12" ry="12"></rect>
                                    </svg>
                                    <div class="av-qr-inner">
                                        <span class="av-card-qr-loader" aria-hidden="true">
                                            <span class="av-card-qr-spinner"></span>
                                        </span>
                                        <img
                                            src="{{ $qrImage }}"
                                            alt="QR for voucher {{ $voucher->code }}"
                                            loading="lazy"
                                            onload="this.style.opacity='1'; this.previousElementSibling.style.display='none';"
                                            onerror="this.style.display='none'; this.previousElementSibling.style.display='none'; this.nextElementSibling.style.display='grid';"
                                        >
                                        <span class="av-card-qr-fallback" style="display:none;">QR</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                @else
                    <p class="text-sm text-slate-500">No approved vouchers available yet.</p>
                @endif
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="glass rounded-2xl p-6 lg:col-span-1">
                <h2 class="brand-font text-xl text-slate-900">Manual Redeem</h2>
                <p class="text-sm text-slate-600 mt-1">Paste QR payload, voucher code, QR code value, or voucher ID.</p>
                <div class="mt-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2">
                    <p class="text-[11px] uppercase tracking-wide text-blue-700 font-semibold">Station Wallet</p>
                    <p class="text-sm font-semibold text-blue-900" id="stationWalletBalanceValue">R {{ number_format((float) ($station->wallet_balance ?? 0), 2) }}</p>
                    <p class="text-[11px] text-blue-700/80 mt-0.5">Redemption will debit this balance.</p>
                </div>

                <form method="POST" action="{{ route('merchant.vouchers.redeem') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm text-slate-700 mb-1">Scan Input</label>
                        <input id="scanInputField" name="scan_input" required class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="e.g. VOUCHER-..., code, or JSON payload">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-700 mb-1">Pump Number (optional)</label>
                        <input id="pumpNumberField" name="pump_number" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Pump 3">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-700 mb-1">Transaction Reference (optional)</label>
                        <input id="transactionReferenceField" name="transaction_reference" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="POS-12345">
                    </div>
                    <button id="prefillLatestVoucherBtn" type="button" class="btn-ghost w-full rounded-xl py-2.5 text-sm font-semibold">Use Latest Voucher</button>
                    <p id="prefillLatestVoucherHint" class="text-xs text-slate-500">Autofill redeem fields from the latest approved voucher.</p>
                    <div id="redeemWalletWarning" class="hidden bw-error-alert bw-error-alert--inline" data-error-alert>
                        <button type="button" aria-label="close-error" class="bw-error-alert-close" data-alert-close>
                            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                        <p class="bw-error-alert-text">
                            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                            <span id="redeemWalletWarningText"></span>
                        </p>
                    </div>
                    <p id="redeemWalletOk" class="hidden rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"></p>
                    <button id="redeemSubmitBtn" class="btn-primary w-full rounded-xl py-2.5 text-sm font-semibold">Redeem Voucher</button>
                </form>
            </div>

            <div class="glass rounded-2xl p-0 overflow-hidden lg:col-span-2">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="brand-font text-xl text-slate-900">Live Voucher Feed</h2>
                    <p class="text-xs text-slate-500">Newest first</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Voucher </th>
                                <th class="px-4 py-3 text-left">Driver</th>
                                <th class="px-4 py-3 text-left">Amount</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Issued</th>
                                <th class="px-4 py-3 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody id="voucherFeedBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-8">
            @include('components.user-feedback-card')
        </div>
    @endif
</section>

<style>
    .merchant-brand-mark {
        width: 68px;
        height: 68px;
        flex: 0 0 68px;
        border-radius: 16px;
        border: 1px solid #dbe4ef;
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        display: grid;
        place-items: center;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    .merchant-brand-mark-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 8px;
    }

    .merchant-brand-mark-text {
        font-size: 1rem;
        font-weight: 800;
        color: #1e293b;
        letter-spacing: 0.12em;
    }

    .bw-error-alert {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding: 0.75rem 3rem 0.75rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid #f85149;
        color: #b22b2b;
        background: linear-gradient(#f851491a, #f851491a);
    }

    .bw-error-alert--inline {
        padding: 0.55rem 2.6rem 0.55rem 0.8rem;
    }

    .bw-error-alert.hidden {
        display: none !important;
    }

    .bw-error-alert svg {
        color: #b22b2b;
        flex: 0 0 auto;
    }

    .bw-error-alert-text {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .bw-error-alert--inline .bw-error-alert-text {
        font-size: 0.75rem;
    }

    .bw-error-alert-close {
        position: absolute;
        right: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        padding: 0.2rem;
        border-radius: 0.4rem;
        border: 1px solid #f85149;
        color: #f85149;
        opacity: 0.4;
        background: transparent;
        transition: opacity 0.2s ease;
    }

    .bw-error-alert-close:hover {
        opacity: 1;
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
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        opacity: 0.24;
        pointer-events: none;
        --s: 60px;
        --c1: #180a22;
        --c2: #5b42f3;
        --_g: radial-gradient(
            25% 25% at 25% 25%,
            var(--c1) 99%,
            rgba(0, 0, 0, 0) 101%
        );
        background: var(--_g) var(--s) var(--s) / calc(2 * var(--s))
                calc(2 * var(--s)),
            var(--_g) 0 0 / calc(2 * var(--s)) calc(2 * var(--s)),
            radial-gradient(50% 50%, var(--c2) 98%, rgba(0, 0, 0, 0)) 0 0 / var(--s)
                var(--s),
            repeating-conic-gradient(var(--c2) 0 50%, var(--c1) 0 100%)
                calc(0.5 * var(--s)) 0 / calc(2 * var(--s)) var(--s);
    }

    .bwiser-station-card {
        --station-border: #3b82f6;
        --station-g1: #1d4ed8;
        --station-g2: #0ea5e9;
        --station-g3: #38bdf8;
        width: 100%;
        min-height: 19rem;
        border: 1px solid color-mix(in srgb, var(--station-border) 45%, #dbeafe);
        border-radius: 1.2rem;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        background: #020617;
    }

    .bwiser-station-card::before {
        background: linear-gradient(
            130deg,
            transparent 0% 34%,
            var(--station-g1) 64%,
            var(--station-g2) 82%,
            var(--station-g3) 100%
        );
        background-position: 0% 0%;
        background-size: 280% 280%;
        content: "";
        inset: 0;
        pointer-events: none;
        position: absolute;
        transition: background-position 320ms ease, transform 320ms ease;
        z-index: 1;
    }

    .bwiser-station-card:hover::before {
        background-position: 100% 100%;
        transform: scale(1.06, 1.03);
    }

    .bwiser-station-card-content {
        background-image: radial-gradient(
            rgba(255, 255, 255, 0.18) 8%,
            transparent 8%
        );
        background-position: 0% 0%;
        background-size: 2.7rem 2.7rem;
        height: calc(100% - 3.3rem);
        padding: 1.6rem;
        position: relative;
        transition: background-position 320ms ease;
        width: calc(100% - 3.2rem);
        z-index: 2;
    }

    .bwiser-station-card:hover > .bwiser-station-card-content {
        background-position: -10% 0%;
    }

    .bwiser-station-eyebrow {
        margin: 0;
        color: #bfdbfe;
        text-transform: uppercase;
        letter-spacing: 0.17em;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .bwiser-station-title,
    .bwiser-station-subtitle {
        color: #f8fafc;
        margin: 0;
    }

    .bwiser-station-title {
        margin-top: 0.55rem;
        font-size: clamp(1.4rem, 2.3vw, 2rem);
        line-height: 1.1;
        font-family: "Space Grotesk", sans-serif;
        font-weight: 600;
    }

    .bwiser-station-subtitle {
        margin-top: 0.8rem;
        font-size: 0.95rem;
        color: #cbd5e1;
        line-height: 1.4;
        max-width: 34ch;
    }

    .bwiser-station-subtitle-word {
        display: inline-block;
        margin-right: 0.28rem;
        opacity: 0;
        transform: translateY(42%);
        transition: none;
    }

    .bwiser-station-card:hover .bwiser-station-subtitle-word {
        opacity: 1;
        transform: translateY(0%);
        transition: opacity 0ms, transform 200ms cubic-bezier(.90, .06, .15, .90);
    }

    .bwiser-station-meta {
        margin-top: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .bwiser-station-meta span {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
        border: 1px solid rgba(147, 197, 253, 0.35);
        background: rgba(15, 23, 42, 0.45);
        color: #dbeafe;
        font-size: 0.72rem;
    }

    .bwiser-station-icon {
        bottom: 0;
        right: 0;
        margin: 1rem;
        position: absolute;
        opacity: 0.78;
        transform: translateY(0);
        transition: opacity 220ms ease, transform 220ms ease;
        z-index: 2;
        pointer-events: none;
    }

    .bwiser-station-card:hover > .bwiser-station-icon {
        opacity: 1;
        transform: translateY(-3px);
    }

    .bwiser-approved-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }

    .av-card {
        width: 100%;
        min-height: 208px;
        background: linear-gradient(145deg, #d9f99d 0%, #fef08a 100%);
        color: #0f172a;
        position: relative;
        border-radius: 2rem;
        padding: 1.35rem;
        transition: transform 0.4s ease;
        border: 1px solid rgba(15, 23, 42, 0.12);
        box-shadow: 0 14px 30px -24px rgba(15, 23, 42, 0.45);
        overflow: hidden;
    }

    .av-card::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: radial-gradient(circle at 85% 30%, rgba(255,255,255,0.35), transparent 45%);
    }

    .av-card:hover {
        cursor: pointer;
        transform: scale(0.97);
    }

    .av-card:active {
        transform: scale(0.93);
    }

    .av-card .av-card-content {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 4.2rem;
        height: 100%;
        transition: transform 0.4s ease;
        position: relative;
        z-index: 2;
    }

    .av-card:hover .av-card-content {
        transform: scale(0.96);
    }

    .av-card .av-card-top,
    .av-card .av-card-bottom {
        display: flex;
        justify-content: space-between;
    }

    .av-card .av-card-top p,
    .av-card .av-card-top .av-card-title,
    .av-card .av-card-bottom p,
    .av-card .av-card-bottom .av-card-title {
        margin: 0;
    }

    .av-card .av-card-title {
        font-weight: 800;
    }

    .av-card .av-card-top p,
    .av-card .av-card-bottom p {
        font-weight: 700;
    }

    .av-card .av-card-bottom {
        align-items: flex-end;
        gap: 0.6rem;
    }

    .av-card .av-card-meta {
        margin-top: 0.3rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: #334155;
    }

    .av-card .av-card-image {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.7rem;
        pointer-events: none;
        z-index: 1;
    }

    .av-qr-card {
        width: 98px;
        height: 132px;
        background: #07182E;
        position: relative;
        display: flex;
        place-content: center;
        place-items: center;
        overflow: hidden;
        border-radius: 14px;
    }

    .av-qr-stroke {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 2;
        pointer-events: none;
    }

    .av-qr-stroke rect {
        fill: none;
        stroke: rgba(125, 211, 252, 0.95);
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 440;
        stroke-dashoffset: 440;
        animation: avQrStrokeDraw 2.4s ease-in-out infinite;
    }

    .av-qr-card::before {
        content: "";
        position: absolute;
        width: 72px;
        background-image: linear-gradient(180deg, rgb(0, 183, 255), rgb(255, 48, 255));
        height: 140%;
        animation: avRotBGimg 3s linear infinite;
        transition: all 0.2s linear;
    }

    .av-qr-card::after {
        content: "";
        position: absolute;
        background: #07182E;
        inset: 4px;
        border-radius: 11px;
    }

    .av-qr-inner {
        position: relative;
        z-index: 3;
        width: calc(100% - 16px);
        height: calc(100% - 16px);
        border-radius: 10px;
        background: #ffffff;
        display: grid;
        place-items: center;
        overflow: hidden;
    }

    .av-card .av-qr-inner img,
    .av-card .av-card-qr-fallback,
    .av-card .av-card-qr-loader {
        width: 100%;
        height: 100%;
        transition: transform 0.4s ease;
    }

    .av-card .av-qr-inner img {
        object-fit: contain;
        padding: 0.2rem;
        opacity: 0;
        transition: transform 0.4s ease, opacity 0.2s ease;
    }

    .av-card .av-card-qr-fallback {
        display: none;
        place-items: center;
        font-size: 0.72rem;
        font-weight: 800;
        color: #334155;
    }

    .av-card .av-card-qr-loader {
        display: grid;
        place-items: center;
        position: absolute;
        inset: 0;
        background: #ffffff;
        z-index: 2;
    }

    .av-card .av-card-qr-spinner {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 2px solid rgba(30, 41, 59, 0.2);
        border-top-color: #0ea5e9;
        animation: avQrSpin 0.8s linear infinite;
    }

    .av-card:hover .av-qr-inner img {
        transform: scale(1.05);
    }

    .av-card .av-card-bottom svg {
        fill: #1e293b;
        flex: 0 0 auto;
    }

    @media (max-width: 420px) {
        .av-card {
            min-height: 198px;
            padding: 1.1rem;
        }

        .av-card .av-card-content {
            gap: 3.8rem;
        }

        .av-qr-card {
            width: 88px;
            height: 120px;
        }
    }

    @keyframes avRotBGimg {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes avQrStrokeDraw {
        0% {
            stroke-dashoffset: 440;
            opacity: 0.25;
        }
        45% {
            stroke-dashoffset: 0;
            opacity: 1;
        }
        100% {
            stroke-dashoffset: 0;
            opacity: 0.45;
        }
    }

    @keyframes avQrSpin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

</style>

@if($station)
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
    (function initStationSubtitle() {
        const subtitle = document.getElementById('bwiserStationSubtitle');
        if (!subtitle || subtitle.dataset.init === '1') return;

        const text = subtitle.dataset.text || '';
        subtitle.innerHTML = '';

        text.split(' ').forEach((wordText, index) => {
            const word = document.createElement('span');
            word.textContent = `${wordText} `;
            word.classList.add('bwiser-station-subtitle-word');
            word.style.transitionDelay = `${index * 35}ms`;
            subtitle.appendChild(word);
        });

        subtitle.dataset.init = '1';
    })();

    const stationId = {{ (int) $station->id }};
    const streamUrl = @json(route('merchant.vouchers.stream'));
    const redeemUrl = @json(route('merchant.vouchers.redeem'));
    const wsConfig = @json($wsConfig);
    let feed = @json($initialVouchers);

    const feedBody = document.getElementById('voucherFeedBody');
    const issuedCount = document.getElementById('issuedCount');
    const approvedCount = document.getElementById('approvedCount');
    const redeemedCount = document.getElementById('redeemedCount');
    const wsStatus = document.getElementById('wsStatus');
    const prefillLatestVoucherBtn = document.getElementById('prefillLatestVoucherBtn');
    const prefillLatestVoucherHint = document.getElementById('prefillLatestVoucherHint');
    const scanInputField = document.getElementById('scanInputField');
    const pumpNumberField = document.getElementById('pumpNumberField');
    const transactionReferenceField = document.getElementById('transactionReferenceField');
    const redeemWalletWarning = document.getElementById('redeemWalletWarning');
    const redeemWalletWarningText = document.getElementById('redeemWalletWarningText');
    const redeemWalletOk = document.getElementById('redeemWalletOk');
    const redeemSubmitBtn = document.getElementById('redeemSubmitBtn');
    const stationWalletBalance = Number(@json((float) ($station->wallet_balance ?? 0)));

    function applySummary(summary) {
        if (!summary) return;
        if (typeof summary.issued !== 'undefined') issuedCount.textContent = summary.issued;
        if (typeof summary.approved !== 'undefined') approvedCount.textContent = summary.approved;
        if (typeof summary.redeemed !== 'undefined') redeemedCount.textContent = summary.redeemed;
    }

    function applySummaryFromFeed() {
        applySummary({
            issued: feed.filter(v => v.status === 'issued').length,
            approved: feed.filter(v => v.status === 'approved').length,
            redeemed: feed.filter(v => v.status === 'redeemed').length,
        });
    }

    function statusClass(status) {
        if (status === 'approved') return 'bg-blue-100 text-blue-700';
        if (status === 'redeemed') return 'bg-emerald-100 text-emerald-700';
        if (status === 'issued') return 'bg-amber-100 text-amber-700';
        return 'bg-slate-100 text-slate-700';
    }

    function formatDate(isoDate) {
        if (!isoDate) return '-';
        const d = new Date(isoDate);
        if (Number.isNaN(d.getTime())) return '-';
        return d.toLocaleString();
    }

    function renderFeed() {
        feedBody.innerHTML = feed.map(v => {
            const isApproved = String(v.status || '').toLowerCase() === 'approved';
            const actionTitle = isApproved
                ? 'Redeem voucher'
                : `Redeem unavailable: status is ${String(v.status || 'unknown')}`;
            const action = `<form method="POST" action="${redeemUrl}" class="inline-flex">\
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.content || ''}">\
                    <input type="hidden" name="scan_input" value="${v.voucher_code}">\
                    <button ${isApproved ? '' : 'disabled'} title="${actionTitle}" class="px-3 py-1.5 rounded-lg text-xs font-semibold ${isApproved ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed'}">Redeem</button>\
               </form>`;

            return `
                <tr>
                    <td class="px-4 py-3 font-semibold text-slate-900">${v.voucher_code}</td>
                    <td class="px-4 py-3 text-slate-700">${v.driver?.name || 'Unknown'}${v.driver?.phone ? ` <span class="text-xs text-slate-400">(${v.driver.phone})</span>` : ''}</td>
                    <td class="px-4 py-3 text-slate-700">R ${Number(v.amount || 0).toFixed(2)}</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-semibold uppercase ${statusClass(v.status)}">${v.status}</span></td>
                    <td class="px-4 py-3 text-slate-600">${formatDate(v.issued_at)}</td>
                    <td class="px-4 py-3">${action}</td>
                </tr>
            `;
        }).join('');
    }

    function upsertVoucher(voucher) {
        if (!voucher || !voucher.voucher_id) return;
        feed = [voucher, ...feed.filter(v => v.voucher_id !== voucher.voucher_id)].slice(0, 60);
        applySummaryFromFeed();
        renderFeed();
        updateRedeemWalletState();
    }

    async function fetchStream() {
        try {
            const response = await fetch(streamUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const data = await response.json();
            if (Array.isArray(data.items)) {
                feed = data.items;
                applySummary(data.summary);
                renderFeed();
            }
        } catch (error) {
            console.error(error);
        }
    }

    function setWsStatus(text, classes) {
        wsStatus.textContent = text;
        wsStatus.className = `inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold ${classes}`;
    }

    function latestVoucherForPrefill() {
        if (!Array.isArray(feed) || feed.length === 0) return null;
        return feed.find((v) => v.status === 'approved') || feed[0];
    }

    function applyLatestVoucherPrefill() {
        const latest = latestVoucherForPrefill();
        if (!latest || !scanInputField) {
            if (prefillLatestVoucherHint) prefillLatestVoucherHint.textContent = 'No voucher available to prefill yet.';
            return;
        }

        scanInputField.value = latest.qr_code || latest.voucher_code || String(latest.voucher_id || '');
        if (pumpNumberField) pumpNumberField.value = latest.pump_number || '';
        if (transactionReferenceField) transactionReferenceField.value = latest.transaction_reference || '';

        if (prefillLatestVoucherHint) {
            const code = latest.voucher_code || '#';
            const status = String(latest.status || '').toUpperCase();
            prefillLatestVoucherHint.textContent = `Loaded ${code} (${status}) into the form.`;
        }

        updateRedeemWalletState();
    }

    function parseScanInput(rawValue) {
        const scanInput = String(rawValue || '').trim();
        if (!scanInput) {
            return { voucherId: null, voucherCode: null, voucherQr: null };
        }

        if (scanInput.startsWith('{') && scanInput.endsWith('}')) {
            try {
                const decoded = JSON.parse(scanInput);
                return {
                    voucherId: decoded?.voucher_id ? Number(decoded.voucher_id) : null,
                    voucherCode: decoded?.code ? String(decoded.code) : null,
                    voucherQr: decoded?.qr_code ? String(decoded.qr_code) : null,
                };
            } catch (e) {
                return { voucherId: null, voucherCode: scanInput, voucherQr: scanInput };
            }
        }

        return {
            voucherId: /^\d+$/.test(scanInput) ? Number(scanInput) : null,
            voucherCode: scanInput,
            voucherQr: scanInput,
        };
    }

    function resolveVoucherFromInput(rawValue) {
        const parsed = parseScanInput(rawValue);
        return feed.find((item) => {
            if (!item) return false;
            if (parsed.voucherId && Number(item.voucher_id) === parsed.voucherId) return true;
            if (parsed.voucherCode && String(item.voucher_code || '').trim() === parsed.voucherCode) return true;
            if (parsed.voucherQr && String(item.qr_code || '').trim() === parsed.voucherQr) return true;
            return false;
        }) || null;
    }

    function setRedeemButtonDisabled(disabled) {
        if (!redeemSubmitBtn) return;
        redeemSubmitBtn.disabled = !!disabled;
        redeemSubmitBtn.classList.toggle('opacity-60', !!disabled);
        redeemSubmitBtn.classList.toggle('cursor-not-allowed', !!disabled);
    }

    function setRedeemWarning(message) {
        if (!redeemWalletWarning) return;
        const text = String(message || '').trim();
        if (redeemWalletWarningText) {
            redeemWalletWarningText.textContent = text;
        }
        if (text === '') {
            redeemWalletWarning.classList.add('hidden');
            redeemWalletWarning.setAttribute('aria-hidden', 'true');
            return;
        }

        redeemWalletWarning.classList.remove('hidden');
        redeemWalletWarning.removeAttribute('aria-hidden');
    }

    function updateRedeemWalletState() {
        if (!scanInputField || !redeemWalletWarning) return;
        const voucher = resolveVoucherFromInput(scanInputField.value);

        if (!voucher || String(voucher.status || '').toLowerCase() !== 'approved') {
            setRedeemWarning('');
            if (redeemWalletOk) {
                redeemWalletOk.classList.add('hidden');
                redeemWalletOk.textContent = '';
            }
            setRedeemButtonDisabled(false);
            return;
        }

        const voucherAmount = Number(voucher.amount || 0);
        if (voucherAmount > stationWalletBalance) {
            setRedeemWarning(`Insufficient station wallet balance. Required: R ${voucherAmount.toFixed(2)} | Available: R ${stationWalletBalance.toFixed(2)}.`);
            if (redeemWalletOk) {
                redeemWalletOk.classList.add('hidden');
                redeemWalletOk.textContent = '';
            }
            setRedeemButtonDisabled(true);
            return;
        }

        setRedeemWarning('');
        if (redeemWalletOk) {
            const remaining = Math.max(0, stationWalletBalance - voucherAmount);
            redeemWalletOk.textContent = `Sufficient balance. Voucher: R ${voucherAmount.toFixed(2)} | Remaining after redeem: R ${remaining.toFixed(2)}.`;
            redeemWalletOk.classList.remove('hidden');
        }
        setRedeemButtonDisabled(false);
    }

    renderFeed();
    updateRedeemWalletState();

    if (prefillLatestVoucherBtn) {
        prefillLatestVoucherBtn.addEventListener('click', applyLatestVoucherPrefill);
    }

    if (scanInputField) {
        scanInputField.addEventListener('input', updateRedeemWalletState);
    }

    setRedeemWarning('');

    document.querySelectorAll('[data-alert-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const box = btn.closest('[data-error-alert]');
            if (box) box.classList.add('hidden');
        });
    });

    setInterval(fetchStream, 7000);

    if (wsConfig.appKey) {
        const useTLS = wsConfig.scheme === 'https';
        const pusher = new Pusher(wsConfig.appKey, {
            wsHost: wsConfig.host,
            wsPort: Number(wsConfig.port),
            wssPort: Number(wsConfig.port),
            forceTLS: useTLS,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            authEndpoint: wsConfig.authEndpoint,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            },
        });

        pusher.connection.bind('connected', () => setWsStatus('Realtime: connected', 'bg-emerald-100 text-emerald-700'));
        pusher.connection.bind('error', () => setWsStatus('Realtime: error', 'bg-rose-100 text-rose-700'));
        pusher.connection.bind('unavailable', () => setWsStatus('Realtime: unavailable', 'bg-amber-100 text-amber-700'));

        const channel = pusher.subscribe(`private-merchant.station.${stationId}`);
        channel.bind('pusher:subscription_succeeded', () => setWsStatus('Realtime: subscribed', 'bg-emerald-100 text-emerald-700'));
        channel.bind('voucher.status.changed', (payload) => upsertVoucher(payload));
    } else {
        setWsStatus('Realtime: polling mode', 'bg-amber-100 text-amber-700');
    }
</script>
@endif
@endsection
