@extends('layouts.app')

@section('title', 'Developer Sandbox')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        <h1 class="brand-font text-2xl text-slate-900">Developer Sandbox</h1>
        <p class="text-slate-600 mt-2">Test merchant developer endpoints safely before production integration.</p>
        @include('merchant.partials.nav')

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-slate-900">Sandbox Runner</h2>
                <p class="text-sm text-slate-600 mt-1">Use a developer token from Credentials and run test calls.</p>

                <label class="block text-sm text-slate-700 mt-4 mb-1">Bearer Token</label>
                <input id="sandboxToken" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Paste token e.g. 1|...">

                <label class="block text-sm text-slate-700 mt-4 mb-1">Endpoint</label>
                <select id="sandboxEndpoint" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    <option value="GET:/api/v1/merchant/developer/sandbox/health">GET /sandbox/health</option>
                    <option value="GET:/api/v1/merchant/developer/sandbox/stations">GET /sandbox/stations</option>
                    <option value="GET:/api/v1/merchant/developer/sandbox/vouchers">GET /sandbox/vouchers</option>
                    <option value="GET:/api/v1/merchant/developer/sandbox/repayments">GET /sandbox/repayments</option>
                    <option value="POST:/api/v1/merchant/developer/sandbox/vouchers/redeem">POST /sandbox/vouchers/redeem</option>
                </select>

                <label class="block text-sm text-slate-700 mt-4 mb-1">JSON Body (for POST)</label>
                <textarea id="sandboxBody" class="w-full min-h-36 rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs">{
  "code": "SBX-VCH-1234"
}</textarea>

                <div class="mt-4 flex gap-2">
                    <button id="sandboxRun" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold">Run Request</button>
                    <button id="sandboxFill" class="btn-ghost px-4 py-2 rounded-xl text-sm font-semibold">Use Redeem Sample</button>
                </div>
            </div>

            <div class="glass rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-slate-900">Response</h2>
                <pre id="sandboxResult" class="mt-4 text-xs bg-slate-950 text-slate-100 rounded-xl p-4 overflow-x-auto min-h-80">{
  "info": "Awaiting request"
}</pre>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 mt-6">
            <h2 class="text-lg font-semibold text-slate-900">Live Merchant API Quick Links</h2>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <code class="rounded-lg bg-slate-900 text-slate-100 px-3 py-2">GET {{ $baseUrl }}/api/v1/merchant/developer/stations</code>
                <code class="rounded-lg bg-slate-900 text-slate-100 px-3 py-2">GET {{ $baseUrl }}/api/v1/merchant/developer/summary</code>
                <code class="rounded-lg bg-slate-900 text-slate-100 px-3 py-2">GET {{ $baseUrl }}/api/v1/merchant/developer/vouchers</code>
                <code class="rounded-lg bg-slate-900 text-slate-100 px-3 py-2">GET {{ $baseUrl }}/api/v1/merchant/developer/vouchers/latest</code>
                <code class="rounded-lg bg-slate-900 text-slate-100 px-3 py-2">POST {{ $baseUrl }}/api/v1/merchant/developer/vouchers/redeem</code>
                <code class="rounded-lg bg-slate-900 text-slate-100 px-3 py-2">GET {{ $baseUrl }}/api/v1/merchant/developer/repayments</code>
            </div>
        </div>
    </div>
</section>

<script>
    const endpointEl = document.getElementById('sandboxEndpoint');
    const tokenEl = document.getElementById('sandboxToken');
    const bodyEl = document.getElementById('sandboxBody');
    const resultEl = document.getElementById('sandboxResult');

    document.getElementById('sandboxFill').addEventListener('click', () => {
        endpointEl.value = 'POST:/api/v1/merchant/developer/sandbox/vouchers/redeem';
        bodyEl.value = JSON.stringify({ code: 'SBX-VCH-1234' }, null, 2);
    });

    document.getElementById('sandboxRun').addEventListener('click', async () => {
        const [method, path] = endpointEl.value.split(':');
        const token = tokenEl.value.trim();

        if (!token) {
            resultEl.textContent = JSON.stringify({ success: false, message: 'Provide Bearer token first.' }, null, 2);
            return;
        }

        const options = {
            method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        };

        if (method === 'POST') {
            options.body = bodyEl.value || '{}';
        }

        resultEl.textContent = 'Running...';

        try {
            const response = await fetch(path, options);
            const text = await response.text();
            let payload;

            try {
                payload = JSON.parse(text);
            } catch {
                payload = { raw: text };
            }

            resultEl.textContent = JSON.stringify({
                status: response.status,
                ok: response.ok,
                data: payload,
            }, null, 2);
        } catch (error) {
            resultEl.textContent = JSON.stringify({ success: false, message: error.message }, null, 2);
        }
    });
</script>
@endsection
