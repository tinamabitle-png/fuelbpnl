@extends('layouts.app')

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

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Voucher Request Form</h2>

            <form action="{{ route('driver.vouchers.store') }}" method="POST" class="mt-5 space-y-5" id="driverVoucherForm">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Fuel Station</label>
                    @php
                        $oldStation = collect($stationsPayload)->firstWhere('id', (int) old('fuel_station_id'));
                    @endphp
                    <input type="hidden" id="fuel_station_id" name="fuel_station_id" value="{{ old('fuel_station_id') }}">
                    <input
                        id="fuel_station_search"
                        list="fuel-station-list"
                        type="text"
                        class="w-full px-4 py-2.5 border border-slate-300 rounded-xl"
                        placeholder="Start typing station name or city..."
                        value="{{ $oldStation ? ($oldStation['name'] . ' - ' . $oldStation['city']) : '' }}"
                        autocomplete="off"
                        required
                    >
                    <datalist id="fuel-station-list">
                        @foreach($stationsPayload as $station)
                            <option value="{{ $station['name'] }} - {{ $station['city'] }}" data-id="{{ $station['id'] }}"></option>
                        @endforeach
                    </datalist>
                    <p class="text-xs text-slate-500 mt-1">Choose a suggested station to continue.</p>
                    @error('fuel_station_id')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Amount (ZAR)</label>
                        <input id="amount" type="number" step="0.01" min="10" max="100000" name="amount" value="{{ old('amount', 500) }}" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl" required>
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
                            <p class="text-xs text-slate-500">Projected Daily Repayment</p>
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
    const leaseDefaults = {!! $leaseDefaultsJson !!};
    const mapsApiKey = @json($googleMapsApiKey);
    let stationsMap;
    let directionsService;
    let directionsRenderer;
    let mapLoadPromise = null;
    let selectedStation = null;
    const markers = [];
    let projectionFrame = null;

    const stationIdInput = document.getElementById('fuel_station_id');
    const stationSearchInput = document.getElementById('fuel_station_search');
    const stationList = document.getElementById('fuel-station-list');
    const fuelTypeSelect = document.getElementById('fuel_type');
    const amountInput = document.getElementById('amount');
    const litersInput = document.getElementById('liters');
    const stationPriceCard = document.getElementById('stationPriceCard');
    const repaymentDaysInput = document.getElementById('repayment_days');
    const repaymentDaysTrack = document.getElementById('repaymentDaysTrack');
    const termDaysLabel = document.getElementById('termDaysLabel');
    const termRateLabel = document.getElementById('termRateLabel');
    const projectedRate = document.getElementById('projectedRate');
    const projectedDaily = document.getElementById('projectedDaily');
    const projectedTotal = document.getElementById('projectedTotal');
    const repaymentRuleMessage = document.getElementById('repaymentRuleMessage');
    const submitVoucherButton = document.querySelector('#driverVoucherForm button[type=\"submit\"]');
    const repaymentControl = document.getElementById('repaymentControl');
    const routeButton = document.getElementById('routeButton');
    const mapStatus = document.getElementById('mapStatus');
    const routeSummary = document.getElementById('routeSummary');

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
        const rate = calculateLeaseRate(days);
        const total = amount + (amount * (rate / 100));
        const daily = days > 0 ? (total / days) : 0;
        const minDailyRepayment = Number(leaseDefaults.min_daily_repayment || 30);
        const isDailyRepaymentValid = daily >= minDailyRepayment;
        const ratio = ((days - minDays) / Math.max(maxDays - minDays, 1)) * 100;

        repaymentDaysInput.value = String(days);
        termDaysLabel.textContent = `${days} Days`;
        termRateLabel.textContent = `${rate.toFixed(2)}% Interest`;
        projectedRate.textContent = `${rate.toFixed(2)}%`;
        projectedTotal.textContent = `R ${total.toFixed(2)}`;
        projectedDaily.textContent = `R ${daily.toFixed(2)}`;

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

        if (submitVoucherButton) {
            submitVoucherButton.disabled = !isDailyRepaymentValid;
            submitVoucherButton.classList.toggle('opacity-60', !isDailyRepaymentValid);
            submitVoucherButton.classList.toggle('cursor-not-allowed', !isDailyRepaymentValid);
        }
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

    function stationLabel(station) {
        return `${station.name} - ${station.city}`;
    }

    function resolveStationIdByLabel(label) {
        const options = stationList.querySelectorAll('option');
        for (const option of options) {
            if (option.value === label) {
                return option.dataset.id || '';
            }
        }
        return '';
    }

    function syncStationFromSearch() {
        const label = stationSearchInput.value.trim();
        const stationId = resolveStationIdByLabel(label);
        stationIdInput.value = stationId;

        const station = getStationById(stationId);
        if (station) {
            focusStationOnMap(station);
        } else {
            selectedStation = null;
            mapStatus.textContent = 'Select a station to preview it on the map.';
            routeSummary.textContent = '';
        }
        updatePriceEstimate();
    }

    function updatePriceEstimate() {
        const stationId = stationIdInput.value;
        const fuelType = fuelTypeSelect.value;
        const station = getStationById(stationId);

        if (!station) {
            stationPriceCard.textContent = 'Select station and fuel type';
            return;
        }

        const price = station.prices?.[fuelType];
        if (!price) {
            stationPriceCard.textContent = 'Price unavailable for selected fuel type at this station';
            return;
        }

        const amount = parseFloat(amountInput.value || '0');
        const estimatedLiters = amount > 0 ? (amount / price) : null;
        stationPriceCard.textContent = `R ${Number(price).toFixed(2)} / L` + (estimatedLiters ? ` • ~${estimatedLiters.toFixed(2)} L` : '');

        if (!litersInput.value && estimatedLiters) {
            litersInput.placeholder = estimatedLiters.toFixed(2);
        }
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
            if (!loaded || !directionsService || !directionsRenderer) {
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

                directionsService.route({
                    origin,
                    destination,
                    travelMode: google.maps.TravelMode.DRIVING
                }, (result, status) => {
                    if (status !== google.maps.DirectionsStatus.OK) {
                        routeSummary.textContent = 'Could not draw route right now.';
                        return;
                    }

                    directionsRenderer.setDirections(result);
                    const leg = result.routes?.[0]?.legs?.[0];
                    if (leg) {
                        routeSummary.textContent = `Route: ${leg.distance.text} • ${leg.duration.text}`;
                    }
                });
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
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsApiKey)}`;
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

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: stationsMap,
            suppressMarkers: false
        });

        driverStations.forEach((station) => {
            if (!station.latitude || !station.longitude) return;
            const marker = new google.maps.Marker({
                map: stationsMap,
                position: { lat: Number(station.latitude), lng: Number(station.longitude) },
                title: station.name
            });
            marker.addListener('click', () => {
                stationIdInput.value = String(station.id);
                stationSearchInput.value = stationLabel(station);
                focusStationOnMap(station);
                updatePriceEstimate();
            });
            markers.push(marker);
        });

        const initialStation = getStationById(stationIdInput.value);
        if (initialStation) {
            focusStationOnMap(initialStation);
        }
    }

    stationSearchInput.addEventListener('change', syncStationFromSearch);
    stationSearchInput.addEventListener('blur', syncStationFromSearch);
    fuelTypeSelect.addEventListener('change', updatePriceEstimate);
    amountInput.addEventListener('input', updatePriceEstimate);
    amountInput.addEventListener('input', queueProjectionUpdate);
    repaymentDaysTrack?.addEventListener('input', queueProjectionUpdate);
    routeButton.addEventListener('click', drawRouteToSelected);

    document.getElementById('driverVoucherForm').addEventListener('submit', (event) => {
        syncStationFromSearch();
        if (!stationIdInput.value) {
            event.preventDefault();
            alert('Please choose a station from the suggestions.');
        }
    });

    updatePriceEstimate();
    queueProjectionUpdate();
</script>
@endsection
