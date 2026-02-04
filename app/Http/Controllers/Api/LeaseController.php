<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LeaseController extends Controller
{
    /**
     * Get user's leases
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        $limit = $request->query('limit', 20);
        $page = $request->query('page', 1);

        $query = $user->leases()->with(['vouchers', 'repayments']);

        if ($status && in_array($status, ['active', 'completed', 'defaulted'])) {
            $query->where('status', $status);
        }

        $leases = $query->latest()
                       ->paginate($limit, ['*'], 'page', $page);

        // Calculate summary
        $summary = [
            'total_leases' => $user->leases()->count(),
            'active_leases' => $user->leases()->where('status', 'active')->count(),
            'total_borrowed' => $user->leases()->sum('principal_amount'),
            'total_repaid' => $user->leases()->sum('total_amount') - 
                            $user->leases()->sum('remaining_balance'),
            'overdue_leases' => $user->leases()->where('status', 'active')
                                    ->where('due_date', '<', now())->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'leases' => $leases,
                'summary' => $summary,
            ]
        ]);
    }

    /**
     * Get lease details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $lease = $user->leases()
                     ->with(['vouchers.fuelStation', 'repayments'])
                     ->find($id);

        if (!$lease) {
            return response()->json([
                'success' => false,
                'message' => 'Lease not found'
            ], 404);
        }

        // Calculate detailed information
        $detailedInfo = [
            'total_paid' => $lease->total_amount - $lease->remaining_balance,
            'days_remaining' => max(0, now()->diffInDays($lease->due_date, false)),
            'daily_interest' => $lease->interest_amount / $lease->term_days,
            'total_savings' => $this->calculateSavings($lease),
            'next_payment_date' => $lease->repayments()
                                        ->where('status', 'pending')
                                        ->orderBy('due_date')
                                        ->first()->due_date ?? null,
            'next_payment_amount' => $lease->repayments()
                                          ->where('status', 'pending')
                                          ->orderBy('due_date')
                                          ->first()->amount ?? null,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'lease' => $lease,
                'details' => $detailedInfo,
                'repayment_schedule' => $lease->repayments()
                                             ->orderBy('due_date')
                                             ->get(),
            ]
        ]);
    }

    /**
     * Get lease repayment schedule
     */
    public function repaymentSchedule(Request $request, $id)
    {
        $user = $request->user();
        
        $lease = $user->leases()->find($id);

        if (!$lease) {
            return response()->json([
                'success' => false,
                'message' => 'Lease not found'
            ], 404);
        }

        $schedule = $lease->repayments()
                         ->orderBy('due_date')
                         ->get()
                         ->groupBy(function ($repayment) {
                             return $repayment->due_date->format('Y-m');
                         });

        return response()->json([
            'success' => true,
            'data' => [
                'lease' => $lease->only(['id', 'total_amount', 'remaining_balance']),
                'schedule' => $schedule,
                'total_due' => $lease->repayments()
                                    ->where('status', 'pending')
                                    ->sum('amount'),
                'overdue_amount' => $lease->repayments()
                                         ->where('status', 'overdue')
                                         ->orWhere(function ($query) {
                                             $query->where('status', 'pending')
                                                   ->where('due_date', '<', now());
                                         })
                                         ->sum('amount'),
            ]
        ]);
    }

    /**
     * Request lease extension
     */
    public function requestExtension(Request $request, $id)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'extra_days' => 'required|integer|min:7|max:30',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $lease = $user->leases()
                     ->where('status', 'active')
                     ->where('due_date', '>=', now())
                     ->find($id);

        if (!$lease) {
            return response()->json([
                'success' => false,
                'message' => 'Lease not found or cannot be extended'
            ], 404);
        }

        // Calculate extension fee (if any)
        $extensionFee = $this->calculateExtensionFee($lease, $request->extra_days);

        // In production, this would go through approval process
        // For now, simulate approval
        
        activity()
            ->performedOn($lease)
            ->causedBy($user)
            ->withProperties([
                'extra_days' => $request->extra_days,
                'reason' => $request->reason,
                'extension_fee' => $extensionFee,
            ])
            ->log('lease_extension_requested');

        return response()->json([
            'success' => true,
            'message' => 'Extension request submitted for approval',
            'data' => [
                'lease_id' => $lease->id,
                'extra_days' => $request->extra_days,
                'extension_fee' => $extensionFee,
                'new_due_date' => $lease->due_date->copy()->addDays($request->extra_days),
                'estimated_decision_time' => '24-48 hours',
            ]
        ]);
    }

    /**
     * Get lease statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $leases = $user->leases()->get();

        $statistics = [
            'total_leases' => $leases->count(),
            'active_leases' => $leases->where('status', 'active')->count(),
            'completed_leases' => $leases->where('status', 'completed')->count(),
            'total_borrowed' => $leases->sum('principal_amount'),
            'total_repaid' => $leases->sum('total_amount') - 
                            $leases->sum('remaining_balance'),
            'total_interest_paid' => $leases->where('status', 'completed')
                                          ->sum('interest_amount'),
            'average_loan_amount' => $leases->avg('principal_amount'),
            'on_time_repayment_rate' => $this->calculateOnTimeRate($user),
            'current_outstanding' => $leases->where('status', 'active')
                                          ->sum('remaining_balance'),
        ];

        // Calculate month-by-month breakdown
        $monthlyData = $leases->where('status', 'completed')
                             ->groupBy(function ($lease) {
                                 return $lease->completed_at->format('Y-m');
                             })
                             ->map(function ($monthLeases) {
                                 return [
                                     'count' => $monthLeases->count(),
                                     'amount' => $monthLeases->sum('principal_amount'),
                                     'interest' => $monthLeases->sum('interest_amount'),
                                 ];
                             });

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'monthly_breakdown' => $monthlyData,
                'credit_utilization' => [
                    'available_credit' => $user->available_credit,
                    'used_credit' => $user->creditLimit->used,
                    'total_limit' => $user->creditLimit->limit,
                    'utilization_percentage' => ($user->creditLimit->used / $user->creditLimit->limit) * 100,
                ],
            ]
        ]);
    }

    /**
     * Calculate savings from BNPL vs immediate payment
     */
    private function calculateSavings($lease)
    {
        // Assuming immediate payment would have been from savings
        // BNPL allows user to keep money invested elsewhere
        // This is a simplified calculation
        $averageReturnRate = 0.08; // 8% annual return
        $savings = $lease->principal_amount * ($averageReturnRate * ($lease->term_days / 365));
        
        return max(0, $savings - $lease->interest_amount);
    }

    /**
     * Calculate extension fee
     */
    private function calculateExtensionFee($lease, $extraDays)
    {
        // Extension fee = extra interest + processing fee
        $extraInterest = ($lease->remaining_balance * $lease->interest_rate * $extraDays) / (365 * 100);
        $processingFee = 200; // KES 200 processing fee
        
        return $extraInterest + $processingFee;
    }

    /**
     * Calculate on-time repayment rate
     */
    private function calculateOnTimeRate($user)
    {
        $totalRepayments = $user->repayments()->count();
        $onTimeRepayments = $user->repayments()
                                ->where('status', 'paid')
                                ->whereColumn('paid_at', '<=', 'due_date')
                                ->count();
        
        if ($totalRepayments === 0) return 100;
        
        return ($onTimeRepayments / $totalRepayments) * 100;
    }
}