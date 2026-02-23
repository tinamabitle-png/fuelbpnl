<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\VoucherStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Repayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MerchantDeveloperController extends Controller
{
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
            ->with(['user:id,name,phone', 'fuelStation:id,name,city'])
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

        try {
            DB::transaction(function () use (&$voucher, $validated) {
                $lockedVoucher = FuelVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
                if ($lockedVoucher->status !== 'approved') {
                    throw new \RuntimeException("Voucher must be APPROVED before redemption. Current status: {$lockedVoucher->status}.");
                }

                $lockedStation = FuelStation::whereKey($lockedVoucher->fuel_station_id)->lockForUpdate()->firstOrFail();
                $lockedStation->deductFromWallet(
                    (float) $lockedVoucher->amount,
                    'Voucher redemption: ' . $lockedVoucher->code
                );

                $lockedVoucher->update([
                    'status' => 'redeemed',
                    'redeemed_at' => now(),
                    'pump_number' => $validated['pump_number'] ?? $lockedVoucher->pump_number,
                    'transaction_reference' => $validated['transaction_reference'] ?? $lockedVoucher->transaction_reference,
                ]);

                $voucher = $lockedVoucher;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redemption failed: ' . $e->getMessage(),
            ], 422);
        }

        if ($voucher->lease) {
            $voucher->lease->ensureRepaymentSchedule(now());
        }

        $payload = $this->voucherPayload($voucher->fresh(['user:id,name,phone', 'fuelStation:id,name,city']));
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
            ->with(['user:id,name,phone', 'fuelStation:id,name,city'])
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

    private function resolveVoucherForStations(Collection $stationIds, string $scanInput): ?FuelVoucher
    {
        $scanInput = trim($scanInput);
        $voucherId = null;
        $voucherCode = null;
        $voucherQr = null;

        if (Str::startsWith($scanInput, '{') && Str::endsWith($scanInput, '}')) {
            $decoded = json_decode($scanInput, true);
            if (is_array($decoded)) {
                $voucherId = isset($decoded['voucher_id']) ? (int) $decoded['voucher_id'] : null;
                $voucherCode = $decoded['code'] ?? null;
                $voucherQr = $decoded['qr_code'] ?? null;
            }
        } else {
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
