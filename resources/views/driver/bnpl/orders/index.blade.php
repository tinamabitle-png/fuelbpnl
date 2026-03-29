@extends('Layouts.app')

@section('title', 'Driver BNPL Orders - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Shopper BNPL Orders</h1>
            <p class="text-sm text-slate-600 mt-2">Create a checkout link for a shopper to pay later, and fulfill once approved.</p>
        </div>
        <a href="{{ route('driver.bnpl.orders.create', [], false) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#020DFF;">
            Create Order
        </a>
    </div>

    @include('driver.partials.nav', ['backUrl' => route('driver.dashboard')])

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

    <div class="mt-6 glass rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-700">
                <tr>
                    <th class="text-left px-4 py-3">Reference</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Amount</th>
                    <th class="text-left px-4 py-3">Expires</th>
                    <th class="text-right px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $order->reference }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 uppercase">{{ $order->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-900">R {{ number_format((float) $order->amount_total, 2) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $order->expires_at ? $order->expires_at->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('driver.bnpl.orders.show', $order, false) }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-slate-600" colspan="5">No BNPL orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

