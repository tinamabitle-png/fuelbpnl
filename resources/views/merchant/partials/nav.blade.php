@php
    $merchantTabs = [
        ['label' => 'Dashboard', 'route' => 'merchant.dashboard'],
        ['label' => 'Settings', 'route' => 'merchant.settings'],
        ['label' => 'All Vouchers', 'route' => 'merchant.vouchers.index'],
        ['label' => 'Developer Credentials', 'route' => 'merchant.developer.credentials'],
        ['label' => 'API Docs', 'route' => 'merchant.developer.docs'],
        ['label' => 'Sandbox', 'route' => 'merchant.developer.sandbox'],
        ['label' => 'Assets', 'route' => 'merchant.energy-assets.index'],
        ['label' => 'Projects', 'route' => 'merchant.energy-projects.index'],
        ['label' => 'Subscriptions', 'route' => 'merchant.energy-subscriptions.index'],
    ];
@endphp

<div class="mt-6 border-b border-slate-200">
    <nav class="flex flex-wrap gap-2 pb-3">
        @foreach($merchantTabs as $tab)
            @php
                if (!Route::has($tab['route'])) {
                    continue;
                }
                $active = request()->routeIs($tab['route']);
            @endphp
            <a
                href="{{ route($tab['route']) }}"
                class="px-3 py-2 rounded-xl text-sm font-medium transition {{ $active ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-blue-50 hover:text-blue-700' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>

@php
    $merchantUser = auth()->user();
    $merchantEmailVerificationPending = $merchantUser
        && $merchantUser->hasRole('merchant')
        && method_exists($merchantUser, 'hasVerifiedEmail')
        && !$merchantUser->hasVerifiedEmail();
@endphp

@if($merchantEmailVerificationPending)
    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="font-semibold">Email verification still needed</p>
                <p class="mt-1 text-amber-800">You can access the merchant dashboard now, but live redemptions and merchant transaction actions stay locked until your email is verified.</p>
            </div>
            <a
                href="{{ route('verification.notice', ['email' => (string) $merchantUser->email]) }}"
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Verify Email
            </a>
        </div>
    </div>
@endif
