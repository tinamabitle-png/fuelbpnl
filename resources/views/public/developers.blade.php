@extends('Layouts.app')

@section('title', 'Bwiser Developers')
@section('meta_description', 'Quick documentation for the Bwiser tapless partner API for retail payment aggregators and station integrations.')
@section('canonical', route('developers.docs'))

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8 md:p-10 overflow-hidden relative">
        <div class="absolute -top-20 -right-20 h-56 w-56 rounded-full bg-sky-400/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-12 h-48 w-48 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-[1]">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-700">Developers</p>
            <h1 class="brand-font mt-3 text-3xl md:text-4xl text-slate-900">Tapless Partner API</h1>
            <p class="mt-3 max-w-3xl text-sm md:text-base text-slate-600">
                Connect retail payment aggregators, forecourt apps, and station systems to Bwiser for tapless payment intent creation, authorization, and redemption.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="mailto:support@bwiser.co.za?subject=Tapless%20Partner%20API%20Access" class="super-button">
                    <span>Request Access</span>
                </a>
                <a href="{{ url('/') }}" class="btn-ghost px-4 py-2 rounded-xl">Back Home</a>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-slate-900">What you can do</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-semibold text-slate-900">Create intents</p>
                            <p class="mt-1 text-sm text-slate-600">Start a payment session against a station, voucher code, tap token, or voucher ID.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-semibold text-slate-900">Authorize safely</p>
                            <p class="mt-1 text-sm text-slate-600">Bwiser checks station assignment, voucher status, expiry, and geofence rules before approval.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-semibold text-slate-900">Redeem in real time</p>
                            <p class="mt-1 text-sm text-slate-600">Complete retail redemption and return final voucher settlement data in one call.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-semibold text-slate-900">Poll status</p>
                            <p class="mt-1 text-sm text-slate-600">Fetch a payment intent at any time to see whether it is created, authorized, redeemed, or failed.</p>
                        </div>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Base URL</h2>
                    <code class="mt-3 block rounded-xl bg-slate-950 px-4 py-3 text-xs text-slate-100 overflow-x-auto">{{ $baseUrl }}/api/v1/partner/tapless</code>

                    <h3 class="mt-6 text-sm font-semibold text-slate-900">Authentication headers</h3>
                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                        <p><code>X-Bwiser-Key</code> your partner public key</p>
                        <p><code>X-Bwiser-Timestamp</code> current unix timestamp</p>
                        <p><code>X-Bwiser-Signature</code> <code>HMAC_SHA256(timestamp + "." + raw_body, secret)</code></p>
                    </div>

                    <div class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm font-semibold text-blue-900">Credentials are issued by Bwiser</p>
                        <p class="mt-1 text-sm text-blue-800">Each partner is mapped to approved stations and can only act within that assigned retail footprint.</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Endpoints</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p><span class="font-mono text-blue-700">GET</span> <code>/health</code></p>
                            <p class="mt-1 text-slate-600">Confirm partner auth and available capabilities.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p><span class="font-mono text-blue-700">GET</span> <code>/stations</code></p>
                            <p class="mt-1 text-slate-600">List the stations assigned to your partner account.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p><span class="font-mono text-emerald-700">POST</span> <code>/intents</code></p>
                            <p class="mt-1 text-slate-600">Create a tapless payment intent with station, reference, and voucher input.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p><span class="font-mono text-blue-700">GET</span> <code>/intents/{publicId}</code></p>
                            <p class="mt-1 text-slate-600">Read the latest payment intent state and voucher payload.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p><span class="font-mono text-emerald-700">POST</span> <code>/intents/{publicId}/authorize</code></p>
                            <p class="mt-1 text-slate-600">Run voucher, geofence, and station checks before redemption.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p><span class="font-mono text-emerald-700">POST</span> <code>/intents/{publicId}/redeem</code></p>
                            <p class="mt-1 text-slate-600">Complete redemption and return the settled voucher result.</p>
                        </div>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Quick start</h2>
                    <div class="mt-4 space-y-4 text-sm text-slate-700">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="font-semibold text-slate-900">1. Create an intent</p>
                            <p class="mt-1">Send <code>station_id</code>, <code>external_reference</code>, and one voucher identifier such as <code>scan_input</code>, <code>code</code>, or <code>voucher_id</code>.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="font-semibold text-slate-900">2. Authorize the payment</p>
                            <p class="mt-1">Bwiser confirms the voucher is valid, belongs to the station, is not expired, and passes any active geofence rule.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="font-semibold text-slate-900">3. Redeem and reconcile</p>
                            <p class="mt-1">Redeem the authorized intent and store the returned voucher payload as your source of truth for completion.</p>
                        </div>
                    </div>

                    <h3 class="mt-6 text-sm font-semibold text-slate-900">Example request</h3>
                    <pre class="mt-3 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100"><code>curl -X POST "{{ $baseUrl }}/api/v1/partner/tapless/intents" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-H "X-Bwiser-Key: YOUR_PUBLIC_KEY" \
-H "X-Bwiser-Timestamp: 1713388800" \
-H "X-Bwiser-Signature: YOUR_HMAC_SIGNATURE" \
-d '{
  "station_id": 12,
  "external_reference": "agg-forecourt-10001",
  "voucher_id": 8451,
  "device_latitude": -26.2041,
  "device_longitude": 28.0473,
  "pump_number": "P3"
}'</code></pre>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
