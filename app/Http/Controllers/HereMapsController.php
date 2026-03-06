<?php

namespace App\Http\Controllers;

use App\Services\HereMapsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class HereMapsController extends Controller
{
    public function geocode(Request $request, HereMapsService $hereMapsService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $payload = $hereMapsService->geocode(
                (string) $validated['q'],
                (int) ($validated['limit'] ?? 5)
            );

            return response()->json($payload);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'items' => [],
            ], 502);
        }
    }

    public function reverse(Request $request, HereMapsService $hereMapsService): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        try {
            $payload = $hereMapsService->reverseGeocode(
                (float) $validated['lat'],
                (float) $validated['lng'],
                (int) ($validated['limit'] ?? 1)
            );

            return response()->json($payload);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'items' => [],
            ], 502);
        }
    }
}

