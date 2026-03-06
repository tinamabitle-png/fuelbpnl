@extends('layouts.app')

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
                                        <a href="{{ route('driver.repayments.pay-now', $repayment, false) }}" class="btn-primary pay-now-btn px-3 py-2 rounded-lg text-xs font-semibold inline-flex items-center">Pay Now</a>
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

    .pay-now-btn,
    .pay-now-btn:hover,
    .pay-now-btn:focus {
        box-shadow: none !important;
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
@endsection
