@extends('Layouts.app')

@section('title', 'Energy Subscription Details')

@section('content')
<section class="max-w-5xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8 space-y-6">
        @include('merchant.partials.nav')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="brand-font text-2xl text-slate-900">Subscription #{{ $energySubscription->id }}</h1>
                <p class="text-slate-600">{{ $energySubscription->user?->name ?? 'Driver' }} • {{ ucfirst(str_replace('_', ' ', $energySubscription->status)) }}</p>
            </div>
            <div class="flex gap-3">
                @if($energySubscription->status === 'pending_approval')
                    <form method="POST" action="{{ route('merchant.energy-subscriptions.approve', $energySubscription) }}">
                        @csrf
                        <button class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('merchant.energy-subscriptions.reject', $energySubscription) }}">
                        @csrf
                        <button class="btn-ghost px-4 py-2 rounded-xl text-sm font-semibold">Reject</button>
                    </form>
                @endif
                <a href="{{ route('merchant.energy-subscriptions.index') }}" class="btn-ghost px-4 py-2 rounded-xl text-sm font-semibold">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Driver</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energySubscription->user?->name ?? '—' }}</p>
                <p class="text-xs text-slate-500">{{ $energySubscription->user?->email }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Project</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energySubscription->project?->title ?? '—' }}</p>
                <p class="text-xs text-slate-500">{{ $energySubscription->station?->name ?? '—' }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Approval</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energySubscription->approver?->name ?? 'Pending' }}</p>
                <p class="text-xs text-slate-500">{{ $energySubscription->approved_at?->format('Y-m-d') ?? '—' }}</p>
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <h2 class="text-lg text-slate-900 font-semibold">Financial Terms</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 text-sm text-slate-600">
                <div><span class="text-slate-500">Principal:</span> ZAR {{ number_format($energySubscription->principal_amount, 2) }}</div>
                <div><span class="text-slate-500">Interest Rate:</span> {{ number_format($energySubscription->interest_rate, 2) }}%</div>
                <div><span class="text-slate-500">Total Amount:</span> ZAR {{ number_format($energySubscription->total_amount, 2) }}</div>
                <div><span class="text-slate-500">Monthly Payment:</span> ZAR {{ number_format($energySubscription->monthly_payment, 2) }}</div>
                <div><span class="text-slate-500">Term:</span> {{ $energySubscription->term_months }} months</div>
                <div><span class="text-slate-500">Platform Fee:</span> ZAR {{ number_format($energySubscription->platform_fee_amount, 2) }}</div>
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <h2 class="text-lg text-slate-900 font-semibold">Repayment Schedule</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="py-2 pr-4">#</th>
                            <th class="py-2 pr-4">Due Date</th>
                            <th class="py-2 pr-4">Amount</th>
                            <th class="py-2 pr-4">Fee</th>
                            <th class="py-2 pr-4">Net</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($energySubscription->repayments->sortBy('due_date') as $repayment)
                            <tr>
                                <td class="py-3 pr-4">{{ data_get($repayment->metadata, 'sequence') ?? $loop->iteration }}</td>
                                <td class="py-3 pr-4">{{ $repayment->due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="py-3 pr-4">ZAR {{ number_format($repayment->amount, 2) }}</td>
                                <td class="py-3 pr-4">ZAR {{ number_format($repayment->platform_fee_amount ?? 0, 2) }}</td>
                                <td class="py-3 pr-4">ZAR {{ number_format($repayment->net_amount ?? 0, 2) }}</td>
                                <td class="py-3 pr-4">{{ ucfirst($repayment->status) }}</td>
                                <td class="py-3">{{ $repayment->paid_at?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-5 text-center text-slate-500">No repayment schedule yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
