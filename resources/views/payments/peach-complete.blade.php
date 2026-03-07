@extends('Layouts.app')

@section('title', 'Payment Status')

@section('content')
<div class="max-w-xl mx-auto px-6 py-12">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8 text-center">
        @if($success)
            <h1 class="text-2xl font-semibold text-slate-900">Payment Successful</h1>
            <p class="text-sm text-slate-600 mt-2">
                Your card is now set up for recurring repayments.
            </p>
        @else
            <h1 class="text-2xl font-semibold text-slate-900">Payment Pending</h1>
            <p class="text-sm text-slate-600 mt-2">
                We couldn't confirm the payment yet. Please try again or contact support.
            </p>
        @endif

        <a href="/" class="inline-block mt-6 px-4 py-2 bg-blue-600 text-white rounded-lg">
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
