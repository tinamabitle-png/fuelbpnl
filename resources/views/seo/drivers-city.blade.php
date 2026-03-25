@extends('Layouts.app')

@php
    /** @var string $city */
    $city = (string) ($city ?? 'South Africa');
@endphp

@section('title', "Fuel vouchers for drivers in {$city}")
@section('meta_description', "Bwiser helps e-hailing and delivery drivers in {$city} access fuel vouchers with credit controls, instant redemption, and clear repayment tracking.")
@section('canonical', url('/drivers/' . ($slug ?? '')))

@section('content')
<section class="max-w-7xl mx-auto px-6 pt-14 pb-16">
    <div class="glass rounded-3xl p-8 md:p-12 overflow-hidden">
        <div class="max-w-3xl">
            <p class="text-xs uppercase tracking-[1px] text-blue-600">Drivers • {{ $city }}</p>
            <h1 class="brand-font text-3xl md:text-5xl font-semibold text-slate-900 mt-4 leading-tight">
                Fuel vouchers for e-hailing and delivery drivers in {{ $city }}
            </h1>
            <p class="text-slate-700 mt-5 leading-relaxed">
                Request voucher-based fuel credit, redeem instantly at approved stations, and track repayments with full visibility.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a class="super-button" href="{{ route('register.driver') }}">
                    <span>Register as Driver</span>
                </a>
                <a class="super-button super-button--ghost" href="{{ route('login') }}">
                    <span>Login</span>
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mt-8">
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Fuel credit built for ops</h2>
            <p class="text-slate-700 mt-2">Controls, approvals, and vouchers designed for real-time driver operations.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Instant redemption</h2>
            <p class="text-slate-700 mt-2">Redeem at station level with validation and an audit-ready trail.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Clear repayment tracking</h2>
            <p class="text-slate-700 mt-2">Know what you owe, what you paid, and what is pending at any time.</p>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8">
        <h2 class="brand-font text-2xl font-semibold text-slate-900">FAQs for {{ $city }}</h2>
        <div class="mt-4 grid gap-3">
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">Who can use Bwiser in {{ $city }}?</summary>
                <p class="text-slate-700 mt-2">E-hailing drivers, taxi operators, couriers, and food or grocery delivery drivers.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How do fuel vouchers work?</summary>
                <p class="text-slate-700 mt-2">Eligible drivers receive secure vouchers, redeem at approved stations, and repayments are tracked with clear records.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How fast can I start?</summary>
                <p class="text-slate-700 mt-2">Register your driver profile, complete verification when prompted, then request vouchers based on eligibility.</p>
            </details>
        </div>
    </div>
</section>
@endsection

