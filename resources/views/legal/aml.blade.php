@extends('layouts.app')

@section('title', 'AML & KYC Policy')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">AML & KYC Policy</h1>
        <p class="text-sm text-slate-600">Effective date: {{ date('F j, Y') }}. This policy should be finalised with compliance and legal teams before go-live.</p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">Policy Objective</h2>
            <p>Bwiser applies a risk-based anti-money laundering and customer due diligence framework to detect, prevent and report suspicious activity.</p>

            <h2 class="text-lg font-semibold text-slate-900">KYC Requirements</h2>
            <p>Drivers and merchants must submit valid identity and business documentation. Enhanced checks may apply for higher-risk users, unusual transaction behavior, or adverse screening results.</p>

            <h2 class="text-lg font-semibold text-slate-900">Monitoring and Alerts</h2>
            <p>We monitor onboarding, voucher issuance, repayment and settlement activity for anomalies including velocity spikes, unusual geolocation patterns, and repeated failed payment behaviour.</p>

            <h2 class="text-lg font-semibold text-slate-900">Escalation and Reporting</h2>
            <p>High-risk cases are escalated to compliance officers for investigation. Where required by law, suspicious activity reports are filed with competent South African authorities.</p>

            <h2 class="text-lg font-semibold text-slate-900">Recordkeeping</h2>
            <p>KYC files, decision logs and audit trails are retained according to legal retention requirements and internal information governance controls.</p>
        </div>
    </div>
</section>
@endsection
