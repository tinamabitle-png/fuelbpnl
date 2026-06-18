@extends('Layouts.app')

@section('title', 'Investor Profile - Bwiser')

@section('content')
<section class="max-w-5xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Investor Account</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">{{ $investor->company_name }}</h1>
            <p class="text-slate-600 mt-3">Review profile details, capital limits, and investment preferences.</p>
        </div>
        <a href="{{ route('investor.dashboard') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Dashboard</a>
    </div>

    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Available Capital</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) $investor->available_capital, 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Invested Capital</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) $investor->invested_capital, 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Status</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $investor->status)) }}</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Company Details</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Registration</dt><dd class="font-semibold text-slate-900 text-right">{{ $investor->registration_number }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Tax ID</dt><dd class="font-semibold text-slate-900 text-right">{{ $investor->tax_id ?: 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Contact</dt><dd class="font-semibold text-slate-900 text-right">{{ $investor->contact_person }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd class="font-semibold text-slate-900 text-right">{{ $investor->contact_email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Phone</dt><dd class="font-semibold text-slate-900 text-right">{{ $investor->contact_phone }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Location</dt><dd class="font-semibold text-slate-900 text-right">{{ $investor->city }}, {{ $investor->country }}</dd></div>
            </dl>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Investment Preferences</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Risk Profile</dt><dd class="font-semibold text-slate-900 text-right">{{ ucfirst($investor->risk_profile) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Investment Range</dt><dd class="font-semibold text-slate-900 text-right">R {{ number_format((float) $investor->minimum_investment_amount, 2) }} - R {{ number_format((float) $investor->maximum_investment_amount, 2) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Interest Range</dt><dd class="font-semibold text-slate-900 text-right">{{ number_format((float) $investor->preferred_interest_rate_min, 2) }}% - {{ number_format((float) $investor->preferred_interest_rate_max, 2) }}%</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Horizon</dt><dd class="font-semibold text-slate-900 text-right">{{ ucfirst(str_replace('_', ' ', $investor->investment_horizon)) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Auto Invest</dt><dd class="font-semibold text-slate-900 text-right">{{ $investor->auto_invest_enabled ? 'Enabled' : 'Disabled' }}</dd></div>
            </dl>
        </div>
    </div>
</section>
@endsection
