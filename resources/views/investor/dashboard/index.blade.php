@extends('Layouts.app')

@section('title', 'Investor Dashboard - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Investor Portfolio</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">Investor Dashboard</h1>
            <p class="text-slate-600 mt-3">Track returns, watch active leases, and allocate available capital with control.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('investor.opportunities') }}" class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold">Explore Opportunities</a>
            <a href="{{ route('investor.investments') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">All Investments</a>
            <a href="{{ route('investor.profile') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Profile</a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Total Invested</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) ($stats['total_invested'] ?? 0), 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Available Capital</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) ($stats['available_capital'] ?? 0), 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Interest Earned</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) ($stats['interest_earned'] ?? 0), 2) }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Active Investments</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['active_investments'] ?? 0 }}</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <h2 class="brand-font text-xl text-slate-900">Recent Investments</h2>
                <a href="{{ route('investor.investments') }}" class="text-sm text-blue-600 hover:text-blue-700">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentInvestments as $investment)
                    @php
                        $leaseDueDate = $investment->lease?->due_date;
                        $formattedLeaseDate = $leaseDueDate ? \Illuminate\Support\Carbon::parse($leaseDueDate)->format('d M Y') : 'N/A';
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Lease #{{ $investment->lease_id }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ $investment->lease?->user?->name ?? 'Unknown Driver' }} • Due {{ $formattedLeaseDate }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-900">R {{ number_format((float) ($investment->amount_invested ?? 0), 2) }}</p>
                                <span class="inline-flex mt-1 px-2 py-1 rounded-full text-[11px] font-semibold uppercase {{ ($investment->status ?? '') === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $investment->status ?? 'unknown' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No recent investments found.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <h2 class="brand-font text-xl text-slate-900">Recent Returns</h2>
                    @if(Route::has('investor.statements'))
                        <a href="{{ route('investor.statements') }}" class="text-sm text-blue-600 hover:text-blue-700">Statements</a>
                    @endif
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($recentReturns as $return)
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900">Lease #{{ $return->lease_id }}</p>
                                <p class="text-sm font-semibold text-slate-900">R {{ number_format((float) ($return->interest_earned ?? 0), 2) }}</p>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Status: {{ ucfirst($return->status ?? 'pending') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No returns booked yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="glass rounded-2xl p-6">
                <h2 class="brand-font text-xl text-slate-900">Upcoming Maturities</h2>
                <div class="mt-4 space-y-3">
                    @forelse($upcomingReturns as $upcoming)
                        @php
                            $maturityDate = $upcoming->lease?->due_date;
                            $formattedMaturityDate = $maturityDate ? \Illuminate\Support\Carbon::parse($maturityDate)->format('d M Y') : 'N/A';
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900">Lease #{{ $upcoming->lease_id }}</p>
                                <p class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold uppercase">{{ $upcoming->status ?? 'active' }}</p>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Expected due date: {{ $formattedMaturityDate }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No maturities in the next 7 days.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
