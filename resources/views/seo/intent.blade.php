@extends('Layouts.guest')

@php
    $kw = (string) ($page['keyword'] ?? '');
    $title = (string) ($page['title'] ?? 'Bwiser');
    $desc = (string) ($page['description'] ?? 'Bwiser connects drivers, stations, and finance teams on one buy now pay later process.');
    $h1 = (string) ($page['h1'] ?? ($kw !== '' ? ucwords($kw) : 'Bwiser'));
    $aliases = (array) ($page['also_known_as'] ?? []);
    $aliases = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $aliases)));
@endphp

@section('title', $title)
@section('meta_description', $desc)
@section('canonical', url()->current())

@section('content')
<section class="min-h-screen py-12 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="glass rounded-2xl p-6 md:p-10 border border-slate-200">
            <p class="text-xs uppercase tracking-[0.18em] text-blue-700">Bwiser Intent Page</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-3">{{ $h1 }}</h1>
            <p class="text-slate-700 mt-4 leading-relaxed">
                {{ $desc }}
            </p>

            @if(!empty($aliases))
                <p class="mt-4 text-sm text-slate-600">
                    Related searches:
                    <span class="font-semibold text-slate-800">{{ implode(', ', $aliases) }}</span>
                </p>
            @endif

            <div class="mt-7 grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-7 surface-card p-6">
                    <h2 class="brand-font text-xl font-semibold text-slate-900">How Bwiser Works</h2>
                    <ol class="mt-4 space-y-3 text-slate-700 text-sm leading-relaxed list-decimal pl-5">
                        <li>
                            Apply for access: drivers and merchants create accounts to enter the approval workflow.
                        </li>
                        <li>
                            Issue secure vouchers: approved limits are turned into redeemable vouchers with clear rules.
                        </li>
                        <li>
                            Redeem and settle: merchants validate in real time and settlements are tracked with an audit trail.
                        </li>
                    </ol>

                    <h3 class="brand-font text-lg font-semibold text-slate-900 mt-6">What You Get</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-700 list-disc pl-5">
                        <li>Real-time voucher validation at station level.</li>
                        <li>Credit controls with role-based approvals and traceability.</li>
                        <li>Instant redemption plus settlement visibility for finance teams.</li>
                    </ul>
                </div>
                <div class="md:col-span-5 surface-card p-6">
                    <h2 class="brand-font text-xl font-semibold text-slate-900">Get Started</h2>
                    <p class="mt-3 text-sm text-slate-700 leading-relaxed">
                        Choose your path. If you are a driver, apply for fuel credit access. If you are a merchant station,
                        request onboarding for voucher redemption and settlements.
                    </p>
                    <div class="mt-5 flex flex-col gap-3">
                        @if(Route::has('register.driver'))
                            <a class="btn-primary px-4 py-3 rounded-xl text-sm font-semibold text-center" href="{{ route('register.driver') }}">
                                Driver Signup
                            </a>
                        @endif
                        @if(Route::has('register.merchant'))
                            <a class="btn-ghost px-4 py-3 rounded-xl text-sm font-semibold text-center" href="{{ route('register.merchant') }}">
                                Merchant Signup
                            </a>
                        @endif
                        <a class="text-sm font-semibold text-blue-700 hover:text-blue-900 text-center" href="{{ url('/') }}">
                            Back to home
                        </a>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-200/70">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Explore</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a class="btn-ghost px-3 py-2 rounded-xl text-xs font-semibold" href="{{ url('/drivers') }}">Drivers</a>
                            <a class="btn-ghost px-3 py-2 rounded-xl text-xs font-semibold" href="{{ url('/merchants') }}">Merchants</a>
                            <a class="btn-ghost px-3 py-2 rounded-xl text-xs font-semibold" href="{{ url('/legal/privacy') }}">Privacy</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-2xl bg-white/70 border border-slate-200 p-6">
                <h2 class="brand-font text-xl font-semibold text-slate-900">FAQ</h2>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-700">
                    <div>
                        <p class="font-semibold text-slate-900">Is this a fuel loan?</p>
                        <p class="mt-1">Bwiser uses voucher-based fuel financing with repayment schedules and audit visibility.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Where can vouchers be redeemed?</p>
                        <p class="mt-1">At participating stations with real-time validation and clear redemption rules.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">How long does approval take?</p>
                        <p class="mt-1">Approval times vary by verification and rollout stage. Early signups are queued for onboarding.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Can merchants reconcile settlements?</p>
                        <p class="mt-1">Yes. Settlements and redemption records are tracked for audit and reporting.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

