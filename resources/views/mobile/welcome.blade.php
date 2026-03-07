@extends('mobile.layouts.app')

@section('title', 'Bwiser Mobile')
@section('meta_description', 'Bwiser fuel finance and payments platform for drivers and merchants in South Africa.')

@section('content')
<main class="px-4 pb-8 pt-6">
    <div class="mx-auto max-w-md space-y-4">
        <section class="mobile-card p-5">
            <p class="text-xs uppercase tracking-[0.25em] text-blue-700">Bwiser Platform</p>
            <h1 class="mt-3 text-3xl font-semibold leading-tight text-slate-900">
                Fuel Credit, Voucher Issuance and Settlement
                <span class="mobile-gradient-text block">For Drivers, Stations and Finance Teams</span>
            </h1>
            <p class="mt-3 text-sm text-slate-600">
                Bwiser connects field users and operations teams with real-time voucher processing, credit control, and settlement workflows backed by your existing APIs.
            </p>
            <div class="mt-5 grid grid-cols-1 gap-2">
                <a href="{{ route('register.driver') }}" class="rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white">
                    Register Driver
                </a>
                <a href="{{ route('register.merchant') }}" class="rounded-xl border border-blue-200 px-4 py-3 text-center text-sm font-semibold text-blue-700 bg-white">
                    Register Merchant
                </a>
                <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 bg-white">
                    Sign In
                </a>
            </div>
        </section>

        <section class="mobile-card p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Core Capabilities</p>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <div class="rounded-lg bg-slate-50 px-3 py-2 border border-slate-200">Real-time voucher validation at station level</div>
                <div class="rounded-lg bg-slate-50 px-3 py-2 border border-slate-200">Credit controls with role-based approvals</div>
                <div class="rounded-lg bg-slate-50 px-3 py-2 border border-slate-200">Automated settlements and audit tracking</div>
            </div>
        </section>

        <section class="mobile-card p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Trusted Network</p>
            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-700">
                @foreach (['Astron', 'BP', 'Engen', 'Sasol', 'Shell', 'Total Energies'] as $brand)
                    <span class="rounded-full border border-slate-300 bg-white px-3 py-1.5">{{ $brand }}</span>
                @endforeach
            </div>
        </section>
    </div>
</main>
@endsection
