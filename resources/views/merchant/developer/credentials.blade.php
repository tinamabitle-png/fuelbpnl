@extends('Layouts.app')

@section('title', 'Developer Credentials')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        <h1 class="brand-font text-2xl text-slate-900">Developer Credentials</h1>
        <p class="text-slate-600 mt-2">Create and manage API credentials for direct system integrations.</p>
        @include('merchant.partials.nav')

        @if(session('success'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($newToken)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm font-semibold text-amber-900">Copy this token now. It will only be shown once.</p>
                <div class="mt-3">
                    <code class="block overflow-x-auto whitespace-nowrap rounded-lg bg-white border border-amber-200 px-3 py-2 text-xs text-slate-800">{{ $newToken }}</code>
                </div>
            </div>
        @endif

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass rounded-2xl p-6 lg:col-span-2">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">One-line Checkout Plugin</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">
                            Use this on a station website, kiosk page, or POS web view. Replace the station and public key values with the Tapless partner credentials issued for that station.
                        </p>
                    </div>
                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Public key only</span>
                </div>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-950 p-4">
                    <code class="block overflow-x-auto whitespace-nowrap text-xs text-white">&lt;script src="{{ url('/js/bwiser-checkout.js') }}" data-bwiser-station="STATION_ID" data-bwiser-public-key="bw_pk_xxxxx" data-bwiser-reference="ORDER-1001" data-bwiser-amount="250.00"&gt;&lt;/script&gt;</code>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-slate-500">
                    This creates a checkout intent only. Voucher authorization and redemption remain protected through the merchant dashboard or server-side API credentials.
                </p>
            </div>

            <div class="glass rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-slate-900">Create Credential</h2>
                <form method="POST" action="{{ route('merchant.developer.tokens.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Credential Name</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="ERP Integration - Production"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Scopes</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($abilities as $ability)
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="abilities[]"
                                        value="{{ $ability }}"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        {{ in_array($ability, old('abilities', ['stations.read','vouchers.read']), true) ? 'checked' : '' }}
                                    >
                                    <span>{{ $ability }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('abilities')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Expiry (days)</label>
                            <input
                                type="number"
                                name="expires_in_days"
                                min="1"
                                max="{{ $maxExpiryDays }}"
                                value="{{ old('expires_in_days', $defaultExpiryDays) }}"
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Allowlisted IPs</label>
                            <input
                                type="text"
                                name="allowed_ips"
                                value="{{ old('allowed_ips') }}"
                                placeholder="196.0.0.12, 102.130.0.0/16"
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <button class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold">
                        Create Credential
                    </button>
                </form>
            </div>

            <div class="glass rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-slate-900">Active Credentials</h2>
                <div class="mt-4 space-y-3">
                    @forelse($tokens as $token)
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $token->name }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Token ID: {{ $token->id }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Scopes: {{ implode(', ', $token->abilities ?? []) ?: '—' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Expires: {{ optional($token->expires_at)->format('d M Y H:i') ?? 'Never' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Last used: {{ optional($token->last_used_at)->format('d M Y H:i') ?? 'Never' }}</p>
                                </div>
                                <div class="text-right">
                                    <form method="POST" action="{{ route('merchant.developer.tokens.destroy', $token) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn-ghost px-3 py-1.5 rounded-lg text-xs font-semibold"
                                            onclick="return confirm('Revoke this credential? Existing integrations will stop working.')"
                                        >
                                            Revoke
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No credentials created yet.</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $tokens->links() }}</div>
            </div>
        </div>
    </div>
</section>
@endsection
