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
            <table class="min-w-full text-[13px] sm:text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left whitespace-nowrap">Code</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left whitespace-nowrap">QR</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left">Station</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left whitespace-nowrap">Fuel</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left whitespace-nowrap">Amount</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left">Refs</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left whitespace-nowrap">Status</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left whitespace-nowrap">Expires</th>
                        <th class="px-3 py-2.5 sm:px-4 sm:py-3 text-left whitespace-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($vouchers as $voucher)
                        @php
                            $isVirtualCardVoucher = \Illuminate\Support\Str::startsWith((string) ($voucher->transaction_reference ?? ''), 'VIRTUALCARD-');
                        @endphp
                        <tr>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 font-semibold text-slate-900 whitespace-nowrap">{{ $voucher->code }}</td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3">
                                @if($isVirtualCardVoucher)
                                    <div class="card-id567" tabindex="0" role="button" aria-label="Voucher {{ $voucher->code }} details">
                                        <svg shape-rendering="crispEdges" viewBox="0 -0.5 29 29" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M0 0h7M8 0h2M14 0h1M16 0h5M22 0h7M0 1h1M6 1h1M13 1h1M17 1h2M22 1h1M28 1h1M0 2h1M2 2h3M6 2h1M8 2h1M11 2h4M18 2h1M20 2h1M22 2h1M24 2h3M28 2h1M0 3h1M2 3h3M6 3h1M8 3h2M11 3h1M13 3h1M15 3h5M22 3h1M24 3h3M28 3h1M0 4h1M2 4h3M6 4h1M8 4h4M13 4h1M15 4h1M19 4h1M22 4h1M24 4h3M28 4h1M0 5h1M6 5h1M9 5h1M12 5h2M17 5h4M22 5h1M28 5h1M0 6h7M8 6h1M10 6h1M12 6h1M14 6h1M16 6h1M18 6h1M20 6h1M22 6h7M9 7h1M11 7h1M15 7h6M0 8h4M6 8h1M8 8h1M13 8h2M17 8h3M21 8h1M24 8h3M28 8h1M2 9h1M4 9h2M7 9h1M9 9h1M14 9h1M16 9h1M19 9h2M22 9h3M28 9h1M0 10h5M6 10h1M8 10h1M13 10h1M16 10h1M18 10h1M20 10h1M22 10h3M26 10h2M1 11h1M3 11h2M7 11h1M11 11h4M16 11h1M18 11h1M20 11h5M28 11h1M1 12h3M5 12h2M9 12h1M11 12h1M13 12h5M19 12h1M25 12h2M0 13h2M3 13h3M8 13h1M10 13h2M14 13h1M16 13h2M19 13h2M22 13h2M26 13h3M0 14h1M2 14h1M4 14h3M9 14h2M12 14h1M14 14h1M16 14h1M19 14h3M23 14h2M26 14h3M0 15h2M3 15h2M8 15h1M12 15h1M14 15h3M20 15h1M22 15h3M27 15h1M0 16h1M2 16h3M6 16h1M10 16h2M18 16h1M20 16h2M24 16h2M27 16h1M1 17h2M4 17h1M7 17h3M12 17h1M14 17h2M18 17h1M20 17h2M23 17h1M25 17h3M0 18h1M3 18h1M6 18h1M8 18h5M15 18h2M23 18h1M26 18h1M2 19h4M12 19h1M14 19h1M16 19h2M19 19h3M26 19h1M1 20h1M3 20h1M6 20h7M14 20h2M17 20h10M8 21h3M12 21h1M18 21h1M20 21h1M24 21h5M0 22h7M9 22h6M19 22h2M22 22h1M24 22h2M27 22h1M0 23h1M6 23h1M9 23h1M13 23h3M18 23h1M20 23h1M24 23h2M27 23h1M0 24h1M2 24h3M6 24h1M10 24h1M12 24h1M14 24h4M20 24h5M26 24h3M0 25h1M2 25h3M6 25h1M8 25h1M11 25h2M15 25h2M19 25h3M24 25h2M28 25h1M0 26h1M2 26h3M6 26h1M8 26h1M10 26h2M13 26h1M21 26h1M23 26h1M26 26h1M28 26h1M0 27h1M6 27h1M8 27h1M11 27h1M14 27h1M16 27h1M18 27h3M23 27h1M25 27h1M27 27h1M0 28h7M8 28h1M14 28h3M19 28h2M25 28h1M27 28h1" stroke="#000000"></path>
                                        </svg>

                                        <div class="prompt-id567">
                                            <div class="token-container">
                                                <svg viewBox="0 0 24 24" fill="none" class="creator-points" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M19.4133 4.89862L14.5863 2.17544C12.9911 1.27485 11.0089 1.27485 9.41368 2.17544L4.58674 4.89862C2.99153 5.7992 2 7.47596 2 9.2763V14.7235C2 16.5238 2.99153 18.2014 4.58674 19.1012L9.41368 21.8252C10.2079 22.2734 11.105 22.5 12.0046 22.5C12.6952 22.5 13.3874 22.3657 14.0349 22.0954C14.2204 22.018 14.4059 21.9273 14.5872 21.8252L19.4141 19.1012C19.9765 18.7831 20.4655 18.3728 20.8651 17.8825C21.597 16.9894 22 15.8671 22 14.7243V9.27713C22 7.47678 21.0085 5.7992 19.4133 4.89862Z" fill="currentColor"></path>
                                                </svg>
                                            </div>
                                            <div class="blurry-splash"></div>
                                            <p>
                                                Hover for voucher<br>
                                                <span class="bold-567">{{ $voucher->code }}</span>
                                            </p>
                                            <p class="really-small-text">
                                                {{ ucfirst($voucher->fuel_type) }} • R {{ number_format((float) $voucher->amount, 2) }}
                                            </p>
                                        </div>

                                        <div class="voucher-reveal-567" aria-hidden="true">
                                            <div class="voucher-reveal-code">{{ $voucher->code }}</div>
                                            <div class="voucher-reveal-meta">
                                                {{ \Illuminate\Support\Str::limit((string) ($voucher->fuelStation?->name ?? 'Station'), 18) }}
                                                • {{ optional($voucher->expires_at)->format('d M H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $qrValue = $voucher->qr_code ?: $voucher->code;
                                        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=96x96&margin=8&ecc=H&format=png&data=' . urlencode($qrValue);
                                        $blurQr = $voucher->status === 'issued';
                                    @endphp
                                    <div @class([
                                        'relative inline-flex items-center justify-center p-1.5 sm:p-2 rounded-xl border-2 border-slate-300 bg-white shadow-sm bwiser-qr-stack',
                                        'approved-voucher-outline' => $voucher->status === 'approved',
                                    ])>
                                        <img
                                            src="{{ $qrImage }}"
                                            alt="QR {{ $voucher->code }}"
                                            class="h-16 w-16 sm:h-20 sm:w-20 rounded-md bg-white {{ $blurQr ? 'blur-sm opacity-70' : '' }}"
                                            loading="lazy"
                                            onerror="this.style.display='none'; const fb=this.parentElement.querySelector('span.hidden'); if(fb) fb.style.display='grid';"
                                        >
                                        <span class="hidden h-16 w-16 sm:h-20 sm:w-20 place-items-center rounded-md border border-dashed border-slate-300 bg-slate-50 text-[9px] sm:text-[10px] font-semibold text-slate-600 px-1 text-center {{ $blurQr ? 'blur-sm opacity-70' : '' }}">
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
                                @endif
                            </td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-slate-700">
                                <div class="max-w-[220px] sm:max-w-none">
                                    {{ $voucher->fuelStation?->name ?? 'Unknown' }}
                                </div>
                            </td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-slate-700 whitespace-nowrap">{{ ucfirst($voucher->fuel_type) }}</td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-slate-700 whitespace-nowrap">R {{ number_format((float) $voucher->amount, 2) }}</td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-slate-700">
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
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 whitespace-nowrap">
                                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 uppercase">
                                    {{ $voucher->status }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-slate-700 whitespace-nowrap">{{ optional($voucher->expires_at)->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 whitespace-nowrap">
                                @if($voucher->status === 'issued')
                                    <form method="POST" action="{{ route('driver.vouchers.cancel', $voucher) }}" onsubmit="return confirm('Cancel this application? Future unpaid repayments for this application will be removed.');">
                                        @csrf
                                        <button type="submit" class="text-sm h-8 w-8 inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 font-semibold hover:bg-rose-100" aria-label="Cancel voucher">
                                            ×
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">No vouchers found.</td>
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

    /* Virtual-card created vouchers (interactive tile) */
    .card-id567 {
        width: 190px;
        height: 190px;
        background: rgb(22, 22, 22);
        color: white;
        border-radius: 1rem;
        padding: 1rem;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: 300ms ease;
        animation: 8s thumb-thumb infinite;
        user-select: none;
        outline: none;
        cursor: pointer;
    }

    .card-id567 svg path {
        transition: 300ms ease;
        opacity: 0;
    }

    .bold-567 {
        font-weight: 800;
        letter-spacing: .06em;
    }

    .creator-points {
        width: 3.25rem;
        height: 3rem;
        color: rgb(167 139 250);
    }

    .blurry-splash {
        position: absolute;
        inset: 0;
        width: 60px;
        margin: 0 auto;
        height: 60px;
        border-radius: 1rem;
        z-index: -1;
        opacity: 0.7;
        filter: blur(15px);
        background: linear-gradient(
            120deg,
            rgba(167, 139, 250, 0.24),
            rgba(167, 139, 250, 0.384),
            rgba(167, 139, 250, 0.226)
        );
    }

    .prompt-id567 {
        position: absolute;
        color: rgb(173, 173, 173);
        text-align: center;
        max-width: 170px;
    }

    .really-small-text {
        text-align: center;
        width: 100%;
        position: absolute;
        font-size: 10px;
        margin-top: 34px;
        opacity: 0.55;
        left: 0;
    }

    @media (hover: hover) {
        .card-id567:hover {
            background-color: white;
        }
    }

    .card-id567.is-open {
        background-color: white;
    }

    .card-id567:hover .prompt-id567,
    .card-id567.is-open .prompt-id567 {
        transition: 300ms ease;
        opacity: 0;
    }

    .token-container {
        animation: 2s spinny-token-yayyy infinite;
        margin-bottom: 10px;
    }

    .prompt-id567 svg path {
        stroke: none;
        opacity: 1;
    }

    .card-id567:hover svg path,
    .card-id567.is-open svg path {
        opacity: 1;
    }

    .voucher-reveal-567 {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: end center;
        padding-bottom: 16px;
        opacity: 0;
        transform: translateY(6px);
        transition: 300ms ease;
        pointer-events: none;
    }

    .card-id567:hover .voucher-reveal-567,
    .card-id567.is-open .voucher-reveal-567 {
        opacity: 1;
        transform: translateY(0);
    }

    .voucher-reveal-code {
        color: #0f172a;
        font-weight: 900;
        letter-spacing: .08em;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 999px;
        padding: 6px 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        margin-bottom: 8px;
    }

    .voucher-reveal-meta {
        color: rgba(15, 23, 42, 0.78);
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        padding: 0 8px;
    }

    @media (max-width: 640px) {
        .card-id567 {
            width: 160px;
            height: 160px;
            padding: 0.85rem;
        }

        .creator-points {
            width: 2.75rem;
            height: 2.5rem;
        }

        .really-small-text {
            font-size: 9px;
            margin-top: 30px;
        }
    }

    @keyframes spinny-token-yayyy {
        0% {
            transform: perspective(200px) rotateY(0deg);
        }

        100% {
            transform: perspective(200px) rotateY(360deg);
        }
    }

    @keyframes thumb-thumb {
        0%, 10%, 100% {
            transform: scale(1);
        }

        5% {
            transform: scale(1.03);
        }

        7% {
            transform: scale(0.97);
        }
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

<script>
    (function () {
        const cards = document.querySelectorAll('.card-id567');
        cards.forEach((el) => {
            el.addEventListener('click', () => el.classList.toggle('is-open'));
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    el.classList.toggle('is-open');
                }
            });
        });
    })();
</script>
@endsection
