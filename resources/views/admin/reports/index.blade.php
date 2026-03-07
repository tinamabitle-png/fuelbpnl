@extends('Layouts.admin')

@section('title', 'Reports Overview')
@section('page-title', 'Reports Overview')
@section('page-description', 'Operational and voucher performance analytics')
@section('breadcrumb', 'Reports')

@section('content')
<div class="p-6 space-y-6">
    <form method="GET" class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col md:flex-row gap-3 md:items-end">
        <div>
            <label class="text-xs text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="text-xs text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">Apply</button>
        <div class="md:ml-auto flex gap-2">
            <a href="{{ route('admin.reports.financial', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg">Financial</a>
            <a href="{{ route('admin.reports.risk', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg">Risk</a>
            <a href="{{ route('admin.reports.export', ['type' => 'overview', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Export CSV</a>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Vouchers</p><p class="text-2xl font-bold">{{ number_format($summary['voucher_total']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Voucher Amount</p><p class="text-2xl font-bold">R {{ number_format($summary['voucher_amount'],2) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Approved</p><p class="text-2xl font-bold">{{ number_format($summary['approved_count']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Redeemed</p><p class="text-2xl font-bold">{{ number_format($summary['redeemed_count']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Settlements</p><p class="text-2xl font-bold">{{ number_format($summary['settlement_total']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Settlement Amount</p><p class="text-2xl font-bold">R {{ number_format($summary['settlement_amount'],2) }}</p></div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <h3 class="font-semibold mb-4">Guardrail Signals</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                <p class="text-xs text-gray-500">Voucher Blocks (Wallet Capacity)</p>
                <p class="text-2xl font-bold text-rose-700">{{ number_format((int) ($guardrails['voucher_blocks'] ?? 0)) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                <p class="text-xs text-gray-500">AutoPay Failures</p>
                <p class="text-2xl font-bold text-amber-700">{{ number_format((int) ($guardrails['autopay_failures'] ?? 0)) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                <p class="text-xs text-gray-500">AutoPay Disabled</p>
                <p class="text-2xl font-bold text-rose-700">{{ number_format((int) ($guardrails['autopay_disables'] ?? 0)) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-4 bg-slate-50">
                <p class="text-xs text-gray-500">Duplicate Payout Prevented</p>
                <p class="text-2xl font-bold text-blue-700">{{ number_format((int) ($guardrails['duplicate_payout_prevented'] ?? 0)) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <h3 class="font-semibold mb-4">Daily Voucher Activity</h3>
            <canvas id="voucherActivityChart" height="120"></canvas>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <h3 class="font-semibold mb-4">Voucher Status Distribution</h3>
            <canvas id="statusChart" height="120"></canvas>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <h3 class="font-semibold mb-4">Top Stations (Redeemed Amount)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2 pr-3">Station</th>
                        <th class="py-2 pr-3">City</th>
                        <th class="py-2 pr-3">Redeemed Count</th>
                        <th class="py-2 pr-3">Redeemed Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topStations as $station)
                        <tr class="border-b">
                            <td class="py-2 pr-3">{{ $station->name }}</td>
                            <td class="py-2 pr-3">{{ $station->city }}</td>
                            <td class="py-2 pr-3">{{ number_format($station->redeemed_count) }}</td>
                            <td class="py-2 pr-3">R {{ number_format((float) $station->redeemed_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-3 text-gray-500">No station data for selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const overviewLabels = @json($charts['labels']);
    const voucherCounts = @json($charts['voucherCounts']);
    const voucherAmounts = @json($charts['voucherAmounts']);
    const settlementAmounts = @json($charts['settlementAmounts']);
    const statusDistribution = @json($statusDistribution);

    new Chart(document.getElementById('voucherActivityChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: overviewLabels,
            datasets: [
                { label: 'Voucher Count', data: voucherCounts, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.15)', yAxisID: 'y' },
                { label: 'Voucher Amount (R)', data: voucherAmounts, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.15)', yAxisID: 'y1' },
                { label: 'Settlement Amount (R)', data: settlementAmounts, borderColor: '#a855f7', backgroundColor: 'rgba(168,85,247,0.15)', yAxisID: 'y1' },
            ]
        },
        options: { responsive: true, interaction: { mode: 'index', intersect: false }, stacked: false, scales: { y: { beginAtZero: true }, y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } } } }
    });

    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusDistribution),
            datasets: [{ data: Object.values(statusDistribution), backgroundColor: ['#3b82f6','#f59e0b','#10b981','#6b7280','#ef4444'] }]
        },
        options: { responsive: true }
    });
</script>
@endpush
@endsection
