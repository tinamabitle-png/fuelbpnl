@extends('Layouts.app')

@section('title', 'Terms & Conditions')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">Terms & Conditions</h1>
        <p class="text-sm text-slate-600">Effective date: {{ date('F j, Y') }}. This document is prepared for South African operations and should be reviewed by legal counsel before final production launch.</p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">1. Scope and Parties</h2>
            <p>These terms govern access to the Bwiser platform by drivers, merchants, administrators and authorised staff. By registering or using the platform, users agree to these terms and linked policies.</p>

            <h2 class="text-lg font-semibold text-slate-900">2. National Credit Act Alignment</h2>
            <p>Credit-related products and repayment arrangements are managed in line with South Africa’s National Credit Act, including affordability checks, transparent pricing, repayment disclosures, and fair collections practices where applicable.</p>

            <h2 class="text-lg font-semibold text-slate-900">3. User Obligations</h2>
            <p>Users must provide accurate information, keep credentials secure, and use the platform lawfully. Fraud, identity misuse, document falsification, or payment abuse may result in suspension and reporting to relevant authorities.</p>

            <h2 class="text-lg font-semibold text-slate-900">4. Fees, Payments and Settlements</h2>
            <p>All fees, voucher costs, settlement timelines and penalties are shown within platform workflows and transaction records. Users are responsible for verifying payment references and authorised payment methods before completion.</p>

            <h2 class="text-lg font-semibold text-slate-900">5. Data Protection and Access Rights</h2>
            <p>Personal information is processed in accordance with POPIA and access-to-information obligations are managed under PAIA. Refer to the POPIA Notice and PAIA Manual for full details.</p>

            <h2 class="text-lg font-semibold text-slate-900">6. Liability and Service Availability</h2>
            <p>The platform is provided on a commercially reasonable basis with security and uptime controls. Liability is limited to the maximum extent permitted by South African law, excluding liability that cannot be excluded by statute.</p>

            <h2 class="text-lg font-semibold text-slate-900">7. Amendments and Governing Law</h2>
            <p>We may update these terms periodically. Continued use after updates means acceptance of revised terms. These terms are governed by South African law and disputes are handled by competent South African courts.</p>
        </div>
    </div>
</section>
@endsection
