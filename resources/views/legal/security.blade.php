@extends('Layouts.app')

@section('title', 'PCI DSS & ISO Compliance')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">PCI DSS & ISO Compliance Information</h1>
        <p class="text-sm text-slate-600">Security posture summary for production use.</p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">PCI DSS Scope</h2>
            <p>Bwiser minimises direct card-data exposure by using approved payment processors and tokenised payment workflows where possible. Cardholder data is not intentionally stored in application databases.</p>

            <h2 class="text-lg font-semibold text-slate-900">Control Areas</h2>
            <p>Controls include strong authentication, role-based access, least privilege, network segmentation, activity logging, vulnerability patching, and incident response procedures.</p>

            <h2 class="text-lg font-semibold text-slate-900">ISO-Aligned Practices</h2>
            <p>Operational security controls are aligned with information security management principles commonly associated with ISO/IEC 27001 and continuity practices associated with ISO 22301.</p>

            <h2 class="text-lg font-semibold text-slate-900">Third-Party Risk</h2>
            <p>Payment gateways, cloud hosting and service providers are subject to onboarding due diligence and periodic compliance review.</p>

            <h2 class="text-lg font-semibold text-slate-900">Monitoring and Response</h2>
            <p>Security events are monitored through logs and alerts, with incident response and breach notification procedures applied in line with South African legal obligations.</p>
        </div>
    </div>
</section>
@endsection
