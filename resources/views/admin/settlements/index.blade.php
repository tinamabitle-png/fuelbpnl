@extends('layouts.admin')

@section('title', 'Direct Bank Deposits Management')
@section('page-title', 'Direct Bank Deposit Processing')
@section('page-description', 'Manage fuel station direct bank deposits and payments')
@section('breadcrumb', 'Direct Bank Deposits')

@php
    // Additional stats calculations
    $todayDirectDeposits = App\Models\Settlement::whereDate('settlement_date', today())->sum('amount');
    $thisMonthDirectDeposits = App\Models\Settlement::whereMonth('settlement_date', now()->month)->sum('amount');
    $avgDirectDepositAmount = $stats['total_settlements'] > 0 ? $stats['total_amount'] / $stats['total_settlements'] : 0;
    $allBrands = collect($fuelStations ?? [])->pluck('company')->filter()->unique()->sort()->values();
    $stationLabelMap = collect($fuelStations ?? [])->mapWithKeys(function ($station) {
        $label = trim(((string) ($station->company ?? '') !== '' ? $station->company . ' - ' : '') . $station->name);
        return [$station->id => $label !== '' ? $label : ('Station #' . $station->id)];
    });
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Direct Bank Deposits -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Direct Bank Deposits</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_settlements']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-money-check-alt text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            ZAR {{ number_format($stats['total_amount'], 2) }} total amount
        </div>
    </div>

    <!-- Pending Direct Bank Deposits -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Pending Direct Bank Deposits</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['pending_count']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium text-yellow-700">
            ZAR {{ number_format($stats['pending_amount'], 2) }} pending
        </div>
    </div>

    <!-- Completed Direct Bank Deposits -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Completed</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['completed_count']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            ZAR {{ number_format($stats['completed_amount'], 2) }} processed
        </div>
    </div>

    <!-- Average Direct Bank Deposit -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Avg. Direct Bank Deposit</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">ZAR {{ number_format($avgDirectDepositAmount, 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            {{ $stats['total_settlements'] > 0 ? number_format(($stats['completed_count'] / $stats['total_settlements']) * 100, 1) : 0 }}% completion rate
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Direct Bank Deposit Management</h2>
            <p class="text-gray-600 mt-1">Process payments to fuel stations for redeemed vouchers</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('admin.settlements.create') }}" 
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i> New Direct Bank Deposit
            </a>
            <a href="{{ route('admin.settlements.export') }}" 
               class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-download mr-2"></i> Export
            </a>
            <button onclick="toggleFilters()" 
                    class="px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300">
                <i class="fas fa-filter"></i>
            </button>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        <span class="font-semibold">How payouts work:</span>
        1) Admin tops up station wallets via direct deposit. 2) Drivers redeem vouchers against station wallet balance. 3) Each station is protected from duplicate payout in the same week unless force is explicitly selected.
    </div>

    @php
        $baseQuery = request()->except(['page', 'history']);
        $historyActive = strtolower((string) request('history', ''));
    @endphp
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">History</span>
        <a
            href="{{ route('admin.settlements.index', $baseQuery) }}"
            class="px-3 py-1.5 rounded-full text-xs font-semibold border {{ $historyActive === '' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            All ({{ number_format($stats['total_settlements']) }})
        </a>
        <a
            href="{{ route('admin.settlements.index', array_merge($baseQuery, ['history' => 'immediate'])) }}"
            class="px-3 py-1.5 rounded-full text-xs font-semibold border {{ $historyActive === 'immediate' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            Immediate Pay ({{ number_format($immediateHistoryCount ?? 0) }})
        </a>
        <a
            href="{{ route('admin.settlements.index', array_merge($baseQuery, ['history' => 'standard'])) }}"
            class="px-3 py-1.5 rounded-full text-xs font-semibold border {{ $historyActive === 'standard' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            Standard ({{ number_format($standardHistoryCount ?? 0) }})
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 mb-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Set Settlement Amount (Pre-Fund)</h3>
                <p class="text-sm text-gray-600 mt-1">Use this when franchises/stations are paid before vouchers are created.</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-semibold">Paystack Only</span>
        </div>

        <form method="POST" action="{{ route('admin.settlements.quick-topup') }}" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Station</label>
                <div class="relative">
                    <input
                        type="text"
                        class="js-station-typeahead w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm"
                        placeholder="Type to search station..."
                        autocomplete="off"
                        data-hidden-target="prefundStationId"
                        value="{{ old('fuel_station_id') ? ($stationLabelMap[(int) old('fuel_station_id')] ?? '') : '' }}">
                    <input type="hidden" id="prefundStationId" name="fuel_station_id" value="{{ old('fuel_station_id') }}">
                    <div class="js-station-suggestions hidden absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Amount (ZAR)</label>
                <input type="number" name="amount" min="0.01" step="0.01" required class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm" placeholder="0.00">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Settlement Date</label>
                <input type="date" name="settlement_date" value="{{ now()->toDateString() }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm">
            </div>
            <div class="md:col-span-5">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Notes (optional)</label>
                <input type="text" name="notes" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm" placeholder="e.g. Weekly franchise prefund">
            </div>
            <div class="md:col-span-5 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-semibold text-slate-700">Optional: Fill missing payout details for Immediate Pay</p>
                <p class="text-[11px] text-slate-500 mt-1">If station bank details are missing, these fields will be saved to the station before Paystack transfer.</p>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input id="payoutAccountName" type="text" name="payout_account_name" value="{{ old('payout_account_name') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs" placeholder="Account name">
                    <input id="payoutAccountNumber" type="text" name="payout_account_number" value="{{ old('payout_account_number') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs" placeholder="Account number">
                    <select id="paystackBankName" name="payout_bank_name" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs">
                        <option value="">{{ old('payout_bank_name') ? 'Loading banks...' : 'Select bank (Paystack)' }}</option>
                    </select>
                    <input id="paystackBankCode" type="text" name="payout_bank_code" value="{{ old('payout_bank_code') }}" readonly class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-slate-100 text-xs" placeholder="Bank code (auto-filled)">
                </div>
            </div>
            <div class="md:col-span-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="force_weekly_duplicate" value="1" class="rounded border-slate-300">
                    Allow duplicate payout in same week (force)
                </label>
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        formaction="{{ route('admin.settlements.quick-topup-immediate') }}"
                        class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">
                        Pay Immediately
                    </button>
                    <button type="submit" class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700">
                        Create Pre-Funded Settlement
                    </button>
                </div>
            </div>
            <div class="md:col-span-5">
                <p class="text-xs text-slate-500">
                    <span class="font-semibold text-slate-700">Pay Immediately:</span> creates and processes the direct bank deposit in one step via Paystack.
                </p>
            </div>
        </form>
    </div>

    <details class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 mb-6">
        <summary class="cursor-pointer list-none flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Weekly Payout Cycles</h3>
                <p class="text-sm text-gray-600 mt-1">Automation settings for brand and station payout days.</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full {{ !empty($weeklyCycles['enabled']) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                {{ !empty($weeklyCycles['enabled']) ? 'ON' : 'OFF' }}
            </span>
        </summary>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 pb-4 border-b border-slate-200">
            <div>
                <p class="text-sm text-gray-600 mt-2">Configure weekly direct-to-account payout days for franchises (brands) and specific stations.</p>
                <p class="text-xs mt-1 {{ !empty($weeklyCycles['enabled']) ? 'text-emerald-700' : 'text-rose-700' }}">
                    Automation: <span class="font-semibold">{{ !empty($weeklyCycles['enabled']) ? 'ON' : 'OFF' }}</span>
                    @if(!empty($weeklyCycles['next_cycle']))
                        • Next cycle: <span class="font-semibold">{{ $weeklyCycles['next_cycle']['label'] }}</span>
                        ({{ $weeklyCycles['next_cycle']['type'] === 'brand' ? 'Brand' : 'Station' }}: {{ $weeklyCycles['next_cycle']['name'] }})
                    @else
                        • Next cycle: <span class="font-semibold">Not configured</span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    Today: {{ ucfirst($weeklyCycles['today'] ?? strtolower(now()->format('l'))) }}
                </span>
                <form method="POST" action="{{ route('admin.settlements.cycles.toggle') }}">
                    @csrf
                    <input type="hidden" name="enabled" value="{{ !empty($weeklyCycles['enabled']) ? 0 : 1 }}">
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold {{ !empty($weeklyCycles['enabled']) ? 'bg-rose-100 text-rose-700 hover:bg-rose-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }}">
                        {{ !empty($weeklyCycles['enabled']) ? 'Turn Off' : 'Turn On' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settlements.cycles.run-due') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800">
                        Run Due Cycles Now
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-900">Franchise / Brand Cycle</p>
                <p class="text-xs text-slate-500 mt-1">Brand cycle bulk-pays only Partner Stations.</p>
                <form method="POST" action="{{ route('admin.settlements.cycles.brand') }}" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2">
                    @csrf
                    <select name="brand" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm" required>
                        <option value="">Select brand</option>
                        @foreach(($allBrands ?? collect()) as $brandName)
                            <option value="{{ $brandName }}">{{ $brandName }}</option>
                        @endforeach
                    </select>
                    <select name="day" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm" required>
                        @foreach(($weeklyCycles['days'] ?? ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']) as $day)
                            <option value="{{ $day }}">{{ ucfirst($day) }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300" checked>
                            Enabled
                        </label>
                        <button type="submit" class="ml-auto px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700">
                            Save Brand Cycle
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-900">Station Cycle (Individual)</p>
                <p class="text-xs text-slate-500 mt-1">Use station-specific weekly payout even if the station is not in partner bulk list.</p>
                <form method="POST" action="{{ route('admin.settlements.cycles.station') }}" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2">
                    @csrf
                    <div class="relative">
                        <input
                            type="text"
                            class="js-station-typeahead w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm"
                            placeholder="Type to search station..."
                            autocomplete="off"
                            data-hidden-target="stationCycleStationId">
                        <input type="hidden" id="stationCycleStationId" name="station_id" required>
                        <div class="js-station-suggestions hidden absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg max-h-56 overflow-y-auto"></div>
                    </div>
                    <select name="day" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm" required>
                        @foreach(($weeklyCycles['days'] ?? ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']) as $day)
                            <option value="{{ $day }}">{{ ucfirst($day) }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300" checked>
                            Enabled
                        </label>
                        <button type="submit" class="ml-auto px-3 py-2 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700">
                            Save Station Cycle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Configured Brand Cycles</p>
                <div class="mt-2 space-y-2 max-h-40 overflow-y-auto pr-1">
                    @forelse(($weeklyCycles['brand_cycles'] ?? []) as $brandName => $cycle)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                            <p class="text-sm text-slate-800">{{ $brandName }}</p>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ !empty($cycle['enabled']) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ ucfirst((string) ($cycle['day'] ?? '-')) }} • {{ !empty($cycle['enabled']) ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No brand cycle configured yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Configured Station Cycles</p>
                <div class="mt-2 space-y-2 max-h-40 overflow-y-auto pr-1">
                    @forelse(($weeklyCycles['station_cycles'] ?? []) as $stationId => $cycle)
                        @php
                            $stationName = optional(($fuelStations ?? collect())->firstWhere('id', (int) $stationId))->name ?: ('Station #' . $stationId);
                        @endphp
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                            <p class="text-sm text-slate-800">{{ $stationName }}</p>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ !empty($cycle['enabled']) ? 'bg-blue-100 text-blue-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ ucfirst((string) ($cycle['day'] ?? '-')) }} • {{ !empty($cycle['enabled']) ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No station cycle configured yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </details>

    <details class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 mb-6">
        <summary class="cursor-pointer list-none flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Retail Brand Payout Engine</h3>
                <p class="text-sm text-gray-600 mt-1">Franchise bulk payouts + partner station assignment.</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700">Advanced</span>
        </summary>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <p class="text-sm text-gray-600 mt-1">Brands are franchise groupings. Bulk runs process only stations marked as <span class="font-semibold text-emerald-700">Partner Station</span> and payout-ready. Non-partner stations are individual payout only.</p>
            </div>
            <form method="POST" action="{{ route('admin.settlements.process-brand') }}">
                @csrf
                <input type="hidden" name="brand" value="__all__">
                <details class="inline-block mr-2">
                    <summary class="text-xs text-amber-700 cursor-pointer">Advanced override</summary>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600 mt-1">
                        <input type="checkbox" name="force_weekly_duplicate" value="1" class="rounded border-slate-300">
                        Force weekly duplicates
                    </label>
                </details>
                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-700 text-white rounded-xl text-sm font-semibold hover:from-indigo-700 hover:to-blue-800">
                    Process All Brands
                </button>
            </form>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($brandPayouts as $brandRow)
                <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $brandRow['brand'] }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $brandRow['count'] }} pending settlement{{ $brandRow['count'] != 1 ? 's' : '' }}</p>
                        </div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $brandRow['blocked_count'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $brandRow['blocked_count'] > 0 ? 'Partially Ready' : 'Ready' }}
                        </span>
                    </div>

                    <p class="mt-3 text-lg font-bold text-slate-900">ZAR {{ number_format((float) $brandRow['amount'], 2) }}</p>
                    <p class="text-xs mt-1 text-slate-600">
                        Ready: {{ $brandRow['ready_count'] }} • Blocked: {{ $brandRow['blocked_count'] }}
                    </p>
                    <p class="text-xs mt-1 text-slate-600">
                        Partner Stations: {{ $brandRow['partner_station_count'] ?? 0 }} / {{ $brandRow['total_station_count'] ?? 0 }}
                    </p>

                    <form method="POST" action="{{ route('admin.settlements.process-brand') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="brand" value="{{ $brandRow['brand'] }}">
                        @if(!empty($brandRow['stations']))
                            <div class="relative mb-2">
                                <input
                                    type="text"
                                    class="js-station-typeahead w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-700"
                                    placeholder="All stations in {{ $brandRow['brand'] }} (type to narrow)"
                                    autocomplete="off"
                                    data-hidden-target="brandStationId{{ $loop->index }}"
                                    data-brand-value="{{ $brandRow['brand'] }}">
                                <input type="hidden" id="brandStationId{{ $loop->index }}" name="station_id">
                                <div class="js-station-suggestions hidden absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg max-h-56 overflow-y-auto"></div>
                            </div>
                        @endif
                        <details class="mb-2">
                            <summary class="text-[11px] text-amber-700 cursor-pointer">Advanced override</summary>
                            <label class="inline-flex items-center gap-2 text-[11px] text-slate-600 mt-1">
                                <input type="checkbox" name="force_weekly_duplicate" value="1" class="rounded border-slate-300">
                                Force weekly duplicates
                            </label>
                        </details>
                        <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-semibold {{ $brandRow['ready_count'] > 0 ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}" {{ $brandRow['ready_count'] > 0 ? '' : 'disabled' }}>
                            Process {{ $brandRow['brand'] }}
                        </button>
                    </form>

                    @if(!empty($brandRow['stations']))
                        <div class="mt-3 space-y-2 max-h-52 overflow-y-auto pr-1">
                            @foreach($brandRow['stations'] as $brandStation)
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-slate-800 truncate">
                                                {{ $brandStation['name'] ?: ('Station #' . $brandStation['id']) }}
                                            </p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Pending {{ (int) ($brandStation['pending_count'] ?? 0) }} •
                                                ZAR {{ number_format((float) ($brandStation['pending_amount'] ?? 0), 2) }}
                                            </p>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ !empty($brandStation['partner']) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ !empty($brandStation['partner']) ? 'Partner Station' : 'Individual Only' }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ !empty($brandStation['ready']) ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ !empty($brandStation['ready']) ? 'Payout Ready' : 'Payout Blocked' }}
                                                </span>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.settlements.stations.partner', ['station' => $brandStation['id']]) }}">
                                            @csrf
                                            <input type="hidden" name="is_partner" value="{{ !empty($brandStation['partner']) ? 0 : 1 }}">
                                            <button type="submit" class="px-2 py-1 rounded-md text-[11px] font-semibold {{ !empty($brandStation['partner']) ? 'bg-rose-100 text-rose-700 hover:bg-rose-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }}">
                                                {{ !empty($brandStation['partner']) ? 'Remove Partner' : 'Set Partner' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    No pending brand payouts at the moment.
                </div>
            @endforelse
        </div>
    </details>

    <!-- Search and Filters -->
    <div id="filterSection" class="hidden bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('admin.settlements.index') }}" method="GET" class="space-y-4">
            @if(request()->filled('history'))
                <input type="hidden" name="history" value="{{ request('history') }}">
            @endif
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2 relative">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search by reference, transaction ID, or station..." 
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                </div>
                
                <!-- Status Filter -->
                <select name="status" 
                        class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
                
                <!-- Station Filter -->
                <div class="relative">
                    <input
                        type="text"
                        class="js-station-typeahead js-filter-field w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                        placeholder="All stations (type to search)"
                        autocomplete="off"
                        data-hidden-target="settlementFilterStationId"
                        data-auto-submit="1"
                        value="{{ request('fuel_station_id') ? ($stationLabelMap[(int) request('fuel_station_id')] ?? '') : '' }}">
                    <input type="hidden" id="settlementFilterStationId" name="fuel_station_id" value="{{ request('fuel_station_id') }}">
                    <div class="js-station-suggestions hidden absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
            </div>
            
            <!-- Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <select name="sort" 
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Sort by: Newest</option>
                        <option value="amount" {{ request('sort') == 'amount' ? 'selected' : '' }}>Sort by: Amount</option>
                        <option value="settlement_date" {{ request('sort') == 'settlement_date' ? 'selected' : '' }}>Sort by: Date</option>
                    </select>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-2">
                <div class="text-sm text-gray-600">
                    Found {{ $settlements->total() }} direct bank deposits
                </div>
                <div class="flex space-x-2">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.settlements.index') }}" 
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                        Clear All
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Direct Bank Deposits Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-3">Reference</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Station Details
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Direct Bank Deposit Info
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($settlements as $settlement)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <!-- Reference -->
                        <td class="px-6 py-5">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="selected_settlements[]" 
                                       value="{{ $settlement->id }}" 
                                       class="settlement-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                                <div class="space-y-1">
                                    <div class="text-sm font-semibold text-gray-900">{{ $settlement->reference }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $settlement->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        ID: {{ $settlement->id }}
                                    </div>
                                    @if(Str::contains((string) $settlement->notes, 'Immediate pre-funded top-up'))
                                        <div class="text-[10px] inline-flex items-center px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-semibold">
                                            Immediate Pay
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Station Details -->
                        <td class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                        <i class="fas fa-gas-pump text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('admin.stations.show', $settlement->fuelStation) }}" class="hover:text-blue-600">
                                            {{ $settlement->fuelStation->name }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $settlement->fuelStation->company }}</div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i> {{ $settlement->fuelStation->city }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Direct Bank Deposit Info -->
                        <td class="px-6 py-5">
                            <div class="space-y-2">
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        ZAR {{ number_format($settlement->amount, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $settlement->voucher_count }} voucher{{ $settlement->voucher_count != 1 ? 's' : '' }}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                                        {{ $settlement->settlement_date->format('M d, Y') }}
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-money-bill-wave mr-2 text-gray-400"></i>
                                        {{ ucfirst(str_replace('_', ' ', $settlement->payment_method)) }}
                                    </div>
                                    @if($settlement->transaction_reference)
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-receipt mr-2 text-gray-400"></i>
                                        {{ $settlement->transaction_reference }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Status -->
                        <td class="px-6 py-5">
                            @php
                                $statusColors = [
                                    'pending' => ['bg-yellow-100', 'text-yellow-800', 'Pending', 'fa-clock'],
                                    'completed' => ['bg-green-100', 'text-green-800', 'Completed', 'fa-check-circle'],
                                    'failed' => ['bg-red-100', 'text-red-800', 'Failed', 'fa-exclamation-circle'],
                                ];
                                $status = $statusColors[$settlement->status] ?? $statusColors['pending'];
                            @endphp
                            <div class="space-y-2">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                    <i class="fas {{ $status[3] }} mr-1.5"></i>
                                    {{ $status[2] }}
                                </span>
                                
                                @if($settlement->processed_at)
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-check mr-1"></i> 
                                    Processed: {{ $settlement->processed_at->format('M d, Y H:i') }}
                                </div>
                                @endif
                                
                                @if($settlement->notes)
                                <div class="text-xs text-gray-500 truncate max-w-xs" title="{{ $settlement->notes }}">
                                    <i class="fas fa-sticky-note mr-1"></i> {{ Str::limit($settlement->notes, 50) }}
                                </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-5">
                            <div class="flex flex-col space-y-2">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.settlements.show', $settlement) }}" 
                                       class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($settlement->status === 'pending')
                                        <a href="{{ route('admin.settlements.edit', $settlement) }}" 
                                           class="text-yellow-600 hover:text-yellow-900 p-2 hover:bg-yellow-50 rounded-lg transition-colors"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form action="{{ route('admin.settlements.process', $settlement) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="force_weekly_duplicate" value="0">
                                            <button type="submit" 
                                                    class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors"
                                                    onclick="return confirm('Process direct bank deposit {{ $settlement->reference }}?')"
                                                    title="Process Direct Bank Deposit">
                                                <i class="fas fa-play-circle"></i>
                                            </button>
                                        </form>
                                        
                                        <button onclick="showFailModal({{ $settlement->id }}, '{{ addslashes($settlement->reference) }}')" 
                                                class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Mark as Failed">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    @endif
                                    
                                    @if($settlement->status === 'pending')
                                    <form action="{{ route('admin.settlements.destroy', $settlement) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                                onclick="return confirm('Delete direct bank deposit {{ $settlement->reference }}?')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                
                                <!-- Quick Links -->
                                <div class="flex flex-wrap gap-1">
                                    <a href="{{ route('admin.stations.show', $settlement->fuelStation) }}" 
                                       class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200">
                                        <i class="fas fa-gas-pump mr-1"></i> Station
                                    </a>
                                    <a href="#" onclick="showVouchers({{ $settlement->id }})" 
                                       class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200">
                                        <i class="fas fa-ticket-alt mr-1"></i> Vouchers
                                    </a>
                                    @if($settlement->transaction_reference)
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                        <i class="fas fa-receipt mr-1"></i> {{ Str::limit($settlement->transaction_reference, 10) }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-money-check-alt text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-700">No direct bank deposits found</p>
                                <p class="text-gray-500 mt-1">Create your first direct bank deposit to get started</p>
                                <a href="{{ route('admin.settlements.create') }}" 
                                   class="inline-block mt-4 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i> Create Direct Bank Deposit
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($settlements->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-semibold">{{ $settlements->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $settlements->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $settlements->total() }}</span> direct bank deposits
                </div>
                <div class="flex space-x-2">
                    @if($settlements->onFirstPage())
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $settlements->previousPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    @foreach(range(1, min(5, $settlements->lastPage())) as $page)
                        <a href="{{ $settlements->url($page) }}" 
                           class="px-3 py-2 border rounded-lg {{ $settlements->currentPage() == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            {{ $page }}
                        </a>
                    @endforeach

                    @if($settlements->hasMorePages())
                        <a href="{{ $settlements->nextPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Bulk Actions Dropdown -->
<div id="bulkActions" class="hidden bg-white p-4 rounded-xl shadow-md border border-gray-200 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="text-gray-700 font-medium">Bulk Actions:</span>
            <select id="bulkActionSelect" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Choose action...</option>
                <option value="process">Process Direct Bank Deposits</option>
                <option value="export">Export Selected</option>
                <option value="delete">Delete Direct Bank Deposits</option>
            </select>
            <button onclick="applyBulkAction()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Apply
            </button>
        </div>
        <button onclick="toggleBulkActions()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Fail Modal -->
<div id="failModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="failForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Mark as Failed</h3>
                    <button type="button" onclick="closeFailModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">Direct Bank Deposit: <span id="failDirectDepositRef" class="font-semibold text-gray-900"></span></p>
                    <p class="text-sm text-gray-500 mt-1">Provide a reason for marking this direct bank deposit as failed.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for Failure
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain why this direct bank deposit failed..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeFailModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Mark as Failed
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Vouchers Modal -->
<div id="vouchersModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Direct Bank Deposit Vouchers</h3>
                <button type="button" onclick="closeVouchersModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="vouchersContent" class="max-h-96 overflow-y-auto">
                <!-- Vouchers will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Bulk Process Modal -->
<div id="bulkProcessModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
        <form action="{{ route('admin.settlements.bulk-process') }}" method="POST" id="bulkProcessForm">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Bulk Process Direct Bank Deposits</h3>
                    <button type="button" onclick="closeBulkProcessModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-6">
                    <p class="text-gray-600">Select multiple pending direct bank deposits to process them all at once.</p>
                    <p class="text-sm text-gray-500 mt-1">This will credit station wallets for all selected direct bank deposits.</p>
                </div>
                
                <!-- Direct Bank Deposits Selection -->
                <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                    @foreach(App\Models\Settlement::pending()->with('fuelStation')->get() as $settlement)
                    <div class="flex items-center p-4 border border-gray-200 rounded-xl hover:border-blue-300 transition-colors">
                        <input type="checkbox" 
                               name="settlements[]" 
                               value="{{ $settlement->id }}" 
                               id="bulk_{{ $settlement->id }}"
                               class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300 bulk-checkbox">
                        <label for="bulk_{{ $settlement->id }}" class="ml-4 flex-1 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">{{ $settlement->reference }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ $settlement->fuelStation->name }}</p>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-calendar-alt text-gray-400 text-xs mr-1"></i>
                                        <span class="text-xs text-gray-500">{{ $settlement->settlement_date->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-gray-900">ZAR {{ number_format($settlement->amount, 2) }}</div>
                                    <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                        {{ $settlement->voucher_count }} vouchers
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                    
                    @if(App\Models\Settlement::pending()->count() === 0)
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-3xl mb-3 opacity-20"></i>
                        <p>No pending direct bank deposits available</p>
                        <p class="text-sm mt-1">All direct bank deposits are already processed</p>
                    </div>
                    @endif
                </div>
                
                <!-- Summary -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600">Selected</p>
                            <p id="bulkSelectedCount" class="text-xl font-bold text-gray-900">0 direct bank deposits</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Total Amount</p>
                            <p id="bulkTotalAmount" class="text-xl font-bold text-blue-700">ZAR 0.00</p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeBulkProcessModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            id="bulkProcessBtn"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        <i class="fas fa-play-circle mr-2"></i>
                        Process Selected
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle filters
    function toggleFilters() {
        const filterSection = document.getElementById('filterSection');
        filterSection.classList.toggle('hidden');
    }

    // Toggle bulk actions
    function toggleBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        bulkActions.classList.toggle('hidden');
    }

    // Select all checkboxes
    document.getElementById('selectAll')?.addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.settlement-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    // Fail Modal
    function showFailModal(settlementId, settlementRef) {
        document.getElementById('failDirectDepositRef').textContent = settlementRef;
        const form = document.getElementById('failForm');
        form.action = `/admin/settlements/${settlementId}/mark-as-failed`;
        document.getElementById('failModal').classList.remove('hidden');
    }
    
    function closeFailModal() {
        document.getElementById('failModal').classList.add('hidden');
        document.getElementById('failForm').reset();
    }

    // Vouchers Modal
    async function showVouchers(settlementId) {
        try {
            const response = await fetch(`/admin/settlements/${settlementId}/vouchers`);
            const data = await response.json();
            
            let vouchersHtml = `
                <div class="space-y-3">
                    <div class="text-sm text-gray-600 mb-4">
                        Total vouchers: ${data.vouchers.length}
                    </div>
            `;
            
            data.vouchers.forEach(voucher => {
                vouchersHtml += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Voucher #${voucher.id}</p>
                            <p class="text-xs text-gray-500">User: ${voucher.user?.name || 'Unknown'}</p>
                            <p class="text-xs text-gray-500">Amount: ZAR ${parseFloat(voucher.amount).toFixed(2)}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs px-2 py-1 rounded ${voucher.status === 'redeemed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                ${voucher.status}
                            </span>
                            <p class="text-xs text-gray-500 mt-1">${new Date(voucher.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                `;
            });
            
            vouchersHtml += '</div>';
            document.getElementById('vouchersContent').innerHTML = vouchersHtml;
            document.getElementById('vouchersModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error loading vouchers:', error);
            document.getElementById('vouchersContent').innerHTML = '<p class="text-red-600">Failed to load vouchers</p>';
            document.getElementById('vouchersModal').classList.remove('hidden');
        }
    }
    
    function closeVouchersModal() {
        document.getElementById('vouchersModal').classList.add('hidden');
    }

    // Bulk process modal
    function showBulkProcess() {
        updateBulkSummary();
        document.getElementById('bulkProcessModal').classList.remove('hidden');
    }
    
    function closeBulkProcessModal() {
        document.getElementById('bulkProcessModal').classList.add('hidden');
        document.querySelectorAll('.bulk-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateBulkSummary();
    }
    
    // Bulk process summary
    function updateBulkSummary() {
        let totalAmount = 0;
        let selectedCount = 0;
        
        document.querySelectorAll('.bulk-checkbox:checked').forEach(checkbox => {
            selectedCount++;
            const settlementRow = checkbox.closest('.flex.items-center');
            const amountText = settlementRow.querySelector('.text-sm.font-bold')?.textContent || '0';
            const amount = parseFloat(amountText.replace('ZAR ', '').replace(/,/g, '')) || 0;
            totalAmount += amount;
        });
        
        document.getElementById('bulkSelectedCount').textContent = selectedCount + ' direct bank deposit' + (selectedCount !== 1 ? 's' : '');
        document.getElementById('bulkTotalAmount').textContent = 'ZAR ' + totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2 });
        document.getElementById('bulkProcessBtn').disabled = selectedCount === 0;
    }
    
    // Add event listeners to bulk checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.bulk-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkSummary);
        });
    });
    
    // Handle bulk form submission
    document.getElementById('bulkProcessForm')?.addEventListener('submit', function(e) {
        const selectedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
        if (selectedCount === 0) {
            e.preventDefault();
            alert('Please select at least one direct bank deposit to process');
            return;
        }
        
        const confirmation = confirm(`Process ${selectedCount} direct bank deposit(s)? This will credit station wallets.`);
        if (!confirmation) {
            e.preventDefault();
        }
    });

    // Apply bulk action
    function applyBulkAction() {
        const action = document.getElementById('bulkActionSelect').value;
        const selectedSettlements = Array.from(document.querySelectorAll('.settlement-checkbox:checked'))
                                       .map(cb => cb.value);
        
        if (selectedSettlements.length === 0) {
            alert('Please select at least one direct bank deposit');
            return;
        }
        
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        if (action === 'process') {
            // Show bulk process modal
            showBulkProcess();
        } else if (action === 'export') {
            // Handle export
            const url = selectedSettlements.length > 0 
                ? `/admin/settlements/export?settlements=${selectedSettlements.join(',')}`
                : `/admin/settlements/export`;
            window.location.href = url;
        } else if (action === 'delete') {
            if (confirm(`Delete ${selectedSettlements.length} direct bank deposit(s)? This will release all vouchers back to unsettled state.`)) {
                // In a real app, you would make an AJAX request here
                alert(`Deleting ${selectedSettlements.length} direct bank deposit(s)...`);
                toggleBulkActions();
            }
        }
    }

    function debounce(fn, wait = 220) {
        let t = null;
        return (...args) => {
            window.clearTimeout(t);
            t = window.setTimeout(() => fn(...args), wait);
        };
    }

    function initStationTypeaheads() {
        const endpoint = '{{ route('admin.settlements.api.stations-search') }}';
        const inputs = document.querySelectorAll('.js-station-typeahead');
        const payoutAccountNameInput = document.getElementById('payoutAccountName');
        const payoutAccountNumberInput = document.getElementById('payoutAccountNumber');
        const payoutBankNameSelect = document.getElementById('paystackBankName');
        const payoutBankCodeInput = document.getElementById('paystackBankCode');

        const autoFillPayoutDetails = (item, hiddenTargetId) => {
            if (hiddenTargetId !== 'prefundStationId' || !item) return;

            const accountName = (item.payout_account_name || '').trim();
            const accountNumber = (item.payout_account_number || '').trim();
            const bankName = (item.payout_bank_name || '').trim();
            const bankCode = (item.payout_bank_code || '').trim();

            if (accountName && payoutAccountNameInput) {
                payoutAccountNameInput.value = accountName;
            }
            if (accountNumber && payoutAccountNumberInput) {
                payoutAccountNumberInput.value = accountNumber;
            }
            if (bankCode && payoutBankCodeInput) {
                payoutBankCodeInput.value = bankCode;
            }
            if (bankName && payoutBankNameSelect) {
                payoutBankNameSelect.value = bankName;
                if (!payoutBankNameSelect.value) {
                    const dynamic = document.createElement('option');
                    dynamic.value = bankName;
                    dynamic.textContent = bankCode ? `${bankName} (${bankCode})` : bankName;
                    dynamic.dataset.code = bankCode;
                    payoutBankNameSelect.appendChild(dynamic);
                    payoutBankNameSelect.value = bankName;
                }
            }
        };

        inputs.forEach((input) => {
            if (input.dataset.typeaheadBound === '1') return;
            input.dataset.typeaheadBound = '1';

            const hiddenId = input.dataset.hiddenTarget;
            const hidden = hiddenId ? document.getElementById(hiddenId) : null;
            const suggestions = input.parentElement.querySelector('.js-station-suggestions');
            if (!hidden || !suggestions) return;

            const clearSelection = () => {
                hidden.value = '';
            };

            const closeSuggestions = () => {
                suggestions.classList.add('hidden');
                suggestions.innerHTML = '';
            };

            const maybeAutoSubmit = () => {
                if (input.dataset.autoSubmit === '1' && input.form) {
                    input.form.submit();
                }
            };

            const renderSuggestions = (items) => {
                if (!Array.isArray(items) || items.length === 0) {
                    suggestions.innerHTML = '<p class="px-3 py-2 text-xs text-slate-500">No stations found.</p>';
                    suggestions.classList.remove('hidden');
                    return;
                }

                suggestions.innerHTML = items.map((item) => {
                    const partnerTag = item.partner
                        ? '<span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Partner</span>'
                        : '<span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">Individual</span>';
                    const readyTag = item.payout_ready
                        ? '<span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Ready</span>'
                        : '<span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Blocked</span>';

                    return `
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0 border-slate-100 station-suggestion"
                            data-id="${item.id}"
                            data-label="${(item.label || '').replace(/"/g, '&quot;')}">
                            <p class="text-xs font-semibold text-slate-800">${item.label || ('Station #' + item.id)}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">${item.city || 'Unknown city'}</p>
                            <div class="mt-1 flex gap-1">${partnerTag}${readyTag}</div>
                        </button>
                    `;
                }).join('');

                suggestions.classList.remove('hidden');

                suggestions.querySelectorAll('.station-suggestion').forEach((button) => {
                    button.addEventListener('click', () => {
                        hidden.value = button.dataset.id || '';
                        input.value = button.dataset.label || '';
                        const selected = items.find((row) => String(row.id) === String(button.dataset.id));
                        autoFillPayoutDetails(selected, hiddenId);
                        closeSuggestions();
                        maybeAutoSubmit();
                    });
                });
            };

            const fetchSuggestions = debounce(async () => {
                const q = input.value.trim();
                const params = new URLSearchParams({ limit: '12' });
                if (q.length > 0) params.set('q', q);
                if (input.dataset.brandValue) params.set('brand', input.dataset.brandValue);

                try {
                    const resp = await fetch(`${endpoint}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!resp.ok) throw new Error('Failed');
                    const data = await resp.json();
                    renderSuggestions(data.items || []);
                } catch (e) {
                    suggestions.innerHTML = '<p class="px-3 py-2 text-xs text-rose-600">Failed to load stations.</p>';
                    suggestions.classList.remove('hidden');
                }
            }, 240);

            input.addEventListener('input', () => {
                clearSelection();
                fetchSuggestions();
            });

            input.addEventListener('focus', () => {
                fetchSuggestions();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeSuggestions();
                }
            });

            input.addEventListener('blur', () => {
                window.setTimeout(() => {
                    if (!hidden.value && input.dataset.autoSubmit === '1') {
                        input.value = '';
                        maybeAutoSubmit();
                    }
                    closeSuggestions();
                }, 150);
            });
        });
    }

    initStationTypeaheads();

    async function initPaystackBankPicker() {
        const bankSelect = document.getElementById('paystackBankName');
        const codeInput = document.getElementById('paystackBankCode');
        if (!bankSelect || !codeInput) return;

        const endpoint = '{{ route('admin.settlements.api.paystack-banks') }}';
        const existingBankName = (bankSelect.value || @json(old('payout_bank_name')) || '').trim();
        const existingBankCode = (codeInput.value || @json(old('payout_bank_code')) || '').trim();

        try {
            const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
            const payload = await response.json();
            const items = Array.isArray(payload.items) ? payload.items : [];

            bankSelect.innerHTML = '<option value="">Select bank (Paystack)</option>';

            items.forEach((bank) => {
                const option = document.createElement('option');
                option.value = bank.name || '';
                option.textContent = `${bank.name || 'Unknown'} (${bank.code || '-'})`;
                option.dataset.code = bank.code || '';
                if (existingBankName && bank.name === existingBankName) {
                    option.selected = true;
                }
                bankSelect.appendChild(option);
            });

            if (existingBankCode) {
                codeInput.value = existingBankCode;
            } else if (bankSelect.selectedOptions.length > 0) {
                codeInput.value = bankSelect.selectedOptions[0].dataset.code || '';
            }

            bankSelect.addEventListener('change', () => {
                const selected = bankSelect.selectedOptions[0];
                codeInput.value = selected?.dataset?.code || '';
            });
        } catch (error) {
            bankSelect.innerHTML = '<option value="">Could not load Paystack banks</option>';
        }
    }

    initPaystackBankPicker();

    // Auto-submit date changes
    document.querySelectorAll('input[type="date"], select[name="status"], .js-filter-field').forEach(element => {
        element.addEventListener('change', function() {
            if (this.form) this.form.submit();
        });
    });

    // Handle fail form submission
    document.getElementById('failForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        submitButton.disabled = true;
        
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Direct Bank Deposit marked as failed!');
                location.reload();
            } else {
                alert('Failed to mark direct bank deposit as failed');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        });
    });

    // Handle bulk process form
    document.getElementById('bulkProcessForm')?.addEventListener('submit', function(e) {
        const submitButton = this.querySelector('#bulkProcessBtn');
        const originalText = submitButton.innerHTML;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        submitButton.disabled = true;
    });
</script>
@endsection
