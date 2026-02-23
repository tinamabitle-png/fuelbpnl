@extends('layouts.app')

@section('title', 'FuelLevy Control Platform')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-5">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Platform Overview</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">FuelLevy Control Platform</h1>
            <p class="text-slate-600 mt-3 max-w-3xl">
                Multi-role credit, voucher, settlement, and station operations with real-time support across admin, investor,
                merchant, driver, and employee surfaces.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold" href="{{ route('quick-login.role', ['role' => 'admin']) }}">One-click Admin</a>
            <a class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold" href="{{ route('quick-login.role', ['role' => 'employee']) }}">One-click Employee</a>
            <a class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold" href="{{ route('quick-login.role', ['role' => 'investor']) }}">One-click Investor</a>
            <a class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold" href="{{ route('quick-login.role', ['role' => 'merchant']) }}">One-click Merchant</a>
            <a class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold" href="{{ route('quick-login.role', ['role' => 'driver']) }}">One-click Driver</a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Core Roles</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">5</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Capabilities</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">56+</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Payment Gateways</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">4</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Client Surfaces</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">3</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Authentication and Compliance</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-600 list-disc list-inside">
                <li>Multi-role authentication for admin, investor, merchant, driver, and employee.</li>
                <li>Role-specific onboarding with document completion and KYC support.</li>
                <li>OTP login and reset flows for web and mobile paths.</li>
                <li>OAuth extension points for Google, Uber, Bolt, and Shell integrations.</li>
            </ul>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Voucher and Settlement Lifecycle</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-600 list-disc list-inside">
                <li>Voucher create, approve/reject, redeem, bulk actions, and export support.</li>
                <li>Settlement processing, mark paid, payout requests, and summary metrics.</li>
                <li>Credit scoring and decision workflows with override and audit-friendly reviews.</li>
                <li>Repayment scheduling with auto-pay options and callback handling.</li>
            </ul>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Operations and Reporting</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-600 list-disc list-inside">
                <li>Fuel station management, services, pricing, and sync endpoints.</li>
                <li>Financial, risk, voucher, and lease reporting with exports.</li>
                <li>Admin system tools: cache tools, logs, backups, settings, and test mail.</li>
                <li>Merchant and station-owner console support with receipt workflows.</li>
            </ul>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Real-time Support and Apps</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-600 list-disc list-inside">
                <li>Websocket-ready voucher visibility for merchant stations.</li>
                <li>Chat thread and message read-state APIs with video token flows.</li>
                <li>Flutter driver app modules for vouchers, wallet, repayments, and profile.</li>
                <li>Desktop station console support for redemption and settlement operations.</li>
            </ul>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8 text-sm text-slate-600">
        End-to-end flow: driver request -> admin credit decision -> voucher issue/redeem -> settlement -> reporting.
    </div>
</section>
@endsection
