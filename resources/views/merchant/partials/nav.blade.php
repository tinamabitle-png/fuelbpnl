@php
    $merchantTabs = [
        ['label' => 'Dashboard', 'route' => 'merchant.dashboard'],
        ['label' => 'All Vouchers', 'route' => 'merchant.vouchers.index'],
        ['label' => 'Developer Credentials', 'route' => 'merchant.developer.credentials'],
        ['label' => 'API Docs', 'route' => 'merchant.developer.docs'],
        ['label' => 'Sandbox', 'route' => 'merchant.developer.sandbox'],
        ['label' => 'Direct Bank Deposits', 'route' => 'merchant.payout.edit'],
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
