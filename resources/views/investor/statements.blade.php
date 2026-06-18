@extends('Layouts.app')

@section('title', 'Investor Statements - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Statements</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">Investment Statements</h1>
            <p class="text-slate-600 mt-3">Review investment activity and returns for the selected period.</p>
        </div>
        <a href="{{ route('investor.dashboard') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Dashboard</a>
    </div>

    <form method="GET" action="{{ route('investor.statements') }}" class="mt-8 glass rounded-2xl p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="text-sm font-semibold text-slate-700">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="mt-2 w-full px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-700">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="mt-2 w-full px-3 py-2">
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary w-full px-4 py-2.5 rounded-xl text-sm font-semibold">Filter</button>
        </div>
    </form>

    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Total Invested In Period</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) $totalInvested, 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Returns Booked In Period</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">R {{ number_format((float) $totalReturns, 2) }}</p>
        </div>
    </div>

    <div class="mt-8 glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <th>Lease</th>
                        <th>Investment Date</th>
                        <th>Invested</th>
                        <th>Interest Earned</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($investments as $investment)
                        <tr>
                            <td>#{{ $investment->lease_id }}</td>
                            <td>{{ optional($investment->investment_date)->format('d M Y') }}</td>
                            <td>R {{ number_format((float) $investment->amount_invested, 2) }}</td>
                            <td>R {{ number_format((float) $investment->interest_earned, 2) }}</td>
                            <td>{{ ucfirst($investment->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500 py-10">No statement activity found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
