@extends('layouts.app')

@section('title', 'Repayment Payment Result - Bwiser')

@section('content')
<section class="max-w-2xl mx-auto px-6 pt-20 pb-20">
    <div class="glass rounded-2xl p-7 md:p-8 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full {{ $success ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} text-2xl font-bold">
            {{ $success ? '✓' : '!' }}
        </div>

        <h1 class="brand-font text-2xl text-slate-900 mt-4">{{ $title ?? ($success ? 'Payment successful' : 'Payment failed') }}</h1>
        <p class="text-slate-600 mt-2">{{ $message ?? '' }}</p>

        @if(!empty($error))
            <p class="text-xs text-slate-500 mt-3">{{ $error }}</p>
        @endif

        @if(!empty($repayment))
            <div class="mt-5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm">
                <p class="text-slate-500">Repayment</p>
                <p class="mt-1 font-semibold text-slate-900">#{{ $repayment->id }} • R {{ number_format((float) $repayment->amount, 2) }}</p>
                <p class="text-xs text-slate-500 mt-1">Status: {{ strtoupper((string) $repayment->status) }}</p>
            </div>
        @endif

        <a href="{{ url('/') }}" class="btn-ghost inline-flex mt-6 px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Home</a>
    </div>
</section>
@endsection
