<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\VoucherStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Repayment;
use App\Models\UssdRedemptionEvent;
use App\Services\TapTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MerchantDeveloperController extends Controller
{
    public function __construct(
        private TapTokenService $tapTokenService,
    ) {
    }

    public function stations(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'stations.read')) {
            return $error;
        }

        $stations = FuelStation::query()
            ->where('owner_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'country', 'status', 'wallet_balance', 'total_settlements']);

        return response()->json([
            'success' => true,
            'data' => $stations,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'vouchers.read')) {
            return $error;
        }

        $stationIds = $this->merchantStationIds($request);

        $voucherQuery = FuelVoucher::query()->whereIn('fuel_station_id', $stationIds);

        $summary = [
            'total_count' => (clone $voucherQuery)->count(),
            'total_value' => (float) ((clone $voucherQuery)->sum('amount') ?: 0),
            'issued_count' => (clone $voucherQuery)->where('status', 'issued')->count(),
            'approved_count' => (clone $voucherQuery)->where('status', 'approved')->count(),
            'redeemed_count' => (clone $voucherQuery)->where('status', 'redeemed')->count(),
            'redeemed_value' => (float) ((clone $voucherQuery)->where('status', 'redeemed')->sum('amount') ?: 0),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    public function vouchers(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'vouchers.read')) {
            return $error;
        }

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['issued', 'approved', 'redeemed', 'expired', 'cancelled'])],
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'latest' => 'nullable|integer|min:1|max:100',
            'station_id' => 'nullable|integer',
        ]);

        $stationIds = $this->merchantStationIds($request);

        $query = FuelVoucher::query()
            ->with(['user:id,name,phone', 'fuelStation:id,name,city,address'])
            ->whereIn('fuel_station_id', $stationIds)
            ->when(!empty($validated['station_id']), fn ($q) => $q->where('fuel_station_id', (int) $validated['station_id']))
            ->when(!empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->when(!empty($validated['search']), function ($q) use ($validated) {
                $search = trim($validated['search']);
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('qr_code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        if (!empty($validated['latest'])) {
            return response()->json([
                'success' => true,
                'data' => $query->limit((int) $validated['latest'])->get()->map(fn (FuelVoucher $voucher) => $this->voucherPayload($voucher)),
            ]);
        }

        $vouchers = $query->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json([
            'success' => true,
            'data' => $vouchers,
        ]);
    }

    public function latestVouchers(Request $request): JsonResponse
    {
        $request->merge(['latest' => 4]);
        return $this->vouchers($request);
    }

    public function ussdEvents(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'vouchers.read')) {
            return $error;
        }

        $stationIds = $this->merchantStationIds($request);
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        $events = UssdRedemptionEvent::query()
            ->whereIn('fuel_station_id', $stationIds)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function repayments(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'repayments.read')) {
            return $error;
        }

        $stationIds = $this->merchantStationIds($request);
        $leaseIds = FuelVoucher::query()
            ->whereIn('fuel_station_id', $stationIds)
            ->whereNotNull('lease_id')
            ->pluck('lease_id')
            ->unique();

        $repayments = Repayment::query()
            ->visibleInSystem()
            ->with(['user:id,name,phone', 'lease:id,total_amount,due_date,status'])
            ->whereIn('lease_id', $leaseIds)
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->latest('due_date')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $repayments,
        ]);
    }

    public function redeem(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'vouchers.redeem')) {
            return $error;
        }

        $validated = $request->validate([
            'scan_input' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:255',
            'voucher_id' => 'nullable|integer',
            'pump_number' => 'nullable|string|max:50',
            'transaction_reference' => 'nullable|string|max:255',
            'device_latitude' => 'nullable|numeric|between:-90,90',
            'device_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $scanInput = $validated['scan_input'] ?? $validated['code'] ?? ($validated['voucher_id'] ?? null);
        if (!$scanInput) {
            return response()->json([
                'success' => false,
                'message' => 'Provide scan_input, code, or voucher_id.',
            ], 422);
        }

        $stationIds = $this->merchantStationIds($request);
        $voucher = $this->resolveVoucherForStations($stationIds, (string) $scanInput);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found for merchant stations.',
            ], 404);
        }

        if ($voucher->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => "Voucher must be APPROVED before redemption. Current status: {$voucher->status}.",
            ], 422);
        }

        if ($voucher->expires_at && now()->gt($voucher->expires_at)) {
            $voucher->update(['status' => 'expired']);
            return response()->json([
                'success' => false,
                'message' => 'Voucher expired and cannot be redeemed.',
            ], 422);
        }

        $deviceLatitude = array_key_exists('device_latitude', $validated) && $validated['device_latitude'] !== null
            ? (float) $validated['device_latitude']
            : null;
        $deviceLongitude = array_key_exists('device_longitude', $validated) && $validated['device_longitude'] !== null
            ? (float) $validated['device_longitude']
            : null;
        if ($geofenceError = $this->geofenceErrorForVoucher($voucher, $deviceLatitude, $deviceLongitude)) {
            return response()->json([
                'success' => false,
                'message' => $geofenceError['message'],
                'data' => $geofenceError['data'] ?? null,
            ], 422);
        }

        try {
            $voucher = $this->performRedemption($voucher, $validated);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redemption failed: ' . $e->getMessage(),
            ], 422);
        }

        if ($voucher->lease) {
            $voucher->lease->ensureRepaymentSchedule(now());
        }

        $payload = $this->voucherPayload($voucher->fresh([
            'user:id,name,phone',
            'fuelStation:id,name,city,address',
            'lease:id,total_amount,daily_repayment,due_date,status,repayment_frequency',
        ]));
        $payload['event'] = 'redeemed';

        try {
            event(new VoucherStatusChanged($payload));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher redeemed successfully.',
            'data' => $payload,
        ]);
    }

    public function offlineSync(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'vouchers.redeem')) {
            return $error;
        }

        $validated = $request->validate([
            'redemptions' => 'required|array|min:1|max:200',
            'redemptions.*.scan_input' => 'nullable|string|max:500',
            'redemptions.*.code' => 'nullable|string|max:255',
            'redemptions.*.voucher_id' => 'nullable|integer',
            'redemptions.*.pump_number' => 'nullable|string|max:50',
            'redemptions.*.transaction_reference' => 'nullable|string|max:255',
            'redemptions.*.device_latitude' => 'nullable|numeric|between:-90,90',
            'redemptions.*.device_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $stationIds = $this->merchantStationIds($request);
        $results = [];

        foreach ($validated['redemptions'] as $index => $item) {
            $scanInput = $item['scan_input'] ?? $item['code'] ?? ($item['voucher_id'] ?? null);
            if (!$scanInput) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'message' => 'Provide scan_input, code, or voucher_id.',
                ];
                continue;
            }

            $voucher = $this->resolveVoucherForStations($stationIds, (string) $scanInput);
            if (!$voucher) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'message' => 'Voucher not found for merchant stations.',
                ];
                continue;
            }

            if ($voucher->status !== 'approved') {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'voucher_id' => $voucher->id,
                    'message' => "Voucher must be APPROVED before redemption. Current status: {$voucher->status}.",
                ];
                continue;
            }

            if ($voucher->expires_at && now()->gt($voucher->expires_at)) {
                $voucher->update(['status' => 'expired']);
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'voucher_id' => $voucher->id,
                    'message' => 'Voucher expired and cannot be redeemed.',
                ];
                continue;
            }

            $deviceLatitude = array_key_exists('device_latitude', $item) && $item['device_latitude'] !== null
                ? (float) $item['device_latitude']
                : null;
            $deviceLongitude = array_key_exists('device_longitude', $item) && $item['device_longitude'] !== null
                ? (float) $item['device_longitude']
                : null;
            if ($geofenceError = $this->geofenceErrorForVoucher($voucher, $deviceLatitude, $deviceLongitude)) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'voucher_id' => $voucher->id,
                    'message' => $geofenceError['message'],
                    'data' => $geofenceError['data'] ?? null,
                ];
                continue;
            }

            try {
                $voucher = $this->performRedemption($voucher, $item);

                if ($voucher->lease) {
                    $voucher->lease->ensureRepaymentSchedule(now());
                }

                $payload = $this->voucherPayload($voucher->fresh([
                    'user:id,name,phone',
                    'fuelStation:id,name,city,address',
                    'lease:id,total_amount,daily_repayment,due_date,status,repayment_frequency',
                ]));
                $payload['event'] = 'redeemed';

                try {
                    event(new VoucherStatusChanged($payload));
                } catch (\Throwable $e) {
                    report($e);
                }

                $results[] = [
                    'index' => $index,
                    'success' => true,
                    'message' => 'Voucher redeemed successfully.',
                    'data' => $payload,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'voucher_id' => $voucher->id,
                    'message' => 'Redemption failed: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Offline sync processed.',
            'data' => [
                'total' => count($results),
                'succeeded' => collect($results)->where('success', true)->count(),
                'failed' => collect($results)->where('success', false)->count(),
                'results' => $results,
            ],
        ]);
    }

    public function sandboxHealth(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'sandbox.access')) {
            return $error;
        }

        return response()->json([
            'success' => true,
            'mode' => 'sandbox',
            'message' => 'Sandbox API online',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function sandboxStations(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'sandbox.access')) {
            return $error;
        }

        $stations = FuelStation::query()
            ->where('owner_id', $request->user()->id)
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'city', 'country', 'status']);

        return response()->json([
            'success' => true,
            'mode' => 'sandbox',
            'simulated' => true,
            'data' => $stations,
        ]);
    }

    public function sandboxVouchers(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'sandbox.access')) {
            return $error;
        }

        $stationIds = $this->merchantStationIds($request);

        $items = FuelVoucher::query()
            ->with(['user:id,name,phone', 'fuelStation:id,name,city,address'])
            ->whereIn('fuel_station_id', $stationIds)
            ->latest()
            ->limit((int) $request->integer('limit', 10))
            ->get()
            ->map(fn (FuelVoucher $voucher) => $this->voucherPayload($voucher));

        return response()->json([
            'success' => true,
            'mode' => 'sandbox',
            'simulated' => true,
            'data' => $items,
        ]);
    }

    public function sandboxRepayments(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'sandbox.access')) {
            return $error;
        }

        $stationIds = $this->merchantStationIds($request);
        $leaseIds = FuelVoucher::query()
            ->whereIn('fuel_station_id', $stationIds)
            ->whereNotNull('lease_id')
            ->pluck('lease_id')
            ->unique();

        $items = Repayment::query()
            ->visibleInSystem()
            ->with(['user:id,name,phone', 'lease:id,total_amount,due_date,status'])
            ->whereIn('lease_id', $leaseIds)
            ->latest('due_date')
            ->limit((int) $request->integer('limit', 10))
            ->get();

        return response()->json([
            'success' => true,
            'mode' => 'sandbox',
            'simulated' => true,
            'data' => $items,
        ]);
    }

    public function sandboxRedeem(Request $request): JsonResponse
    {
        if ($error = $this->enforceTokenAbility($request, 'sandbox.access')) {
            return $error;
        }

        $validated = $request->validate([
            'scan_input' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:255',
            'voucher_id' => 'nullable|integer',
        ]);

        $scanInput = $validated['scan_input'] ?? $validated['code'] ?? ($validated['voucher_id'] ?? null);
        if (!$scanInput) {
            return response()->json([
                'success' => false,
                'mode' => 'sandbox',
                'message' => 'Provide scan_input, code, or voucher_id.',
            ], 422);
        }

        $stationIds = $this->merchantStationIds($request);
        $voucher = $this->resolveVoucherForStations($stationIds, (string) $scanInput);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'mode' => 'sandbox',
                'message' => 'Voucher not found for merchant stations.',
            ], 404);
        }

        $canRedeem = $voucher->status === 'approved' && (!$voucher->expires_at || now()->lte($voucher->expires_at));

        return response()->json([
            'success' => true,
            'mode' => 'sandbox',
            'simulated' => true,
            'message' => $canRedeem
                ? 'Voucher would be redeemed in live mode.'
                : 'Voucher would fail redemption in live mode.',
            'data' => [
                'voucher' => $this->voucherPayload($voucher),
                'can_redeem' => $canRedeem,
            ],
        ]);
    }

    private function merchantStationIds(Request $request): Collection
    {
        return FuelStation::query()
            ->where('owner_id', $request->user()->id)
            ->pluck('id');
    }

    private function performRedemption(FuelVoucher $voucher, array $payload): FuelVoucher
    {
        DB::transaction(function () use (&$voucher, $payload) {
            $lockedVoucher = FuelVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            if ($lockedVoucher->status !== 'approved') {
                throw new \RuntimeException("Voucher must be APPROVED before redemption. Current status: {$lockedVoucher->status}.");
            }

            if ($lockedVoucher->expires_at && now()->gte($lockedVoucher->expires_at)) {
                $lockedVoucher->update(['status' => 'expired']);
                throw new \RuntimeException('Voucher expired and cannot be redeemed.');
            }

            $lockedStation = FuelStation::whereKey($lockedVoucher->fuel_station_id)->lockForUpdate()->firstOrFail();
            $lockedStation->deductFromWallet(
                (float) $lockedVoucher->amount,
                'Voucher redemption: ' . $lockedVoucher->code
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

    private function resolveVoucherForStations(Collection $stationIds, string $scanInput): ?FuelVoucher
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
            ->whereIn('fuel_station_id', $stationIds)
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
            'lease' => $voucher->lease ? [
                'id' => $voucher->lease->id,
                'status' => $voucher->lease->status,
                'total_amount' => (float) $voucher->lease->total_amount,
                'daily_repayment' => (float) $voucher->lease->daily_repayment,
                'due_date' => optional($voucher->lease->due_date)->toDateString(),
                'remaining_balance' => (float) $voucher->lease->remaining_balance,
                'repayment_frequency' => (string) ($voucher->lease->repayment_frequency ?? 'daily'),
            ] : null,
            'upcoming_repayments' => $this->upcomingRepaymentsPayload($voucher),
            'issued_at' => optional($voucher->issued_at)->toIso8601String(),
            'expires_at' => optional($voucher->expires_at)->toIso8601String(),
            'redeemed_at' => optional($voucher->redeemed_at)->toIso8601String(),
            'redeemed_fuel_amount' => $voucher->redeemed_fuel_amount !== null ? (float) $voucher->redeemed_fuel_amount : null,
            'redeemed_airtime_amount' => $voucher->redeemed_airtime_amount !== null ? (float) $voucher->redeemed_airtime_amount : null,
            'airtime_phone' => $voucher->airtime_phone,
            'airtime_reference' => $voucher->airtime_reference,
            'airtime_status' => $voucher->airtime_status,
            'pump_number' => $voucher->pump_number,
            'transaction_reference' => $voucher->transaction_reference,
        ];
    }

    private function upcomingRepaymentsPayload(FuelVoucher $voucher): array
    {
        if (!$voucher->lease_id) {
            return [];
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $rows = Repayment::query()
            ->where('lease_id', $voucher->lease_id)
            ->where('status', 'pending')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->get(['id', 'amount', 'due_date', 'status']);

        return $rows->map(function (Repayment $repayment) use ($baseUrl) {
            return [
                'id' => $repayment->id,
                'amount' => (float) $repayment->amount,
                'due_date' => optional($repayment->due_date)->toDateString(),
                'status' => $repayment->status,
                'pay_url' => $baseUrl !== '' ? "{$baseUrl}/driver/repayments?repayment_id={$repayment->id}" : null,
            ];
        })->toArray();
    }

    private function enforceTokenAbility(Request $request, string $ability): ?JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Developer token required. Use Bearer token authentication.',
            ], 401);
        }

        if (!$token->can('*') && !$token->can($ability)) {
            return response()->json([
                'success' => false,
                'message' => "Token missing required ability: {$ability}",
            ], 403);
        }

        return null;
    }
}
