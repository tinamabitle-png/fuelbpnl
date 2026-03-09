@extends('Layouts.guest')

@section('title', 'Driver Registration')
@section('meta_description', 'Register as a driver on Bwiser to access voucher and fuel finance workflows.')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="min-h-screen bg-slate-100 py-10 px-4">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-semibold text-slate-900">Register as Driver</h1>
            <p class="text-sm text-slate-600 mt-1">Create your driver account to apply for vouchers.</p>

            <form method="POST" action="{{ route('register.driver.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700">Full Name</label>
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
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"
                        placeholder="+27XXXXXXXXX or 0XXXXXXXXX"
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
                        <input id="driver_city" name="city" type="text" value="{{ old('city') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Johannesburg">
                        @error('city')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Country</label>
                        <input id="driver_country" name="country" type="text" value="{{ old('country', 'South Africa') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="South Africa">
                        @error('country')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Home Address</label>
                    <input id="driver_home_address" name="home_address" type="text" value="{{ old('home_address') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Street address, suburb">
                    <div id="driverAddressSuggestions" class="mt-2 hidden rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden"></div>
                    <p class="text-xs text-slate-500 mt-1">Address suggestions and reverse geocode update while typing.</p>
                    @error('home_address')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <input type="hidden" id="driver_latitude" name="latitude" value="{{ old('latitude') }}">
                <input type="hidden" id="driver_longitude" name="longitude" value="{{ old('longitude') }}">

                <div>
                    <label class="block text-sm font-medium text-slate-700">Delivery Platform</label>
                    <select id="driver_platform" name="driver_platform" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option value="">Select platform</option>
                        <option value="checkers_sixty60" @selected(old('driver_platform') === 'checkers_sixty60')>Checkers Sixty60</option>
                        <option value="mr_d" @selected(old('driver_platform') === 'mr_d')>Mr D</option>
                        <option value="takealot" @selected(old('driver_platform') === 'takealot')>Takealot</option>
                        <option value="indrive" @selected(old('driver_platform') === 'indrive')>inDrive</option>
                        <option value="uber" @selected(old('driver_platform') === 'uber')>Uber</option>
                        <option value="bolt" @selected(old('driver_platform') === 'bolt')>Bolt</option>
                        <option value="other" @selected(old('driver_platform') === 'other')>Other</option>
                    </select>
                    @error('driver_platform')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div id="driver_platform_other_wrap" class="{{ old('driver_platform') === 'other' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-slate-700">Other Platform Name</label>
                    <input name="driver_platform_other" type="text" value="{{ old('driver_platform_other') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Enter platform name">
                    @error('driver_platform_other')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <input name="password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @error('password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                    <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">South African ID Number</label>
                    <input name="id_number" type="text" value="{{ old('id_number') }}" required maxlength="13" pattern="[0-9]{13}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="13 digits">
                    @error('id_number')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <p class="text-xs uppercase tracking-wide text-slate-600 font-semibold">Required documents</p>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="file-drop rounded-xl border-2 border-dashed border-blue-300 bg-white p-4 text-center cursor-pointer" data-target="id_document">
                            <p class="text-sm font-semibold text-slate-800">ID Document (PDF/JPG/PNG)</p>
                            <p class="text-xs text-slate-500 mt-1">Drag and drop or click to upload (max 8MB)</p>
                            <p class="text-xs text-blue-700 mt-2 file-name" data-name-for="id_document">No file selected</p>
                            <input type="file" name="id_document" id="id_document" accept=".pdf,.jpg,.jpeg,.png" required class="hidden">
                        </div>
                        @error('id_document')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

                        <div class="file-drop rounded-xl border-2 border-dashed border-blue-300 bg-white p-4 text-center cursor-pointer" data-target="driver_license_document">
                            <p class="text-sm font-semibold text-slate-800">Driver License (PDF/JPG/PNG)</p>
                            <p class="text-xs text-slate-500 mt-1">Drag and drop or click to upload (max 8MB)</p>
                            <p class="text-xs text-blue-700 mt-2 file-name" data-name-for="driver_license_document">No file selected</p>
                            <input type="file" name="driver_license_document" id="driver_license_document" accept=".pdf,.jpg,.jpeg,.png" required class="hidden">
                        </div>
                        @error('driver_license_document')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

                        <div class="file-drop rounded-xl border-2 border-dashed border-blue-300 bg-white p-4 text-center cursor-pointer" data-target="vehicle_license_document">
                            <p class="text-sm font-semibold text-slate-800">Vehicle License (PDF/JPG/PNG)</p>
                            <p class="text-xs text-slate-500 mt-1">Drag and drop or click to upload (max 8MB)</p>
                            <p class="text-xs text-blue-700 mt-2 file-name" data-name-for="vehicle_license_document">No file selected</p>
                            <input type="file" name="vehicle_license_document" id="vehicle_license_document" accept=".pdf,.jpg,.jpeg,.png" required class="hidden">
                        </div>
                        @error('vehicle_license_document')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-blue-600 text-white py-2.5 font-semibold hover:bg-blue-700">Create Driver Account</button>
            </form>

            <div class="mt-5 text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="text-blue-600 font-medium">Sign in</a>
            </div>
            <div class="mt-2 text-sm text-slate-600">
                Registering a station?
                <a href="{{ route('register.merchant') }}" class="text-blue-600 font-medium">Merchant registration</a>
            </div>
        </div>
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 lg:sticky lg:top-6">
                <h2 class="text-base font-semibold text-slate-900">Home Location Preview</h2>
                <p class="text-xs text-slate-600 mt-1">Type address or use current location to pin your home base.</p>
                <button
                    type="button"
                    id="useDriverCurrentLocationBtn"
                    class="mt-3 inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                >
                    Use current location
                </button>
                <div id="driverRegisterMap" class="mt-3 h-[420px] rounded-xl border border-slate-200 overflow-hidden"></div>
                <p id="driverMapStatus" class="text-xs text-slate-500 mt-2">Start entering an address to locate your home base.</p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
const googleMapsEnabled = @json((bool) config('services.google_maps.enabled', true));
const googleMapsApiKey = @json(config('services.google_maps.key'));
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

const platformSelect = document.getElementById('driver_platform');
const platformOtherWrap = document.getElementById('driver_platform_other_wrap');
const togglePlatformOther = () => {
    if (!platformSelect || !platformOtherWrap) return;
    platformOtherWrap.classList.toggle('hidden', platformSelect.value !== 'other');
};
if (platformSelect) {
    platformSelect.addEventListener('change', togglePlatformOther);
    togglePlatformOther();
}

const driverAddressInput = document.getElementById('driver_home_address');
const driverCityInput = document.getElementById('driver_city');
const driverCountryInput = document.getElementById('driver_country');
const driverLatitudeInput = document.getElementById('driver_latitude');
const driverLongitudeInput = document.getElementById('driver_longitude');
const driverMapStatus = document.getElementById('driverMapStatus');
const driverMapNode = document.getElementById('driverRegisterMap');
const useDriverCurrentLocationBtn = document.getElementById('useDriverCurrentLocationBtn');
const driverAddressSuggestions = document.getElementById('driverAddressSuggestions');

const hereMapsApiKey = @json(config('services.here_maps.key'));
const hereGeocodeUrl = @json(route('here.geocode'));
const hereReverseUrl = @json(route('here.reverse'));

const fallbackCenter = [-26.2041, 28.0473];
const hasGoogleMaps = Boolean(googleMapsEnabled && googleMapsApiKey);
const hasHereMaps = Boolean(hereMapsApiKey);
let activeMapProvider = hasGoogleMaps ? 'google' : (hasHereMaps ? 'here' : 'none');
let driverMap = null;
let driverMarker = null;
let driverGeocodeTimer = null;
let driverGeocoder = null;
let driverAutocompleteService = null;
let driverHerePlatform = null;

const upsertDriverMarker = (currentMarker, position) => {
    if (!driverMap) return currentMarker;

    if (activeMapProvider === 'here') {
        if (currentMarker) {
            currentMarker.setGeometry(position);
            return currentMarker;
        }
        const iconMarkup = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><circle cx="12" cy="12" r="10" fill="#2563eb"/></svg>';
        const icon = new H.map.Icon(iconMarkup);
        const next = new H.map.Marker(position, { icon });
        driverMap.addObject(next);
        return next;
    }

    if (currentMarker) {
        if (typeof currentMarker.setPosition === 'function') {
            currentMarker.setPosition(position);
        } else {
            currentMarker.position = position;
        }
        return currentMarker;
    }

    if (google.maps.marker?.AdvancedMarkerElement) {
        return new google.maps.marker.AdvancedMarkerElement({ map: driverMap, position });
    }

    return new google.maps.Marker({ map: driverMap, position });
};

const updateDriverMapStatus = (message, isError = false) => {
    if (!driverMapStatus) return;
    driverMapStatus.textContent = message;
    driverMapStatus.classList.toggle('text-rose-600', isError);
    driverMapStatus.classList.toggle('text-slate-500', !isError);
};

const hideDriverSuggestions = () => {
    if (!driverAddressSuggestions) return;
    driverAddressSuggestions.innerHTML = '';
    driverAddressSuggestions.classList.add('hidden');
};

const driverComponentValue = (components, types) => {
    const wanted = new Set(types);
    const match = (components || []).find((entry) => (entry.types || []).some((t) => wanted.has(t)));
    return match ? (match.long_name || '') : '';
};

const fillDriverAddressFields = (components, fallbackAddress = '') => {
    const streetNumber = driverComponentValue(components, ['street_number']);
    const route = driverComponentValue(components, ['route']);
    const suburb = driverComponentValue(components, ['sublocality', 'sublocality_level_1', 'neighborhood']);
    const city = driverComponentValue(components, ['locality', 'administrative_area_level_2', 'administrative_area_level_1']);
    const country = driverComponentValue(components, ['country']);
    const line1 = [streetNumber, route].filter(Boolean).join(' ').trim();
    const composedAddress = [line1, suburb].filter(Boolean).join(', ').trim();

    if (driverAddressInput) {
        driverAddressInput.value = composedAddress || fallbackAddress || driverAddressInput.value;
    }
    if (driverCityInput && city) driverCityInput.value = city;
    if (driverCountryInput && country) driverCountryInput.value = country;

    return { composedAddress: composedAddress || fallbackAddress || '', city, country };
};

const fillDriverAddressFromHereItem = (item) => {
    const address = item?.address || {};
    const line1 = [address.houseNumber || '', address.street || ''].filter(Boolean).join(' ').trim();
    const area = address.district || address.subdistrict || '';
    const city = address.city || address.county || '';
    const country = address.countryName || '';
    const fallbackAddress = address.label || item?.title || '';
    const composedAddress = [line1, area].filter(Boolean).join(', ').trim();

    if (driverAddressInput) {
        driverAddressInput.value = composedAddress || fallbackAddress || driverAddressInput.value;
    }
    if (driverCityInput && city) driverCityInput.value = city;
    if (driverCountryInput && country) driverCountryInput.value = country;

    return { composedAddress: composedAddress || fallbackAddress || '', city, country };
};

const driverHereFetchGeocode = async (query) => {
    const url = `${hereGeocodeUrl}?q=${encodeURIComponent(query)}&limit=5`;
    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!response.ok) throw new Error('HERE geocode request failed');
    const payload = await response.json();
    return Array.isArray(payload?.items) ? payload.items : [];
};

const driverHereFetchReverse = async (lat, lng) => {
    const url = `${hereReverseUrl}?lat=${encodeURIComponent(String(lat))}&lng=${encodeURIComponent(String(lng))}&limit=1`;
    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!response.ok) throw new Error('HERE reverse geocode request failed');
    const payload = await response.json();
    const first = Array.isArray(payload?.items) ? payload.items[0] : null;
    if (!first) throw new Error('HERE reverse empty response');
    return first;
};

const driverGeocodeByPlaceId = (placeId) => new Promise((resolve, reject) => {
    if (activeMapProvider !== 'google') return reject(new Error('Google geocoder not active'));
    if (!driverGeocoder) return reject(new Error('Geocoder not available'));
    driverGeocoder.geocode({ placeId }, (results, status) => {
        if (status === 'OK' && results && results.length) {
            resolve(results[0]);
            return;
        }
        reject(new Error('Place geocode failed: ' + status));
    });
});

const driverGeocodeByAddress = (address) => new Promise((resolve, reject) => {
    if (activeMapProvider !== 'google') return reject(new Error('Google geocoder not active'));
    if (!driverGeocoder) return reject(new Error('Geocoder not available'));
    driverGeocoder.geocode({ address }, (results, status) => {
        if (status === 'OK' && results && results.length) {
            resolve(results[0]);
            return;
        }
        reject(new Error('Address geocode failed: ' + status));
    });
});

const driverReverseGeocode = (lat, lng) => new Promise((resolve, reject) => {
    if (activeMapProvider !== 'google') return reject(new Error('Google geocoder not active'));
    if (!driverGeocoder) return reject(new Error('Geocoder not available'));
    driverGeocoder.geocode({ location: { lat, lng } }, (results, status) => {
        if (status === 'OK' && results && results.length) {
            resolve(fillDriverAddressFields(results[0].address_components || [], results[0].formatted_address || ''));
            return;
        }
        reject(new Error('Reverse geocode failed: ' + status));
    });
});

const applyDriverLocationToMap = (lat, lng) => {
    if (driverLatitudeInput) driverLatitudeInput.value = String(lat);
    if (driverLongitudeInput) driverLongitudeInput.value = String(lng);

    if (driverMap) {
        const pos = { lat, lng };
        driverMarker = upsertDriverMarker(driverMarker, pos);
        driverMap.setCenter(pos);
        driverMap.setZoom(15);
    }
};

const pickDriverSuggestion = async (prediction) => {
    try {
        let lat;
        let lng;

        if (activeMapProvider === 'google') {
            const result = await driverGeocodeByPlaceId(prediction.place_id);
            const location = result.geometry?.location;
            if (!location) throw new Error('Missing geometry');
            lat = location.lat();
            lng = location.lng();
            fillDriverAddressFields(result.address_components || [], result.formatted_address || prediction.description || '');
        } else {
            const position = prediction?.position || {};
            lat = Number(position.lat);
            lng = Number(position.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) throw new Error('Missing geometry');
            fillDriverAddressFromHereItem(prediction);
        }

        applyDriverLocationToMap(lat, lng);
        updateDriverMapStatus(`Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
    } catch (error) {
        updateDriverMapStatus('Could not load selected suggestion details.', true);
    }
    hideDriverSuggestions();
};

const renderDriverSuggestions = (items) => {
    if (!driverAddressSuggestions) return;
    if (!Array.isArray(items) || !items.length) {
        hideDriverSuggestions();
        return;
    }

    driverAddressSuggestions.innerHTML = '';
    items.forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'w-full px-3 py-2 text-left hover:bg-slate-50 border-b last:border-b-0 border-slate-100';
        button.innerHTML = `<span class="block text-xs text-slate-700 truncate">${item?.description || item?.address?.label || item?.title || 'Suggested location'}</span>`;
        button.addEventListener('click', () => pickDriverSuggestion(item));
        driverAddressSuggestions.appendChild(button);
    });

    driverAddressSuggestions.classList.remove('hidden');
};

const loadDriverHereFallbackMap = () => {
    if (!hasHereMaps) {
        updateDriverMapStatus('Map preview unavailable. Configure Google or HERE maps keys.', true);
        return;
    }

    activeMapProvider = 'here';
    updateDriverMapStatus('Loading HERE Maps fallback...');
    let attempts = 0;
    const tryInit = () => {
        if (window.H?.service) {
            initDriverRegisterMap();
            return;
        }
        attempts += 1;
        if (attempts >= 20) {
            updateDriverMapStatus('HERE Maps failed to load. Check key, CSP, or internet access.', true);
            return;
        }
        window.setTimeout(tryInit, 250);
    };
    tryInit();
};

const initDriverRegisterMap = () => {
    if (!driverMapNode) return;

    if (activeMapProvider === 'google') {
        if (!window.google?.maps || !googleMapsApiKey) {
            updateDriverMapStatus('Google map unavailable, attempting HERE fallback...');
            if (hasHereMaps) {
                loadDriverHereFallbackMap();
                return;
            }
            updateDriverMapStatus('Map preview unavailable. Set GOOGLE_MAPS_API_KEY or HERE_MAPS_API_KEY.', true);
            return;
        }

        driverMap = new google.maps.Map(driverMapNode, {
            center: { lat: fallbackCenter[0], lng: fallbackCenter[1] },
            zoom: 11,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });
        driverGeocoder = new google.maps.Geocoder();
        driverAutocompleteService = new google.maps.places.AutocompleteService();
    } else if (activeMapProvider === 'here') {
        if (!window.H?.service || !hereMapsApiKey) {
            updateDriverMapStatus('HERE map preview unavailable. Check HERE_MAPS_API_KEY.', true);
            return;
        }

        driverHerePlatform = new H.service.Platform({ apikey: hereMapsApiKey });
        const layers = driverHerePlatform.createDefaultLayers();
        driverMap = new H.Map(
            driverMapNode,
            layers.vector.normal.map,
            { center: { lat: fallbackCenter[0], lng: fallbackCenter[1] }, zoom: 11, pixelRatio: window.devicePixelRatio || 1 }
        );
        const behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(driverMap));
        void behavior;
        H.ui.UI.createDefault(driverMap, layers);
        window.addEventListener('resize', () => driverMap.getViewPort().resize());
    } else {
        updateDriverMapStatus('Map preview unavailable. Configure Google or HERE maps keys.', true);
        return;
    }

    const existingLat = parseFloat(driverLatitudeInput?.value || '');
    const existingLng = parseFloat(driverLongitudeInput?.value || '');
    if (Number.isFinite(existingLat) && Number.isFinite(existingLng)) {
        driverMarker = upsertDriverMarker(driverMarker, { lat: existingLat, lng: existingLng });
        driverMap.setCenter({ lat: existingLat, lng: existingLng });
        driverMap.setZoom(15);
        updateDriverMapStatus(`Pinned at ${existingLat.toFixed(6)}, ${existingLng.toFixed(6)}`);
    }
};
window.initDriverRegisterMap = initDriverRegisterMap;

if (activeMapProvider === 'google' && window.google?.maps && googleMapsApiKey) {
    initDriverRegisterMap();
} else if (activeMapProvider === 'google' && googleMapsApiKey) {
    updateDriverMapStatus('Loading Google Maps...');
    if (typeof window.gm_authFailure !== 'function') {
        window.gm_authFailure = () => {
            if (hasHereMaps) {
                loadDriverHereFallbackMap();
                return;
            }
            updateDriverMapStatus('Google Maps authentication failed. Check API key, billing, and domain restrictions.', true);
        };
    }
    window.setTimeout(() => {
        if (!driverMap && !window.google?.maps) {
            if (hasHereMaps) {
                loadDriverHereFallbackMap();
                return;
            }
            updateDriverMapStatus('Google Maps failed to load. Check API key restrictions or internet access.', true);
        }
    }, 4500);
} else if (hasHereMaps) {
    loadDriverHereFallbackMap();
} else {
    updateDriverMapStatus('Map preview unavailable. Configure Google or HERE maps keys.', true);
}

const scheduleDriverGeocode = () => {
    if (activeMapProvider === 'google' && (!driverGeocoder || !driverAutocompleteService)) return;
    if (activeMapProvider === 'none') return;
    if (driverGeocodeTimer) window.clearTimeout(driverGeocodeTimer);

    driverGeocodeTimer = window.setTimeout(async () => {
        const parts = [
            driverAddressInput?.value?.trim() || '',
            driverCityInput?.value?.trim() || '',
            driverCountryInput?.value?.trim() || ''
        ].filter(Boolean);

        if (!parts.length) {
            if (driverLatitudeInput) driverLatitudeInput.value = '';
            if (driverLongitudeInput) driverLongitudeInput.value = '';
            updateDriverMapStatus('Start entering an address to locate your home base.');
            return;
        }

        if ((driverAddressInput?.value || '').trim().length < 3 && !driverCityInput?.value?.trim()) {
            hideDriverSuggestions();
            updateDriverMapStatus('Type at least 3 address characters for suggestions.');
            return;
        }

        const query = parts.join(', ');
        updateDriverMapStatus('Locating address...');

        if (activeMapProvider === 'google') {
            driverAutocompleteService.getPlacePredictions({ input: query }, async (predictions, status) => {
                const validPredictions = Array.isArray(predictions) ? predictions : [];
                if (status === google.maps.places.PlacesServiceStatus.OK && validPredictions.length) {
                    renderDriverSuggestions(validPredictions);
                } else {
                    hideDriverSuggestions();
                }

                try {
                    const result = await driverGeocodeByAddress(query);
                    const location = result.geometry?.location;
                    if (!location) throw new Error('Missing geometry');
                    const lat = location.lat();
                    const lng = location.lng();
                    applyDriverLocationToMap(lat, lng);
                    updateDriverMapStatus(`Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
                } catch (error) {
                    if (!validPredictions.length) {
                        if (driverLatitudeInput) driverLatitudeInput.value = '';
                        if (driverLongitudeInput) driverLongitudeInput.value = '';
                        updateDriverMapStatus('Address not found yet. Keep typing more detail.', true);
                    }
                }
            });
        } else {
            try {
                const items = await driverHereFetchGeocode(query);
                if (items.length) {
                    renderDriverSuggestions(items);
                    const first = items[0];
                    const lat = Number(first?.position?.lat);
                    const lng = Number(first?.position?.lng);
                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        applyDriverLocationToMap(lat, lng);
                        fillDriverAddressFromHereItem(first);
                        updateDriverMapStatus(`Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
                    }
                } else {
                    hideDriverSuggestions();
                    if (driverLatitudeInput) driverLatitudeInput.value = '';
                    if (driverLongitudeInput) driverLongitudeInput.value = '';
                    updateDriverMapStatus('Address not found yet. Keep typing more detail.', true);
                }
            } catch (error) {
                if (driverLatitudeInput) driverLatitudeInput.value = '';
                if (driverLongitudeInput) driverLongitudeInput.value = '';
                hideDriverSuggestions();
                updateDriverMapStatus('Unable to geocode right now. Check internet or try again.', true);
            }
        }
    }, 550);
};

[driverAddressInput, driverCityInput, driverCountryInput].forEach((field) => {
    if (!field) return;
    field.addEventListener('input', scheduleDriverGeocode);
    field.addEventListener('change', scheduleDriverGeocode);
});

if ((driverAddressInput?.value || driverCityInput?.value || driverCountryInput?.value) && activeMapProvider !== 'none') {
    scheduleDriverGeocode();
}

if (driverAddressInput) {
    driverAddressInput.addEventListener('blur', () => {
        window.setTimeout(() => hideDriverSuggestions(), 200);
    });
    driverAddressInput.addEventListener('focus', () => {
        if (driverAddressSuggestions && driverAddressSuggestions.children.length > 0) {
            driverAddressSuggestions.classList.remove('hidden');
        }
    });
}

if (useDriverCurrentLocationBtn) {
    useDriverCurrentLocationBtn.addEventListener('click', () => {
        if (activeMapProvider === 'none') {
            updateDriverMapStatus('Map provider is unavailable. Configure Google or HERE maps keys.', true);
            return;
        }
        if (!navigator.geolocation) {
            updateDriverMapStatus('Geolocation is not supported in this browser.', true);
            return;
        }

        useDriverCurrentLocationBtn.disabled = true;
        useDriverCurrentLocationBtn.classList.add('opacity-60', 'cursor-not-allowed');
        updateDriverMapStatus('Fetching your current location...');

        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                applyDriverLocationToMap(lat, lng);
                updateDriverMapStatus('Location found. Resolving address...');
                const resolved = activeMapProvider === 'google'
                    ? await driverReverseGeocode(lat, lng)
                    : fillDriverAddressFromHereItem(await driverHereFetchReverse(lat, lng));
                const hasText = resolved.composedAddress || resolved.city || resolved.country;
                updateDriverMapStatus(
                    hasText
                        ? 'Current location applied and form auto-filled.'
                        : `Pinned at ${lat.toFixed(6)}, ${lng.toFixed(6)}`
                );
            } catch (error) {
                updateDriverMapStatus('Location pinned. Could not auto-fill address, please select a suggestion or type manually.', true);
            } finally {
                useDriverCurrentLocationBtn.disabled = false;
                useDriverCurrentLocationBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        }, (error) => {
            let message = 'Unable to access current location.';
            if (error?.code === 1) message = 'Location permission denied. Allow location access and try again.';
            if (error?.code === 2) message = 'Location unavailable. Check GPS/network and try again.';
            if (error?.code === 3) message = 'Location request timed out. Try again.';
            updateDriverMapStatus(message, true);
            useDriverCurrentLocationBtn.disabled = false;
            useDriverCurrentLocationBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        });
    });
}
</script>
@if(config('services.google_maps.enabled', true) && config('services.google_maps.key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode((string) config('services.google_maps.key')) }}&libraries=places,marker&loading=async&callback=initDriverRegisterMap" async defer></script>
@endif
@if(config('services.here_maps.key'))
<script src="https://js.api.here.com/v3/3.1/mapsjs-core.js" async defer></script>
<script src="https://js.api.here.com/v3/3.1/mapsjs-service.js" async defer></script>
<script src="https://js.api.here.com/v3/3.1/mapsjs-ui.js" async defer></script>
<script src="https://js.api.here.com/v3/3.1/mapsjs-mapevents.js" async defer></script>
<link rel="stylesheet" href="https://js.api.here.com/v3/3.1/mapsjs-ui.css" />
@endif
@endpush
