@extends('layouts.app')

@section('title', 'Secure Card Payment')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-12">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <h1 class="text-2xl font-semibold text-slate-900">Add Card for AutoPay</h1>
        <p class="text-sm text-slate-600 mt-2">
            Your card will be tokenised for recurring repayments. The first payment may require 3D Secure.
        </p>

        <div id="payment-form" class="mt-6"></div>
    </div>
</div>

<script src="{{ $embeddedJs }}"></script>
<script>
  const checkout = Checkout.initiate({
      key: "{{ $entityId }}",
      checkoutId: "{{ $checkoutId }}",
      options: {
          paymentMethods: {
              include: ['CARD'],
          },
      },
      customisations: {
          showCancelButton: false,
          showAmountField: false,
          theme: {
              brand: {
                  primary: '#2563eb',
              },
              cards: {
                  background: '#ffffff',
                  backgroundHover: '#f8fafc',
              },
          },
          card: {
              headingText: 'Enter your card details',
              submitButtonText: 'Save card & pay',
              showBillingFields: false,
              brands: ['VISA', 'MASTERCARD', 'AMEX', 'DINERS'],
          },
      },
      events: {
          onCompleted: function() {
              window.location.href = "{{ $completeUrl }}";
          },
          onCancelled: function() {
              window.location.href = "{{ $completeUrl }}";
          },
          onExpired: function() {
              window.location.href = "{{ $completeUrl }}";
          }
      },
  });
  checkout.render("#payment-form");
</script>
@endsection
