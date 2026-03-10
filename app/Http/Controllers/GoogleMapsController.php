<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleMapsController extends Controller
{
    public function autocomplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:500'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $apiKey = trim((string) config('services.google_maps.key'));
        if ($apiKey === '') {
            return response()->json([
                'message' => 'Google Maps API key is missing.',
                'items' => [],
            ], 422);
        }

        $params = [
            'input' => (string) $validated['q'],
            'types' => 'geocode',
            'key' => $apiKey,
        ];
        $country = strtoupper((string) ($validated['country'] ?? 'ZA'));
        if ($country !== '') {
            $params['components'] = 'country:' . $country;
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);

        if (!$response->ok()) {
            return response()->json([
                'message' => 'Google autocomplete request failed.',
                'items' => [],
            ], 502);
        }

        $json = $response->json();
        $predictions = is_array($json['predictions'] ?? null) ? $json['predictions'] : [];

        return response()->json([
            'items' => collect($predictions)->map(function (array $prediction): array {
                return [
                    'place_id' => (string) ($prediction['place_id'] ?? ''),
                    'description' => (string) ($prediction['description'] ?? ''),
                    'structured' => $prediction['structured_formatting'] ?? null,
                ];
            })->filter(function (array $item): bool {
                return $item['place_id'] !== '';
            })->values(),
        ]);
    }

    public function place(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'place_id' => ['required', 'string', 'max:255'],
        ]);

        $apiKey = trim((string) config('services.google_maps.key'));
        if ($apiKey === '') {
            return response()->json([
                'message' => 'Google Maps API key is missing.',
                'item' => null,
            ], 422);
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => (string) $validated['place_id'],
                'fields' => 'place_id,name,formatted_address,geometry,address_component',
                'key' => $apiKey,
            ]);

        if (!$response->ok()) {
            return response()->json([
                'message' => 'Google place details request failed.',
                'item' => null,
            ], 502);
        }

        $json = $response->json();
        $result = is_array($json['result'] ?? null) ? $json['result'] : null;
        if (!$result) {
            return response()->json([
                'message' => 'Google place details are empty.',
                'item' => null,
            ], 404);
        }

        return response()->json([
            'item' => [
                'place_id' => (string) ($result['place_id'] ?? ''),
                'name' => (string) ($result['name'] ?? ''),
                'formatted_address' => (string) ($result['formatted_address'] ?? ''),
                'location' => [
                    'lat' => data_get($result, 'geometry.location.lat'),
                    'lng' => data_get($result, 'geometry.location.lng'),
                ],
                'address_components' => $result['address_components'] ?? [],
            ],
        ]);
    }

    public function reverse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $apiKey = trim((string) config('services.google_maps.key'));
        if ($apiKey === '') {
            return response()->json([
                'message' => 'Google Maps API key is missing.',
                'item' => null,
            ], 422);
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => ((string) $validated['lat']) . ',' . ((string) $validated['lng']),
                'key' => $apiKey,
            ]);

        if (!$response->ok()) {
            return response()->json([
                'message' => 'Google reverse geocode request failed.',
                'item' => null,
            ], 502);
        }

        $json = $response->json();
        $result = is_array($json['results'][0] ?? null) ? $json['results'][0] : null;
        if (!$result) {
            return response()->json([
                'message' => 'Google reverse geocode is empty.',
                'item' => null,
            ], 404);
        }

        return response()->json([
            'item' => [
                'formatted_address' => (string) ($result['formatted_address'] ?? ''),
                'address_components' => $result['address_components'] ?? [],
                'location' => [
                    'lat' => data_get($result, 'geometry.location.lat'),
                    'lng' => data_get($result, 'geometry.location.lng'),
                ],
            ],
        ]);
    }
}
