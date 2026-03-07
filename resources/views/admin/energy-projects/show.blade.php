@extends('Layouts.admin')

@section('title', 'Energy Project Details')
@section('page-title', 'Energy Project Details')
@section('page-description', 'View renewable energy project overview')
@section('breadcrumb', 'Energy Projects / View')

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $energyProject->title }}</h2>
                <p class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $energyProject->project_type)) }} • {{ ucfirst($energyProject->status) }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.energy-projects.edit', $energyProject) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg">Edit</a>
                <a href="{{ route('admin.energy-projects.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg">Back</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Station</h3>
            <p class="text-gray-700 mt-2">{{ $energyProject->station?->name ?? '—' }}</p>
            <p class="text-sm text-gray-500">{{ $energyProject->station?->address }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Owner</h3>
            <p class="text-gray-700 mt-2">{{ $energyProject->owner?->name ?? 'Unassigned' }}</p>
            <p class="text-sm text-gray-500">{{ $energyProject->owner?->phone }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Linked Asset</h3>
            <p class="text-gray-700 mt-2">{{ $energyProject->asset?->name ?? 'No asset linked' }}</p>
            <p class="text-sm text-gray-500">{{ $energyProject->asset?->asset_type ? ucfirst(str_replace('_', ' ', $energyProject->asset->asset_type)) : '' }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financing</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Asset Cost:</span> {{ $energyProject->total_cost ? 'ZAR ' . number_format($energyProject->total_cost, 2) : '—' }}</div>
            <div><span class="text-gray-500">Financed Amount:</span> {{ $energyProject->financed_amount ? 'ZAR ' . number_format($energyProject->financed_amount, 2) : '—' }}</div>
            <div><span class="text-gray-500">Interest Rate:</span> {{ $energyProject->interest_rate ? number_format($energyProject->interest_rate, 2) . '%' : '—' }}</div>
            <div><span class="text-gray-500">Term:</span> {{ $energyProject->term_months ? $energyProject->term_months . ' months' : '—' }}</div>
            <div><span class="text-gray-500">Monthly Payment:</span> {{ $energyProject->monthly_payment ? 'ZAR ' . number_format($energyProject->monthly_payment, 2) : '—' }}</div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Start Date:</span> {{ $energyProject->start_date?->format('Y-m-d') ?? '—' }}</div>
            <div><span class="text-gray-500">End Date:</span> {{ $energyProject->end_date?->format('Y-m-d') ?? '—' }}</div>
            <div><span class="text-gray-500">Activated At:</span> {{ $energyProject->activated_at?->format('Y-m-d') ?? '—' }}</div>
            <div><span class="text-gray-500">Completed At:</span> {{ $energyProject->completed_at?->format('Y-m-d') ?? '—' }}</div>
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
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Paid At</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($energyProject->repayments->sortBy('due_date') as $repayment)
                        <tr>
                            <td class="px-4 py-2">{{ data_get($repayment->metadata, 'sequence') ?? $loop->iteration }}</td>
                            <td class="px-4 py-2">{{ $repayment->due_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-2">ZAR {{ number_format($repayment->amount, 2) }}</td>
                            <td class="px-4 py-2">{{ ucfirst($repayment->status) }}</td>
                            <td class="px-4 py-2">{{ $repayment->paid_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                @if(in_array($repayment->status, ['pending', 'overdue'], true))
                                    <form method="POST" action="{{ route('payments.paystack.energy-repayment', $repayment) }}" class="inline-flex flex-wrap justify-end gap-2">
                                        @csrf
                                        <button name="payment_method" value="apple_pay" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-black text-white hover:bg-slate-800">
                                            <i class="fab fa-apple mr-1"></i> Apple Pay
                                        </button>
                                        <button name="payment_method" value="google_pay" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-300 text-gray-800 hover:bg-gray-50">
                                            <i class="fab fa-google mr-1"></i> Google Pay
                                        </button>
                                        <button name="payment_method" value="card" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700">Card</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No repayment schedule generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
