@extends('layouts.app')

@section('title', 'Energy Project Details')

@section('content')
<section class="max-w-5xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8 space-y-6">
        @include('merchant.partials.nav')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="brand-font text-2xl text-slate-900">{{ $energyProject->title }}</h1>
                <p class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $energyProject->project_type)) }} • {{ ucfirst($energyProject->status) }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('merchant.energy-projects.edit', $energyProject) }}" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold">Edit</a>
                <a href="{{ route('merchant.energy-projects.index') }}" class="btn-ghost px-4 py-2 rounded-xl text-sm font-semibold">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Station</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energyProject->station?->name ?? '—' }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Linked Asset</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energyProject->asset?->name ?? 'No asset linked' }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Asset Cost</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energyProject->total_cost ? 'ZAR ' . number_format($energyProject->total_cost, 2) : '—' }}</p>
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <h2 class="text-lg text-slate-900 font-semibold">Financing</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 text-sm text-slate-600">
                <div><span class="text-slate-500">Asset Cost:</span> {{ $energyProject->total_cost ? 'ZAR ' . number_format($energyProject->total_cost, 2) : '—' }}</div>
                <div><span class="text-slate-500">Interest Rate:</span> {{ $energyProject->interest_rate ? number_format($energyProject->interest_rate, 2) . '%' : '—' }}</div>
                <div><span class="text-slate-500">Term:</span> {{ $energyProject->term_months ? $energyProject->term_months . ' months' : '—' }}</div>
                <div><span class="text-slate-500">Monthly Payment:</span> {{ $energyProject->monthly_payment ? 'ZAR ' . number_format($energyProject->monthly_payment, 2) : '—' }}</div>
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
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2">Paid At</th>
                            <th class="py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($energyProject->repayments->sortBy('due_date') as $repayment)
                            <tr>
                                <td class="py-3 pr-4">{{ data_get($repayment->metadata, 'sequence') ?? $loop->iteration }}</td>
                                <td class="py-3 pr-4">{{ $repayment->due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="py-3 pr-4">ZAR {{ number_format($repayment->amount, 2) }}</td>
                                <td class="py-3 pr-4">{{ ucfirst($repayment->status) }}</td>
                                <td class="py-3">{{ $repayment->paid_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="py-3 text-right">
                                    @if(in_array($repayment->status, ['pending', 'overdue'], true))
                                        <form method="POST" action="{{ route('payments.paystack.energy-repayment', $repayment) }}" class="inline-flex flex-wrap justify-end gap-2">
                                            @csrf
                                            <button name="payment_method" value="apple_pay" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-black text-white hover:bg-slate-800">
                                                <i class="fab fa-apple mr-1"></i> Apple Pay
                                            </button>
                                            <button name="payment_method" value="google_pay" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-800 hover:bg-slate-50">
                                                <i class="fab fa-google mr-1"></i> Google Pay
                                            </button>
                                            <button name="payment_method" value="card" class="btn-primary px-3 py-1.5 rounded-lg text-xs font-semibold">Card</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-slate-500">No repayment schedule yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
