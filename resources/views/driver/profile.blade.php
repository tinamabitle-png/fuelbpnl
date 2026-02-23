@extends('layouts.app')

@section('title', 'Driver Profile - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">My Profile</h1>
            <p class="text-slate-600 mt-2">Personal details, credit summary, and repayment setup.</p>
        </div>
        <a href="{{ route('driver.dashboard') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Dashboard</a>
    </div>
    @include('driver.partials.nav', ['backUrl' => route('driver.dashboard')])

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="glass rounded-2xl p-6 lg:col-span-2">
            <h2 class="brand-font text-xl text-slate-900">Account Information</h2>
            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Full Name</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">{{ $user->name ?: 'N/A' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Email</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">{{ $user->email ?: 'N/A' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Phone</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">{{ $user->phone ?: 'N/A' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">ID Number</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">{{ $user->id_number ?: 'N/A' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Account Status</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1 uppercase">{{ $user->status ?: 'N/A' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">AutoPay</p>
                    <p class="text-sm font-semibold mt-1 {{ $user->autopay_enabled ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $user->autopay_enabled ? 'Enabled' : 'Not enabled' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Wallet & Credit</h2>
            <div class="mt-5 space-y-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Wallet Balance</p>
                    <p class="text-lg font-semibold text-slate-900 mt-1">R {{ number_format((float) optional($user->wallet)->balance, 2) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Outstanding Balance</p>
                    <p class="text-lg font-semibold text-slate-900 mt-1">R {{ number_format((float) optional($user->wallet)->outstanding_balance, 2) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Credit Limit</p>
                    <p class="text-lg font-semibold text-slate-900 mt-1">R {{ number_format((float) optional($user->creditLimit)->limit, 2) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">Available Credit</p>
                    <p class="text-lg font-semibold text-slate-900 mt-1">R {{ number_format((float) $user->available_credit, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Total Vouchers</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">{{ $summary['total_vouchers'] }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Redeemed Vouchers</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">{{ $summary['redeemed_vouchers'] }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Active Leases</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">{{ $summary['active_leases'] }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Paid Repayments</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">{{ $summary['paid_repayments'] }}</p>
        </div>
    </div>
</section>
@endsection
