@extends('layouts.app')

@section('title', 'Cookie Policy')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">Cookie Policy</h1>
        <p class="text-sm text-slate-600">Effective date: {{ date('F j, Y') }}</p>

        <div class="space-y-4 text-sm text-slate-700">
            <p>This policy explains how Bwiser uses cookies and similar technologies on web portals and mobile web interfaces.</p>
            <h2 class="text-lg font-semibold text-slate-900">Cookie Types</h2>
            <p>We use strictly necessary cookies for authentication, session security, CSRF protection and platform functionality. We may also use analytics or performance cookies where consent is obtained.</p>
            <h2 class="text-lg font-semibold text-slate-900">Consent and Control</h2>
            <p>Where required, users can manage non-essential cookies via browser controls or consent tools. Disabling required cookies may impact login and transaction workflows.</p>
            <h2 class="text-lg font-semibold text-slate-900">Retention</h2>
            <p>Session cookies expire automatically. Persistent cookies are retained only for legitimate operational periods and then deleted or rotated.</p>
            <h2 class="text-lg font-semibold text-slate-900">Third-Party Services</h2>
            <p>Embedded map and payment services may set their own cookies under their respective policies. Use of these providers is subject to contractual and compliance controls.</p>
        </div>
    </div>
</section>
@endsection
