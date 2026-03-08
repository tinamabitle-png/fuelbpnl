@extends('Layouts.app')

@section('title', 'Apply for Voucher - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Apply for Fuel Voucher</h1>
            <p class="text-slate-600 mt-2">Select a station, estimate liters by fuel type, and request a voucher.</p>
        </div>
        <a href="{{ route('driver.vouchers.index') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Vouchers</a>
    </div>
    @include('driver.partials.nav', ['backUrl' => route('driver.vouchers.index')])

    @php
        $oldStation = collect($stationsPayload)->firstWhere('id', (int) old('fuel_station_id'));
        $popularBrandMeta = collect([
            ['name' => 'Shell', 'slug' => 'shell-sa'],
            ['name' => 'BP', 'slug' => 'bp-southern-africa'],
            ['name' => 'Engen', 'slug' => 'engen'],
            ['name' => 'Sasol', 'slug' => 'sasol'],
            ['name' => 'Astron Energy', 'slug' => 'astron-energy'],
            ['name' => 'TotalEnergies', 'slug' => 'totalenergies'],
        ])->filter(function ($brand) {
            return file_exists(public_path('images/brands/' . $brand['slug'] . '.png'));
        })->values();
        $allowedBrands = $popularBrandMeta->pluck('name')->all();
        $stationBrands = collect($stationsPayload)
            ->pluck('brand')
            ->map(fn ($brand) => trim((string) $brand))
            ->filter()
            ->filter(fn ($brand) => in_array($brand, $allowedBrands, true))
            ->unique()
            ->sort()
            ->values();
        $brandSlugByName = $popularBrandMeta->mapWithKeys(fn ($brand) => [$brand['name'] => $brand['slug']])->all();
    @endphp

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Voucher Request Form</h2>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                    <p class="text-[11px] uppercase tracking-wide text-emerald-700 font-semibold">Your Eligible Limit</p>
                    <p class="text-sm font-semibold text-emerald-800 mt-1">R {{ number_format((float) ($maxEligibleAmount ?? 0), 2) }}</p>
                </div>
                <div id="stationCapacityHint" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    Select a station to view its available voucher capacity.
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wide">Search</label>
                <input
                    id="fuel_station_search"
                    type="text"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl bg-white"
                    placeholder="Search by station name, city, or address..."
                    value=""
                    autocomplete="off"
                >
                <div class="mt-3 flex flex-wrap gap-2" id="brandLogoGrid">
                    <button type="button" class="brand-logo-chip active" data-brand="" aria-label="All brands" title="All brands">
                        <span class="brand-logo-pill">ALL</span>
                    </button>
                    @foreach($stationBrands as $brand)
                        @php
                            $brandSlug = $brandSlugByName[$brand] ?? null;
                            $brandLogoPath = public_path('images/brands/' . $brandSlug . '.png');
                            $brandLogoUrl = file_exists($brandLogoPath) ? asset('images/brands/' . $brandSlug . '.png') : null;
                        @endphp
                        @if($brandLogoUrl)
                            <button type="button" class="brand-logo-chip" data-brand="{{ $brand }}" aria-label="{{ $brand }}" title="{{ $brand }}">
                                <img src="{{ $brandLogoUrl }}" alt="{{ $brand }} logo" loading="lazy">
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>

            <form action="{{ route('driver.vouchers.store') }}" method="POST" class="mt-5 space-y-5" id="driverVoucherForm">
                @csrf
                <input type="hidden" id="fuel_station_id" name="fuel_station_id" value="{{ old('fuel_station_id') }}">

                @error('fuel_station_id')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Amount (ZAR)</label>
                        <input id="amount" type="number" step="0.01" min="10" max="{{ max(10, (int) floor((float) ($maxEligibleAmount ?? 100000))) }}" name="amount" value="{{ old('amount', 500) }}" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl" required>
                        <p id="amountEligibilityHint" class="text-xs text-slate-500 mt-1">Maximum eligible amount: R {{ number_format((float) ($maxEligibleAmount ?? 0), 2) }}.</p>
                        @error('amount')
                            <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Fuel Type</label>
                        <select id="fuel_type" name="fuel_type" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl" required>
                            <option value="petrol" @selected(old('fuel_type') === 'petrol')>Petrol</option>
                            <option value="diesel" @selected(old('fuel_type') === 'diesel')>Diesel</option>
                            <option value="super" @selected(old('fuel_type') === 'super')>Super</option>
                        </select>
                        @error('fuel_type')
                            <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Repayment Frequency</label>
                    <select id="repayment_frequency" name="repayment_frequency" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl" required>
                        <option value="daily" @selected(old('repayment_frequency', 'daily') === 'daily')>Daily</option>
                        <option value="weekly" @selected(old('repayment_frequency') === 'weekly')>Weekly</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Choose how often repayment installments are due.</p>
                    @error('repayment_frequency')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Liters (optional)</label>
                        <input id="liters" type="number" step="0.01" min="0.1" name="liters" value="{{ old('liters') }}" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl">
                        <p class="text-xs text-slate-500 mt-1">Leave empty to auto-calculate from station price.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Estimated Station Price</label>
                        <div id="stationPriceCard" class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-600">
                            Select station and fuel type
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Voucher Reference (optional)</label>
                        <input id="voucher_reference"
                               type="text"
                               name="voucher_reference"
                               maxlength="120"
                               value="{{ old('voucher_reference') }}"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl"
                               placeholder="e.g. TRIP-8433 / SHIFT-A">
                        @error('voucher_reference')
                            <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Card / Fleet Details (optional)</label>
                        <input id="card_reference"
                               type="text"
                               name="card_reference"
                               maxlength="50"
                               value="{{ old('card_reference') }}"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl"
                               placeholder="e.g. Shell card ****1234">
                        @error('card_reference')
                            <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                    <p class="text-sm font-medium text-slate-700">Repayment Flow Selector</p>
                    <p class="text-sm text-slate-600 mt-2">
                        Move the slider to choose repayment days. Longer repayment duration increases the interest rate.
                    </p>

                    <input type="hidden" name="repayment_days" id="repayment_days" value="{{ old('repayment_days', (int) $leaseDefaults['term_days']) }}">

                    <div class="repayment-control-wrap mt-4">
                        <div class="repayment-control" id="repaymentControl">
                            <input
                                id="repaymentDaysTrack"
                                type="range"
                                min="{{ (int) $leaseDefaults['min_days'] }}"
                                max="{{ (int) $leaseDefaults['max_days'] }}"
                                value="{{ old('repayment_days', (int) $leaseDefaults['term_days']) }}"
                            >
                            <div aria-hidden="true" class="repayment-tooltip">
                                <span id="termDaysLabel">{{ old('repayment_days', (int) $leaseDefaults['term_days']) }} Days</span>
                                <span id="termRateLabel">{{ number_format((float) $leaseDefaults['rate'], 2) }}% Interest</span>
                            </div>
                            <label for="repaymentDaysTrack" class="sr-only">Repayment days selector</label>
                            <div class="repayment-track">
                                <div class="repayment-track-slide">
                                    <div class="repayment-fill"></div>
                                    <div class="repayment-indicator"></div>
                                    <div class="repayment-fill"></div>
                                </div>
                            </div>
                        </div>
                    <div class="flex items-center justify-between mt-2 text-xs text-slate-500">
                        <span>{{ (int) $leaseDefaults['min_days'] }} days</span>
                        <span>{{ (int) $leaseDefaults['max_days'] }} days</span>
                    </div>
                    <p id="repaymentRuleMessage" class="mt-2 text-xs font-medium text-slate-500">
                        Minimum repayment: R{{ number_format((float) ($leaseDefaults['min_daily_repayment'] ?? 30), 2) }} per day.
                    </p>
                </div>

                    @error('repayment_days')
                        <p class="text-sm text-rose-600 mt-2">{{ $message }}</p>
                    @enderror

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="px-4 py-2.5 border border-slate-200 rounded-xl bg-white">
                            <p class="text-xs text-slate-500">Projected Interest Rate</p>
                            <p id="projectedRate" class="text-sm font-semibold text-slate-900 mt-1">{{ number_format((float) $leaseDefaults['rate'], 2) }}%</p>
                        </div>
                        <div class="px-4 py-2.5 border border-slate-200 rounded-xl bg-white">
                            <p id="projectedRepaymentLabel" class="text-xs text-slate-500">Projected Daily Repayment</p>
                            <p id="projectedDaily" class="text-sm font-semibold text-slate-900 mt-1">R 0.00</p>
                        </div>
                        <div class="px-4 py-2.5 border border-slate-200 rounded-xl bg-white">
                            <p class="text-xs text-slate-500">Projected Total Repayment</p>
                            <p id="projectedTotal" class="text-sm font-semibold text-slate-900 mt-1">R 0.00</p>
                        </div>
                        <div class="px-4 py-2.5 border border-slate-200 rounded-xl bg-white">
                            <p class="text-xs text-slate-500">Base Policy</p>
                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ number_format((float) $leaseDefaults['rate'], 2) }}% / {{ (int) $leaseDefaults['term_days'] }} days</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full px-4 py-3 rounded-xl text-sm font-semibold">Submit Voucher Request</button>
            </form>
        </div>

        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <h2 class="brand-font text-xl text-slate-900">Station Map & Route</h2>
                <button id="routeButton" type="button" class="btn-ghost px-3 py-2 rounded-lg text-sm font-semibold">Route from my location</button>
            </div>

            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <label class="block text-sm font-semibold text-slate-700 mb-3">Choose Fuel Station</label>
                <div class="mt-3">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label for="fuel_station_picker" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Station</label>
                            <span id="stationPickerMeta" class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600">0 stations</span>
                        </div>
                        <div class="station-picker-shell">
                            <select id="fuel_station_picker" size="5" class="station-picker w-full px-4 py-2.5 border border-slate-300 rounded-xl" required></select>
                        </div>
                    </div>
                </div>
                <p id="stationAddressHint" class="text-xs text-slate-500 mt-2">Choose a brand and station to see address details.</p>
            </div>

            @if(!config('services.google_maps.key'))
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Google Maps API key not configured. Set <code>GOOGLE_MAPS_API_KEY</code> to enable map and route drawing.
                </div>
            @endif

            <div id="mapStatus" class="mt-4 text-sm text-slate-500">Select a station to preview it on the map.</div>
            <div id="routeSummary" class="mt-2 text-sm text-slate-600"></div>
            <div id="stationsMap" class="mt-4 h-[420px] rounded-xl border border-slate-200 bg-slate-100"></div>
        </div>
    </div>
</section>

@php
    $stationsJson = $stationsPayload->toJson();
    $leaseDefaultsJson = collect($leaseDefaults)->toJson();
    $googleMapsApiKey = (string) config('services.google_maps.key');
@endphp
<style>
    .repayment-control-wrap {
        max-width: 100%;
    }

    .brand-logo-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        padding: 0;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        transition: all 0.18s ease;
    }

    .brand-logo-chip:hover {
        border-color: #60a5fa;
        background: #eff6ff;
        color: #1e40af;
    }

    .brand-logo-chip.active {
        border-color: #2563eb;
        background: #dbeafe;
        color: #1e3a8a;
    }

    .brand-logo-chip img,
    .brand-logo-pill {
        width: 1.3rem;
        height: 1.3rem;
        border-radius: 999px;
        display: grid;
        place-items: center;
        object-fit: contain;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 0.56rem;
        font-weight: 800;
    }

    #brandLogoGrid {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        padding-bottom: 0.15rem;
        scrollbar-width: thin;
    }

    #brandLogoGrid::-webkit-scrollbar {
        height: 8px;
    }

    #brandLogoGrid::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 999px;
    }

    #brandLogoGrid::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .station-picker-shell {
        position: relative;
        border-radius: 0.9rem;
        border: 1px solid #dbe2ea;
        background: #ffffff;
        box-shadow: 0 16px 36px -30px rgba(15, 23, 42, 0.5);
        overflow: hidden;
    }

    .station-picker-shell::before,
    .station-picker-shell::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        height: 5.2rem;
        pointer-events: none;
        z-index: 2;
    }

    .station-picker-shell::before {
        top: 0;
        background: linear-gradient(to bottom, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0));
    }

    .station-picker-shell::after {
        bottom: 0;
        background: linear-gradient(to top, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0));
    }

    .station-picker {
        appearance: none;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent;
        font-size: 0.84rem;
        line-height: 1.35;
        color: #1e293b;
        min-height: 10.5rem;
        max-height: 13.5rem;
        padding: 0.5rem 0.7rem !important;
        outline: none;
        mask-image: linear-gradient(to bottom, transparent 0%, black 26%, black 74%, transparent 100%);
        -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 26%, black 74%, transparent 100%);
    }

    .station-picker:focus {
        box-shadow: inset 0 0 0 2px rgba(37, 99, 235, 0.18);
    }

    .station-picker optgroup {
        font-weight: 700;
        color: #2563eb;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 0.35rem 0.35rem;
    }

    .station-picker option {
        background: #ffffff;
        color: rgba(30, 41, 59, 0.72);
        padding: 0.52rem 0.6rem;
        border-radius: 0.55rem;
        margin: 0.12rem 0;
        white-space: normal;
    }

    .station-picker option:checked {
        background: #dbeafe linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e3a8a;
        font-weight: 700;
    }

    .station-picker option:hover {
        background: #f1f5f9;
    }

    .station-picker::-webkit-scrollbar {
        width: 10px;
    }

    .station-picker::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    .station-picker::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
        border: 2px solid #f8fafc;
    }

    .station-picker::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .repayment-control {
        --value: 0;
        --shift: 0;
        --active: 0;
        --repay-g1: var(--primary-dark, #1d4ed8);
        --repay-g2: var(--primary, #2563eb);
        --repay-g3: var(--accent, #38bdf8);
        --repay-light: rgba(255, 255, 255, 0.7);
        position: relative;
        display: grid;
        place-items: center;
        width: 100%;
        min-height: 76px;
    }

    .repayment-control:focus-within,
    .repayment-control:hover {
        --active: 1;
    }

    .repayment-control input[type='range'] {
        width: 100%;
        height: 60px;
        opacity: 0;
        position: relative;
        z-index: 4;
        cursor: pointer;
        margin: 0;
    }

    .repayment-tooltip {
        font-size: 0.74rem;
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        height: 32px;
        pointer-events: none;
        z-index: 5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #0f172a;
        font-weight: 700;
    }

    .repayment-track {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 36px;
        border-radius: 10px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        overflow: hidden;
        pointer-events: none;
        z-index: 2;
        background: linear-gradient(92deg, var(--repay-g1) 0%, var(--repay-g2) 55%, var(--repay-g3) 100%);
    }

    .repayment-track-slide {
        height: 100%;
        width: 100%;
        position: relative;
        transform: translateX(calc(-50% + (var(--value) * 1%)));
        transition: transform 120ms linear;
    }

    .repayment-fill {
        position: absolute;
        height: 100%;
        width: 100%;
        border-radius: 8px;
    }

    .repayment-fill:nth-of-type(1) {
        right: calc(50% + 0.35rem);
        background: color-mix(in srgb, var(--primary-dark, #1d4ed8) 74%, transparent);
    }

    .repayment-fill:nth-of-type(3) {
        left: calc(50% + 0.35rem);
        background: color-mix(in srgb, var(--repay-light) 84%, transparent);
    }

    .repayment-indicator {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 4px;
        height: 76%;
        border-radius: 4px;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, calc((var(--active) * 0.4) + 0.5));
    }
</style>
<script>
    const driverStations = {!! $stationsJson !!};
    const allowedBrands = @json($stationBrands->values());
    const allowedBrandSet = new Set((allowedBrands || []).map((brand) => String(brand).trim()));
    const initialBrandFilter = @json($oldStation['brand'] ?? '');
    const maxEligibleAmount = Number(@json((float) ($maxEligibleAmount ?? 100000)));
    const leaseDefaults = {!! $leaseDefaultsJson !!};
    const mapsApiKey = @json($googleMapsApiKey);
    let stationsMap;
    let mapLoadPromise = null;
    let selectedStation = null;
    let filteredStations = [];
    let userLocation = null;
    const markers = [];
    let projectionFrame = null;
    let activeBrandFilter = String(initialBrandFilter || '').trim();

    const stationIdInput = document.getElementById('fuel_station_id');
    const brandLogoChips = Array.from(document.querySelectorAll('.brand-logo-chip'));
    const stationSearchInput = document.getElementById('fuel_station_search');
    const stationPicker = document.getElementById('fuel_station_picker');
    const stationAddressHint = document.getElementById('stationAddressHint');
    const stationPickerMeta = document.getElementById('stationPickerMeta');
    const fuelTypeSelect = document.getElementById('fuel_type');
    const repaymentFrequencySelect = document.getElementById('repayment_frequency');
    const amountInput = document.getElementById('amount');
    const litersInput = document.getElementById('liters');
    const stationPriceCard = document.getElementById('stationPriceCard');
    const stationCapacityHint = document.getElementById('stationCapacityHint');
    const amountEligibilityHint = document.getElementById('amountEligibilityHint');
    const repaymentDaysInput = document.getElementById('repayment_days');
    const repaymentDaysTrack = document.getElementById('repaymentDaysTrack');
    const termDaysLabel = document.getElementById('termDaysLabel');
    const termRateLabel = document.getElementById('termRateLabel');
    const projectedRate = document.getElementById('projectedRate');
    const projectedRepaymentLabel = document.getElementById('projectedRepaymentLabel');
    const projectedDaily = document.getElementById('projectedDaily');
    const projectedTotal = document.getElementById('projectedTotal');
    const repaymentRuleMessage = document.getElementById('repaymentRuleMessage');
    const submitVoucherButton = document.querySelector('#driverVoucherForm button[type=\"submit\"]');
    const repaymentControl = document.getElementById('repaymentControl');
    const routeButton = document.getElementById('routeButton');
    const mapStatus = document.getElementById('mapStatus');
    const routeSummary = document.getElementById('routeSummary');
    const voucherForm = document.getElementById('driverVoucherForm');
    let lastDailyRepaymentValid = true;
    let submitInProgress = false;

    function getCurrentAmount() {
        return Number(amountInput?.value || 0);
    }

    function getSelectedStationCapacity() {
        const station = getStationById(stationIdInput.value);
        return station ? Number(station.available_capacity ?? 0) : null;
    }

    function refreshSubmitState() {
        const amount = getCurrentAmount();
        const stationId = Number(stationIdInput.value || 0);
        const hasStation = stationId > 0;
        const selectedCapacity = getSelectedStationCapacity();
        const exceedsEligible = amount > maxEligibleAmount;
        const exceedsCapacity = hasStation && selectedCapacity !== null && amount > selectedCapacity;
        const canSubmit = hasStation && lastDailyRepaymentValid && !exceedsEligible && !exceedsCapacity && !submitInProgress;

        if (submitVoucherButton) {
            submitVoucherButton.disabled = !canSubmit;
            submitVoucherButton.classList.toggle('opacity-60', !canSubmit);
            submitVoucherButton.classList.toggle('cursor-not-allowed', !canSubmit);
        }
    }

    function updateEligibilityAndCapacityHints() {
        const amount = getCurrentAmount();
        const station = getStationById(stationIdInput.value);

        if (amountEligibilityHint) {
            if (amount > maxEligibleAmount) {
                amountEligibilityHint.textContent = `Amount exceeds your current eligible limit of R ${maxEligibleAmount.toFixed(2)}.`;
                amountEligibilityHint.className = 'text-xs text-rose-600 mt-1 font-medium';
            } else {
                amountEligibilityHint.textContent = `Maximum eligible amount: R ${maxEligibleAmount.toFixed(2)}.`;
                amountEligibilityHint.className = 'text-xs text-slate-500 mt-1';
            }
        }

        if (stationCapacityHint) {
            if (!station) {
                stationCapacityHint.textContent = 'Select a station to view its available voucher capacity.';
                stationCapacityHint.className = 'rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600';
            } else {
                const available = Number(station.available_capacity ?? 0);
                const openExposure = Number(station.open_exposure ?? 0);
                const walletBalance = Number(station.wallet_balance ?? 0);
                const overCapacity = amount > available;
                stationCapacityHint.textContent = `${station.name}: Available capacity R ${available.toFixed(2)} (Wallet R ${walletBalance.toFixed(2)} • Open exposure R ${openExposure.toFixed(2)}).`;
                stationCapacityHint.className = overCapacity
                    ? 'rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 font-medium'
                    : 'rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600';
            }
        }
    }

    function calculateLeaseRate(days) {
        const baseRate = Number(leaseDefaults.rate || 5);
        const baseTerm = Number(leaseDefaults.term_days || 30);
        const stepRate = Number(leaseDefaults.rate_per_day || 0.05);
        const deltaDays = Number(days) - baseTerm;
        const rate = baseRate + (deltaDays * stepRate);
        return Math.max(1, Math.min(35, Number(rate.toFixed(2))));
    }

    function updateRepaymentProjection() {
        if (!repaymentDaysTrack) return;

        const days = Number(repaymentDaysTrack.value || leaseDefaults.term_days || 30);
        const minDays = Number(repaymentDaysTrack.min || leaseDefaults.min_days || 7);
        const maxDays = Number(repaymentDaysTrack.max || leaseDefaults.max_days || 90);
        const amount = Number(amountInput.value || 0);
        const repaymentFrequency = String(repaymentFrequencySelect?.value || 'daily').toLowerCase();
        const rate = calculateLeaseRate(days);
        const total = amount + (amount * (rate / 100));
        const daily = days > 0 ? (total / days) : 0;
        const weeklyInstallments = Math.max(1, Math.ceil(days / 7));
        const weekly = weeklyInstallments > 0 ? (total / weeklyInstallments) : total;
        const minDailyRepayment = Number(leaseDefaults.min_daily_repayment || 30);
        const isDailyRepaymentValid = daily >= minDailyRepayment;
        const ratio = ((days - minDays) / Math.max(maxDays - minDays, 1)) * 100;

        repaymentDaysInput.value = String(days);
        termDaysLabel.textContent = `${days} Days`;
        termRateLabel.textContent = `${rate.toFixed(2)}% Interest`;
        projectedRate.textContent = `${rate.toFixed(2)}%`;
        projectedTotal.textContent = `R ${total.toFixed(2)}`;
        projectedDaily.textContent = repaymentFrequency === 'weekly'
            ? `R ${weekly.toFixed(2)}`
            : `R ${daily.toFixed(2)}`;
        if (projectedRepaymentLabel) {
            projectedRepaymentLabel.textContent = repaymentFrequency === 'weekly'
                ? 'Projected Weekly Repayment'
                : 'Projected Daily Repayment';
        }

        if (repaymentControl) {
            repaymentControl.style.setProperty('--value', String(Math.max(0, Math.min(100, ratio))));
            repaymentControl.style.setProperty('--shift', ratio > 40 && ratio < 68 ? '1' : '0');
        }

        if (repaymentRuleMessage) {
            if (isDailyRepaymentValid) {
                repaymentRuleMessage.textContent = `Minimum repayment: R${minDailyRepayment.toFixed(2)} per day.`;
                repaymentRuleMessage.className = 'mt-2 text-xs font-medium text-emerald-700';
            } else {
                repaymentRuleMessage.textContent = `Projected repayment is below R${minDailyRepayment.toFixed(2)}. Increase amount or reduce days.`;
                repaymentRuleMessage.className = 'mt-2 text-xs font-medium text-rose-600';
            }
        }

        lastDailyRepaymentValid = isDailyRepaymentValid;
        refreshSubmitState();
    }

    function queueProjectionUpdate() {
        if (projectionFrame) return;
        projectionFrame = window.requestAnimationFrame(() => {
            projectionFrame = null;
            updateRepaymentProjection();
        });
    }

    function getStationById(id) {
        return driverStations.find((station) => Number(station.id) === Number(id)) || null;
    }

    function stationLabel(station, withAddress = false) {
        const brand = station.brand ? `${station.brand} - ` : '';
        const city = station.city ? ` (${station.city})` : '';
        const addressText = String(station.address || '').trim();
        const shortAddress = addressText.length > 48 ? `${addressText.slice(0, 48)}...` : addressText;
        const address = withAddress && shortAddress ? ` • ${shortAddress}` : '';
        return `${brand}${station.name}${city}${address}`;
    }

    function stationMatchesSearch(station, searchTerm) {
        if (!searchTerm) return true;
        const needle = searchTerm.toLowerCase();
        const haystack = [
            station.name || '',
            station.brand || '',
            station.city || '',
            station.address || '',
        ].join(' ').toLowerCase();
        return haystack.includes(needle);
    }

    function stationDistance(station) {
        if (!userLocation || !station.latitude || !station.longitude) return Number.POSITIVE_INFINITY;

        const lat1 = Number(userLocation.latitude);
        const lon1 = Number(userLocation.longitude);
        const lat2 = Number(station.latitude);
        const lon2 = Number(station.longitude);

        const toRad = (value) => (value * Math.PI) / 180;
        const r = 6371;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return r * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    function getFilteredStations() {
        const selectedBrand = activeBrandFilter;
        const searchTerm = stationSearchInput.value.trim();

        return driverStations
            .filter((station) => {
                const stationBrand = String(station.brand || '').trim();
                if (!allowedBrandSet.has(stationBrand)) return false;
                if (selectedBrand && String(station.brand || '').trim() !== selectedBrand) return false;
                return stationMatchesSearch(station, searchTerm);
            })
            .sort((a, b) => {
                const distanceDelta = stationDistance(a) - stationDistance(b);
                if (Number.isFinite(distanceDelta) && Math.abs(distanceDelta) > 0.0001) return distanceDelta;
                return String(a.name || '').localeCompare(String(b.name || ''));
            });
    }

    function renderStationPicker() {
        const stations = getFilteredStations();
        filteredStations = stations;
        stationPicker.innerHTML = '';
        stationPicker.size = Math.max(4, Math.min(6, stations.length || 4));
        if (stationPickerMeta) {
            const brand = activeBrandFilter || 'All brands';
            stationPickerMeta.textContent = `${stations.length} station${stations.length === 1 ? '' : 's'} • ${brand}`;
        }

        if (!stations.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No stations match this brand/search.';
            stationPicker.appendChild(option);
            stationIdInput.value = '';
            selectedStation = null;
            stationAddressHint.textContent = 'No matching station found. Try a different brand or search term.';
            updatePriceEstimate();
            return;
        }

        const byBrand = new Map();
        stations.forEach((station) => {
            const brand = String(station.brand || 'Other').trim() || 'Other';
            if (!byBrand.has(brand)) byBrand.set(brand, []);
            byBrand.get(brand).push(station);
        });

        byBrand.forEach((brandStations, brand) => {
            const group = document.createElement('optgroup');
            group.label = brand;

            brandStations.forEach((station) => {
                const option = document.createElement('option');
                option.value = String(station.id);
                const distance = stationDistance(station);
                const distanceHint = Number.isFinite(distance) ? ` • ${distance.toFixed(1)}km` : '';
                option.textContent = stationLabel(station, true) + distanceHint;
                group.appendChild(option);
            });

            stationPicker.appendChild(group);
        });

        const currentId = Number(stationIdInput.value || 0);
        const hasCurrent = stations.some((station) => Number(station.id) === currentId);
        const selectedId = hasCurrent ? String(currentId) : String(stations[0].id);
        stationPicker.value = selectedId;
        stationIdInput.value = selectedId;
        applyStationSelection(selectedId);
        requestAnimationFrame(centerSelectedStationOption);
    }

    function centerSelectedStationOption() {
        if (!stationPicker) return;
        const selectedIndex = stationPicker.selectedIndex;
        if (selectedIndex < 0) return;
        const selected = stationPicker.options[selectedIndex];
        if (!selected) return;

        const optionTop = selected.offsetTop;
        const optionHeight = selected.offsetHeight || 24;
        const targetTop = optionTop - ((stationPicker.clientHeight / 2) - (optionHeight / 2));
        stationPicker.scrollTop = Math.max(0, targetTop);
    }

    function syncBrandChipState() {
        const selectedBrand = activeBrandFilter;
        brandLogoChips.forEach((chip) => {
            chip.classList.toggle('active', (chip.dataset.brand || '') === selectedBrand);
        });
    }

    function applyStationSelection(stationId) {
        const station = getStationById(stationId);
        if (station) {
            stationIdInput.value = String(station.id);
            focusStationOnMap(station);
            stationAddressHint.textContent = `${station.brand || 'Brand'} • ${station.address || station.city || 'Address unavailable'}`;
        } else {
            stationIdInput.value = '';
            selectedStation = null;
            mapStatus.textContent = 'Select a station to preview it on the map.';
            routeSummary.textContent = '';
            stationAddressHint.textContent = 'Choose a brand and station to see address details.';
        }
        updatePriceEstimate();
    }

    function updatePriceEstimate() {
        const stationId = stationIdInput.value;
        const fuelType = fuelTypeSelect.value;
        const station = getStationById(stationId);

        if (!station) {
            stationPriceCard.textContent = 'Select station and fuel type';
            updateEligibilityAndCapacityHints();
            refreshSubmitState();
            return;
        }

        const price = station.prices?.[fuelType];
        if (!price) {
            stationPriceCard.textContent = 'Price unavailable for selected fuel type at this station';
            updateEligibilityAndCapacityHints();
            refreshSubmitState();
            return;
        }

        const amount = parseFloat(amountInput.value || '0');
        const estimatedLiters = amount > 0 ? (amount / price) : null;
        stationPriceCard.textContent = `R ${Number(price).toFixed(2)} / L` + (estimatedLiters ? ` • ~${estimatedLiters.toFixed(2)} L` : '');

        if (!litersInput.value && estimatedLiters) {
            litersInput.placeholder = estimatedLiters.toFixed(2);
        }

        updateEligibilityAndCapacityHints();
        refreshSubmitState();
    }

    function focusStationOnMap(station) {
        selectedStation = station;
        if (!station || !station.latitude || !station.longitude) return;

        if (!stationsMap) {
            ensureMapLoaded().then((loaded) => {
                if (loaded) {
                    focusStationOnMap(station);
                }
            });
            return;
        }

        const target = { lat: Number(station.latitude), lng: Number(station.longitude) };
        stationsMap.panTo(target);
        stationsMap.setZoom(12);
        mapStatus.textContent = `Selected: ${station.name}, ${station.city}`;
    }

    function drawRouteToSelected() {
        if (!selectedStation || !selectedStation.latitude || !selectedStation.longitude) {
            routeSummary.textContent = 'Select a station with map coordinates first.';
            return;
        }
        if (!navigator.geolocation) {
            routeSummary.textContent = 'Geolocation is not supported by your browser.';
            return;
        }
        ensureMapLoaded().then((loaded) => {
            if (!loaded) {
                routeSummary.textContent = 'Map services are not ready.';
                return;
            }

            routeSummary.textContent = 'Getting your location...';
            navigator.geolocation.getCurrentPosition((position) => {
                const origin = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                const destination = {
                    lat: Number(selectedStation.latitude),
                    lng: Number(selectedStation.longitude)
                };

                const directionsUrl = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(`${origin.lat},${origin.lng}`)}&destination=${encodeURIComponent(`${destination.lat},${destination.lng}`)}&travelmode=driving`;
                window.open(directionsUrl, '_blank', 'noopener,noreferrer');
                routeSummary.textContent = 'Opened driving directions in a new tab.';
            }, () => {
                routeSummary.textContent = 'Location permission denied. Allow location access and try again.';
            });
        });
    }

    function ensureMapLoaded() {
        if (!mapsApiKey) {
            mapStatus.textContent = 'Google Maps API key not configured.';
            return Promise.resolve(false);
        }

        if (stationsMap) {
            return Promise.resolve(true);
        }

        if (window.google?.maps) {
            initDriverStationsMap();
            return Promise.resolve(true);
        }

        if (mapLoadPromise) {
            return mapLoadPromise;
        }

        mapStatus.textContent = 'Loading map services...';
        mapLoadPromise = new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsApiKey)}&libraries=marker&loading=async`;
            script.async = true;
            script.defer = true;
            script.onload = () => {
                initDriverStationsMap();
                resolve(true);
            };
            script.onerror = () => {
                mapStatus.textContent = 'Failed to load map services.';
                resolve(false);
            };
            document.head.appendChild(script);
        });

        return mapLoadPromise;
    }

    function initDriverStationsMap() {
        stationsMap = new google.maps.Map(document.getElementById('stationsMap'), {
            center: { lat: -30.5595, lng: 22.9375 },
            zoom: 5
        });

        driverStations.forEach((station) => {
            if (!station.latitude || !station.longitude) return;
            const position = { lat: Number(station.latitude), lng: Number(station.longitude) };
            const marker = google.maps.marker?.AdvancedMarkerElement
                ? new google.maps.marker.AdvancedMarkerElement({
                    map: stationsMap,
                    position,
                    title: station.name
                })
                : new google.maps.Marker({
                    map: stationsMap,
                    position,
                    title: station.name
                });
            marker.addListener('click', () => {
                stationIdInput.value = String(station.id);
                const brandValue = String(station.brand || '').trim();
                activeBrandFilter = brandValue;
                renderStationPicker();
                stationPicker.value = String(station.id);
                applyStationSelection(String(station.id));
            });
            markers.push(marker);
        });

        const initialStation = getStationById(stationIdInput.value);
        if (initialStation) {
            focusStationOnMap(initialStation);
        }
    }

    brandLogoChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            activeBrandFilter = String(chip.dataset.brand || '').trim();
            syncBrandChipState();
            renderStationPicker();
        });
    });
    stationSearchInput.addEventListener('input', renderStationPicker);
    stationPicker.addEventListener('change', (event) => {
        applyStationSelection(event.target.value);
        requestAnimationFrame(centerSelectedStationOption);
    });
    fuelTypeSelect.addEventListener('change', updatePriceEstimate);
    amountInput.addEventListener('input', updatePriceEstimate);
    amountInput.addEventListener('input', queueProjectionUpdate);
    repaymentDaysTrack?.addEventListener('input', queueProjectionUpdate);
    repaymentFrequencySelect?.addEventListener('change', queueProjectionUpdate);
    routeButton.addEventListener('click', drawRouteToSelected);
    voucherForm.addEventListener('submit', (event) => {
        if (!stationIdInput.value) {
            event.preventDefault();
            alert('Please choose a station from the suggestions.');
            return;
        }

        updateEligibilityAndCapacityHints();
        refreshSubmitState();

        if (submitVoucherButton?.disabled || submitInProgress) {
            event.preventDefault();
            return;
        }

        submitInProgress = true;
        if (submitVoucherButton) {
            submitVoucherButton.disabled = true;
            submitVoucherButton.classList.add('opacity-60', 'cursor-not-allowed');
            submitVoucherButton.textContent = 'Submitting...';
        }
    });

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            userLocation = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            };
            renderStationPicker();
        }, () => {});
    }

    syncBrandChipState();
    renderStationPicker();
    updatePriceEstimate();
    queueProjectionUpdate();
    updateEligibilityAndCapacityHints();
    refreshSubmitState();
</script>
@endsection
