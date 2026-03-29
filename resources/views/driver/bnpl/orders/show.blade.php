@extends('Layouts.app')

@section('title', 'BNPL Order - Bwiser')

@section('content')
<section class="max-w-4xl mx-auto px-6 pt-16 pb-20">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">BNPL Order</h1>
            <p class="text-sm text-slate-600 mt-2">Reference: <span class="font-semibold text-slate-900">{{ $order->reference }}</span></p>
        </div>
        <a href="{{ route('driver.bnpl.orders.index', [], false) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-800 hover:bg-slate-50">
            Back
        </a>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="glass rounded-2xl border border-slate-200 bg-white p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Order</p>
            <p class="text-lg font-semibold text-slate-900 mt-2">{{ $order->title ?: 'Shopper Purchase' }}</p>
            @if($order->description)
                <p class="text-sm text-slate-600 mt-2">{{ $order->description }}</p>
            @endif
            <div class="mt-4 space-y-1 text-sm">
                <p class="text-slate-700">Total: <span class="font-semibold text-slate-900">R {{ number_format((float) $order->amount_total, 2) }}</span></p>
                <p class="text-slate-700">Deposit: <span class="font-semibold text-slate-900">R {{ number_format((float) $order->deposit_amount, 2) }}</span></p>
                <p class="text-slate-700">Financed: <span class="font-semibold text-slate-900">R {{ number_format((float) $order->financed_amount, 2) }}</span></p>
                <p class="text-slate-700">Installments: <span class="font-semibold text-slate-900">{{ (int) $order->installments_count }}</span></p>
            </div>
        </div>

        <div class="glass rounded-2xl border border-slate-200 bg-white p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Shopper Link</p>
            <p class="text-sm text-slate-600 mt-2">Share this checkout link with the shopper.</p>
            <input
                class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs"
                readonly
                value="{{ $checkoutUrl }}"
                onclick="this.select()"
            />
            <div class="mt-3 flex gap-2">
                <a href="{{ $checkoutUrl }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" style="background:#020DFF;">
                    Open Checkout
                </a>
                <button type="button" class="px-4 py-2 rounded-xl text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $checkoutUrl }}')">
                    Copy
                </button>
            </div>
            <p class="text-[11px] text-slate-500 mt-3">This link expires in 3 days.</p>
        </div>
    </div>

    <div class="mt-6 glass rounded-2xl border border-slate-200 bg-white p-6">
        <p class="text-sm font-semibold text-slate-900">Repayment Schedule (Preview)</p>
        <p class="text-xs text-slate-500 mt-1">This is a placeholder schedule. Payment collection will be wired next.</p>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-700">
                    <tr>
                        <th class="text-left px-4 py-3">#</th>
                        <th class="text-left px-4 py-3">Due</th>
                        <th class="text-left px-4 py-3">Amount</th>
                        <th class="text-left px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($order->installments as $inst)
                        <tr>
                            <td class="px-4 py-3 text-slate-900 font-semibold">{{ $inst->sequence }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $inst->due_at ? $inst->due_at->format('d M Y') : 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-900">R {{ number_format((float) $inst->amount, 2) }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 uppercase">{{ $inst->status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

