@extends('Layouts.app')

@section('title', 'Investment Detail - Bwiser')

@section('content')
<section class="max-w-4xl mx-auto px-6 pt-16 pb-20">
    <a href="{{ route('investor.investments') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Back to investments</a>
    <div class="mt-6 glass rounded-3xl p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Investment #{{ $investment->id }}</p>
                <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Lease #{{ $investment->lease_id }}</h1>
                <p class="text-slate-600 mt-2">{{ $investment->lease?->user?->name ?? 'Unknown Driver' }} • {{ ucfirst($investment->lease?->risk_band ?? 'unknown') }}</p>
            </div>
            <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-semibold {{ $investment->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($investment->status) }}</span>
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-sm text-slate-500">Amount Invested</p>
                <p class="text-2xl font-semibold text-slate-900 mt-2">R {{ number_format((float) $investment->amount_invested, 2) }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-sm text-slate-500">Expected Interest</p>
                <p class="text-2xl font-semibold text-emerald-700 mt-2">R {{ number_format((float) $investment->expected_interest, 2) }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-sm text-slate-500">Ownership</p>
                <p class="text-2xl font-semibold text-slate-900 mt-2">{{ number_format((float) $investment->percentage_ownership, 2) }}%</p>
            </div>
        </div>

        <div class="mt-8 rounded-2xl bg-white border border-slate-200 p-5">
            <h2 class="brand-font text-xl text-slate-900">Approved Voucher Link</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($investment->lease?->vouchers ?? [] as $voucher)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="font-semibold text-slate-900">{{ $voucher->code }}</p>
                        <p class="text-sm text-slate-500 mt-1">R {{ number_format((float) $voucher->amount, 2) }} • {{ ucfirst($voucher->status) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No voucher linked.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
