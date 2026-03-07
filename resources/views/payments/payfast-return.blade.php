@extends('Layouts.app')

@section('title', 'Payment Submitted')

@section('content')
<section class="max-w-3xl mx-auto px-6 py-16 text-center">
    <div class="glass rounded-3xl p-10">
        <h1 class="brand-font text-2xl text-slate-900">Payment Submitted</h1>
        <p class="text-slate-600 mt-3">
            Thanks! Your payment is being confirmed. We’ll update your account once PayFast finalizes the transaction.
        </p>
        <div class="mt-6 text-sm text-slate-500">
            Reference: {{ $payment->merchant_reference }}
        </div>
    </div>
</section>
@endsection
