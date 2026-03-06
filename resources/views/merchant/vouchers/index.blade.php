@extends('layouts.app')

@section('title', 'Merchant Vouchers - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Merchant Voucher Ledger</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">All Vouchers</h1>
            <p class="text-slate-600 mt-3">Latest 4 plus complete voucher history, filtered by status and date.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('merchant.dashboard') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Dashboard</a>
        </div>
    </div>

    @include('merchant.partials.nav')

    @if(!$station)
        <div class="glass rounded-2xl p-6 mt-8">
            <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                No station is linked to this merchant account yet.
            </p>
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass rounded-2xl p-5">
                <p class="text-sm text-slate-500">Station</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $station->name }}</p>
            </div>
            <div class="glass rounded-2xl p-5">
                <p class="text-sm text-slate-500">Voucher Count</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totals['count'] }}</p>
            </div>
            <div class="glass rounded-2xl p-5">
                <p class="text-sm text-slate-500">Total Value</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) $totals['value'], 2) }}</p>
            </div>
            <div class="glass rounded-2xl p-5">
                <p class="text-sm text-slate-500">Redeemed Value</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">R {{ number_format((float) $totals['redeemed_value'], 2) }}</p>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 mt-8">
            <div class="flex items-center justify-between">
                <h2 class="brand-font text-xl text-slate-900">Latest 4 Vouchers</h2>
                <span class="text-xs text-slate-500">Quick view</span>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                @forelse($latestFour as $voucher)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $voucher->code }}</p>
                            <span class="text-[11px] px-2 py-1 rounded-full uppercase font-semibold {{ $voucher->status === 'approved' ? 'bg-blue-100 text-blue-700' : ($voucher->status === 'redeemed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">{{ $voucher->status }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">{{ $voucher->user?->name ?? 'Unknown Driver' }}</p>
                        <p class="text-sm text-slate-700 mt-1">R {{ number_format((float) $voucher->amount, 2) }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ optional($voucher->issued_at)->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No vouchers available yet.</p>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-2xl p-6 mt-8">
            <h2 class="brand-font text-xl text-slate-900">Filter Vouchers</h2>
            <form method="GET" action="{{ route('merchant.vouchers.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] }}"
                    class="rounded-xl border border-slate-300 px-3 py-2"
                    placeholder="Search code, QR, driver"
                >
                <select name="status" class="rounded-xl border border-slate-300 px-3 py-2">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-xl border border-slate-300 px-3 py-2">
                <input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-xl border border-slate-300 px-3 py-2">
                <button class="btn-primary rounded-xl px-4 py-2 text-sm font-semibold">Apply</button>
            </form>
        </div>

        <div class="glass rounded-2xl p-0 overflow-hidden mt-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Voucher</th>
                            <th class="px-4 py-3 text-left">Driver</th>
                            <th class="px-4 py-3 text-left">Amount</th>
                            <th class="px-4 py-3 text-left">Fuel</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Issued</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($vouchers as $voucher)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $voucher->code }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ $voucher->qr_code }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $voucher->user?->name ?? 'Unknown' }}
                                    @if($voucher->user?->phone)
                                        <p class="text-xs text-slate-500 mt-1">{{ $voucher->user->phone }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900">R {{ number_format((float) $voucher->amount, 2) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ ucfirst($voucher->fuel_type) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold uppercase {{ $voucher->status === 'approved' ? 'bg-blue-100 text-blue-700' : ($voucher->status === 'redeemed' ? 'bg-emerald-100 text-emerald-700' : ($voucher->status === 'expired' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')) }}">
                                        {{ $voucher->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($voucher->issued_at)->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No vouchers found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-4 border-t border-slate-200">{{ $vouchers->links() }}</div>
        </div>
    @endif
</section>

@endsection
