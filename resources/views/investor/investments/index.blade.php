@extends('Layouts.app')

@section('title', 'Investor Investments - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Portfolio</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">My Investments</h1>
            <p class="text-slate-600 mt-3">Track funded leases, ownership percentage, expected returns, and maturity.</p>
        </div>
        <a href="{{ route('investor.opportunities') }}" class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold">Explore Opportunities</a>
    </div>

    <div class="mt-8 glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <th>Investment</th>
                        <th>Lease</th>
                        <th>Amount</th>
                        <th>Return</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($investments as $investment)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-900">#{{ $investment->id }}</p>
                                <p class="text-xs text-slate-500">{{ optional($investment->investment_date)->format('d M Y') }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-900">Lease #{{ $investment->lease_id }}</p>
                                <p class="text-xs text-slate-500">{{ $investment->lease?->user?->name ?? 'Unknown Driver' }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-900">R {{ number_format((float) $investment->amount_invested, 2) }}</p>
                                <p class="text-xs text-slate-500">{{ number_format((float) $investment->percentage_ownership, 2) }}% ownership</p>
                            </td>
                            <td>
                                <p class="font-semibold text-emerald-700">R {{ number_format((float) $investment->expected_interest, 2) }}</p>
                                <p class="text-xs text-slate-500">{{ number_format((float) $investment->interest_rate, 2) }}%</p>
                            </td>
                            <td>
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $investment->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($investment->status) }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('investor.investments.show', $investment) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500 py-10">No investments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $investments->links() }}</div>
</section>
@endsection
