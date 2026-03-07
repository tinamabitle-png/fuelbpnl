@extends('Layouts.app')

@section('title', 'Developer API Docs')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8 overflow-hidden relative">
        <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-16 w-56 h-56 rounded-full bg-sky-400/10 blur-3xl pointer-events-none"></div>
        <h1 class="brand-font text-2xl text-slate-900">Developer API Documentation</h1>
        <p class="text-slate-600 mt-2">Integrate your petroleum systems directly with Bwiser safely.</p>
        @include('merchant.partials.nav')

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-slate-900">Base URL</h2>
                <code class="mt-3 block rounded-lg bg-slate-900 text-slate-100 px-3 py-2 text-xs overflow-x-auto">{{ $baseUrl }}/api/v1</code>
                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                    <p class="text-sm font-semibold text-blue-900">Sandbox is available</p>
                    <p class="text-xs text-blue-700 mt-1">Use `/merchant/developer/sandbox/*` endpoints to validate integrations safely without mutating production data.</p>
                </div>

                <h3 class="text-sm font-semibold text-slate-900 mt-6">Authentication</h3>
                <p class="text-sm text-slate-600 mt-2">Use the credential token in the `Authorization` header:</p>
                <code class="mt-2 block rounded-lg bg-slate-900 text-slate-100 px-3 py-2 text-xs overflow-x-auto">Authorization: Bearer {token}</code>

                <h3 class="text-sm font-semibold text-slate-900 mt-6">Available Scopes</h3>
                <ul class="mt-2 text-sm text-slate-700 space-y-1">
                    @foreach($abilities as $ability)
                        <li>• {{ $ability }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="glass rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-slate-900">Endpoints</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p><span class="font-mono text-blue-700">GET</span> `/merchant/developer/stations`</p>
                        <p class="text-slate-600 mt-1">List stations you can access.</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p><span class="font-mono text-blue-700">GET</span> `/merchant/developer/summary`</p>
                        <p class="text-slate-600 mt-1">Totals for voucher counts and values.</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p><span class="font-mono text-blue-700">GET</span> `/merchant/developer/vouchers`</p>
                        <p class="text-slate-600 mt-1">List vouchers by station/status/date filters.</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p><span class="font-mono text-blue-700">GET</span> `/merchant/developer/vouchers/latest`</p>
                        <p class="text-slate-600 mt-1">Return latest 4 vouchers for merchant stations.</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p><span class="font-mono text-emerald-700">POST</span> `/merchant/developer/vouchers/redeem`</p>
                        <p class="text-slate-600 mt-1">Redeem voucher by `code` or `voucher_id`.</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p><span class="font-mono text-blue-700">GET</span> `/merchant/developer/repayments`</p>
                        <p class="text-slate-600 mt-1">Read repayments linked to your station vouchers.</p>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                        <p><span class="font-mono text-blue-700">SANDBOX</span> `/merchant/developer/sandbox/*`</p>
                        <p class="text-blue-700 mt-1">Test calls with realistic responses while keeping production data untouched.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 mt-6 developer-lab" x-data="developerSandboxLab()">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Interactive API Explorer</h2>
                    <p class="text-sm text-slate-600 mt-1">Switch between Live and Sandbox, generate cURL, and preview responses.</p>
                </div>
                <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200">
                    <button type="button" @click="mode='live'" class="px-3 py-1.5 text-xs rounded-lg transition" :class="mode==='live' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'">Live</button>
                    <button type="button" @click="mode='sandbox'" class="px-3 py-1.5 text-xs rounded-lg transition" :class="mode==='sandbox' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'">Sandbox</button>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Endpoint</label>
                    <select x-model="endpointKey" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <template x-for="(ep, key) in filteredEndpoints" :key="key">
                            <option :value="key" x-text="`${ep.method} ${ep.path}`"></option>
                        </template>
                    </select>

                    <div class="mt-4">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">JSON Body</label>
                        <textarea x-model="requestBody" class="w-full min-h-40 rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <button type="button" @click="generateSampleResponse()" class="btn-primary px-4 py-2 rounded-xl text-xs font-semibold">Generate Sample Response</button>
                        <button type="button" @click="copyCurl()" class="btn-ghost px-4 py-2 rounded-xl text-xs font-semibold">Copy cURL</button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-950 text-slate-100 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">cURL</p>
                        <pre class="mt-2 text-xs overflow-x-auto whitespace-pre-wrap" x-text="curlCommand"></pre>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Sample Response</p>
                        <pre class="mt-2 text-xs text-slate-800 overflow-x-auto whitespace-pre-wrap" x-text="sampleResponse"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
<script>
    function developerSandboxLab() {
        const baseUrl = @json($baseUrl . '/api/v1');
        const endpoints = {
            stations: { method: 'GET', path: '/merchant/developer/stations', body: null },
            summary: { method: 'GET', path: '/merchant/developer/summary', body: null },
            vouchers_list: { method: 'GET', path: '/merchant/developer/vouchers?status=issued', body: null },
            vouchers_latest: { method: 'GET', path: '/merchant/developer/vouchers/latest', body: null },
            voucher_redeem: {
                method: 'POST',
                path: '/merchant/developer/vouchers/redeem',
                body: {
                    code: 'VCH-EXAMPLE-123',
                    pump_number: 'P1',
                }
            },
            repayments: { method: 'GET', path: '/merchant/developer/repayments?status=pending', body: null },
            sbx_health: { method: 'GET', path: '/merchant/developer/sandbox/health', body: null, sandboxOnly: true },
            sbx_stations: { method: 'GET', path: '/merchant/developer/sandbox/stations', body: null, sandboxOnly: true },
            sbx_vouchers: { method: 'GET', path: '/merchant/developer/sandbox/vouchers', body: null, sandboxOnly: true },
            sbx_redeem: {
                method: 'POST',
                path: '/merchant/developer/sandbox/vouchers/redeem',
                body: {
                    code: 'SBX-VCH-1234',
                },
                sandboxOnly: true
            },
            sbx_repayments: { method: 'GET', path: '/merchant/developer/sandbox/repayments', body: null, sandboxOnly: true },
        };

        return {
            mode: 'sandbox',
            endpointKey: 'sbx_health',
            requestBody: '',
            sampleResponse: '{\n  "success": true\n}',
            get filteredEndpoints() {
                return Object.fromEntries(
                    Object.entries(endpoints).filter(([, endpoint]) => {
                        if (this.mode === 'sandbox') return !!endpoint.sandboxOnly;
                        return !endpoint.sandboxOnly;
                    })
                );
            },
            get currentEndpoint() {
                const selected = this.filteredEndpoints[this.endpointKey];
                if (selected) return selected;
                const fallback = Object.keys(this.filteredEndpoints)[0];
                this.endpointKey = fallback;
                return this.filteredEndpoints[fallback];
            },
            get curlCommand() {
                if (!this.currentEndpoint) return '';
                const method = this.currentEndpoint.method;
                const url = `${baseUrl}${this.currentEndpoint.path}`;
                let command = `curl -X ${method} "${url}" \\\n-H "Authorization: Bearer YOUR_TOKEN" \\\n-H "Accept: application/json"`;
                if (method !== 'GET') {
                    command += ` \\\n-H "Content-Type: application/json"`;
                    const body = this.requestBody?.trim() || '{}';
                    command += ` \\\n-d '${body.replace(/'/g, "\\'")}'`;
                }
                return command;
            },
            init() {
                this.$watch('mode', () => {
                    const first = Object.keys(this.filteredEndpoints)[0];
                    this.endpointKey = first;
                    this.seedBody();
                    this.generateSampleResponse();
                });
                this.$watch('endpointKey', () => {
                    this.seedBody();
                    this.generateSampleResponse();
                });
                this.seedBody();
                this.generateSampleResponse();
            },
            seedBody() {
                const body = this.currentEndpoint?.body;
                this.requestBody = body ? JSON.stringify(body, null, 2) : '';
            },
            copyCurl() {
                navigator.clipboard?.writeText(this.curlCommand);
            },
            generateSampleResponse() {
                const now = new Date().toISOString();
                const isSandbox = this.mode === 'sandbox';
                const key = this.endpointKey;
                let payload = { success: true, timestamp: now };
                if (key.includes('health')) {
                    payload = { success: true, mode: isSandbox ? 'sandbox' : 'live', message: isSandbox ? 'Sandbox online' : 'Live endpoint ready', timestamp: now };
                } else if (key.includes('stations')) {
                    payload = {
                        success: true,
                        mode: isSandbox ? 'sandbox' : 'live',
                        data: [
                            { id: 12, name: isSandbox ? 'Demo Station (Sandbox)' : 'Downtown Station', city: 'Johannesburg', status: 'active' },
                        ],
                    };
                } else if (key.includes('summary')) {
                    payload = {
                        success: true,
                        mode: isSandbox ? 'sandbox' : 'live',
                        data: { total_count: 24, total_value: 19840.5, issued_count: 4, approved_count: 7, redeemed_count: 13, redeemed_value: 11200.0 },
                    };
                } else if (key.includes('redeem')) {
                    payload = {
                        success: true,
                        mode: isSandbox ? 'sandbox' : 'live',
                        message: isSandbox ? 'Simulated redemption. No production write.' : 'Voucher redeemed.',
                        data: { status: 'redeemed', redeemed_at: now },
                    };
                } else if (key.includes('repayments')) {
                    payload = {
                        success: true,
                        mode: isSandbox ? 'sandbox' : 'live',
                        data: [{ id: 501, amount: 240.5, status: 'pending', due_date: '2026-03-01' }],
                    };
                }
                this.sampleResponse = JSON.stringify(payload, null, 2);
            },
        };
    }
</script>

<style>
    .developer-lab {
        background:
            radial-gradient(80% 110% at 10% 0%, rgba(59, 130, 246, 0.06), transparent 55%),
            radial-gradient(75% 100% at 90% 100%, rgba(14, 165, 233, 0.08), transparent 60%),
            #fff;
    }
</style>
@endsection
