@extends('Layouts.app')

@section('title', 'Employee Dashboard - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Employee Operations</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">Employee Dashboard</h1>
            <p class="text-slate-600 mt-3">Monitor approvals, review flagged accounts, and keep voucher operations moving.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('employee.approvals') }}" class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold">Open Approvals</a>
            <a href="{{ route('admin.dashboard') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Admin View</a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Pending Approvals</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['pending_approvals'] ?? 0 }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Driver Accounts</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['total_users'] ?? 0 }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Flagged Users</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['flagged_users'] ?? 0 }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm text-slate-500">Today Vouchers</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['today_vouchers'] ?? 0 }}</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <h2 class="brand-font text-xl text-slate-900">Pending Voucher Queue</h2>
                <a href="{{ route('employee.approvals') }}" class="text-sm text-blue-600 hover:text-blue-700">Review all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($pending_vouchers as $voucher)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $voucher->code ?? ('#' . $voucher->id) }}</p>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $voucher->user?->name ?? 'Unknown Driver' }}
                                    @if($voucher->fuelStation)
                                        • {{ $voucher->fuelStation->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-900">R {{ number_format((float) $voucher->amount, 2) }}</p>
                                <span class="inline-flex mt-1 px-2 py-1 rounded-full text-[11px] font-semibold uppercase bg-amber-100 text-amber-700">
                                    {{ $voucher->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No vouchers awaiting review.</p>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <h2 class="brand-font text-xl text-slate-900">Flagged Accounts</h2>
                <span class="text-sm text-slate-500">Operational watchlist</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($flagged_users as $user)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ $user->email }}</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] font-semibold uppercase bg-rose-100 text-rose-700">
                                    {{ $user->status ?? 'flagged' }}
                                </span>
                                <p class="text-xs text-slate-500 mt-1">
                                    Wallet: R {{ number_format((float) ($user->wallet->available_balance ?? 0), 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No flagged users in the current window.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
