@extends('mobile.layouts.app')

@section('title', 'Sign In - Bwiser Mobile')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<main class="px-4 pb-8 pt-6">
    <div class="mx-auto max-w-md space-y-4">
        <section class="mobile-card p-5">
            <p class="text-xs uppercase tracking-[0.22em] text-blue-700">Bwiser Access</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Sign in</h1>
            <p class="mt-1 text-xs text-slate-600">Access driver, merchant, admin, or operations dashboards.</p>

            @if($errors->any())
                <div class="mt-4 rounded-xl border border-rose-400/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('status'))
                <div class="mt-4 rounded-xl border border-emerald-400/40 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700">Email</label>
                    <input name="email" type="email" required value="{{ old('email') }}" autocomplete="email"
                        class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500"
                        placeholder="you@example.com">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Password</label>
                    <input name="password" type="password" required autocomplete="current-password"
                        class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500"
                        placeholder="••••••••">
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="hidden" name="remember" value="0">
                    <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 bg-white text-blue-600">
                    <label for="remember" class="text-xs text-slate-700">Remember me</label>
                </div>
                <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">
                    Sign In
                </button>
            </form>

            @if(config('services.google.client_id'))
                <div class="mt-3">
                    @include('partials.google-button', [
                        'label' => 'Continue with Google',
                        'class' => 'w-full'
                    ])
                </div>
            @endif
        </section>

        <section class="mobile-card p-4">
            <p class="text-xs text-slate-600">New to Bwiser?</p>
            <div class="mt-3 grid grid-cols-1 gap-2">
                <a href="{{ route('register.driver') }}" class="rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-blue-700">Register Driver</a>
                <a href="{{ route('register.merchant') }}" class="rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-blue-700">Register Merchant</a>
            </div>
        </section>
    </div>
</main>
@endsection
