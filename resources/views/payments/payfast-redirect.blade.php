@extends('layouts.app')

@section('title', 'Redirecting to PayFast')

@section('content')
<section class="max-w-3xl mx-auto px-6 py-16 text-center">
    <div class="glass rounded-3xl p-10">
        <h1 class="brand-font text-2xl text-slate-900">Redirecting to PayFast</h1>
        <p class="text-slate-600 mt-3">
            Please wait while we send you to the secure payment gateway.
        </p>
        <form id="payfastForm" method="POST" action="{{ $url }}" class="mt-6">
            @foreach($data as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="btn-primary px-6 py-3 rounded-xl text-sm font-semibold">
                Continue to PayFast
            </button>
        </form>
    </div>
</section>

<script>
    document.getElementById('payfastForm').submit();
</script>
@endsection
