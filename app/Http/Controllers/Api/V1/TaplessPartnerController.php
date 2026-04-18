<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\VoucherStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\TaplessApiPartner;
use App\Models\TaplessPaymentIntent;
use App\Services\TapTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaplessPartnerController extends Controller
{
    public function __construct(
        private TapTokenService $tapTokenService,
    ) {
    }

    public function health(Request $request): JsonResponse
    {
        $partner = $this->partner($request);

        return response()->json([
            'success' => true,
            'data' => [
                'partner' => [
                    'name' => $partner->name,
                    'slug' => $partner->slug,
                    'status' => $partner->status,
                ],
                'capabilities' => [
                    'tapless.create_intent',
                    'tapless.authorize',
                    'tapless.redeem',
                    'tapless.status',
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function stations(Request $request): JsonResponse
    {
        $partner = $this->partner($request);
        $stations = $partner->stations()
            ->orderBy('name')
            ->get(['fuel_stations.id', 'name', 'city', 'country', 'status', 'latitude', 'longitude']);

        return response()->json([
            'success' => true,
            'data' => $stations,
        ]);
    }

    public function createIntent(Request $request): JsonResponse
    {
        $partner = $this->partner($request);
        $validated = $request->validate([
            'station_id' => 'required|integer',
            'external_reference' => 'required|string|max:120',
            'scan_input' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:255',
            'voucher_id' => 'nullable|integer',
            'amount' => 'nullable|numeric|min:0|max:100000',
            'device_latitude' => 'nullable|numeric|between:-90,90',
            'device_longitude' => 'nullable|numeric|between:-180,180',
            'pump_number' => 'nullable|string|max:50',
            'transaction_reference' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $station = $this->partnerStationOrFail($partner, (int) $validated['station_id']);
        $existing = TaplessPaymentIntent::query()
            ->where('partner_id', $partner->id)
            ->where('external_reference', $validated['external_reference'])
            ->latest('id')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Existing tapless payment intent returned.',
                'data' => $this->intentPayload($existing->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ]);
        }

        $scanInput = $validated['scan_input']
            ?? $validated['code']
            ?? (isset($validated['voucher_id']) ? (string) $validated['voucher_id'] : null);

        $voucher = $scanInput
            ? $this->resolveVoucherForStation($station->id, (string) $scanInput)
            : null;

        $intent = TaplessPaymentIntent::create([
            'partner_id' => $partner->id,
            'fuel_station_id' => $station->id,
            'fuel_voucher_id' => $voucher?->id,
            'external_reference' => $validated['external_reference'],
            'scan_input' => $scanInput,
            'amount' => $validated['amount'] ?? null,
            'device_latitude' => $validated['device_latitude'] ?? null,
            'device_longitude' => $validated['device_longitude'] ?? null,
            'pump_number' => $validated['pump_number'] ?? null,
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'request_payload' => $validated,
            'status' => 'created',
        ]);

        $intent->load(['station', 'voucher.fuelStation', 'voucher.user']);

        return response()->json([
            'success' => true,
            'message' => 'Tapless payment intent created.',
            'data' => $this->intentPayload($intent),
        ], 201);
    }

    public function showIntent(Request $request, string $publicId): JsonResponse
    {
        $intent = $this->partnerIntent($request, $publicId);

        return response()->json([
            'success' => true,
            'data' => $this->intentPayload($intent),
        ]);
    }

    public function authorizeIntent(Request $request, string $publicId): JsonResponse
    {
        $intent = $this->partnerIntent($request, $publicId);
        if (in_array($intent->status, ['redeemed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Intent can no longer be authorized from status {$intent->status}.",
            ], 422);
        }

        if ($this->intentExpired($intent)) {
            $this->failIntent($intent, 'Tapless payment intent expired before authorization.');

            return response()->json([
                'success' => false,
                'message' => 'Tapless payment intent expired before authorization.',
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ], 422);
        }

        $voucher = $intent->voucher;
        if (!$voucher && $intent->scan_input) {
            $voucher = $this->resolveVoucherForStation($intent->fuel_station_id, (string) $intent->scan_input);
            if ($voucher) {
                $intent->forceFill(['fuel_voucher_id' => $voucher->id])->save();
            }
        }

        if (!$voucher) {
            $this->failIntent($intent, 'Voucher not found for the selected station.');

            return response()->json([
                'success' => false,
                'message' => 'Voucher not found for the selected station.',
                'data' => $this->intentPayload($intent->fresh(['station'])),
            ], 404);
        }

        if ($voucher->fuel_station_id !== $intent->fuel_station_id) {
            $this->failIntent($intent, 'Voucher does not belong to the requested station.');

            return response()->json([
                'success' => false,
                'message' => 'Voucher does not belong to the requested station.',
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ], 422);
        }

        $isLegacyVirtualCardVoucher = ($voucher->status === 'issued') && $voucher->isVirtualCardConvertedVoucher();
        if ($voucher->status !== 'approved' && !$isLegacyVirtualCardVoucher) {
            $this->failIntent($intent, "Voucher must be APPROVED before redemption. Current status: {$voucher->status}.");

            return response()->json([
                'success' => false,
                'message' => "Voucher must be APPROVED before redemption. Current status: {$voucher->status}.",
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ], 422);
        }

        if ($voucher->expires_at && now()->gte($voucher->expires_at)) {
            $voucher->update(['status' => 'expired']);
            $this->failIntent($intent, 'Voucher expired and cannot be redeemed.');

            return response()->json([
                'success' => false,
                'message' => 'Voucher expired and cannot be redeemed.',
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ], 422);
        }

        $geofenceError = $this->geofenceErrorForVoucher(
            $voucher,
            $intent->device_latitude !== null ? (float) $intent->device_latitude : null,
            $intent->device_longitude !== null ? (float) $intent->device_longitude : null,
        );
        if ($geofenceError) {
            $this->failIntent($intent, $geofenceError['message'], $geofenceError['data'] ?? null);

            return response()->json([
                'success' => false,
                'message' => $geofenceError['message'],
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ], 422);
        }

        $intent->forceFill([
            'status' => 'authorized',
            'authorized_at' => now(),
            'failure_reason' => null,
            'response_payload' => [
                'event' => 'authorized',
                'voucher' => $this->voucherPayload($voucher->fresh(['user:id,name,phone', 'fuelStation:id,name,city,address', 'lease:id,total_amount,daily_repayment,due_date,status,repayment_frequency'])),
            ],
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Tapless payment intent authorized.',
            'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
        ]);
    }

    public function redeemIntent(Request $request, string $publicId): JsonResponse
    {
        $intent = $this->partnerIntent($request, $publicId);
        if ($intent->status === 'redeemed') {
            return response()->json([
                'success' => true,
                'message' => 'Tapless payment intent already redeemed.',
                'data' => $this->intentPayload($intent),
            ]);
        }

        if (!in_array($intent->status, ['created', 'authorized'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Intent cannot be redeemed from status {$intent->status}.",
                'data' => $this->intentPayload($intent),
            ], 422);
        }

        if ($this->intentExpired($intent)) {
            $this->failIntent($intent, 'Tapless payment intent expired before redemption.');

            return response()->json([
                'success' => false,
                'message' => 'Tapless payment intent expired before redemption.',
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ], 422);
        }

        if ($intent->status !== 'authorized') {
            $authorizationResponse = $this->authorizeIntent($request, $publicId);
            if ($authorizationResponse->getStatusCode() >= 400) {
                return $authorizationResponse;
            }
            $intent = $this->partnerIntent($request, $publicId);
        }

        $voucher = $intent->voucher;
        if (!$voucher) {
            $this->failIntent($intent, 'Authorized intent does not have a voucher.');

            return response()->json([
                'success' => false,
                'message' => 'Authorized intent does not have a voucher.',
                'data' => $this->intentPayload($intent),
            ], 422);
        }

        try {
            $voucher = $this->performRedemption($voucher, [
                'pump_number' => $intent->pump_number,
                'transaction_reference' => $intent->transaction_reference ?: $intent->external_reference,
            ]);

            if ($voucher->lease) {
                $voucher->lease->ensureRepaymentSchedule(now());
            }

            $voucherPayload = $this->voucherPayload($voucher->fresh([
                'user:id,name,phone',
                'fuelStation:id,name,city,address',
                'lease:id,total_amount,daily_repayment,due_date,status,repayment_frequency',
            ]));
            $voucherPayload['event'] = 'redeemed';

            $intent->forceFill([
                'status' => 'redeemed',
                'redeemed_at' => now(),
                'response_payload' => $voucherPayload,
                'failure_reason' => null,
            ])->save();

            try {
                event(new VoucherStatusChanged($voucherPayload));
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tapless payment redeemed successfully.',
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ]);
        } catch (\Throwable $e) {
            $this->failIntent($intent, 'Redemption failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Redemption failed: ' . $e->getMessage(),
                'data' => $this->intentPayload($intent->fresh(['station', 'voucher.fuelStation', 'voucher.user'])),
            ], 422);
        }
    }

    private function partner(Request $request): TaplessApiPartner
    {
        /** @var TaplessApiPartner $partner */
        $partner = $request->attributes->get('tapless_partner');

        return $partner;
    }

    private function partnerIntent(Request $request, string $publicId): TaplessPaymentIntent
    {
        return TaplessPaymentIntent::query()
            ->with(['station', 'voucher.fuelStation', 'voucher.user'])
            ->where('partner_id', $this->partner($request)->id)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    private function partnerStationOrFail(TaplessApiPartner $partner, int $stationId): FuelStation
    {
        return $partner->stations()
            ->where('fuel_stations.id', $stationId)
            ->firstOrFail();
    }

    private function resolveVoucherForStation(int $stationId, string $scanInput): ?FuelVoucher
    {
        $scanInput = trim($scanInput);
        $voucherId = null;
        $voucherCode = null;
        $voucherQr = null;

        $tapPayload = $this->tapTokenService->verify($scanInput);
        if (is_array($tapPayload)) {
            $voucherId = isset($tapPayload['vid']) ? (int) $tapPayload['vid'] : null;
            $voucherCode = isset($tapPayload['code']) ? (string) $tapPayload['code'] : null;
            $voucherQr = $voucherCode;
        }

        if (!$tapPayload && Str::startsWith($scanInput, '{') && Str::endsWith($scanInput, '}')) {
            $decoded = json_decode($scanInput, true);
            if (is_array($decoded)) {
                $voucherId = isset($decoded['voucher_id']) ? (int) $decoded['voucher_id'] : null;
                $voucherCode = $decoded['code'] ?? null;
                $voucherQr = $decoded['qr_code'] ?? null;
            }
        } elseif (!$tapPayload) {
            $voucherCode = $scanInput;
            $voucherQr = $scanInput;
            if (ctype_digit($scanInput)) {
                $voucherId = (int) $scanInput;
            }
        }

        return FuelVoucher::query()
            ->where('fuel_station_id', $stationId)
            ->where(function ($q) use ($voucherId, $voucherCode, $voucherQr) {
                if ($voucherId) {
                    $q->orWhere('id', $voucherId);
                }
                if ($voucherCode) {
                    $q->orWhere('code', $voucherCode);
                }
                if ($voucherQr) {
                    $q->orWhere('qr_code', $voucherQr);
                }
            })
            ->latest()
            ->first();
    }

    private function performRedemption(FuelVoucher $voucher, array $payload): FuelVoucher
    {
        DB::transaction(function () use (&$voucher, $payload) {
            $lockedVoucher = FuelVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            $isLegacyVirtualCardVoucher = ($lockedVoucher->status === 'issued') && $lockedVoucher->isVirtualCardConvertedVoucher();
            if ($lockedVoucher->status !== 'approved' && !$isLegacyVirtualCardVoucher) {
                throw new \RuntimeException("Voucher must be APPROVED before redemption. Current status: {$lockedVoucher->status}.");
            }

            if ($lockedVoucher->expires_at && now()->gte($lockedVoucher->expires_at)) {
                $lockedVoucher->update(['status' => 'expired']);
                throw new \RuntimeException('Voucher expired and cannot be redeemed.');
            }

            $lockedStation = FuelStation::whereKey($lockedVoucher->fuel_station_id)->lockForUpdate()->firstOrFail();
            $lockedStation->deductFromWallet(
                (float) $lockedVoucher->amount,
                'Partner tapless redemption: ' . $lockedVoucher->code
            );

            $lockedVoucher->update([
                'status' => 'redeemed',
                'redeemed_at' => now(),
                'pump_number' => $payload['pump_number'] ?? $lockedVoucher->pump_number,
                'transaction_reference' => $payload['transaction_reference'] ?? $lockedVoucher->transaction_reference,
                'redeemed_fuel_amount' => (float) $lockedVoucher->amount,
                'redeemed_airtime_amount' => 0,
                'airtime_status' => 'not_requested',
            ]);

            $voucher = $lockedVoucher;
        });

        return $voucher;
    }

    private function geofenceErrorForVoucher(FuelVoucher $voucher, ?float $deviceLatitude, ?float $deviceLongitude): ?array
    {
        $enabled = (bool) config('services.redemption_geofence.enabled', false);
        if (!$enabled) {
            return null;
        }

        if ($deviceLatitude === null || $deviceLongitude === null) {
            return [
                'message' => 'Device location is required for voucher redemption.',
            ];
        }

        $station = $voucher->fuelStation;
        if (!$station || $station->latitude === null || $station->longitude === null) {
            return [
                'message' => 'Station geofence is not configured.',
            ];
        }

        $distanceMeters = $this->distanceMeters(
            $deviceLatitude,
            $deviceLongitude,
            (float) $station->latitude,
            (float) $station->longitude
        );

        $radiusMeters = (float) config('services.redemption_geofence.radius_meters', 150);
        if ($distanceMeters > $radiusMeters) {
            return [
                'message' => 'Redemption denied: device is outside station geofence.',
                'data' => [
                    'distance_meters' => round($distanceMeters, 2),
                    'radius_meters' => $radiusMeters,
                ],
            ];
        }

        return null;
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function voucherPayload(FuelVoucher $voucher): array
    {
        return [
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'qr_code' => $voucher->qr_code,
            'status' => $voucher->status,
            'amount' => (float) $voucher->amount,
            'fuel_type' => $voucher->fuel_type,
            'liters' => (float) $voucher->liters,
            'station_id' => $voucher->fuel_station_id,
            'station' => [
                'id' => $voucher->fuelStation?->id ?? $voucher->fuel_station_id,
                'name' => $voucher->fuelStation?->name,
                'city' => $voucher->fuelStation?->city,
                'address' => $voucher->fuelStation?->address,
            ],
            'driver' => [
                'id' => $voucher->user?->id,
                'name' => $voucher->user?->name,
                'phone' => $voucher->user?->phone,
            ],
            'issued_at' => optional($voucher->issued_at)->toIso8601String(),
            'expires_at' => optional($voucher->expires_at)->toIso8601String(),
            'redeemed_at' => optional($voucher->redeemed_at)->toIso8601String(),
            'pump_number' => $voucher->pump_number,
            'transaction_reference' => $voucher->transaction_reference,
        ];
    }

    private function intentPayload(TaplessPaymentIntent $intent): array
    {
        return [
            'intent_id' => $intent->public_id,
            'status' => $intent->status,
            'external_reference' => $intent->external_reference,
            'station' => $intent->station ? [
                'id' => $intent->station->id,
                'name' => $intent->station->name,
                'city' => $intent->station->city,
                'country' => $intent->station->country,
            ] : null,
            'voucher' => $intent->voucher ? $this->voucherPayload($intent->voucher) : null,
            'amount' => $intent->amount !== null ? (float) $intent->amount : null,
            'currency' => $intent->currency,
            'pump_number' => $intent->pump_number,
            'transaction_reference' => $intent->transaction_reference,
            'failure_reason' => $intent->failure_reason,
            'metadata' => $intent->metadata,
            'authorized_at' => optional($intent->authorized_at)->toIso8601String(),
            'redeemed_at' => optional($intent->redeemed_at)->toIso8601String(),
            'expires_at' => optional($intent->expires_at)->toIso8601String(),
        ];
    }

    private function failIntent(TaplessPaymentIntent $intent, string $reason, ?array $responsePayload = null): void
    {
        $intent->forceFill([
            'status' => 'failed',
            'failure_reason' => $reason,
            'response_payload' => $responsePayload,
        ])->save();
    }

    private function intentExpired(TaplessPaymentIntent $intent): bool
    {
        return $intent->expires_at !== null && now()->gte($intent->expires_at);
    }
}
