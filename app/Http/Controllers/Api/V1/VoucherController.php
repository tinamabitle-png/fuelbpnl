<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FuelVoucher;
use App\Models\FuelStation;
use App\Models\Lease;
use App\Services\Core\CreditService;
use App\Services\TapTokenService;
use App\Services\Core\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    protected $creditService;
    protected $voucherService;
    protected $tapTokenService;

    public function __construct(
        CreditService $creditService,
        VoucherService $voucherService,
        TapTokenService $tapTokenService
    )
    {
        $this->creditService = $creditService;
        $this->voucherService = $voucherService;
        $this->tapTokenService = $tapTokenService;
    }

    public function requestVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:100|max:5000',
            'fuel_type' => 'required|in:petrol,diesel,super',
            'liters' => 'sometimes|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $amount = $request->amount;

        // Check credit approval
        $approval = $this->creditService->checkApproval($user, $amount);

        if (!$approval['approved']) {
            return response()->json([
                'success' => false,
                'message' => $approval['message'] ?? 'Credit not approved',
                'requires_approval' => $approval['requires_approval'] ?? false,
            ], 400);
        }

        // Create lease if this is BNPL
        if ($approval['is_bnpl']) {
            $lease = Lease::create([
                'user_id' => $user->id,
                'principal_amount' => $amount,
                'interest_rate' => 5.0, // Default interest rate
                'interest_amount' => $amount * 0.05,
                'total_amount' => $amount * 1.05,
                'term_days' => 30,
                'daily_repayment' => ($amount * 1.05) / 30,
                'issued_at' => now(),
                'due_date' => now()->addDays(30),
            ]);

            // Create repayment schedule
            $this->createRepaymentSchedule($lease);
        }

        // Create voucher
        $voucher = $this->voucherService->createVoucher([
            'user_id' => $user->id,
            'fuel_station_id' => $request->fuel_station_id,
            'amount' => $amount,
            'liters' => $request->liters ?? ($amount / 150), // Estimate liters based on price
            'fuel_type' => $request->fuel_type,
            'lease_id' => $approval['is_bnpl'] ? $lease->id : null,
            'expires_at' => now()->addHours(24),
        ]);

        // Update user's wallet outstanding balance if BNPL
        if ($approval['is_bnpl']) {
            $user->wallet->increment('outstanding_balance', $amount);
            $user->wallet->increment('total_credit_used', $amount);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully',
            'data' => [
                'voucher' => $voucher,
                'lease' => $approval['is_bnpl'] ? $lease : null,
                'qr_data' => $voucher->generateQRData(),
            ]
        ], 201);
    }

    public function myVouchers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:issued,redeemed,expired',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $vouchers = FuelVoucher::where('user_id', $user->id)
            ->with('fuelStation')
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->limit ?? 20);

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    public function getVoucher($id)
    {
        $voucher = FuelVoucher::with(['fuelStation', 'settlement'])
            ->findOrFail($id);

        // Check ownership
        if ($voucher->user_id !== auth()->id() && !auth()->user()->hasRole(['employee', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'voucher' => $voucher,
                'qr_data' => $voucher->generateQRData(),
                'can_redeem' => $voucher->canBeRedeemed(),
            ]
        ]);
    }

    public function cancelVoucher($id)
    {
        $voucher = FuelVoucher::findOrFail($id);

        // Check ownership and status
        if ($voucher->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$voucher->isIssued()) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher cannot be cancelled'
            ], 400);
        }

        $voucher->update(['status' => 'cancelled']);

        // If BNPL, reduce outstanding balance
        if ($voucher->lease_id) {
            auth()->user()->wallet->decrement('outstanding_balance', $voucher->amount);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher cancelled successfully'
        ]);
    }

    public function getNearbyStations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:1|max:50', // in kilometers
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $stations = FuelStation::selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$request->latitude, $request->longitude, $request->latitude])
            ->where('status', 'active')
            ->having('distance', '<', $request->radius ?? 10)
            ->orderBy('distance')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stations
        ]);
    }

    private function createRepaymentSchedule(Lease $lease)
    {
        $dailyAmount = $lease->daily_repayment;
        $totalDays = $lease->term_days;

        for ($i = 1; $i <= $totalDays; $i++) {
            $lease->repayments()->create([
                'amount' => $dailyAmount,
                'due_date' => now()->addDays($i),
                'status' => 'pending',
                'type' => 'daily',
            ]);
        }
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'scan_input' => 'required_without_all:code,voucher_id|string',
            'code' => 'required_without_all:scan_input,voucher_id|string',
            'voucher_id' => 'required_without_all:scan_input,code|integer',
        ]);

        $scanInput = (string) ($request->input('scan_input') ?? '');
        $tapPayload = $scanInput !== '' ? $this->tapTokenService->verify($scanInput) : null;

        $voucherQuery = FuelVoucher::query();
        if (is_array($tapPayload)) {
            $voucherQuery
                ->where('id', (int) ($tapPayload['vid'] ?? 0))
                ->where('code', (string) ($tapPayload['code'] ?? ''));
        } elseif ($request->filled('voucher_id')) {
            $voucherQuery->where('id', $request->integer('voucher_id'));
        } else {
            $code = (string) ($request->input('code') ?: $scanInput);
            $voucherQuery->where(function ($q) use ($code) {
                $q->where('code', $code)->orWhere('qr_code', $code);
            });
        }
        $voucher = $voucherQuery->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher not found'], 404);
        }

        if (!$voucher->is_redeemable) {
            return response()->json(['success' => false, 'message' => 'Voucher cannot be redeemed'], 422);
        }

        $voucher->redeem();

        return response()->json([
            'success' => true,
            'message' => 'Voucher redeemed successfully',
            'data' => $voucher->fresh(),
        ]);
    }
}
