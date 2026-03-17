@extends('Layouts.app')

@section('title', 'Repayments - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Repayments</h1>
            <p class="text-slate-600 mt-2">Track dues and pay outstanding amounts via Paystack.</p>
        </div>
        <a href="{{ route('driver.dashboard') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Dashboard</a>
    </div>
    @include('driver.partials.nav', ['backUrl' => route('driver.dashboard')])

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

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Pending Items</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">{{ $summary['pending_count'] }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Outstanding Amount</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">R {{ number_format((float) $summary['pending_amount'], 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Paid This Month</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">R {{ number_format((float) $summary['paid_this_month'], 2) }}</p>
        </div>
    </div>

    @if(!empty($overdueSeconds) && (int) $overdueSeconds > 0)
        <div class="mt-6 glass rounded-2xl p-6 overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-rose-600">Overdue</p>
                    <h2 class="brand-font text-xl text-slate-900 mt-1">Most overdue repayment</h2>
                    <p class="text-sm text-slate-600 mt-1">
                        @if(!empty($mostOverdue))
                            Due {{ \Illuminate\Support\Carbon::parse($mostOverdue->due_date)->format('d M Y') }}
                            • R {{ number_format((float) $mostOverdue->amount, 2) }}
                        @endif
                    </p>
                </div>

                <div class="overdue-clock-wrap">
                    <div class="overdue-clock" data-overdue-seconds="{{ (int) $overdueSeconds }}" aria-label="00:00:00:00">
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
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">New Payment Method</p>
                    <h2 class="brand-font text-lg text-slate-900 mt-1">1Voucher Weekly Repayment Pay</h2>
                    <p class="text-sm text-slate-600 mt-1">Pay all repayments due in the next 7 days using a prepaid 1Voucher PIN.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('driver.repayments.1voucher.week') }}" class="w-full md:w-auto">
                @csrf
                <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                    <input
                        type="password"
                        name="pin"
                        required
                        autocomplete="off"
                        inputmode="numeric"
                        placeholder="Enter 1Voucher PIN"
                        class="w-full sm:w-56 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400"
                    >
                    <input type="hidden" name="days" value="7">
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800">
                        Pay Week
                    </button>
                </div>
                <p class="text-[11px] text-slate-500 mt-2">Your PIN is never stored. A new PIN will be issued after payment.</p>
            </form>
        </div>
    </div>

    <div class="mt-6 glass rounded-2xl p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="brand-font text-xl text-slate-900">Daily Auto-Pay (24h)</h2>
                <p class="text-sm text-slate-600 mt-1">Charge due repayments every 24 hours using your saved Paystack authorization.</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full font-semibold {{ ($autopay['enabled'] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                {{ strtoupper((string) ($autopay['status'] ?? 'inactive')) }}
            </span>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <p class="text-slate-500">Gateway</p>
                <p class="mt-1 font-semibold text-slate-800">{{ strtoupper((string) ($autopay['gateway'] ?: 'paystack')) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <p class="text-slate-500">Authorization</p>
                <p class="mt-1 font-semibold {{ ($autopay['has_token'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ ($autopay['has_token'] ?? false) ? 'Saved' : 'Not Captured Yet' }}
                </p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <p class="text-slate-500">Next Attempt</p>
                <p class="mt-1 font-semibold text-slate-800">{{ optional($autopay['next_attempt_at'] ?? null)->format('d M Y H:i') ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <p class="text-slate-500">Failures</p>
                <p class="mt-1 font-semibold text-slate-800">{{ (int) ($autopay['failures'] ?? 0) }}</p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-4 items-start">
            <div class="pay-card-wrap" aria-label="Saved repayment card">
                <div class="pay-card">
                    <div class="pay-card__info">
                        <div class="pay-card__logo">{{ strtoupper((string) ($autopay['card']['brand'] ?? 'Card')) }}</div>
                        <div class="pay-card__chip">
                            <svg class="pay-card__chip-lines" role="img" width="20" height="20" viewBox="0 0 100 100" aria-label="Chip">
                                <g opacity="0.8">
                                    <polyline points="0,50 35,50" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="0,20 20,20 35,35" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="50,0 50,35" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="65,35 80,20 100,20" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="100,50 65,50" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="35,35 65,35 65,65 35,65 35,35" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="0,80 20,80 35,65" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="50,100 50,65" fill="none" stroke="#000" stroke-width="2"></polyline>
                                    <polyline points="65,65 80,80 100,80" fill="none" stroke="#000" stroke-width="2"></polyline>
                                </g>
                            </svg>
                            <div class="pay-card__chip-texture"></div>
                        </div>
                        <div class="pay-card__type">autopay</div>
                        <div class="pay-card__number">
                            <span class="pay-card__digit-group">••••</span>
                            <span class="pay-card__digit-group">••••</span>
                            <span class="pay-card__digit-group">••••</span>
                            <span class="pay-card__digit-group">{{ ($autopay['card']['last4'] ?? '') !== '' ? ($autopay['card']['last4']) : '----' }}</span>
                        </div>
                        <div class="pay-card__valid-thru" aria-label="Valid thru">Valid<br>thru</div>
                        <div class="pay-card__exp-date">
                            <time datetime="2038-01">{{ (string) ($autopay['card']['expiry'] ?? 'N/A') }}</time>
                        </div>
                        <div class="pay-card__name">{{ strtoupper((string) ($autopay['card']['holder'] ?? 'Card Holder')) }}</div>
                        @php
                            $cardBrand = strtolower((string) ($autopay['card']['brand'] ?? ''));
                        @endphp
                        @if(str_contains($cardBrand, 'visa'))
                            <div class="pay-card__vendor pay-card__vendor--visa" role="img" aria-label="Visa">
                                <span class="pay-card__vendor-visa-text">VISA</span>
                            </div>
                        @elseif(str_contains($cardBrand, 'master'))
                            <div class="pay-card__vendor pay-card__vendor--mastercard" role="img" aria-label="Mastercard">
                                <span class="pay-card__vendor-sr">Mastercard</span>
                            </div>
                        @else
                            <div class="pay-card__vendor pay-card__vendor--generic" role="img" aria-label="Card">
                                <span class="pay-card__vendor-generic-text">{{ strtoupper((string) ($autopay['card']['brand'] ?? 'CARD')) }}</span>
                            </div>
                        @endif
                        <div class="pay-card__texture"></div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600 max-w-sm">
                <p class="font-semibold text-slate-800">Saved Card</p>
                <p class="mt-1">
                    {{ ($autopay['card']['is_saved'] ?? false) ? ('Using ' . ($autopay['card']['brand'] ?? 'Card') . ' ending in ' . ($autopay['card']['last4'] ?? '')) : 'No saved card yet. Make one successful card repayment first.' }}
                </p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('driver.repayments.autopay.toggle') }}">
                @csrf
                <input type="hidden" name="enabled" value="{{ ($autopay['enabled'] ?? false) ? 0 : 1 }}">
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold {{ ($autopay['enabled'] ?? false) ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                    {{ ($autopay['enabled'] ?? false) ? 'Disable Auto-Pay' : 'Enable Auto-Pay' }}
                </button>
            </form>
            @if(!($autopay['has_token'] ?? false))
                <p class="text-xs text-amber-700">Complete one successful Paystack card repayment to capture authorization token first.</p>
            @endif
        </div>
    </div>

    <div class="mt-6 glass rounded-2xl p-6">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="brand-font text-xl text-slate-900">Ethereum Repayments</h2>
                <p class="text-sm text-slate-600">Crypto repayment rail preview. Backend settlement logic will be enabled next.</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700 font-semibold">Coming Soon</span>
        </div>

        <div class="flex flex-col md:flex-row gap-5 items-start">
            <div class="ethpay-card" aria-hidden="true">
                <svg class="ethpay-img" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="100%" height="100%" viewBox="0 0 784.37 1277.39">
                    <g>
                        <polygon fill="#343434" fill-rule="nonzero" points="392.07,0 383.5,29.11 383.5,873.74 392.07,882.29 784.13,650.54"></polygon>
                        <polygon fill="#8C8C8C" fill-rule="nonzero" points="392.07,0 -0,650.54 392.07,882.29 392.07,472.33"></polygon>
                        <polygon fill="#3C3C3B" fill-rule="nonzero" points="392.07,956.52 387.24,962.41 387.24,1263.28 392.07,1277.38 784.37,724.89"></polygon>
                        <polygon fill="#8C8C8C" fill-rule="nonzero" points="392.07,1277.38 392.07,956.52 -0,724.89"></polygon>
                        <polygon fill="#141414" fill-rule="nonzero" points="392.07,882.29 784.13,650.54 392.07,472.33"></polygon>
                        <polygon fill="#393939" fill-rule="nonzero" points="0,650.54 392.07,882.29 392.07,472.33"></polygon>
                    </g>
                </svg>
                <div class="ethpay-text-box">
                    <p class="ethpay-text ethpay-head">Ethereum</p>
                    <span>Repayment Rail</span>
                    <p class="ethpay-text ethpay-price">Outstanding: R {{ number_format((float) $summary['pending_amount'], 2) }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 w-full md:max-w-md">
                <p class="text-sm text-slate-700">
                    When enabled, drivers will be able to settle repayment amounts via ETH with wallet signature and on-chain confirmation.
                </p>
                <ul class="mt-3 space-y-2 text-xs text-slate-500">
                    <li>Network: Ethereum Mainnet (planned)</li>
                    <li>Settlement conversion: ETH -> ZAR (planned)</li>
                    <li>Webhook reconciliation and receipt posting (planned)</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="glass rounded-2xl mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Due Date</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Lease</th>
                        <th class="px-4 py-3 text-left">Station</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($repayments as $repayment)
                        @php
                            $stationName = optional(optional($repayment->lease)->vouchers->first())->fuelStation->name ?? 'N/A';
                            $voucherCode = optional(optional($repayment->lease)->vouchers->sortByDesc('id')->first())->code;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">R {{ number_format((float) $repayment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-slate-700">#{{ $repayment->lease_id }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $stationName }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 uppercase">{{ $repayment->status }}</span>
                                <p class="text-[11px] text-slate-500 mt-1">Voucher: {{ $voucherCode ?: 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if(in_array($repayment->status, ['pending', 'overdue'], true))
                                    @php
                                        $requestUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                                            'driver.repayments.request.show',
                                            now()->addDays(7),
                                            ['repayment' => $repayment->id]
                                        );
                                        $shareText = sprintf(
                                            'Please help me settle my Bwiser repayment of R %s for voucher %s due on %s.',
                                            number_format((float) $repayment->amount, 2),
                                            $voucherCode ?: ('#' . (string) $repayment->id),
                                            \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y')
                                        );
                                        $shareTextWithUrl = $shareText . ' ' . $requestUrl;
                                    @endphp
	                                    <div class="flex flex-wrap gap-2">
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
	                                        <a
	                                            href="{{ route('driver.repayments.payshap.show', $repayment) }}"
	                                            class="payshap-chip"
	                                            aria-label="Pay via PayShap"
	                                            title="Pay via PayShap"
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
	                                        <button type="button" class="px-3 py-2 rounded-lg text-xs font-semibold bg-violet-100 text-violet-700 border border-violet-200 cursor-not-allowed" title="Ethereum repayments coming soon" disabled>
	                                            <i class="fab fa-ethereum mr-1"></i> ETH (Soon)
	                                        </button>
	                                    </div>
                                    <div class="mt-2">
                                        <a href="https://wa.me/?text={{ urlencode($shareTextWithUrl) }}" target="_blank" rel="noopener" class="pay-for-me-btn">
                                            <span class="pay-for-me-btn-label">Pay for me</span>
                                            <span class="pay-for-me-btn-sub">Share on WhatsApp</span>
                                        </a>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-2">Voucher {{ $voucherCode ?: 'N/A' }} • Manual buttons perform a one-time override only. Auto-pay remains enabled for upcoming repayments.</p>
                                @elseif(!in_array($repayment->status, ['pending', 'overdue'], true))
                                    <span class="text-xs text-emerald-600 font-medium">Paid</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No repayment records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-4 bg-white border-t border-slate-100">
            {{ $repayments->links() }}
        </div>
    </div>

    <div class="glass rounded-2xl mt-6 p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="brand-font text-xl text-slate-900">Auto-Pay History</h2>
            <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold">Latest 12</span>
        </div>
        <div class="mt-4 space-y-2">
            @forelse(($autopayEvents ?? collect()) as $event)
                @php
                    $tone = str_contains((string) $event->action, 'failed') || str_contains((string) $event->action, 'disabled')
                        ? 'border-rose-200 bg-rose-50 text-rose-800'
                        : (str_contains((string) $event->action, 'succeeded') || str_contains((string) $event->action, 'verified')
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                            : 'border-slate-200 bg-slate-50 text-slate-700');
                @endphp
                <div class="rounded-xl border px-3 py-2 {{ $tone }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase">{{ str_replace('_', ' ', $event->action) }}</p>
                        <p class="text-[11px] opacity-80">{{ $event->created_at?->format('d M Y H:i') }}</p>
                    </div>
                    @if($event->description)
                        <p class="text-xs mt-1">{{ $event->description }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No auto-pay events yet.</p>
            @endforelse
        </div>
    </div>
</section>

<style>
    .overdue-clock-wrap {
        display: flex;
        justify-content: flex-end;
    }

    .overdue-clock {
        display: flex;
        flex-direction: column;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .overdue-clock .clock__block {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 0.9rem;
        box-shadow: 0 18px 40px -30px rgba(15, 23, 42, 0.35);
        font-size: 2.2rem;
        line-height: 1.6;
        overflow: hidden;
        text-align: center;
        width: 6.5rem;
        height: 6.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .overdue-clock .clock__digit-group {
        display: flex;
        flex-direction: column-reverse;
        width: 100%;
        height: 3.5rem;
    }

    .overdue-clock .clock__digits {
        width: 100%;
        height: 100%;
        font-weight: 800;
        color: #0f172a;
    }

    .overdue-clock .clock__label {
        margin-top: 0.15rem;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #64748b;
    }

    .overdue-clock .clock__colon {
        display: none;
        font-size: 2em;
        opacity: 0.4;
        position: relative;
        width: 0.75rem;
        height: 6.5rem;
        color: #0f172a;
    }

    .overdue-clock .clock__colon:before,
    .overdue-clock .clock__colon:after {
        background-color: currentColor;
        border-radius: 50%;
        content: "";
        display: block;
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0.35rem;
        height: 0.35rem;
        transform: translate(-50%, -50%);
        opacity: 0.35;
    }

    .overdue-clock .clock__colon:before {
        margin-top: -0.9rem;
    }

    .overdue-clock .clock__colon:after {
        margin-top: 0.9rem;
    }

    .overdue-clock .clock__block--bounce {
        animation: overdue-bounce 0.75s;
    }

    .overdue-clock .clock__block--bounce .clock__digit-group {
        animation: overdue-roll 0.75s ease-in-out forwards;
        transform: translateY(-50%);
    }

    .overdue-clock .clock__block--delay1,
    .overdue-clock .clock__block--delay1 .clock__digit-group {
        animation-delay: 0.1s;
    }

    .overdue-clock .clock__block--delay2,
    .overdue-clock .clock__block--delay2 .clock__digit-group {
        animation-delay: 0.2s;
    }

    @media (min-width: 768px) {
        .overdue-clock {
            flex-direction: row;
            align-items: stretch;
        }

        .overdue-clock .clock__colon {
            display: block;
        }
    }

    @keyframes overdue-bounce {
        from,
        to {
            animation-timing-function: ease-in;
            transform: translateY(0);
        }
        50% {
            animation-timing-function: ease-out;
            transform: translateY(10%);
        }
    }

    @keyframes overdue-roll {
        from {
            transform: translateY(-50%);
        }
        to {
            transform: translateY(0);
        }
    }

    .ethpay-card {
        width: 195px;
        height: 285px;
        background: #313131;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #fff;
        transition: 0.2s ease-in-out;
        position: relative;
        overflow: hidden;
    }

    .ethpay-img {
        height: 30%;
        position: absolute;
        transition: 0.2s ease-in-out;
        z-index: 1;
    }

    .ethpay-text-box {
        opacity: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 15px;
        transition: 0.2s ease-in-out;
        z-index: 2;
        text-align: center;
        padding: 0 0.5rem;
    }

    .ethpay-text-box > .ethpay-text {
        font-weight: bold;
    }

    .ethpay-text-box > .ethpay-head {
        font-size: 20px;
    }

    .ethpay-text-box > .ethpay-price {
        font-size: 13px;
    }

    .ethpay-text-box > span {
        font-size: 12px;
        color: lightgrey;
    }

    .ethpay-card:hover > .ethpay-text-box {
        opacity: 1;
    }

    .ethpay-card:hover > .ethpay-img {
        height: 65%;
        filter: blur(7px);
        animation: ethpayAnim 3s infinite;
    }

    .ethpay-card:hover {
        transform: scale(1.04) rotate(-1deg);
    }

    .pay-for-me-btn {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 0.1rem;
        padding: 0.55rem 0.85rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(16, 185, 129, 0.45);
        background: linear-gradient(130deg, #10b981 0%, #22c55e 55%, #34d399 100%);
        color: #ffffff;
        text-decoration: none;
        box-shadow: 0 10px 22px -14px rgba(5, 150, 105, 0.9);
        transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
    }

    .pay-for-me-btn:hover {
        transform: translateY(-1px);
        filter: saturate(1.05);
        box-shadow: 0 14px 28px -14px rgba(5, 150, 105, 0.9);
    }

    .pay-for-me-btn-label {
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .pay-for-me-btn-sub {
        font-size: 0.65rem;
        opacity: 0.92;
        line-height: 1;
        letter-spacing: 0.04em;
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

    .payshap-chip {
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
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        text-decoration: none;
    }

    .payshap-chip:hover {
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

    .pay-card,
    .pay-card__chip {
        overflow: hidden;
        position: relative;
    }

    .pay-card,
    .pay-card__chip-texture,
    .pay-card__texture {
        animation-duration: 3s;
        animation-timing-function: ease-in-out;
        animation-iteration-count: infinite;
    }

    .pay-card-wrap {
        perspective: 700px;
    }

    .pay-card {
        --pay-primary: #1d4ed8;
        animation-name: payCardRotate;
        background-color: var(--pay-primary);
        background-image:
            radial-gradient(circle at 100% 0%, hsla(0,0%,100%,0.1) 29.5%, hsla(0,0%,100%,0) 30%),
            radial-gradient(circle at 100% 0%, hsla(0,0%,100%,0.1) 39.5%, hsla(0,0%,100%,0) 40%),
            radial-gradient(circle at 100% 0%, hsla(0,0%,100%,0.1) 49.5%, hsla(0,0%,100%,0) 50%),
            linear-gradient(130deg, #1d4ed8 0%, #0ea5e9 100%);
        border-radius: 0.9rem;
        box-shadow: 0 18px 34px -20px rgba(2, 6, 23, 0.55), -0.2rem 0 0.75rem 0 rgba(2, 6, 23, 0.25);
        color: #fff;
        width: 18rem;
        height: 11.2rem;
        transform: translate3d(0, 0, 0);
    }

    .pay-card__info,
    .pay-card__chip-texture,
    .pay-card__texture {
        position: absolute;
    }

    .pay-card__chip-texture,
    .pay-card__texture {
        animation-name: payCardTexture;
        top: 0;
        left: 0;
        width: 200%;
        height: 100%;
    }

    .pay-card__info {
        font: 0.8rem/1 "DM Sans", sans-serif;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        padding: 0.95rem;
        inset: 0;
    }

    .pay-card__logo,
    .pay-card__number {
        width: 100%;
    }

    .pay-card__logo {
        font-weight: 700;
        letter-spacing: 0.06em;
    }

    .pay-card__chip {
        background-image: linear-gradient(hsl(0,0%,70%), hsl(0,0%,80%));
        border-radius: 0.25rem;
        box-shadow: 0 0 0 0.05rem hsla(0,0%,0%,0.45) inset;
        width: 1.5rem;
        height: 1.5rem;
    }

    .pay-card__chip-lines {
        width: 100%;
        height: auto;
    }

    .pay-card__chip-texture {
        background-image: linear-gradient(-80deg, hsla(0,0%,100%,0), hsla(0,0%,100%,0.55) 48% 52%, hsla(0,0%,100%,0));
    }

    .pay-card__type {
        align-self: flex-end;
        margin-left: auto;
        text-transform: uppercase;
        font-size: 0.62rem;
        letter-spacing: 0.08em;
    }

    .pay-card__digit-group,
    .pay-card__exp-date,
    .pay-card__name {
        background: linear-gradient(hsl(0,0%,100%), hsl(0,0%,85%) 15% 55%, hsl(0,0%,70%) 70%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-family: "Courier Prime", monospace;
        filter: drop-shadow(0 0.05rem hsla(0,0%,0%,0.3));
    }

    .pay-card__number {
        font-size: 0.92rem;
        display: flex;
        justify-content: space-between;
        margin-top: 0.2rem;
    }

    .pay-card__valid-thru,
    .pay-card__name {
        text-transform: uppercase;
    }

    .pay-card__valid-thru,
    .pay-card__exp-date {
        margin-bottom: 0.2rem;
        width: 50%;
    }

    .pay-card__valid-thru {
        font-size: 0.38rem;
        padding-right: 0.25rem;
        text-align: right;
    }

    .pay-card__exp-date,
    .pay-card__name {
        font-size: 0.66rem;
    }

    .pay-card__exp-date {
        padding-left: 0.25rem;
    }

    .pay-card__name {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        width: 11rem;
    }

    .pay-card__vendor,
    .pay-card__vendor:before,
    .pay-card__vendor:after {
        position: absolute;
    }

    .pay-card__vendor {
        right: 0.6rem;
        bottom: 0.6rem;
        width: 2.9rem;
        height: 1.6rem;
    }

    .pay-card__vendor--mastercard:before,
    .pay-card__vendor--mastercard:after {
        border-radius: 50%;
        content: "";
        display: block;
        top: 0;
        width: 1.6rem;
        height: 1.6rem;
    }

    .pay-card__vendor--mastercard:before {
        background-color: #e71d1a;
        left: 0;
    }

    .pay-card__vendor--mastercard:after {
        background-color: #fa5e03;
        box-shadow: -1.1rem 0 0 #f59d1a inset;
        right: 0;
    }

    .pay-card__vendor--visa {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .pay-card__vendor-visa-text {
        font-family: "DM Sans", sans-serif;
        font-weight: 800;
        font-size: 1.05rem;
        letter-spacing: 0.03em;
        color: #f8fafc;
        text-shadow: 0 1px 2px rgba(2, 6, 23, 0.45);
    }

    .pay-card__vendor--generic {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .pay-card__vendor-generic-text {
        font-family: "DM Sans", sans-serif;
        font-weight: 700;
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        color: #f8fafc;
        text-shadow: 0 1px 2px rgba(2, 6, 23, 0.45);
    }

    .pay-card__vendor-sr {
        clip: rect(1px, 1px, 1px, 1px);
        overflow: hidden;
        position: absolute;
        width: 1px;
        height: 1px;
    }

    .pay-card__texture {
        animation-name: payCardTexture;
        background-image: linear-gradient(-80deg, hsla(0,0%,100%,0.25) 25%, hsla(0,0%,100%,0) 45%);
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

    @keyframes payCardRotate {
        from, to {
            animation-timing-function: ease-in;
            box-shadow: 0 18px 34px -20px rgba(2, 6, 23, 0.55);
            transform: rotateY(-8deg);
        }

        25%, 75% {
            animation-timing-function: ease-out;
            box-shadow: 0 22px 36px -24px rgba(2, 6, 23, 0.5);
            transform: rotateY(0deg);
        }

        50% {
            animation-timing-function: ease-in;
            box-shadow: 0 26px 40px -24px rgba(2, 6, 23, 0.55);
            transform: rotateY(8deg);
        }
    }

    @keyframes payCardTexture {
        from, to {
            transform: translate3d(0, 0, 0);
        }

        50% {
            transform: translate3d(-50%, 0, 0);
        }
    }

    @keyframes ethpayAnim {
        0% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-20px);
        }
        100% {
            transform: translateY(0);
        }
    }
</style>

@if(!empty($overdueSeconds) && (int) $overdueSeconds > 0)
    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const els = document.querySelectorAll(".overdue-clock[data-overdue-seconds]");
            els.forEach((el) => new BwOverdueClock(el));
        });

        class BwOverdueClock {
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
                    Array.from(letterEls).forEach((el, i) => {
                        el.textContent = this.time[letter][i];
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

                const dd = String(days).padStart(2, "0");
                const hh = String(hours).padStart(2, "0");
                const mm = String(mins).padStart(2, "0");
                const ss = String(secs).padStart(2, "0");

                this.time.a = [...this.time.b];
                this.time.b = [dd, hh, mm, ss];
                if (!this.time.a.length) this.time.a = [...this.time.b];
            }
        }
    </script>
@endif

@endsection
