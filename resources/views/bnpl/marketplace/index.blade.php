@extends('Layouts.app')

@section('title', 'Order From Drivers - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Bwiser</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Order From Drivers</h1>
            <p class="text-sm text-slate-600 mt-2">Choose a participating driver and request a BNPL checkout.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('bnpl.orders.index', [], false) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
                My Orders
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 glass rounded-2xl border border-slate-200 bg-white p-5">
        <form method="GET" action="{{ route('bnpl.marketplace.index', [], false) }}" class="flex flex-col md:flex-row gap-3 md:items-end">
            <div class="flex-1">
                <label class="block text-xs uppercase tracking-[0.2em] text-slate-500">City (optional)</label>
                <input name="city" value="{{ $city }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="e.g. Johannesburg" />
            </div>
            <button type="submit" class="px-5 py-3 rounded-xl text-sm font-semibold text-white" style="background:#020DFF;">
                Search
            </button>
        </form>
    </div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($drivers as $driver)
            @php
                $platform = trim((string) ($driver->driver_platform_other ?: $driver->driver_platform));
            @endphp
            <div class="glass rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-semibold text-slate-900">{{ $driver->name }}</p>
                        <p class="text-sm text-slate-600 mt-1">
                            {{ $driver->city ?: 'South Africa' }}
                            @if($platform !== '')
                                <span class="text-slate-400">·</span> {{ strtoupper($platform) }}
                            @endif
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold uppercase">Active</span>
                </div>
                <p class="text-xs text-slate-500 mt-4">Request a BNPL order, then fulfill after driver confirmation.</p>
                <a href="{{ route('bnpl.marketplace.order.create', $driver, false) }}" class="mt-4 inline-flex w-full justify-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#020DFF;">
                    Order
                </a>
            </div>
        @empty
            <div class="glass rounded-2xl border border-slate-200 bg-white p-8 text-slate-700">
                No active drivers found.
            </div>
        @endforelse
    </div>
</section>
@endsection

