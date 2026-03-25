@extends('Layouts.guest')

@section('title', 'Verify your email')
@section('meta_description', 'Verify your email address to complete your Bwiser registration.')
@section('meta_robots', 'noindex,nofollow')

@section('content')
@if(session('driver_registered_popup'))
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-6 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
            <p class="text-xs font-extrabold tracking-[1px] uppercase text-blue-700">Driver registered</p>
            <h2 class="brand-font mt-3 text-2xl font-semibold text-slate-900">
                {{ data_get(session('driver_registered_popup'), 'name', 'New driver') }}
            </h2>
            <p class="mt-2 text-sm text-slate-700">
                {{ data_get(session('driver_registered_popup'), 'message') }}
            </p>
            <p class="mt-2 text-sm text-slate-600">Next: verify your email to secure your account.</p>
            <a class="super-button mt-5 w-full justify-center" href="{{ route('verification.notice', ['email' => (string) request()->query('email', '')]) }}">
                <span>Continue</span>
            </a>
        </div>
    </div>
@endif
<section class="min-h-screen py-12 px-4">
    <div class="max-w-xl mx-auto">
        <div class="glass rounded-2xl p-6 md:p-8 border border-slate-200">
            <p class="text-xs uppercase tracking-[1px] text-blue-600">Email verification</p>
            <h1 class="brand-font text-2xl md:text-3xl font-semibold text-slate-900 mt-3">Check your inbox</h1>
            <p class="text-slate-700 mt-3 leading-relaxed">
                We sent a verification link to your email address. Click the link to verify your account.
                If you do not see it, check your spam or promotions folder.
            </p>

            <div class="mt-6 rounded-xl bg-white/70 border border-slate-200 p-4">
                <form method="POST" action="{{ route('verification.send') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-slate-900">Email</label>
                        <input
                            name="email"
                            type="email"
                            required
                            value="{{ old('email', (string) request()->query('email', '')) }}"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"
                            placeholder="you@domain.com"
                            autocomplete="email"
                        >
                        @error('email')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="super-button w-full justify-center">
                        <span>Resend verification email</span>
                    </button>
                </form>
            </div>

            <div class="mt-6 flex items-center justify-between gap-3">
                <a class="text-sm font-semibold text-slate-700 hover:text-slate-900" href="{{ route('login') }}">Back to login</a>
                <a class="text-sm font-semibold text-blue-700 hover:text-blue-900" href="{{ url('/') }}">Home</a>
            </div>
        </div>
    </div>
</section>
@endsection
