@extends('Layouts.app')

@section('title', 'Voucher redemption and settlement for fuel stations')
@section('meta_description', 'Bwiser helps South African fuel stations and merchants redeem secure vouchers, validate in real time, and settle to bank with audit-ready reporting.')
@section('canonical', url('/merchants'))

@section('content')
<section class="max-w-7xl mx-auto px-6 pt-14 pb-16">
    <div class="glass rounded-3xl p-8 md:p-12 overflow-hidden">
        <div class="max-w-3xl">
            <p class="text-xs uppercase tracking-[1px] text-blue-600">Merchants</p>
            <h1 class="brand-font text-3xl md:text-5xl font-semibold text-slate-900 mt-4 leading-tight">
                Voucher redemption and settlement for South African fuel stations
            </h1>
            <p class="text-slate-700 mt-5 leading-relaxed">
                Validate vouchers at station level, split fuel and kiosk amounts where needed, and settle to bank with clear
                reconciliation and audit visibility.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a class="super-button" href="{{ route('register.merchant') }}">
                    <span>Register as Merchant</span>
                </a>
                <a class="super-button super-button--ghost" href="{{ route('login') }}">
                    <span>Login</span>
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mt-8">
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Real-time validation</h2>
            <p class="text-slate-700 mt-2">Confirm voucher details before fueling, reduce fraud, and keep a clean audit trail.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Settlement to bank</h2>
            <p class="text-slate-700 mt-2">Automated settlement tracking with clear statements and reconciliation reporting.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Role-based controls</h2>
            <p class="text-slate-700 mt-2">Approve, redeem, and manage voucher operations with appropriate permissions.</p>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8">
        <h2 class="brand-font text-2xl font-semibold text-slate-900">FAQs</h2>
        <div class="mt-4 grid gap-3">
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">Do you support kiosk and fuel splits?</summary>
                <p class="text-slate-700 mt-2">Yes. Voucher amounts can be split into fuel and kiosk portions based on station rules.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How do settlements work?</summary>
                <p class="text-slate-700 mt-2">Redemptions are recorded and then settled to bank with a full reconciliation record and audit tracking.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How do we onboard a station?</summary>
                <p class="text-slate-700 mt-2">Register as a merchant, complete verification, and configure settlement details for your station wallet.</p>
            </details>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8">
        <h2 class="brand-font text-2xl font-semibold text-slate-900">Top cities</h2>
        <p class="text-slate-700 mt-2">Merchant voucher redemption and settlement by city.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach([
                ['Johannesburg', url('/merchants/johannesburg')],
                ['Pretoria', url('/merchants/pretoria')],
                ['Cape Town', url('/merchants/cape-town')],
                ['Durban', url('/merchants/durban')],
                ['Sandton', url('/merchants/sandton')],
                ['Midrand', url('/merchants/midrand')],
                ['Stellenbosch', url('/merchants/stellenbosch')],
                ['Gqeberha', url('/merchants/gqeberha')],
            ] as $item)
                <a class="inline-flex items-center rounded-full border border-slate-200 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-white"
                   href="{{ $item[1] }}">
                    {{ $item[0] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
@endsection
