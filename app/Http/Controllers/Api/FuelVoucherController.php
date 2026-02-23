<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\FuelStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FuelVoucherController extends Controller
{
    private const MIN_REPAYMENT_AMOUNT = 30.00;

    /**
     * Request a fuel voucher
     */
    public function requestVoucher(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:500|max:50000',
            'fuel_type' => 'required|in:petrol,diesel,super',
            'payment_type' => 'required|in:wallet,bnpl',
            'liters' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user can request voucher
        if (!$user->canRequestVoucher($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot request voucher. Check your credit limit or wallet balance.'
            ], 403);
        }

        // Check if fuel station is active
        $fuelStation = FuelStation::find($request->fuel_station_id);
        if ($fuelStation->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Selected fuel station is not active'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $lease = null;
            
            if ($request->payment_type === 'bnpl') {
                // Create a BNPL lease
                $lease = $this->createLease($user, $request->amount);
                
                // Use credit
                $user->creditLimit->useCredit($request->amount);
                $user->wallet->increment('outstanding_balance', $request->amount);
                $user->wallet->increment('total_credit_used', $request->amount);
            } else {
                // Pay from wallet
                $user->wallet->deductFunds($request->amount, 'Fuel voucher purchase');
            }

            // Calculate liters if not provided
            $liters = $request->liters;
            if (!$liters) {
                // Calculate based on average fuel price
                $pricePerLiter = $this->getFuelPrice($request->fuel_type);
                $liters = $request->amount / $pricePerLiter;
            }

            // Create voucher
            $lockedStation = FuelStation::whereKey((int) $request->fuel_station_id)
                ->lockForUpdate()
                ->firstOrFail();
            $openExposure = FuelVoucher::where('fuel_station_id', $lockedStation->id)
                ->whereIn('status', ['issued', 'approved'])
                ->lockForUpdate()
                ->sum('amount');
            $availableCapacity = max(0, (float) $lockedStation->wallet_balance - (float) $openExposure);
            if ($availableCapacity < (float) $request->amount) {
                throw new \InvalidArgumentException(sprintf(
                    'Insufficient station pre-funded balance. Available capacity: R%.2f.',
                    $availableCapacity
                ));
            }

            $voucher = FuelVoucher::create([
                'user_id' => $user->id,
                'fuel_station_id' => $request->fuel_station_id,
                'lease_id' => $lease ? $lease->id : null,
                'amount' => $request->amount,
                'liters' => $liters,
                'fuel_type' => $request->fuel_type,
                'status' => 'issued',
            ]);

            // Log the activity
            activity()
                ->performedOn($voucher)
                ->causedBy($user)
                ->withProperties([
                    'amount' => $request->amount,
                    'fuel_type' => $request->fuel_type,
                    'payment_type' => $request->payment_type,
                ])
                ->log('voucher_requested');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fuel voucher generated successfully',
                'data' => [
                    'voucher' => $voucher,
                    'qr_data' => $voucher->generateQRData(),
                    'lease' => $lease,
                    'remaining_credit' => $user->available_credit,
                    'wallet_balance' => $user->wallet->balance,
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's vouchers
     */
    public function myVouchers(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        $limit = $request->query('limit', 20);
        $page = $request->query('page', 1);

        $query = $user->vouchers()->with('fuelStation');

        if ($status && in_array($status, ['issued', 'redeemed', 'expired', 'cancelled'])) {
            $query->where('status', $status);
        }

        // Exclude expired vouchers from active list
        if ($status === 'active') {
            $query->where('status', 'issued')
                  ->where('expires_at', '>', now());
        }

        $vouchers = $query->latest()
                         ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'vouchers' => $vouchers,
                'summary' => [
                    'total' => $user->vouchers()->count(),
                    'active' => $user->vouchers()->where('status', 'issued')
                                        ->where('expires_at', '>', now())->count(),
                    'redeemed' => $user->vouchers()->where('status', 'redeemed')->count(),
                    'expired' => $user->vouchers()->where('status', 'expired')->count(),
                    'total_amount' => $user->vouchers()->sum('amount'),
                ]
            ]
        ]);
    }

    /**
     * Get voucher details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $voucher = $user->vouchers()
                       ->with(['fuelStation', 'lease'])
                       ->find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'voucher' => $voucher,
                'qr_data' => $voucher->generateQRData(),
                'is_redeemable' => $voucher->is_redeemable,
                'is_expired' => $voucher->is_expired,
                'expires_in' => now()->diffInMinutes($voucher->expires_at, false),
            ]
        ]);
    }

    /**
     * Cancel a voucher
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        
        $voucher = $user->vouchers()
                       ->where('status', 'issued')
                       ->find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found or cannot be cancelled'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $voucher->cancel();

            // Log the activity
            activity()
                ->performedOn($voucher)
                ->causedBy($user)
                ->log('voucher_cancelled');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voucher cancelled successfully',
                'data' => [
                    'voucher' => $voucher,
                    'refunded_amount' => $voucher->amount,
                    'remaining_credit' => $user->available_credit,
                    'wallet_balance' => $user->wallet->balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create BNPL lease
     */
    private function createLease($user, $amount)
    {
        // Calculate interest (simple calculation)
        $interestRate = 10; // Default 10%
        $termDays = 30; // 30-day term
        $interestAmount = ($amount * $interestRate * $termDays) / (365 * 100);
        $totalAmount = $amount + $interestAmount;
        $dailyRepayment = round($totalAmount / max($termDays, 1), 2);
        if ($dailyRepayment < self::MIN_REPAYMENT_AMOUNT) {
            throw new \InvalidArgumentException(sprintf(
                'Repayment per day cannot be below R%.2f. Increase voucher amount.',
                self::MIN_REPAYMENT_AMOUNT
            ));
        }

        $lease = Lease::create([
            'user_id' => $user->id,
            'principal_amount' => $amount,
            'interest_rate' => $interestRate,
            'interest_amount' => $interestAmount,
            'total_amount' => $totalAmount,
            'term_days' => $termDays,
            'daily_repayment' => $dailyRepayment,
            'status' => 'active',
            'issued_at' => now(),
            'due_date' => now()->addDays($termDays),
        ]);

        // Create repayment schedule
        for ($i = 1; $i <= $termDays; $i++) {
            $lease->repayments()->create([
                'user_id' => $user->id,
                'amount' => $dailyRepayment,
                'due_date' => now()->addDays($i),
                'status' => 'pending',
            ]);
        }

        return $lease;
    }

    /**
     * Get fuel price per liter
     */
    private function getFuelPrice($fuelType)
    {
        // In production, fetch from API or database
        $prices = [
            'petrol' => 180, // KES per liter
            'diesel' => 165,
            'super' => 190,
        ];

        return $prices[$fuelType] ?? 180;
    }
}
