@php
    $backUrl = $backUrl ?? null;
    $driverTabs = [
        ['label' => 'Dashboard', 'route' => 'driver.dashboard', 'active' => 'driver.dashboard'],
        ['label' => 'My Vouchers', 'route' => 'driver.vouchers.index', 'active' => 'driver.vouchers.*'],
        ['label' => 'Apply', 'route' => 'driver.vouchers.create', 'active' => 'driver.vouchers.create'],
        ['label' => 'Virtual Cards', 'route' => 'driver.virtual-cards.index', 'active' => 'driver.virtual-cards.*'],
        ['label' => 'Repayments', 'route' => 'driver.repayments.index', 'active' => 'driver.repayments.*'],
        ['label' => 'Profile', 'route' => 'driver.profile', 'active' => 'driver.profile*'],
    ];
@endphp

<div class="mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div class="w-full md:w-auto overflow-x-auto">
        <nav class="inline-flex min-w-max items-center gap-2 rounded-xl border border-slate-200 bg-white p-1">
            @foreach($driverTabs as $tab)
                @if(!Route::has($tab['route']))
                    @continue
                @endif

                @php
                    $isActive = request()->routeIs($tab['active']);
                @endphp

                <a
                    href="{{ route($tab['route']) }}"
                    class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition {{ $isActive ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' }}"
                >
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    @if($backUrl)
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    @endif
</div>
