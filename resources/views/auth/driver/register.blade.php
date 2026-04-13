@extends('Layouts.guest')

@section('title', 'Driver Registration')
@section('meta_description', 'Register as a driver on Bwiser to access voucher and fuel finance workflows.')

@section('content')
<section class="min-h-screen bg-slate-100 py-10 px-4">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-semibold text-slate-900">Register as Driver</h1>
            <p class="text-sm text-slate-600 mt-1">Create your driver account to apply for vouchers.</p>

	            <form id="driverRegisterForm" method="POST" action="{{ route('register.driver.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
	                @csrf
	                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
	                    <div>
	                        <label class="block text-sm font-medium text-slate-700">First Name</label>
	                        <input id="driver_first_name" name="first_name" type="text" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" autocomplete="given-name">
	                        @error('first_name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium text-slate-700">Last Name</label>
	                        <input id="driver_last_name" name="last_name" type="text" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" autocomplete="family-name">
	                        @error('last_name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
	                    </div>
	                </div>
	                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
	                    <div>
	                        <label class="block text-sm font-medium text-slate-700">Gender</label>
	                        <select id="driver_gender" name="gender" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
	                            <option value="">Select</option>
	                            <option value="male" @selected(old('gender', 'male') === 'male')>Male</option>
	                            <option value="female" @selected(old('gender') === 'female')>Female</option>
	                            <option value="other" @selected(old('gender') === 'other')>Other</option>
	                        </select>
	                        @error('gender')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
	                    </div>
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
	                    <input id="driver_id_number" name="id_number" type="text" value="{{ old('id_number') }}" required maxlength="13" pattern="[0-9]{13}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="13 digits">
	                    @error('id_number')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-500 mt-2">
                            Document uploads happen after signup in your dashboard. Voucher applications are enabled once documents are uploaded.
                        </p>
	                </div>
                    <input type="hidden" id="driverDriverTermsAccepted" name="driver_terms_accepted" value="{{ old('driver_terms_accepted') ? '1' : '' }}">
                    <input type="hidden" id="driverDriverCreditConsent" name="driver_credit_consent" value="{{ old('driver_credit_consent') ? '1' : '' }}">
                    <input type="hidden" id="driverDriverAgreementVersion" name="driver_agreement_version" value="{{ old('driver_agreement_version', 'driver-platform-v1-2026-04-13') }}">

                <button type="button" data-driver-agreement-open="driverRegisterForm" class="w-full rounded-xl bg-blue-600 text-white py-2.5 font-semibold hover:bg-blue-700">Create Driver Account</button>
                <p class="text-xs leading-5 text-slate-500">
                    Before your account is created, you’ll need to accept the driver agreement, POPIA processing notice, and NCR-aligned verification consent.
                </p>
            </form>

            @include('auth.driver.partials.agreement-modal', [
                'formId' => 'driverRegisterForm',
                'prefix' => 'driver',
                'agreementVersion' => 'driver-platform-v1-2026-04-13',
            ])

            <div class="mt-5 text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="text-blue-600 font-medium">Sign in</a>
            </div>
            @if(config('services.registration.public_merchant_enabled'))
                <div class="mt-2 text-sm text-slate-600">
                    Registering a station?
                    <a href="{{ route('register.merchant') }}" class="text-blue-600 font-medium">Merchant registration</a>
                </div>
            @endif
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

	(function initIdentityFields() {
	    const idEl = document.getElementById('driver_id_number');

	    const deriveDobFromSaId = (raw) => {
	        const digits = String(raw || '').replace(/\D+/g, '');
	        if (!/^\d{13}$/.test(digits)) return '';
	        const yy = Number(digits.slice(0, 2));
	        const mm = Number(digits.slice(2, 4));
	        const dd = Number(digits.slice(4, 6));
	        if (!yy || !mm || !dd) return '';
	        const now = new Date();
	        const nowYY = Number(String(now.getFullYear()).slice(-2));
	        const yyyy = (yy <= nowYY ? 2000 : 1900) + yy;
	        const dt = new Date(yyyy, mm - 1, dd);
	        if (Number.isNaN(dt.getTime())) return '';
	        if (dt.getFullYear() !== yyyy || dt.getMonth() !== (mm - 1) || dt.getDate() !== dd) return '';
	        if (dt.getTime() > now.getTime()) return '';
	        return `${String(yyyy).padStart(4, '0')}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
	    };

	    if (idEl) {
	        const validateDob = () => deriveDobFromSaId(idEl.value);
	        idEl.addEventListener('input', validateDob);
	        idEl.addEventListener('blur', validateDob);
	        validateDob();
	    }
	})();

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
const hasHereMaps = Boolean(hereMapsApiKey);
let activeMapProvider = 'leaflet';
let driverMap = null;
let driverMarker = null;
let driverGeocodeTimer = null;

const upsertDriverMarker = (currentMarker, position) => {
    if (!driverMap) return currentMarker;

    if (activeMapProvider === 'leaflet') {
        const latLng = [position.lat, position.lng];
        if (currentMarker) {
            currentMarker.setLatLng(latLng);
            return currentMarker;
        }
        const next = window.L.marker(latLng);
        next.addTo(driverMap);
        return next;
    }

    return currentMarker;
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
    const composedAddress = [line1, area].filter(Boolean).join(', ').trim() || fallbackAddress;

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
    const url = `${hereReverseUrl}?lat=${encodeURIComponent(String(lat))}&lng=${encodeURIComponent(String(lng))}&limit=3`;
    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!response.ok) throw new Error('HERE reverse geocode request failed');
    const payload = await response.json();
    const items = Array.isArray(payload?.items) ? payload.items : [];
    const pickBest = (candidates) => {
        if (!Array.isArray(candidates) || !candidates.length) return null;
        const score = (item) => {
            const addr = item?.address || {};
            let points = 0;
            if (addr.houseNumber) points += 3;
            if (addr.street) points += 2;
            if (addr.postalCode) points += 1;
            if (addr.city || addr.county) points += 1;
            if (/\d/.test(String(addr.label || ''))) points += 1;
            return points;
        };
        return [...candidates].sort((a, b) => score(b) - score(a))[0] || null;
    };
    const best = pickBest(items);
    if (!best) throw new Error('HERE reverse empty response');
    return best;
};

const applyDriverLocationToMap = (lat, lng) => {
    if (driverLatitudeInput) driverLatitudeInput.value = String(lat);
    if (driverLongitudeInput) driverLongitudeInput.value = String(lng);

    if (driverMap && activeMapProvider === 'leaflet') {
        const pos = { lat, lng };
        driverMarker = upsertDriverMarker(driverMarker, pos);
        driverMap.setView([lat, lng], 15);
    }
};

const pickDriverSuggestion = async (prediction) => {
    try {
        let lat;
        let lng;

        const position = prediction?.position || {};
        lat = Number(position.lat);
        lng = Number(position.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) throw new Error('Missing geometry');
        let resolved = prediction;
        if (!prediction?.address?.street && !prediction?.address?.houseNumber) {
            try {
                resolved = await driverHereFetchReverse(lat, lng);
            } catch (error) {
                resolved = prediction;
            }
        }
        fillDriverAddressFromHereItem(resolved);

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
        const addr = item?.address || {};
        const line1 = [addr.houseNumber || '', addr.street || ''].filter(Boolean).join(' ').trim();
        const area = addr.district || addr.subdistrict || addr.neighborhood || '';
        const city = addr.city || addr.county || addr.state || '';
        const hint = [line1, area, city].filter(Boolean).join(', ').trim()
            || addr.label
            || item?.description
            || item?.title
            || 'Suggested location';
        button.innerHTML = `<span class="block text-xs text-slate-700 truncate">${hint}</span>`;
        button.addEventListener('click', () => pickDriverSuggestion(item));
        driverAddressSuggestions.appendChild(button);
    });

    driverAddressSuggestions.classList.remove('hidden');
};

const loadDriverLeafletMap = () => {
    if (!window.L?.map) {
        updateDriverMapStatus('Map preview unavailable. Leaflet failed to load.', true);
        return;
    }

    activeMapProvider = 'leaflet';
    initDriverRegisterMap();
};

const initDriverRegisterMap = () => {
    if (!driverMapNode) return;

    if (activeMapProvider !== 'leaflet' || !window.L?.map) {
        updateDriverMapStatus('Map preview unavailable. Leaflet failed to load.', true);
        return;
    }

    if (!driverMap) {
        driverMap = window.L.map(driverMapNode, {
            zoomControl: true,
            attributionControl: true,
        }).setView(fallbackCenter, 11);

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(driverMap);
    }

    const existingLat = parseFloat(driverLatitudeInput?.value || '');
    const existingLng = parseFloat(driverLongitudeInput?.value || '');
    if (Number.isFinite(existingLat) && Number.isFinite(existingLng)) {
        driverMarker = upsertDriverMarker(driverMarker, { lat: existingLat, lng: existingLng });
        driverMap.setView([existingLat, existingLng], 15);
        updateDriverMapStatus(`Pinned at ${existingLat.toFixed(6)}, ${existingLng.toFixed(6)}`);
    }
};
window.initDriverRegisterMap = initDriverRegisterMap;

if (window.L?.map) {
    initDriverRegisterMap();
} else {
    updateDriverMapStatus('Loading Leaflet map...');
    window.setTimeout(() => {
        if (!driverMap && window.L?.map) {
            loadDriverLeafletMap();
            return;
        }
        if (!window.L?.map) {
            updateDriverMapStatus('Leaflet map failed to load. Check internet access or CSP settings.', true);
        }
    }, 4500);
}

const scheduleDriverGeocode = () => {
    if (!hasHereMaps) return;
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

        try {
            const items = await driverHereFetchGeocode(query);
            if (items.length) {
                renderDriverSuggestions(items);
                const first = items[0];
                const lat = Number(first?.position?.lat);
                const lng = Number(first?.position?.lng);
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    applyDriverLocationToMap(lat, lng);
                    let resolved = first;
                    if (!first?.address?.street && !first?.address?.houseNumber) {
                        try {
                            resolved = await driverHereFetchReverse(lat, lng);
                        } catch (error) {
                            resolved = first;
                        }
                    }
                    fillDriverAddressFromHereItem(resolved);
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
    }, 550);
};

[driverAddressInput, driverCityInput, driverCountryInput].forEach((field) => {
    if (!field) return;
    field.addEventListener('input', scheduleDriverGeocode);
    field.addEventListener('change', scheduleDriverGeocode);
});

if ((driverAddressInput?.value || driverCityInput?.value || driverCountryInput?.value) && hasHereMaps) {
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
        if (!window.L?.map && !driverMap) {
            updateDriverMapStatus('Map provider is unavailable. Leaflet failed to load.', true);
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
                const resolved = hasHereMaps
                    ? fillDriverAddressFromHereItem(await driverHereFetchReverse(lat, lng))
                    : { composedAddress: '', city: '', country: '' };
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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" async defer></script>
@endpush
