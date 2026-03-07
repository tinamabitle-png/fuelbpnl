@extends('Layouts.admin')

@section('title', 'Risk Reports')
@section('page-title', 'Risk Reports')
@section('page-description', 'Default, overdue and anomaly monitoring insights')
@section('breadcrumb', 'Reports / Risk')

@section('content')
<div class="p-6 space-y-6">
    <form method="GET" class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col md:flex-row gap-3 md:items-end">
        <div><label class="text-xs text-gray-500">From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg"></div>
        <div><label class="text-xs text-gray-500">To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg"></div>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">Apply</button>
        <div class="md:ml-auto flex gap-2">
            <a href="{{ route('admin.reports.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg">Overview</a>
            <a href="{{ route('admin.reports.financial', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg">Financial</a>
            <a href="{{ route('admin.reports.export', ['type' => 'risk', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Export CSV</a>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Flagged Users</p><p class="text-2xl font-bold text-amber-700">{{ number_format($userRiskCounts['flagged']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Blocked Users</p><p class="text-2xl font-bold text-red-700">{{ number_format($userRiskCounts['blocked']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Suspended Users</p><p class="text-2xl font-bold">{{ number_format($userRiskCounts['suspended']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Active Leases</p><p class="text-2xl font-bold">{{ number_format($leaseRiskCounts['active']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Defaulted Leases</p><p class="text-2xl font-bold text-red-700">{{ number_format($leaseRiskCounts['defaulted']) }}</p></div>
        <div class="bg-white border border-gray-200 rounded-xl p-4"><p class="text-xs text-gray-500">Completed Leases</p><p class="text-2xl font-bold">{{ number_format($leaseRiskCounts['completed']) }}</p></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <h3 class="font-semibold mb-4">Monthly Lease Default Rate (%)</h3>
            <canvas id="defaultRateChart" height="120"></canvas>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <h3 class="font-semibold mb-4">Anomaly Checks (Daily)</h3>
            @if(!$anomalyEnabled)
                <p class="text-sm text-amber-700">Anomaly checks table not migrated yet. Run migrations to enable this chart.</p>
            @endif
            <canvas id="anomalyChart" height="120"></canvas>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <h3 class="font-semibold mb-4">Most Overdue Repayments</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pr-3">Repayment ID</th><th class="py-2 pr-3">User</th><th class="py-2 pr-3">Due Date</th><th class="py-2 pr-3">Status</th><th class="py-2 pr-3">Amount</th></tr></thead>
                <tbody>
                    @forelse($overdueRepayments as $row)
                        <tr class="border-b">
                            <td class="py-2 pr-3">#{{ $row->id }}</td>
                            <td class="py-2 pr-3">{{ $row->user->name ?? 'Unknown' }}</td>
                            <td class="py-2 pr-3">{{ optional($row->due_date)->format('Y-m-d') }}</td>
                            <td class="py-2 pr-3">{{ ucfirst($row->status) }}</td>
                            <td class="py-2 pr-3">R {{ number_format((float) $row->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3 text-gray-500">No overdue repayments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    new Chart(document.getElementById('defaultRateChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($charts['defaultLabels']),
            datasets: [{ label: 'Default Rate %', data: @json($charts['defaultRates']), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.15)' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
    });

    new Chart(document.getElementById('anomalyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($charts['anomalyLabels']),
            datasets: [
                { label: 'Flagged', data: @json($charts['anomalyFlagged']), backgroundColor: 'rgba(245,158,11,0.75)' },
                { label: 'Total Checks', data: @json($charts['anomalyTotal']), backgroundColor: 'rgba(59,130,246,0.45)' },
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush
@endsection
