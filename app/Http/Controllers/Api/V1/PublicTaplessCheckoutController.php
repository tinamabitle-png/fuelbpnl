<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use App\Models\TaplessApiPartner;
use App\Models\TaplessPaymentIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PublicTaplessCheckoutController extends Controller
{
    public function createIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'public_key' => ['required', 'string', 'max:255'],
            'station_id' => ['required', 'integer'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'currency' => ['nullable', 'string', 'in:ZAR,zar'],
            'scan_input' => ['nullable', 'string', 'max:500'],
            'pump_number' => ['nullable', 'string', 'max:50'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'checkout_token' => ['nullable', 'string'],
        ]);

        $partner = $this->partnerFromPublicKey((string) $validated['public_key']);
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout key is invalid or inactive.',
            ], 403);
        }

        $station = $this->partnerStation($partner, (int) $validated['station_id']);
        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'This station is not enabled for the checkout key.',
            ], 403);
        }

        if (!$this->originAllowed($partner, $request, $validated)) {
            return response()->json([
                'success' => false,
                'message' => 'This website is not allowed to use the checkout key.',
            ], 403);
        }

        $reference = trim((string) ($validated['external_reference'] ?? ''));
        if ($reference === '') {
            $reference = 'BW-CHECKOUT-' . strtoupper(Str::random(10));
        }
        $storedReference = $this->stationScopedReference($station->id, $reference);

        $existing = TaplessPaymentIntent::query()
            ->where('partner_id', $partner->id)
            ->where('external_reference', $storedReference)
            ->latest('id')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Existing checkout intent returned.',
                'data' => $this->intentPayload($existing->fresh(['station', 'partner'])),
            ]);
        }

        $intent = TaplessPaymentIntent::create([
            'partner_id' => $partner->id,
            'fuel_station_id' => $station->id,
            'external_reference' => $storedReference,
            'scan_input' => $validated['scan_input'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'currency' => 'ZAR',
            'pump_number' => $validated['pump_number'] ?? null,
            'transaction_reference' => $validated['transaction_reference'] ?? $reference,
            'metadata' => array_merge((array) ($validated['metadata'] ?? []), [
                'source' => 'public_checkout_widget',
                'pos_reference' => $reference,
                'origin' => (string) $request->headers->get('origin', ''),
                'referer' => (string) $request->headers->get('referer', ''),
            ]),
            'request_payload' => $validated,
            'status' => 'created',
        ]);

        $partner->forceFill(['last_used_at' => now()])->save();
        $intent->load(['station', 'partner']);
        $this->dispatchWebhook($partner, 'checkout.intent.created', $intent);

        return response()->json([
            'success' => true,
            'message' => 'Checkout intent created.',
            'data' => $this->intentPayload($intent),
        ], 201);
    }

    public function showIntent(Request $request, string $publicId): JsonResponse
    {
        $validated = $request->validate([
            'public_key' => ['required', 'string', 'max:255'],
        ]);

        $partner = $this->partnerFromPublicKey((string) $validated['public_key']);
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout key is invalid or inactive.',
            ], 403);
        }

        $intent = TaplessPaymentIntent::query()
            ->with(['station', 'partner'])
            ->where('partner_id', $partner->id)
            ->where('public_id', $publicId)
            ->first();

        if (!$intent) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout intent was not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->intentPayload($intent),
        ]);
    }

    private function partnerFromPublicKey(string $publicKey): ?TaplessApiPartner
    {
        return TaplessApiPartner::query()
            ->where('public_key', trim($publicKey))
            ->where('status', 'active')
            ->first();
    }

    private function partnerStation(TaplessApiPartner $partner, int $stationId): ?FuelStation
    {
        return $partner->stations()
            ->where('fuel_stations.id', $stationId)
            ->first();
    }

    private function intentPayload(TaplessPaymentIntent $intent): array
    {
        $displayReference = (string) data_get($intent->metadata, 'pos_reference', $intent->external_reference);

        return [
            'intent_id' => $intent->public_id,
            'status' => $intent->status,
            'external_reference' => $displayReference,
            'stored_reference' => $intent->external_reference,
            'amount' => $intent->amount !== null ? (float) $intent->amount : null,
            'currency' => $intent->currency ?: 'ZAR',
            'pump_number' => $intent->pump_number,
            'transaction_reference' => $intent->transaction_reference,
            'failure_reason' => $intent->failure_reason,
            'station' => $intent->station ? [
                'id' => $intent->station->id,
                'name' => $intent->station->name,
                'city' => $intent->station->city,
                'country' => $intent->station->country,
            ] : null,
            'checkout_url' => route('checkout.bwiser.embed', [
                'public_key' => $intent->partner?->public_key,
                'station_id' => $intent->fuel_station_id,
                'reference' => $displayReference,
                'amount' => $intent->amount,
                'pump' => $intent->pump_number,
            ]),
            'expires_at' => optional($intent->expires_at)->toIso8601String(),
            'created_at' => optional($intent->created_at)->toIso8601String(),
        ];
    }

    private function stationScopedReference(int $stationId, string $reference): string
    {
        return 'station-' . $stationId . ':' . $reference;
    }

    private function originAllowed(TaplessApiPartner $partner, Request $request, array $validated): bool
    {
        $allowed = collect((array) data_get($partner->meta, 'allowed_origins', []))
            ->map(fn ($origin) => strtolower(trim((string) $origin)))
            ->filter()
            ->values();

        if ($allowed->isEmpty()) {
            return true;
        }

        $tokenPayload = $this->checkoutTokenPayload((string) ($validated['checkout_token'] ?? ''));
        if (!$tokenPayload) {
            return false;
        }

        $tokenOrigin = strtolower(trim((string) ($tokenPayload['parent_origin'] ?? '')));
        $tokenPublicKey = trim((string) ($tokenPayload['public_key'] ?? ''));
        $tokenStationId = (int) ($tokenPayload['station_id'] ?? 0);

        return $tokenPublicKey === (string) $partner->public_key
            && $tokenStationId === (int) ($validated['station_id'] ?? 0)
            && $allowed->contains($tokenOrigin);
    }

    private function checkoutTokenPayload(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
            if (!is_array($payload)) {
                return null;
            }

            if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
                return null;
            }

            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function dispatchWebhook(TaplessApiPartner $partner, string $event, TaplessPaymentIntent $intent): void
    {
        $url = trim((string) $partner->webhook_url);
        if ($url === '') {
            return;
        }

        try {
            $payload = [
                'event' => $event,
                'created_at' => now()->toIso8601String(),
                'data' => $this->intentPayload($intent),
            ];
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $secret = $partner->decryptWebhookSecret();
            $signature = $secret !== '' ? hash_hmac('sha256', (string) $body, $secret) : '';

            Http::timeout(5)
                ->acceptJson()
                ->withHeaders(array_filter([
                    'X-Bwiser-Event' => $event,
                    'X-Bwiser-Signature' => $signature,
                ]))
                ->withBody((string) $body, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
