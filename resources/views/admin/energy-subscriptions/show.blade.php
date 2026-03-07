@extends('Layouts.admin')

@section('title', 'Energy Subscription')
@section('page-title', 'Energy Subscription')
@section('page-description', 'Review subscription details and repayment schedule')
@section('breadcrumb', 'Energy Subscriptions / View')

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Subscription #{{ $energySubscription->id }}</h2>
                <p class="text-gray-600">
                    {{ $energySubscription->user?->name ?? 'Driver' }} • {{ ucfirst(str_replace('_', ' ', $energySubscription->status)) }}
                </p>
            </div>
            <div class="flex gap-3">
                @if($energySubscription->status === 'pending_approval')
                    <form method="POST" action="{{ route('admin.energy-subscriptions.approve', $energySubscription) }}">
                        @csrf
                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.energy-subscriptions.reject', $energySubscription) }}">
                        @csrf
                        <button class="px-4 py-2 border border-rose-300 text-rose-600 rounded-lg">Reject</button>
                    </form>
                @endif
                <a href="{{ route('admin.energy-subscriptions.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg">Back</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Driver</h3>
            <p class="text-gray-700 mt-2">{{ $energySubscription->user?->name ?? '—' }}</p>
            <p class="text-sm text-gray-500">{{ $energySubscription->user?->email }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Project</h3>
            <p class="text-gray-700 mt-2">{{ $energySubscription->project?->title ?? '—' }}</p>
            <p class="text-sm text-gray-500">{{ $energySubscription->station?->name ?? '—' }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Approval</h3>
            <p class="text-gray-700 mt-2">{{ $energySubscription->approver?->name ?? 'Pending' }}</p>
            <p class="text-sm text-gray-500">{{ $energySubscription->approved_at?->format('Y-m-d H:i') ?? '—' }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Terms</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Principal:</span> ZAR {{ number_format($energySubscription->principal_amount, 2) }}</div>
            <div><span class="text-gray-500">Interest Rate:</span> {{ number_format($energySubscription->interest_rate, 2) }}%</div>
            <div><span class="text-gray-500">Interest Amount:</span> ZAR {{ number_format($energySubscription->interest_amount, 2) }}</div>
            <div><span class="text-gray-500">Total Amount:</span> ZAR {{ number_format($energySubscription->total_amount, 2) }}</div>
            <div><span class="text-gray-500">Term:</span> {{ $energySubscription->term_months }} months</div>
            <div><span class="text-gray-500">Monthly Payment:</span> ZAR {{ number_format($energySubscription->monthly_payment, 2) }}</div>
            <div><span class="text-gray-500">Platform Fee (2%):</span> ZAR {{ number_format($energySubscription->platform_fee_amount, 2) }}</div>
            <div><span class="text-gray-500">Remaining Balance:</span> ZAR {{ number_format($energySubscription->remaining_balance, 2) }}</div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Repayment Schedule</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-gray-500 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Due Date</th>
                        <th class="px-4 py-2 text-left">Amount</th>
                        <th class="px-4 py-2 text-left">Fee</th>
                        <th class="px-4 py-2 text-left">Net</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Paid At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($energySubscription->repayments->sortBy('due_date') as $repayment)
                        <tr>
                            <td class="px-4 py-2">{{ data_get($repayment->metadata, 'sequence') ?? $loop->iteration }}</td>
                            <td class="px-4 py-2">{{ $repayment->due_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-2">ZAR {{ number_format($repayment->amount, 2) }}</td>
                            <td class="px-4 py-2">ZAR {{ number_format($repayment->platform_fee_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2">ZAR {{ number_format($repayment->net_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ ucfirst($repayment->status) }}</td>
                            <td class="px-4 py-2">{{ $repayment->paid_at?->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">No repayment schedule generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
