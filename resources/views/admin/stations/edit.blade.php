@extends('Layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Edit Fuel Station</h2>
                <p class="text-sm text-gray-600">Update station details.</p>
            </div>
            <a href="{{ route('admin.stations.show', $station) }}" class="text-sm text-blue-600 hover:text-blue-800">Back</a>
        </div>

        <form method="POST" action="{{ route('admin.stations.update', $station) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Station Name</label>
                    <input name="name" value="{{ old('name', $station->name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                    <input name="company" value="{{ old('company', $station->company) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                    <input name="license_number" value="{{ old('license_number', $station->license_number) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                    <select name="owner_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Select Merchant</option>
                        @foreach($owners as $owner)
                            <option value="{{ $owner->id }}" {{ $station->owner_id == $owner->id ? 'selected' : '' }}>
                                {{ $owner->name }} ({{ $owner->phone ?? $owner->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input id="stationAddress" name="address" value="{{ old('address', $station->address) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input id="stationCity" name="city" value="{{ old('city', $station->city) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input id="stationCountry" name="country" value="{{ old('country', $station->country) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input id="stationLatitude" name="latitude" value="{{ old('latitude', $station->latitude) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input id="stationLongitude" name="longitude" value="{{ old('longitude', $station->longitude) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input name="contact_person" value="{{ old('contact_person', $station->contact_person) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <input name="contact_phone" value="{{ old('contact_phone', $station->contact_phone) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                    <input name="contact_email" value="{{ old('contact_email', $station->contact_email) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="active" {{ $station->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $station->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="suspended" {{ $station->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        (function () {
            const addressInput = document.getElementById('stationAddress');
            const cityInput = document.getElementById('stationCity');
            const countryInput = document.getElementById('stationCountry');
            const latInput = document.getElementById('stationLatitude');
            const lngInput = document.getElementById('stationLongitude');

            if (!addressInput) return;

            function pickComponent(components, type) {
                const match = components.find((c) => c.types && c.types.includes(type));
                return match ? match.long_name : '';
            }

            window.initStationAutocomplete = function () {
                if (!window.google || !google.maps || !google.maps.places) return;
                const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                    types: ['geocode'],
                    fields: ['address_components', 'geometry', 'formatted_address'],
                });

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place) return;
                    if (place.formatted_address) {
                        addressInput.value = place.formatted_address;
                    }
                    const components = place.address_components || [];
                    const city =
                        pickComponent(components, 'locality') ||
                        pickComponent(components, 'postal_town') ||
                        pickComponent(components, 'sublocality') ||
                        pickComponent(components, 'administrative_area_level_2');
                    const country = pickComponent(components, 'country');
                    if (city && cityInput) cityInput.value = city;
                    if (country && countryInput) countryInput.value = country;

                    const location = place.geometry && place.geometry.location;
                    if (location && latInput && lngInput) {
                        latInput.value = location.lat().toFixed(6);
                        lngInput.value = location.lng().toFixed(6);
                    }
                });
            };
        })();
    </script>
    @if(config('services.google_maps.key'))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initStationAutocomplete" async defer></script>
    @endif
@endpush
