@extends('Layouts.app')

@section('title', 'Payment Result')

@section('content')
@php
    $context = $context ?? null;
    $isRepayment = $context === 'repayment';
    $isEnergyRepayment = $context === 'energy_repayment';
    $isEnergySubscriptionRepayment = $context === 'energy_subscription_repayment';
@endphp

<section class="max-w-3xl mx-auto px-6 py-16">
    <div class="glass rounded-3xl p-10 text-center">
        @if($success)
            <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <i class="fas fa-check text-xl"></i>
            </div>
            <h1 class="text-2xl font-semibold text-slate-900">
                {{ $isEnergySubscriptionRepayment ? 'Energy subscription payment received' : ($isEnergyRepayment ? 'Energy repayment received' : ($isRepayment ? 'Repayment received' : 'AutoPay setup complete')) }}
            </h1>
            <p class="mt-3 text-slate-500">
                {{ $isEnergySubscriptionRepayment || $isEnergyRepayment || $isRepayment ? 'Thank you. Your payment is now recorded.' : 'Your card has been authorized for future AutoPay charges.' }}
            </p>
        @else
            <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <i class="fas fa-times text-xl"></i>
            </div>
            <h1 class="text-2xl font-semibold text-slate-900">
                {{ $isEnergySubscriptionRepayment ? 'Energy subscription payment failed' : ($isEnergyRepayment ? 'Energy repayment failed' : ($isRepayment ? 'Repayment failed' : 'AutoPay setup failed')) }}
            </h1>
            <p class="mt-3 text-slate-500">Please try again or contact support.</p>
        @endif

        <p class="mt-6 text-xs text-slate-400">Reference: {{ $reference }}</p>
    </div>
</section>
@endsection
