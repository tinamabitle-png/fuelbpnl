@extends('Layouts.admin')

@section('title', 'Voucher Live Monitor')
@section('page-title', 'Voucher Live Monitor')
@section('page-description', 'Realtime redemption stream with AI anomaly flags')
@section('breadcrumb', 'Vouchers / Live Monitor')

@push('styles')
<style>
    .risk-pill { border-radius: 9999px; padding: 0.15rem 0.6rem; font-size: 0.75rem; font-weight: 700; }
    .risk-high { background: #fee2e2; color: #991b1b; }
    .risk-mid { background: #fef3c7; color: #92400e; }
    .risk-low { background: #dcfce7; color: #166534; }
</style>
@endpush

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white rounded-2xl border border-gray-200 p-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Real-Time Voucher Redemptions</h2>
            <p class="text-sm text-gray-600">Every redemption is scored and flagged using anomaly analysis.</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="monitorStatus" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                Connecting...
            </span>
            <a href="{{ route('admin.vouchers.index') }}" class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200">
                Back to vouchers
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Events loaded</p>
            <p id="eventsCount" class="text-2xl font-bold text-gray-900 mt-2">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Flagged</p>
            <p id="flaggedCount" class="text-2xl font-bold text-red-700 mt-2">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Latest risk score</p>
            <p id="latestRisk" class="text-2xl font-bold text-gray-900 mt-2">-</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Redemption Stream</h3>
            <p class="text-xs text-gray-500">Newest on top</p>
        </div>
        <div id="eventsContainer" class="divide-y divide-gray-100"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
    const initialEvents = @json($initialEvents);
    const wsConfig = @json($wsConfig);
    const recheckRouteTemplate = @json($recheckRouteTemplate);

    const eventsContainer = document.getElementById('eventsContainer');
    const eventsCount = document.getElementById('eventsCount');
    const flaggedCount = document.getElementById('flaggedCount');
    const latestRisk = document.getElementById('latestRisk');
    const monitorStatus = document.getElementById('monitorStatus');

    let items = Array.isArray(initialEvents) ? initialEvents : [];

    function riskClass(score) {
        if (score >= 70) return 'risk-high';
        if (score >= 40) return 'risk-mid';
        return 'risk-low';
    }

    function render() {
        eventsCount.textContent = String(items.length);
        const flagged = items.filter(item => item?.anomaly?.flagged).length;
        flaggedCount.textContent = String(flagged);
        latestRisk.textContent = items.length ? String(Math.round(Number(items[0]?.anomaly?.risk_score || 0))) : '-';

        eventsContainer.innerHTML = items.map(item => {
            const score = Number(item?.anomaly?.risk_score || 0);
            const reasons = Array.isArray(item?.anomaly?.reasons) ? item.anomaly.reasons : [];
            const redeemedAt = item?.redeemed_at ? new Date(item.redeemed_at).toLocaleString() : 'N/A';
            return `
                <div class="px-5 py-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="font-semibold text-gray-900">Voucher ${item.voucher_code || ('#' + item.voucher_id)} · R${Number(item.amount || 0).toFixed(2)}</p>
                            <p class="text-sm text-gray-600">${item.user?.name || 'Unknown user'} at ${item.station?.name || 'Unknown station'} (${item.station?.city || 'N/A'})</p>
                            <p class="text-xs text-gray-500 mt-1">Redeemed: ${redeemedAt} · Fuel: ${item.fuel_type || '-'} · Pump: ${item.pump_number || '-'}</p>
                        </div>
                        <div class="text-left md:text-right">
                            <span class="risk-pill ${riskClass(score)}">Risk ${Math.round(score)}</span>
                            <p class="text-xs mt-2 ${item?.anomaly?.flagged ? 'text-red-700 font-semibold' : 'text-green-700 font-semibold'}">
                                ${item?.anomaly?.flagged ? 'Flagged for review' : 'No flag'}
                            </p>
                            <button type="button" data-voucher-id="${item.voucher_id}" class="recheck-btn mt-2 text-xs px-3 py-1 rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200">
                                Re-check
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        ${reasons.length ? reasons.map(reason => `<span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">${reason}</span>`).join('') : '<span class="text-xs text-gray-500">No reasons</span>'}
                    </div>
                </div>
            `;
        }).join('');
    }

    function upsertEvent(payload) {
        if (!payload || !payload.voucher_id) {
            return;
        }
        items = [payload, ...items.filter(item => item.voucher_id !== payload.voucher_id)].slice(0, 100);
        render();
    }

    function setStatus(text, classes) {
        monitorStatus.textContent = text;
        monitorStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${classes}`;
    }

    render();

    eventsContainer.addEventListener('click', async (event) => {
        const button = event.target.closest('.recheck-btn');
        if (!button) {
            return;
        }
        const voucherId = button.getAttribute('data-voucher-id');
        if (!voucherId) {
            return;
        }
        button.disabled = true;
        button.textContent = 'Checking...';
        try {
            const url = recheckRouteTemplate.replace('__VOUCHER_ID__', String(voucherId));
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            });
            const payload = await response.json();
            if (response.ok && payload?.success && payload?.data) {
                upsertEvent(payload.data);
            }
        } catch (error) {
            console.error('Re-check failed', error);
        } finally {
            button.disabled = false;
            button.textContent = 'Re-check';
        }
    });

    if (!wsConfig.appKey) {
        setStatus('Realtime key missing', 'bg-red-100 text-red-700');
    } else {
        const useTLS = wsConfig.scheme === 'https';
        const pusher = new Pusher(wsConfig.appKey, {
            wsHost: wsConfig.host,
            wsPort: Number(wsConfig.port),
            wssPort: Number(wsConfig.port),
            forceTLS: useTLS,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            authEndpoint: wsConfig.authEndpoint,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            },
        });

        pusher.connection.bind('connected', () => setStatus('Live connected', 'bg-green-100 text-green-700'));
        pusher.connection.bind('error', () => setStatus('Connection error', 'bg-red-100 text-red-700'));
        pusher.connection.bind('unavailable', () => setStatus('Server unavailable', 'bg-yellow-100 text-yellow-700'));

        const channel = pusher.subscribe('private-admin.vouchers.monitor');
        channel.bind('pusher:subscription_succeeded', () => setStatus('Subscribed', 'bg-green-100 text-green-700'));
        channel.bind('voucher.redeemed.monitored', (payload) => upsertEvent(payload));
        channel.bind('voucher.status.changed', (payload) => upsertEvent(payload));
    }
</script>
@endpush
