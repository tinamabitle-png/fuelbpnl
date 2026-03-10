@extends('Layouts.app')

@section('title', 'Repayment Payment Result - Bwiser')

@section('content')
<section class="max-w-2xl mx-auto px-6 pt-20 pb-20">
    <div class="receipt-paper p-7 md:p-8 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full receipt-status text-2xl font-bold">
            {{ $success ? '✓' : '!' }}
        </div>

        <h1 class="brand-font text-2xl text-slate-900 mt-4 receipt-title">{{ $title ?? ($success ? 'Payment successful' : 'Payment failed') }}</h1>
        <p class="text-slate-600 mt-2 receipt-copy">{{ $message ?? '' }}</p>

        @if(!empty($error))
            <p class="text-xs text-slate-500 mt-3 receipt-copy">{{ $error }}</p>
        @endif

        @if(!empty($repayment))
            <div class="mt-5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm receipt-panel">
                <p class="text-slate-500 receipt-copy">Repayment</p>
                <p class="mt-1 font-semibold text-slate-900">#{{ $repayment->id }} • R {{ number_format((float) $repayment->amount, 2) }}</p>
                <p class="text-xs text-slate-500 mt-1 receipt-copy">Status: {{ strtoupper((string) $repayment->status) }}</p>
            </div>
        @endif

        <a href="{{ url('/') }}" class="btn-ghost inline-flex mt-6 px-4 py-2.5 rounded-xl text-sm font-semibold receipt-btn">Back to Home</a>
    </div>
</section>

<style>
    .receipt-paper {
        position: relative;
        border-radius: 0;
        border: none;
        background:
            linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%),
            repeating-linear-gradient(0deg, rgba(15, 23, 42, 0.035) 0, rgba(15, 23, 42, 0.035) 1px, transparent 1px, transparent 3px);
        box-shadow: 0 14px 28px -20px rgba(15, 23, 42, 0.55);
        filter: grayscale(1);
        overflow: hidden;
    }

    .receipt-paper::before,
    .receipt-paper::after {
        content: "";
        position: absolute;
        left: 0;
        width: 100%;
        height: 22px;
        background:
            radial-gradient(circle at 10px 10px, transparent 10px, #f8fafc 10.6px);
        background-size: 20px 20px;
        z-index: 1;
        pointer-events: none;
    }

    .receipt-paper::before {
        top: -11px;
    }

    .receipt-paper::after {
        bottom: -11px;
        transform: rotate(180deg) scaleX(-1);
    }

    .receipt-status {
        background: #e5e7eb;
        color: #111827;
        border: 1px solid #9ca3af;
    }

    .receipt-title,
    .receipt-copy,
    .receipt-panel,
    .receipt-btn {
        filter: grayscale(1);
    }

    .receipt-panel {
        border-style: dashed;
        background: #f8fafc;
    }

    .receipt-btn {
        border-color: #6b7280 !important;
        color: #111827 !important;
        background: #e5e7eb !important;
    }
</style>
@endsection
