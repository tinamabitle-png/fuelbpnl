@extends('layouts.app')

@section('title', 'Repayment Support Request - Bwiser')

@section('content')
<section class="max-w-3xl mx-auto px-6 pt-16 pb-20">
    <div class="glass rounded-2xl p-6 md:p-8">
        <p class="text-xs uppercase tracking-[0.18em] text-blue-600">Bwiser Repayment Request</p>
        <h1 class="brand-font text-2xl md:text-3xl text-slate-900 mt-2">Help Settle Repayment</h1>
        <p class="text-sm text-slate-600 mt-2">You are about to pay a driver repayment request securely via Paystack.</p>

        @if(session('error'))
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-slate-500">Amount</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">R {{ number_format((float) $repayment->amount, 2) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-slate-500">Due Date</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ optional($repayment->due_date)->format('d M Y') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-slate-500">Station</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ $stationName }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-slate-500">Status</p>
                <p class="mt-1 text-base font-semibold {{ $isPayable ? 'text-amber-700' : 'text-emerald-700' }} uppercase">{{ $repayment->status }}</p>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">Fine print: This request applies to voucher <span class="font-semibold text-slate-700">{{ $voucherCode ?: 'N/A' }}</span>.</p>

        @if($isPayable)
            <form method="POST" action="{{ $payUrl }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm text-slate-700 mb-1">Payer Email (optional)</label>
                    <input
                        type="email"
                        name="payer_email"
                        value="{{ old('payer_email', $payerEmailPrefill) }}"
                        placeholder="you@example.com"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    >
                    <p class="text-xs text-slate-500 mt-1">Used by Paystack for receipt and checkout reference.</p>
                </div>
                <button type="submit" class="btn-primary w-full rounded-xl py-3 text-sm font-semibold">Pay This Repayment</button>
            </form>
        @else
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                This repayment is already settled and no longer needs payment.
            </div>
        @endif
    </div>
</section>
@endsection
