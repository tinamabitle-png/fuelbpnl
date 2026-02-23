@extends('layouts.admin')

@section('title', 'Financial Reports')
@section('page-title', 'Financial Reports')
@section('page-description', 'Revenue, repayment and exposure analytics')
@section('breadcrumb', 'Reports / Financial')

@section('content')
<div class="p-6 space-y-6">
    <form method="GET" class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col md:flex-row gap-3 md:items-end">
        <div><label class="text-xs text-gray-500">From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg"></div>
        <div><label class="text-xs text-gray-500">To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg"></div>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">Apply</button>
        <div class="md:ml-auto flex gap-2">
            <a href="{{ route('admin.reports.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg">Overview</a>
            <a href="{{ route('admin.reports.risk', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg">Risk</a>
            <a href="{{ route('admin.reports.export', ['type' => 'financial', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Export CSV</a>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Paid Repayments</p><p class="text-2xl font-bold">R {{ number_format($summary['paid_repayments'], 2) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Overdue Amount</p><p class="text-2xl font-bold text-red-700">R {{ number_format($summary['overdue_amount'], 2) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Lease Exposure</p><p class="text-2xl font-bold">R {{ number_format($summary['lease_exposure'], 2) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Active Exposure</p><p class="text-2xl font-bold">R {{ number_format($summary['active_lease_exposure'], 2) }}</p></div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <h3 class="font-semibold mb-4">Monthly Financial Flows</h3>
        <canvas id="financialFlowChart" height="120"></canvas>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <h3 class="font-semibold mb-4">Top Station Wallet Balances</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pr-3">Station</th><th class="py-2 pr-3">City</th><th class="py-2 pr-3">Wallet Balance</th><th class="py-2 pr-3">Total Settlements</th></tr></thead>
                <tbody>
                    @forelse($stationWallets as $station)
                        <tr class="border-b"><td class="py-2 pr-3">{{ $station->name }}</td><td class="py-2 pr-3">{{ $station->city }}</td><td class="py-2 pr-3">R {{ number_format((float) $station->wallet_balance,2) }}</td><td class="py-2 pr-3">R {{ number_format((float) $station->total_settlements,2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-3 text-gray-500">No station wallet data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    new Chart(document.getElementById('financialFlowChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($charts['labels']),
            datasets: [
                { label: 'Issued (R)', data: @json($charts['issuedAmounts']), backgroundColor: 'rgba(59,130,246,0.7)' },
                { label: 'Redeemed (R)', data: @json($charts['redeemedAmounts']), backgroundColor: 'rgba(16,185,129,0.7)' },
                { label: 'Repayments Paid (R)', data: @json($charts['repaymentAmounts']), backgroundColor: 'rgba(168,85,247,0.7)' },
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush
@endsection
