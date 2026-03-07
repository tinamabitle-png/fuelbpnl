@extends('Layouts.admin')

@section('title', 'Stations Map')
@section('page-title', 'Stations Map')
@section('page-description', 'View all petrol station coordinates')

@push('styles')
<style>
    #stationsMap {
        height: 70vh;
        min-height: 480px;
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">All Stations</h2>
                <p class="text-sm text-gray-600">Markers show each station coordinate.</p>
            </div>
            <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                {{ $stations->count() }} stations
            </span>
        </div>

        <div id="stationsMap" class="mt-4"></div>
    </div>
</div>

@push('scripts')
<script>
    const stations = @json($stations);
    let map;
    let userMarker;
    let directionsService;
    let directionsRenderer;
    let userLocation = null;

    function initStationsMap() {
        if (!stations.length) {
            return;
        }

        const first = stations[0];
        const center = {
            lat: Number(first.latitude),
            lng: Number(first.longitude),
        };

        map = new google.maps.Map(document.getElementById('stationsMap'), {
            center,
            zoom: 6,
            mapTypeControl: false,
        });

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            suppressMarkers: false,
            preserveViewport: true,
        });
        directionsRenderer.setMap(map);

        const bounds = new google.maps.LatLngBounds();
        stations.forEach((station) => {
            const lat = Number(station.latitude);
            const lng = Number(station.longitude);
            const marker = new google.maps.Marker({
                position: { lat, lng },
                map,
                title: station.name || 'Station',
            });
            bounds.extend(marker.getPosition());
            const info = new google.maps.InfoWindow({
                content: `
                    <div style="font-size: 13px;">
                        <div style="font-weight: 600; margin-bottom: 4px;">${station.name || ''}</div>
                        <div>${station.address ?? ''}</div>
                        <div>${station.city ?? ''}</div>
                        <div style="margin-top: 4px;">${station.latitude}, ${station.longitude}</div>
                    </div>
                `,
            });
            marker.addListener('click', () => {
                info.open({ anchor: marker, map });
                if (userLocation) {
                    routeTo({ lat, lng });
                }
            });
        });

        map.fitBounds(bounds);
        locateUser();
    }

    function locateUser() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                if (userMarker) userMarker.setMap(null);
                userMarker = new google.maps.Marker({
                    position: userLocation,
                    map,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 6,
                        fillColor: '#16a34a',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                    },
                    title: 'Your location',
                });
            },
            () => {
                userLocation = null;
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }

    function routeTo(destination) {
        if (!directionsService || !userLocation) return;
        directionsService.route(
            {
                origin: userLocation,
                destination,
                travelMode: google.maps.TravelMode.DRIVING,
            },
            (result, status) => {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(result);
                }
            }
        );
    }
</script>
@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initStationsMap" async defer></script>
@endif
@endpush
@endsection
