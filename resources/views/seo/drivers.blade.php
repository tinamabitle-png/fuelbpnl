@extends('Layouts.app')

@section('title', 'Fuel vouchers for drivers in South Africa')
@section('meta_description', 'Bwiser helps e-hailing and delivery drivers in South Africa access fuel vouchers with credit controls, instant redemption, and clear repayment tracking.')
@section('canonical', url('/drivers'))

@section('content')
<section class="max-w-7xl mx-auto px-6 pt-14 pb-16">
    <div class="glass rounded-3xl p-8 md:p-12 overflow-hidden">
        <div class="max-w-3xl">
            <p class="text-xs uppercase tracking-[1px] text-blue-600">Drivers</p>
            <h1 class="brand-font text-3xl md:text-5xl font-semibold text-slate-900 mt-4 leading-tight">
                Fuel vouchers for e-hailing and delivery drivers in South Africa
            </h1>
            <p class="text-slate-700 mt-5 leading-relaxed">
                Bwiser lets drivers request fuel credit, receive secure vouchers, redeem instantly at approved stations,
                and track repayments with full visibility.
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
            <h2 class="brand-font text-xl font-semibold text-slate-900">Access fuel credit</h2>
            <p class="text-slate-700 mt-2">Apply for voucher-based fuel financing designed for real-time operations.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Redeem instantly</h2>
            <p class="text-slate-700 mt-2">Secure redemption at station level with validation and audit visibility.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl font-semibold text-slate-900">Track repayments</h2>
            <p class="text-slate-700 mt-2">Clear repayment schedules, history, and settlement reporting.</p>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8">
        <h2 class="brand-font text-2xl font-semibold text-slate-900">FAQs</h2>
        <div class="mt-4 grid gap-3">
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">Which drivers is Bwiser for?</summary>
                <p class="text-slate-700 mt-2">Drivers doing e-hailing, taxi operations, courier work, food delivery, and grocery delivery.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How do vouchers work?</summary>
                <p class="text-slate-700 mt-2">Vouchers are issued with controls and redeemed at approved stations; usage and settlements are tracked end-to-end.</p>
            </details>
            <details class="rounded-xl border border-slate-200 bg-white/70 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">How fast can I get started?</summary>
                <p class="text-slate-700 mt-2">Register your account, complete verification when prompted, and you can request fuel vouchers based on eligibility.</p>
            </details>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-8">
        <h2 class="brand-font text-2xl font-semibold text-slate-900">Top cities</h2>
        <p class="text-slate-700 mt-2">Find fuel voucher info for drivers by city.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach([
                ['Johannesburg', url('/drivers/johannesburg')],
                ['Cape Town', url('/drivers/cape-town')],
                ['Durban', url('/drivers/durban')],
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
