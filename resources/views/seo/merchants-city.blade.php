@extends('Layouts.app')

@php
    /** @var string $city */
    $city = (string) ($city ?? 'South Africa');
@endphp

@section('title', "Voucher redemption and settlement for fuel stations in {$city}")
@section('meta_description', "Bwiser helps fuel stations and merchants in {$city} redeem secure vouchers, validate in real time, and settle to bank with audit-ready reporting.")
@section('canonical', url('/merchants/' . ($slug ?? '')))

@section('content')
<section class="max-w-7xl mx-auto px-6 pt-14 pb-16">
    <div class="glass rounded-3xl p-8 md:p-12 overflow-hidden">
        <div class="max-w-3xl">
            <p class="text-xs uppercase tracking-[1px] text-blue-600">Merchants • {{ $city }}</p>
            <h1 class="brand-font text-3xl md:text-5xl font-semibold text-slate-900 mt-4 leading-tight">
                Voucher redemption and settlement for fuel stations in {{ $city }}
            </h1>
            <p class="text-slate-700 mt-5 leading-relaxed">
                Validate vouchers at station level, split fuel and kiosk amounts where needed, and settle to bank with clear reconciliation.
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
            <p class="text-slate-700 mt-2">Confirm voucher details before fueling and keep clean redemption records.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Fuel and kiosk splits</h2>
            <p class="text-slate-700 mt-2">Support station rules by splitting voucher amounts into fuel and shop portions.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Settlement and reporting</h2>
            <p class="text-slate-700 mt-2">Automated settlement tracking to bank with reconciliation and audit visibility.</p>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8">
        <h2 class="brand-font text-2xl font-semibold text-slate-900">FAQs for {{ $city }}</h2>
        <div class="mt-4 grid gap-3">
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">What types of merchants are supported?</summary>
                <p class="text-slate-700 mt-2">Fuel stations and merchants redeeming driver vouchers with bank settlement and reporting.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How do settlements work?</summary>
                <p class="text-slate-700 mt-2">Redemptions are recorded and settled to bank with full reconciliation and a clear audit trail.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How do we onboard in {{ $city }}?</summary>
                <p class="text-slate-700 mt-2">Register as a merchant, complete verification, then configure your settlement details.</p>
            </details>
        </div>
    </div>
</section>
@endsection

