@extends('Layouts.app')

@section('title', 'Investor Opportunities - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Subprime Approved Vouchers</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">Investment Opportunities</h1>
            <p class="text-slate-600 mt-3">Fund approved voucher leases for subprime drivers only. Each lease is capped at its remaining unfunded balance.</p>
        </div>
        <a href="{{ route('investor.dashboard') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Dashboard</a>
    </div>

    @if(session('error'))
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Available Capital</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) $investor->available_capital, 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Investment Range</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">R {{ number_format((float) $investor->minimum_investment_amount, 2) }} - R {{ number_format((float) $investor->maximum_investment_amount, 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Wallet Balance</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) $investor->wallet_balance, 2) }}</p>
        </div>
    </div>

    <div class="mt-8 glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <th>Lease</th>
                        <th>Driver</th>
                        <th>Voucher</th>
                        <th>Funding</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opportunities as $lease)
                        @php
                            $approvedVoucher = $lease->vouchers->firstWhere('status', 'approved') ?: $lease->vouchers->firstWhere('status', 'redeemed');
                            $remaining = (float) $lease->investor_funding_remaining;
                            $defaultAmount = min((float) $investor->maximum_investment_amount, (float) $investor->available_capital, $remaining);
                            $defaultAmount = max((float) $investor->minimum_investment_amount, $defaultAmount);
                        @endphp
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-900">#{{ $lease->id }}</p>
                                <p class="text-xs text-slate-500">{{ ucfirst($lease->risk_band) }} • {{ (float) $lease->interest_rate }}%</p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-900">{{ $lease->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-slate-500">Score {{ $lease->user?->credit_score ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <span class="inline-flex px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-semibold">Approved voucher</span>
                                <p class="text-xs text-slate-500 mt-1">{{ $approvedVoucher?->code ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <p class="text-sm text-slate-700">Total R {{ number_format((float) $lease->total_amount, 2) }}</p>
                                <p class="text-xs text-slate-500">Remaining R {{ number_format($remaining, 2) }}</p>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('investor.invest') }}" class="flex flex-col sm:flex-row gap-2">
                                    @csrf
                                    <input type="hidden" name="lease_id" value="{{ $lease->id }}">
                                    <input type="number" min="{{ (float) $investor->minimum_investment_amount }}" max="{{ $remaining }}" step="0.01" name="amount" value="{{ number_format($defaultAmount, 2, '.', '') }}" class="w-36 px-3 py-2 text-sm">
                                    <button type="submit" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold">Fund</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500 py-10">No approved subprime voucher leases are available right now.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $opportunities->links() }}</div>
</section>
@endsection
