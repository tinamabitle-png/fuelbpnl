@extends('Layouts.app')

@section('title', 'My Vouchers - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">My Voucher Requests</h1>
        </div>
        <a href="{{ route('driver.vouchers.create') }}" class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold">Apply for New Voucher</a>
    </div>
    @include('driver.partials.nav', ['backUrl' => route('driver.dashboard')])

    @if(session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="glass rounded-2xl p-6 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select name="status" class="px-4 py-2.5 border border-slate-300 rounded-xl">
                <option value="">All statuses</option>
                @foreach(['issued', 'approved', 'redeemed', 'expired', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="brand" class="px-4 py-2.5 border border-slate-300 rounded-xl">
                <option value="">All brands</option>
                @foreach(($brands ?? collect()) as $brand)
                    <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
                @endforeach
            </select>
            <select name="station_id" class="px-4 py-2.5 border border-slate-300 rounded-xl">
                <option value="">All stations</option>
                @foreach($stations->groupBy(fn ($station) => trim((string) ($station->company ?? '')) ?: 'Other') as $brandName => $brandStations)
                    <optgroup label="{{ $brandName }}">
                        @foreach($brandStations as $station)
                            <option value="{{ $station->id }}" @selected((int) request('station_id') === $station->id)>
                                {{ $station->name }}{{ $station->city ? ' - ' . $station->city : '' }}{{ $station->address ? ' • ' . \Illuminate\Support\Str::limit($station->address, 36) : '' }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <button class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Filter</button>
        </form>
    </div>

    <div class="glass rounded-2xl mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Code</th>
                        <th class="px-4 py-3 text-left">QR Code</th>
                        <th class="px-4 py-3 text-left">Station</th>
                        <th class="px-4 py-3 text-left">Fuel</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">References</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Expires</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $voucher->code }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $qrValue = $voucher->qr_code ?: $voucher->code;
                                    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=96x96&margin=8&ecc=H&format=png&data=' . urlencode($qrValue);
                                    $blurQr = $voucher->status === 'issued';
                                @endphp
                                <div @class([
                                    'relative inline-flex items-center justify-center p-2 rounded-xl border-2 border-slate-300 bg-white shadow-sm bwiser-qr-stack',
                                    'approved-voucher-outline' => $voucher->status === 'approved',
                                ])>
                                    <img
                                        src="{{ $qrImage }}"
                                        alt="QR {{ $voucher->code }}"
                                        class="h-20 w-20 rounded-md bg-white {{ $blurQr ? 'blur-sm opacity-70' : '' }}"
                                        loading="lazy"
                                        onerror="this.style.display='none'; const fb=this.parentElement.querySelector('span.hidden'); if(fb) fb.style.display='grid';"
                                    >
                                    <span class="hidden h-20 w-20 place-items-center rounded-md border border-dashed border-slate-300 bg-slate-50 text-[10px] font-semibold text-slate-600 px-1 text-center {{ $blurQr ? 'blur-sm opacity-70' : '' }}">
                                        {{ $voucher->code }}
                                    </span>
                                    @if($blurQr)
                                        <span class="absolute inset-0 grid place-items-center rounded-xl bg-white/70">
                                            <span class="flex items-center justify-center">
                                                <svg class="pl approval-loader" width="240" height="240" viewBox="0 0 240 240" aria-hidden="true">
                                                    <circle class="pl__ring pl__ring--a" cx="120" cy="120" r="105" fill="none" stroke-width="20" stroke-dasharray="0 660" stroke-dashoffset="-330" stroke-linecap="round"></circle>
                                                    <circle class="pl__ring pl__ring--b" cx="120" cy="120" r="35" fill="none" stroke-width="20" stroke-dasharray="0 220" stroke-dashoffset="-110" stroke-linecap="round"></circle>
                                                    <circle class="pl__ring pl__ring--c" cx="85" cy="120" r="70" fill="none" stroke-width="20" stroke-dasharray="0 440" stroke-linecap="round"></circle>
                                                    <circle class="pl__ring pl__ring--d" cx="155" cy="120" r="70" fill="none" stroke-width="20" stroke-dasharray="0 440" stroke-linecap="round"></circle>
                                                </svg>
                                            </span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $voucher->fuelStation?->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ ucfirst($voucher->fuel_type) }}</td>
                            <td class="px-4 py-3 text-slate-700">R {{ number_format((float) $voucher->amount, 2) }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                @if($voucher->transaction_reference || $voucher->pump_number)
                                    <div class="space-y-1">
                                        @if($voucher->transaction_reference)
                                            <p class="text-xs">
                                                <span class="font-semibold text-slate-800">Voucher:</span>
                                                {{ $voucher->transaction_reference }}
                                            </p>
                                        @endif
                                        @if($voucher->pump_number)
                                            <p class="text-xs">
                                                <span class="font-semibold text-slate-800">Card:</span>
                                                {{ $voucher->pump_number }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 uppercase">
                                    {{ $voucher->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ optional($voucher->expires_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">No vouchers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 bg-white border-t border-slate-100">
            {{ $vouchers->links() }}
        </div>
    </div>
</section>

<style>
    .approved-voucher-outline {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        border-color: transparent;
    }

    .approved-voucher-outline::before {
        content: '';
        position: absolute;
        width: 68px;
        background-image: linear-gradient(180deg, rgb(0, 183, 255), rgb(255, 48, 255));
        height: 130%;
        animation: approvedRotBgImg 3s linear infinite;
        transition: all 0.2s linear;
        z-index: 0;
    }

    .approved-voucher-outline::after {
        content: '';
        position: absolute;
        background: #fff;
        inset: 3px;
        border-radius: 11px;
        z-index: 0;
    }

    .approved-voucher-outline > * {
        z-index: 1;
    }

    .approval-loader {
        width: 2.25rem;
        height: 2.25rem;
    }

    .approval-loader .pl__ring {
        animation: ringA 2s linear infinite;
    }

    .approval-loader .pl__ring--a {
        stroke: #1d4ed8;
    }

    .approval-loader .pl__ring--b {
        animation-name: ringB;
        stroke: #38bdf8;
    }

    .approval-loader .pl__ring--c {
        animation-name: ringC;
        stroke: #2563eb;
    }

    .approval-loader .pl__ring--d {
        animation-name: ringD;
        stroke: #93c5fd;
    }

    @keyframes approvedRotBgImg {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes ringA {
        from, 4% { stroke-dasharray: 0 660; stroke-width: 20; stroke-dashoffset: -330; }
        12% { stroke-dasharray: 60 600; stroke-width: 30; stroke-dashoffset: -335; }
        32% { stroke-dasharray: 60 600; stroke-width: 30; stroke-dashoffset: -595; }
        40%, 54% { stroke-dasharray: 0 660; stroke-width: 20; stroke-dashoffset: -660; }
        62% { stroke-dasharray: 60 600; stroke-width: 30; stroke-dashoffset: -665; }
        82% { stroke-dasharray: 60 600; stroke-width: 30; stroke-dashoffset: -925; }
        90%, to { stroke-dasharray: 0 660; stroke-width: 20; stroke-dashoffset: -990; }
    }

    @keyframes ringB {
        from, 12% { stroke-dasharray: 0 220; stroke-width: 20; stroke-dashoffset: -110; }
        20% { stroke-dasharray: 20 200; stroke-width: 30; stroke-dashoffset: -115; }
        40% { stroke-dasharray: 20 200; stroke-width: 30; stroke-dashoffset: -195; }
        48%, 62% { stroke-dasharray: 0 220; stroke-width: 20; stroke-dashoffset: -220; }
        70% { stroke-dasharray: 20 200; stroke-width: 30; stroke-dashoffset: -225; }
        90% { stroke-dasharray: 20 200; stroke-width: 30; stroke-dashoffset: -305; }
        98%, to { stroke-dasharray: 0 220; stroke-width: 20; stroke-dashoffset: -330; }
    }

    @keyframes ringC {
        from { stroke-dasharray: 0 440; stroke-width: 20; stroke-dashoffset: 0; }
        8% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -5; }
        28% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -175; }
        36%, 58% { stroke-dasharray: 0 440; stroke-width: 20; stroke-dashoffset: -220; }
        66% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -225; }
        86% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -395; }
        94%, to { stroke-dasharray: 0 440; stroke-width: 20; stroke-dashoffset: -440; }
    }

    @keyframes ringD {
        from, 8% { stroke-dasharray: 0 440; stroke-width: 20; stroke-dashoffset: 0; }
        16% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -5; }
        36% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -175; }
        44%, 50% { stroke-dasharray: 0 440; stroke-width: 20; stroke-dashoffset: -220; }
        58% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -225; }
        78% { stroke-dasharray: 40 400; stroke-width: 30; stroke-dashoffset: -395; }
        86%, to { stroke-dasharray: 0 440; stroke-width: 20; stroke-dashoffset: -440; }
    }
</style>
@endsection
