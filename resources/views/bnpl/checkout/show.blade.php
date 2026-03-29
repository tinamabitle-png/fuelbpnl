@extends('Layouts.app')

@section('title', 'Bwiser Checkout')

@section('content')
<section class="max-w-3xl mx-auto px-6 pt-16 pb-20">
    <div class="glass rounded-2xl border border-slate-200 bg-white p-6">
        <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Bwiser BNPL</p>
        <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Pay Later Checkout</h1>
        <p class="text-sm text-slate-600 mt-2">Driver: <span class="font-semibold text-slate-900">{{ $order->driver?->name ?? 'Driver' }}</span></p>

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-900">{{ $order->title ?: 'Shopper Purchase' }}</p>
            @if($order->description)
                <p class="text-sm text-slate-600 mt-1">{{ $order->description }}</p>
            @endif
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-white border border-slate-200 p-3">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Total</p>
                    <p class="text-lg font-semibold text-slate-900 mt-1">R {{ number_format((float) $order->amount_total, 2) }}</p>
                </div>
                <div class="rounded-lg bg-white border border-slate-200 p-3">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Deposit</p>
                    <p class="text-lg font-semibold text-slate-900 mt-1">R {{ number_format((float) $order->deposit_amount, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <p class="text-sm font-semibold text-slate-900">Installments</p>
            <div class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-700">
                        <tr>
                            <th class="text-left px-4 py-3">#</th>
                            <th class="text-left px-4 py-3">Due</th>
                            <th class="text-left px-4 py-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($order->installments as $inst)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $inst->sequence }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $inst->due_at ? $inst->due_at->format('d M Y') : 'N/A' }}</td>
                                <td class="px-4 py-3 text-slate-900">R {{ number_format((float) $inst->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Payments are not enabled yet. This is the preview checkout UI for local rollout.
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            @guest
                <a href="{{ route('login') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#020DFF;">
                    Log in to Continue
                </a>
                <a href="{{ route('register') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
                    Create Account
                </a>
            @else
                <span class="text-sm text-slate-700">Logged in as <span class="font-semibold">{{ auth()->user()->email ?? auth()->user()->name }}</span>.</span>
            @endguest
        </div>
    </div>
</section>
@endsection

