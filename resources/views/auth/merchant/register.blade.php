@extends('Layouts.guest')

@section('title', 'Merchant Registration')
@section('meta_description', 'Register your fuel station or merchant account on Bwiser for voucher redemption and settlements.')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="min-h-screen bg-slate-100 py-10 px-4">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-semibold text-slate-900">Register as Merchant</h1>
            <p class="text-sm text-slate-600 mt-1">Create your merchant account to redeem vouchers at station level.</p>

            <form method="POST" action="{{ route('register.merchant.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700">Business / Contact Name</label>
                    <input name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Phone (South Africa)</label>
                    <input
                        name="phone"
                        type="tel"
                        value="{{ old('phone') }}"
                        required
                        inputmode="tel"
                        autocomplete="tel"
                        pattern="^(\+27|27|0)[6-8][0-9]{8}$"
                        title="Use SA mobile format: +27XXXXXXXXX, 27XXXXXXXXX, or 0XXXXXXXXX"
                        placeholder="+27XXXXXXXXX or 0XXXXXXXXX"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"
                    >
                    <p class="text-xs text-slate-500 mt-1">Accepted formats: `+27XXXXXXXXX`, `27XXXXXXXXX`, or `0XXXXXXXXX`.</p>
                    @error('phone')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @error('email')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">City</label>
                        <input id="merchant_city" name="city" type="text" value="{{ old('city') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Johannesburg">
                        @error('city')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Country</label>
                        <input id="merchant_country" name="country" type="text" value="{{ old('country', 'South Africa') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="South Africa">
                        @error('country')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Business Address</label>
                    <div id="merchantAddressTopSuggestion" class="mt-1 mb-1 hidden">
                        <button
                            type="button"
                            id="merchantAddressTopSuggestionBtn"
                            class="w-full text-left rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 hover:bg-blue-100"
                        >
                            <span class="text-[11px] uppercase tracking-wide text-blue-700 font-semibold">Suggested address</span>
                            <span id="merchantAddressTopSuggestionText" class="block text-xs text-slate-700 truncate mt-0.5"></span>
                        </button>
                    </div>
                    <input id="merchant_address" name="business_address" type="text" value="{{ old('business_address') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Street address, suburb">
                    <div id="merchantAddressSuggestions" class="mt-2 hidden rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden"></div>
                    <p class="text-xs text-slate-500 mt-1">Map updates automatically while you type.</p>
                    @error('business_address')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <input type="hidden" id="merchant_latitude" name="latitude" value="{{ old('latitude') }}">
                <input type="hidden" id="merchant_longitude" name="longitude" value="{{ old('longitude') }}">
                <input type="hidden" id="merchant_station_latitude" name="station_latitude" value="{{ old('station_latitude', old('latitude')) }}">
                <input type="hidden" id="merchant_station_longitude" name="station_longitude" value="{{ old('station_longitude', old('longitude')) }}">
                @error('latitude')<p class="text-xs text-rose-600 -mt-2">{{ $message }}</p>@enderror
                @error('longitude')<p class="text-xs text-rose-600 -mt-2">{{ $message }}</p>@enderror
                @error('station_latitude')<p class="text-xs text-rose-600 -mt-2">{{ $message }}</p>@enderror
                @error('station_longitude')<p class="text-xs text-rose-600 -mt-2">{{ $message }}</p>@enderror

                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <input name="password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @error('password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                    <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>
                @php $selectedFranchise = (string) old('franchise_id', ''); @endphp
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <p class="text-xs uppercase tracking-wide text-slate-600 font-semibold">Select franchise</p>
                    <input type="hidden" name="franchise_id" id="franchise_id" value="{{ $selectedFranchise }}">
                    <div class="grid grid-cols-2 gap-2" id="franchiseGrid">
                        @forelse(($franchises ?? collect()) as $franchise)
                            <button
                                type="button"
                                class="franchise-chip rounded-xl border border-slate-300 bg-white p-3 text-left {{ $selectedFranchise === (string) $franchise['id'] ? 'ring-2 ring-blue-500 border-blue-500' : '' }}"
                                data-id="{{ $franchise['id'] }}"
                            >
                                <div class="h-8 flex items-center justify-center">
                                    @if(!empty($franchise['logo_url']))
                                        <img src="{{ $franchise['logo_url'] }}" alt="{{ $franchise['name'] }} logo" class="h-7 w-full object-contain">
                                    @else
                                        <span class="text-xs font-bold text-slate-700">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($franchise['name'], 0, 2)) }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-xs font-medium text-slate-700 truncate">{{ $franchise['name'] }}</p>
                            </button>
                        @empty
                            <p class="text-xs text-amber-700 col-span-2">No franchise catalog configured yet. Ask admin to add merchant franchises.</p>
                        @endforelse
                    </div>
                    @error('franchise_id')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <p class="text-xs uppercase tracking-wide text-slate-600 font-semibold">Required business documents</p>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="file-drop rounded-xl border-2 border-dashed border-blue-300 bg-white p-4 text-center cursor-pointer" data-target="ck_document">
                            <p class="text-sm font-semibold text-slate-800">CK Document (PDF/JPG/PNG)</p>
                            <p class="text-xs text-slate-500 mt-1">Drag and drop or click to upload (max 8MB)</p>
                            <p class="text-xs text-blue-700 mt-2 file-name" data-name-for="ck_document">No file selected</p>
                            <input type="file" name="ck_document" id="ck_document" accept=".pdf,.jpg,.jpeg,.png" required class="hidden">
                        </div>
                        @error('ck_document')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

                        <div class="file-drop rounded-xl border-2 border-dashed border-blue-300 bg-white p-4 text-center cursor-pointer" data-target="bbbee_document">
                            <p class="text-sm font-semibold text-slate-800">B-BBEE Document (Optional, if applicable)</p>
                            <p class="text-xs text-slate-500 mt-1">Drag and drop or click to upload (max 8MB)</p>
                            <p class="text-xs text-blue-700 mt-2 file-name" data-name-for="bbbee_document">No file selected</p>
                            <input type="file" name="bbbee_document" id="bbbee_document" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                        </div>
                        @error('bbbee_document')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-blue-600 text-white py-2.5 font-semibold hover:bg-blue-700">Create Merchant Account</button>
            </form>

            <div class="mt-5 text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="text-blue-600 font-medium">Sign in</a>
            </div>
            <div class="mt-2 text-sm text-slate-600">
                Driving instead?
                <a href="{{ route('register.driver') }}" class="text-blue-600 font-medium">Driver registration</a>
            </div>
        </div>
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 lg:sticky lg:top-6">
                <h2 class="text-base font-semibold text-slate-900">Station Location Preview</h2>
                <p class="text-xs text-slate-600 mt-1">Type address details to geocode and drop the marker.</p>
                <button
                    type="button"
                    id="useCurrentLocationBtn"
                    class="mt-3 inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                >
                    Use current location
                </button>
                <div id="merchantRegisterMap" class="mt-3 h-[420px] rounded-xl border border-slate-200 overflow-hidden"></div>
                <p id="merchantMapStatus" class="text-xs text-slate-500 mt-2">Start entering an address to locate the station.</p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.file-drop').forEach((dropZone) => {
    const inputId = dropZone.getAttribute('data-target');
    const input = document.getElementById(inputId);
    const nameEl = document.querySelector(`[data-name-for="${inputId}"]`);
    if (!input || !nameEl) return;

    const updateName = (files) => {
        nameEl.textContent = files && files.length ? files[0].name : 'No file selected';
    };

    dropZone.addEventListener('click', () => input.click());
    input.addEventListener('change', () => updateName(input.files));

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });
    });

    dropZone.addEventListener('drop', (event) => {
        const files = event.dataTransfer?.files;
        if (!files || !files.length) return;
        input.files = files;
        updateName(files);
    });
});

const franchiseInput = document.getElementById('franchise_id');
const franchiseChips = Array.from(document.querySelectorAll('.franchise-chip'));
const refreshFranchiseChips = () => {
    const activeId = franchiseInput ? String(franchiseInput.value || '') : '';
    franchiseChips.forEach((chip) => {
        const isActive = String(chip.dataset.id || '') === activeId;
        chip.classList.toggle('ring-2', isActive);
        chip.classList.toggle('ring-blue-500', isActive);
        chip.classList.toggle('border-blue-500', isActive);
    });
};
franchiseChips.forEach((chip) => {
    chip.addEventListener('click', () => {
        if (!franchiseInput) return;
        franchiseInput.value = String(chip.dataset.id || '');
        refreshFranchiseChips();
    });
});
refreshFranchiseChips();

const addressInput = document.getElementById('merchant_address');
const cityInput = document.getElementById('merchant_city');
const countryInput = document.getElementById('merchant_country');
const latitudeInput = document.getElementById('merchant_latitude');
const longitudeInput = document.getElementById('merchant_longitude');
const stationLatitudeInput = document.getElementById('merchant_station_latitude');
const stationLongitudeInput = document.getElementById('merchant_station_longitude');
const mapStatus = document.getElementById('merchantMapStatus');
const mapNode = document.getElementById('merchantRegisterMap');
const useCurrentLocationBtn = document.getElementById('useCurrentLocationBtn');
const addressSuggestions = document.getElementById('merchantAddressSuggestions');
const topSuggestionWrap = document.getElementById('merchantAddressTopSuggestion');
const topSuggestionBtn = document.getElementById('merchantAddressTopSuggestionBtn');
const topSuggestionText = document.getElementById('merchantAddressTopSuggestionText');
const googleMapsEnabled = @json((bool) config('services.google_maps.enabled', true));
const googleMapsApiKey = @json(config('services.google_maps.key'));
const googleAutocompleteUrl = @json(route('google.autocomplete'));
const googlePlaceUrl = @json(route('google.place'));
const googleReverseUrl = @json(route('google.reverse'));
const hasGoogleGeocode = Boolean(googleMapsEnabled && googleMapsApiKey);
const fallbackCenter = [-26.2041, 28.0473];
let map = null;
let marker = null;
let geocodeTimer = null;

const initLeafletMap = () => {
    if (!mapNode || !window.L) return;
    map = L.map(mapNode, { zoomControl: true }).setView(fallbackCenter, 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const existingLat = parseFloat(latitudeInput?.value || stationLatitudeInput?.value || '');
    const existingLng = parseFloat(longitudeInput?.value || stationLongitudeInput?.value || '');
    if (Number.isFinite(existingLat) && Number.isFinite(existingLng)) {
        applyLocationToMap(existingLat, existingLng);
        updateMapStatus(`Pinned at ${existingLat.toFixed(6)}, ${existingLng.toFixed(6)}`);
    } else {
        updateMapStatus('Start entering an address to locate the station.');
    }
};

const updateMapStatus = (message, isError = false) => {
    if (!mapStatus) return;
    mapStatus.textContent = message;
    mapStatus.classList.toggle('text-rose-600', isError);
    mapStatus.classList.toggle('text-slate-500', !isError);
};

const hideSuggestions = () => {
    if (!addressSuggestions) return;
    addressSuggestions.innerHTML = '';
    addressSuggestions.classList.add('hidden');
};

const clearTopSuggestion = () => {
    if (!topSuggestionWrap || !topSuggestionText || !topSuggestionBtn) return;
    topSuggestionWrap.classList.add('hidden');
    topSuggestionText.textContent = '';
    delete topSuggestionBtn.dataset.suggestion;
};

const renderTopSuggestion = (item) => {
    if (!topSuggestionWrap || !topSuggestionText || !topSuggestionBtn || !item) return;
    const title = item?.description || item?.display_name || item?.name || '';
    if (!title) {
        clearTopSuggestion();
        return;
    }
    topSuggestionText.textContent = title;
    topSuggestionBtn.dataset.suggestion = JSON.stringify(item);
    topSuggestionWrap.classList.remove('hidden');
};

const fillAddressFromNominatimItem = (item) => {
    const addr = item?.address || {};
    const line1 = [addr.house_number || '', addr.road || addr.pedestrian || '']
        .filter(Boolean)
        .join(' ')
        .trim();
    const suburb = addr.suburb || addr.neighbourhood || addr.quarter || '';
    const city = addr.city || addr.town || addr.village || addr.municipality || '';
    const country = addr.country || '';
    const fallbackAddress = item?.display_name || '';
    const composedAddress = [line1, suburb].filter(Boolean).join(', ').trim();

    if (addressInput) {
        addressInput.value = composedAddress || fallbackAddress || addressInput.value;
    }
    if (cityInput && city) cityInput.value = city;
    if (countryInput && country) countryInput.value = country;

    return {
        composedAddress: composedAddress || fallbackAddress,
        city,
        country,
    };
};

const nominatimFetchGeocode = async (query) => {
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&q=${encodeURIComponent(query)}`;
    const response = await fetch(url, {
        headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) throw new Error('Nominatim geocode request failed');
    const payload = await response.json();
    return Array.isArray(payload) ? payload : [];
};

const nominatimFetchReverse = async (lat, lng) => {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${encodeURIComponent(String(lat))}&lon=${encodeURIComponent(String(lng))}`;
    const response = await fetch(url, {
        headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) throw new Error('Nominatim reverse geocode request failed');
    const first = await response.json();
    if (!first) throw new Error('HERE reverse empty response');
    return first;
};

const googleFetchAutocomplete = async (query) => {
    const response = await fetch(`${googleAutocompleteUrl}?q=${encodeURIComponent(query)}&country=ZA`, {
        headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) throw new Error('Google autocomplete failed');
    const payload = await response.json();
    return Array.isArray(payload?.items) ? payload.items : [];
};

const googleFetchPlace = async (placeId) => {
    const response = await fetch(`${googlePlaceUrl}?place_id=${encodeURIComponent(placeId)}`, {
        headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) throw new Error('Google place details failed');
    const payload = await response.json();
    return payload?.item || null;
};

const googleFetchReverse = async (lat, lng) => {
    const response = await fetch(`${googleReverseUrl}?lat=${encodeURIComponent(String(lat))}&lng=${encodeURIComponent(String(lng))}`, {
        headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) throw new Error('Google reverse geocode failed');
    const payload = await response.json();
    return payload?.item || null;
};

const googleComponentValue = (components, types) => {
    const wanted = new Set(types);
    const match = (components || []).find((entry) => (entry.types || []).some((t) => wanted.has(t)));
    return match ? (match.long_name || '') : '';
};

const fillAddressFromGoogleItem = (item) => {
    const components = item?.address_components || [];
    const streetNumber = googleComponentValue(components, ['street_number']);
    const route = googleComponentValue(components, ['route']);
    const suburb = googleComponentValue(components, ['sublocality', 'sublocality_level_1', 'neighborhood']);
    const city = googleComponentValue(components, ['locality', 'administrative_area_level_2', 'administrative_area_level_1']);
    const country = googleComponentValue(components, ['country']);
    const line1 = [streetNumber, route].filter(Boolean).join(' ').trim();
    const composedAddress = [line1, suburb].filter(Boolean).join(', ').trim();
    const fallbackAddress = item?.formatted_address || '';

    if (addressInput) {
        addressInput.value = composedAddress || fallbackAddress || addressInput.value;
    }
    if (cityInput && city) cityInput.value = city;
    if (countryInput && country) countryInput.value = country;

    return {
        composedAddress: composedAddress || fallbackAddress || '',
        city,
        country,
    };
};

const pickSuggestion = async (prediction) => {
    try {
        let lat;
        let lng;
        if (prediction?.source === 'google') {
            const details = await googleFetchPlace(prediction.place_id);
            lat = Number(details?.location?.lat);
            lng = Number(details?.location?.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) throw new Error('Missing geometry');
            fillAddressFromGoogleItem(details);
        } else {
            lat = Number(prediction?.lat);
            lng = Number(prediction?.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) throw new Error('Missing geometry');
            fillAddressFromNominatimItem(prediction);
        }
        applyLocationToMap(lat, lng);
        updateMapStatus(`Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
    } catch (error) {
        updateMapStatus('Could not load selected suggestion details.', true);
    }
    hideSuggestions();
};

const renderSuggestions = (items) => {
    if (!addressSuggestions) return;
    if (!Array.isArray(items) || !items.length) {
        clearTopSuggestion();
        hideSuggestions();
        return;
    }

    renderTopSuggestion(items[0]);
    addressSuggestions.innerHTML = '';
    items.forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'w-full px-3 py-2 text-left hover:bg-slate-50 border-b last:border-b-0 border-slate-100';
        const title = item?.description || item?.display_name || item?.name || 'Suggested location';
        button.innerHTML = `<span class="block text-xs text-slate-700 truncate">${title}</span>`;
        button.addEventListener('click', () => pickSuggestion(item));
        addressSuggestions.appendChild(button);
    });

    addressSuggestions.classList.remove('hidden');
};

if (topSuggestionBtn) {
    topSuggestionBtn.addEventListener('click', () => {
        try {
            const raw = topSuggestionBtn.dataset.suggestion || '';
            if (!raw) return;
            const item = JSON.parse(raw);
            pickSuggestion(item);
        } catch (_) {
            // ignore stale suggestion payload
        }
    });
}

const bootLeaflet = (attempt = 0) => {
    if (window.L) {
        initLeafletMap();
        return;
    }
    if (attempt >= 40) {
        updateMapStatus('Leaflet map failed to load. Check internet or CSP.', true);
        return;
    }
    window.setTimeout(() => bootLeaflet(attempt + 1), 150);
};
bootLeaflet();

const scheduleGeocode = () => {
    if (geocodeTimer) window.clearTimeout(geocodeTimer);

    geocodeTimer = window.setTimeout(async () => {
        const parts = [
            addressInput?.value?.trim() || '',
            cityInput?.value?.trim() || '',
            countryInput?.value?.trim() || ''
        ].filter(Boolean);

        if (!parts.length) {
            if (latitudeInput) latitudeInput.value = '';
            if (longitudeInput) longitudeInput.value = '';
            updateMapStatus('Start entering an address to locate the station.');
            return;
        }

        if ((addressInput?.value || '').trim().length < 3 && !cityInput?.value?.trim()) {
            hideSuggestions();
            updateMapStatus('Type at least 3 address characters for suggestions.');
            return;
        }

        const query = parts.join(', ');
        updateMapStatus('Locating address...');

        try {
            const items = hasGoogleGeocode
                ? (await googleFetchAutocomplete(query)).map((item) => ({ ...item, source: 'google' }))
                : await nominatimFetchGeocode(query);
            if (items.length) {
                renderSuggestions(items);
                const first = items[0];
                if (first?.source === 'google') {
                    const details = await googleFetchPlace(first.place_id);
                    const lat = Number(details?.location?.lat);
                    const lng = Number(details?.location?.lng);
                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        applyLocationToMap(lat, lng);
                        fillAddressFromGoogleItem(details);
                        updateMapStatus(`Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
                    }
                } else {
                    const lat = Number(first?.lat);
                    const lng = Number(first?.lon);
                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        applyLocationToMap(lat, lng);
                        fillAddressFromNominatimItem(first);
                        updateMapStatus(`Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
                    }
                }
            } else {
                hideSuggestions();
                if (latitudeInput) latitudeInput.value = '';
                if (longitudeInput) longitudeInput.value = '';
                if (stationLatitudeInput) stationLatitudeInput.value = '';
                if (stationLongitudeInput) stationLongitudeInput.value = '';
                clearTopSuggestion();
                updateMapStatus('Address not found yet. Keep typing more detail.', true);
            }
        } catch (error) {
            if (latitudeInput) latitudeInput.value = '';
            if (longitudeInput) longitudeInput.value = '';
            if (stationLatitudeInput) stationLatitudeInput.value = '';
            if (stationLongitudeInput) stationLongitudeInput.value = '';
            hideSuggestions();
            clearTopSuggestion();
            updateMapStatus('Unable to geocode right now. Check internet or try again.', true);
        }
    }, 550);
};

[addressInput, cityInput, countryInput].forEach((field) => {
    if (!field) return;
    field.addEventListener('input', scheduleGeocode);
    field.addEventListener('change', scheduleGeocode);
});

if ((addressInput?.value || cityInput?.value || countryInput?.value)) {
    scheduleGeocode();
}

if (addressInput) {
    addressInput.addEventListener('blur', () => {
        window.setTimeout(() => hideSuggestions(), 200);
    });
    addressInput.addEventListener('focus', () => {
        if (addressSuggestions && addressSuggestions.children.length > 0) {
            addressSuggestions.classList.remove('hidden');
        }
    });
}

const applyLocationToMap = (lat, lng) => {
    if (latitudeInput) latitudeInput.value = String(lat);
    if (longitudeInput) longitudeInput.value = String(lng);
    if (stationLatitudeInput) stationLatitudeInput.value = String(lat);
    if (stationLongitudeInput) stationLongitudeInput.value = String(lng);

    if (map) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
        map.setView([lat, lng], 15);
    }
};

let autoLocateRequested = false;

const setLocationButtonBusy = (busy) => {
    if (!useCurrentLocationBtn) return;
    useCurrentLocationBtn.disabled = busy;
    useCurrentLocationBtn.classList.toggle('opacity-60', busy);
    useCurrentLocationBtn.classList.toggle('cursor-not-allowed', busy);
};

const requestCurrentLocation = (silent = false) => {
    if (!navigator.geolocation) {
        if (!silent) updateMapStatus('Geolocation is not supported in this browser.', true);
        return;
    }

    setLocationButtonBusy(true);
    updateMapStatus(silent ? 'Attempting automatic location…' : 'Fetching your current location...');

        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                applyLocationToMap(lat, lng);
                updateMapStatus('Location found. Resolving address...');
                let resolved = null;
                if (hasGoogleGeocode) {
                    try {
                        resolved = fillAddressFromGoogleItem(await googleFetchReverse(lat, lng));
                    } catch (_) {
                        // Fallback when Google reverse geocode fails on key/billing/restrictions.
                        resolved = fillAddressFromNominatimItem(await nominatimFetchReverse(lat, lng));
                    }
                } else {
                    resolved = fillAddressFromNominatimItem(await nominatimFetchReverse(lat, lng));
                }
                const hasText = resolved.composedAddress || resolved.city || resolved.country;
                updateMapStatus(
                    hasText
                        ? 'Current location applied and form auto-filled.'
                    : `Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`
            );
        } catch (error) {
            updateMapStatus('Location pinned. Could not auto-fill address, please select a suggestion or type it manually.', true);
        } finally {
            setLocationButtonBusy(false);
        }
    }, (error) => {
        let message = 'Unable to access current location.';
        if (error?.code === 1) message = 'Location permission denied. Allow location access and try again.';
        if (error?.code === 2) message = 'Location unavailable. Check GPS/network and try again.';
        if (error?.code === 3) message = 'Location request timed out. Try again.';
        if (!silent) {
            updateMapStatus(message, true);
        }
        setLocationButtonBusy(false);
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
    });
};

if (useCurrentLocationBtn) {
    useCurrentLocationBtn.addEventListener('click', () => requestCurrentLocation(false));
}

window.setTimeout(() => {
    if (autoLocateRequested) return;
    autoLocateRequested = true;
    requestCurrentLocation(true);
}, 900);
</script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
@endpush
