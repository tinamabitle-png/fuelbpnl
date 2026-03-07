@extends('Layouts.app')

@section('title', 'POPIA Privacy Notice')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">POPIA Privacy Notice</h1>
        <p class="text-sm text-slate-600">Effective date: {{ date('F j, Y') }}</p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">Responsible Party</h2>
            <p>Bwiser acts as a responsible party for personal information processed in connection with platform registration, onboarding, payment and support activities.</p>

            <h2 class="text-lg font-semibold text-slate-900">Information Collected</h2>
            <p>We may process identity details, contact details, address and geolocation data, business records, uploaded compliance documents, payment metadata, and operational audit logs.</p>

            <h2 class="text-lg font-semibold text-slate-900">Purpose of Processing</h2>
            <p>Information is processed for account setup, credit and risk evaluation, fraud prevention, payment processing, regulatory compliance, reporting, and customer support.</p>

            <h2 class="text-lg font-semibold text-slate-900">Data Subject Rights</h2>
            <p>Data subjects may request access, correction, deletion (where lawful), objection, and complaint escalation to the Information Regulator under POPIA.</p>

            <h2 class="text-lg font-semibold text-slate-900">Security Safeguards</h2>
            <p>We apply technical and organisational controls including access management, encryption in transit, audit trails, monitoring and vendor due diligence.</p>

            <h2 class="text-lg font-semibold text-slate-900">Cross-Border Transfers</h2>
            <p>Where processing includes third-party infrastructure outside South Africa, safeguards are implemented to ensure lawful and secure transfer conditions.</p>
        </div>
    </div>
</section>
@endsection
