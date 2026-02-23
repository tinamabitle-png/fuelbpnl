<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\User;
use App\Models\Repayment;
use App\Models\FuelVoucher;
use App\Models\FuelStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeaseController extends Controller
{
    private const MIN_REPAYMENT_AMOUNT = 30.00;

    /**
     * Display a listing of leases.
     */
    public function index(Request $request)
    {
        $query = Lease::with(['user', 'vouchers', 'repayments'])
            ->latest();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Amount range filter
        if ($request->filled('amount_range')) {
            switch ($request->amount_range) {
                case '0-1000':
                    $query->where('total_amount', '<=', 1000);
                    break;
                case '1000-5000':
                    $query->whereBetween('total_amount', [1000, 5000]);
                    break;
                case '5000+':
                    $query->where('total_amount', '>', 5000);
                    break;
            }
        }

        // Overdue filter
        if ($request->boolean('overdue')) {
            $query->where('status', 'active')
                  ->where('due_date', '<', now());
        }

        $leases = $query->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => Lease::count(),
            'active' => Lease::where('status', 'active')->count(),
            'completed' => Lease::where('status', 'completed')->count(),
            'defaulted' => Lease::where('status', 'defaulted')->count(),
            'overdue' => Lease::where('status', 'active')
                ->where('due_date', '<', now())
                ->count(),
            'total_portfolio' => Lease::sum('total_amount'),
            'total_paid' => Lease::with('repayments')
                ->get()
                ->sum(function($lease) {
                    return $lease->repayments->where('status', 'paid')->sum('amount');
                }),
        ];

        $recentLeases = Lease::with('user')->latest()->take(5)->get();

        return view('admin.leases.index', compact('leases', 'stats', 'recentLeases'));
    }

    /**
     * Show the form for creating a new lease.
     */
    public function create()
    {
        $users = User::where('status', 'active')->get();
        $stations = FuelStation::active()->get();
        
        return view('admin.leases.create', compact('users', 'stations'));
    }

    /**
     * Store a newly created lease in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'principal_amount' => 'required|numeric|min:100|max:50000',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'term_days' => 'required|integer|min:1|max:365',
            'issued_at' => 'required|date',
            'fuel_station_id' => 'nullable|exists:fuel_stations,id',
            'fuel_type' => 'nullable|string|in:petrol,diesel,premium',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $user = User::findOrFail($request->user_id);
            
            // Calculate lease details
            $principal = $request->principal_amount;
            $interestRate = $request->interest_rate;
            $interestAmount = $principal * ($interestRate / 100);
            $totalAmount = $principal + $interestAmount;
            $dailyRepayment = round($totalAmount / max((int) $request->term_days, 1), 2);
            if ($dailyRepayment < self::MIN_REPAYMENT_AMOUNT) {
                return redirect()->back()
                    ->withErrors([
                        'term_days' => sprintf(
                            'Repayment per day cannot be below R%.2f. Increase principal or reduce term days.',
                            self::MIN_REPAYMENT_AMOUNT
                        ),
                    ])
                    ->withInput();
            }

            // Check user credit limit
            if ($user->available_credit < $totalAmount) {
                throw new \Exception('User does not have sufficient credit limit');
            }

            // Create lease
            $lease = Lease::create([
                'user_id' => $user->id,
                'principal_amount' => $principal,
                'interest_rate' => $interestRate,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalAmount,
                'term_days' => $request->term_days,
                'daily_repayment' => $dailyRepayment,
                'status' => 'active',
                'issued_at' => $request->issued_at,
                'due_date' => now()->parse($request->issued_at)->addDays($request->term_days),
            ]);

            // Create repayment schedule
            $this->createRepaymentSchedule($lease);

            // Use credit limit
            $user->creditLimit->useCredit($totalAmount);

            // Create fuel voucher if station selected
            if ($request->filled('fuel_station_id')) {
                $station = FuelStation::whereKey((int) $request->fuel_station_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $openExposure = FuelVoucher::where('fuel_station_id', $station->id)
                    ->whereIn('status', ['issued', 'approved'])
                    ->lockForUpdate()
                    ->sum('amount');
                $availableCapacity = max(0, (float) $station->wallet_balance - (float) $openExposure);
                if ($availableCapacity < (float) $principal) {
                    throw new \Exception(sprintf(
                        'Insufficient station pre-funded balance. Available capacity: R%.2f.',
                        $availableCapacity
                    ));
                }

                $voucher = FuelVoucher::create([
                    'user_id' => $user->id,
                    'fuel_station_id' => $request->fuel_station_id,
                    'lease_id' => $lease->id,
                    'amount' => $principal, // Voucher amount is the principal (fuel amount)
                    'fuel_type' => $request->fuel_type ?? 'petrol',
                    'status' => 'issued',
                    'issued_at' => now(),
                    'expires_at' => now()->addDays(7),
                ]);

                // Generate voucher code
                $voucher->code = 'VCH-' . strtoupper(uniqid());
                $voucher->qr_code = 'VOUCHER-' . time() . '-' . strtoupper(uniqid());
                $voucher->save();
            }

            DB::commit();

            return redirect()->route('admin.leases.show', $lease)
                ->with('success', 'Lease created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to create lease: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified lease.
     */
    public function show(Lease $lease)
    {
        $lease->load(['user', 'vouchers', 'vouchers.fuelStation', 'repayments']);
        
        return view('admin.leases.show', compact('lease'));
    }

    /**
     * Show the form for editing the specified lease.
     */
    public function edit(Lease $lease)
    {
        $lease->load(['user', 'vouchers']);
        $users = User::where('status', 'active')->get();
        $stations = FuelStation::active()->get();
        
        return view('admin.leases.edit', compact('lease', 'users', 'stations'));
    }

    /**
     * Update the specified lease in storage.
     */
    public function update(Request $request, Lease $lease)
    {
        if ($lease->status != 'active') {
            return redirect()->back()
                ->with('error', 'Only active leases can be updated');
        }

        $validator = Validator::make($request->all(), [
            'principal_amount' => 'required|numeric|min:100|max:50000',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'term_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,completed,defaulted,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // Calculate new amounts
            $principal = $request->principal_amount;
            $interestRate = $request->interest_rate;
            $interestAmount = $principal * ($interestRate / 100);
            $totalAmount = $principal + $interestAmount;
            $dailyRepayment = round($totalAmount / max((int) $request->term_days, 1), 2);
            if ($dailyRepayment < self::MIN_REPAYMENT_AMOUNT) {
                return redirect()->back()
                    ->withErrors([
                        'term_days' => sprintf(
                            'Repayment per day cannot be below R%.2f. Increase principal or reduce term days.',
                            self::MIN_REPAYMENT_AMOUNT
                        ),
                    ])
                    ->withInput();
            }

            // Check if user credit limit needs adjustment
            $creditDifference = $totalAmount - $lease->total_amount;
            if ($creditDifference > 0) {
                if ($lease->user->available_credit < $creditDifference) {
                    throw new \Exception('User does not have sufficient credit limit for the increase');
                }
                $lease->user->creditLimit->useCredit($creditDifference);
            } elseif ($creditDifference < 0) {
                $lease->user->creditLimit->releaseCredit(abs($creditDifference));
            }

            // Update lease
            $lease->update([
                'principal_amount' => $principal,
                'interest_rate' => $interestRate,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalAmount,
                'term_days' => $request->term_days,
                'daily_repayment' => $dailyRepayment,
                'status' => $request->status,
            ]);

            // Update repayment schedule if needed
            if ($lease->wasChanged('term_days') || $lease->wasChanged('daily_repayment')) {
                $this->updateRepaymentSchedule($lease);
            }

            DB::commit();

            return redirect()->route('admin.leases.show', $lease)
                ->with('success', 'Lease updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to update lease: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified lease from storage.
     */
    public function destroy(Lease $lease)
    {
        DB::beginTransaction();

        try {
            // Release credit limit
            $remainingBalance = $lease->remaining_balance;
            if ($remainingBalance > 0) {
                $lease->user->creditLimit->releaseCredit($remainingBalance);
            }

            // Cancel associated vouchers
            foreach ($lease->vouchers as $voucher) {
                if ($voucher->status == 'issued') {
                    $voucher->update(['status' => 'cancelled']);
                }
            }

            // Delete repayments
            $lease->repayments()->delete();

            // Delete lease
            $lease->delete();

            DB::commit();

            return redirect()->route('admin.leases.index')
                ->with('success', 'Lease deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to delete lease: ' . $e->getMessage());
        }
    }

    /**
     * Record a payment for the lease.
     */
    public function recordPayment(Request $request, Lease $lease)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:' . $lease->remaining_balance,
            'payment_method' => 'required|string|in:cash,bank_transfer,mobile_money,card,wallet',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $repayment = $lease->markAsPaid(
                $request->amount,
                $request->payment_method,
                $request->transaction_reference
            );

            // Add notes if provided
            if ($request->filled('notes')) {
                $repayment->update(['notes' => $request->notes]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully!',
                'data' => [
                    'remaining_balance' => $lease->fresh()->remaining_balance,
                    'progress_percentage' => $lease->fresh()->progress_percentage,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extend the lease term.
     */
    public function extend(Request $request, Lease $lease)
    {
        if ($lease->status != 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active leases can be extended'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'extra_days' => 'required|integer|min:1|max:90',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $lease->extend($request->extra_days);

            // Log the extension
            activity()
                ->performedOn($lease)
                ->causedBy(auth()->user())
                ->withProperties([
                    'extra_days' => $request->extra_days,
                    'reason' => $request->reason,
                    'new_due_date' => $lease->due_date,
                ])
                ->log('lease_extended');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lease extended successfully!',
                'data' => [
                    'new_due_date' => $lease->due_date->format('Y-m-d'),
                    'term_days' => $lease->term_days,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend lease: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark lease as defaulted.
     */
    public function markAsDefaulted(Lease $lease)
    {
        if ($lease->status != 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active leases can be marked as defaulted'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $lease->markAsDefaulted();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lease marked as defaulted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark lease as defaulted: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show repayment history for a lease.
     */
    public function repaymentHistory(Lease $lease)
    {
        $repayments = $lease->repayments()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.leases.repayment-history', compact('lease', 'repayments'));
    }

    /**
     * Quick payment for a lease.
     */
    public function quickPayment(Request $request, Lease $lease)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:' . $lease->remaining_balance,
            'payment_method' => 'required|string|in:cash,bank_transfer,mobile_money',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $lease->markAsPaid($request->amount, $request->payment_method);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Payment recorded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }

    /**
     * Export leases.
     */
    public function export(Request $request)
    {
        $query = Lease::with(['user', 'vouchers', 'repayments']);

        if ($request->filled('leases')) {
            $leaseIds = explode(',', $request->leases);
            $query->whereIn('id', $leaseIds);
        }

        $leases = $query->get();

        // Generate CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leases-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($leases) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Lease ID',
                'User',
                'Phone',
                'Principal Amount',
                'Interest Rate',
                'Total Amount',
                'Amount Paid',
                'Remaining Balance',
                'Status',
                'Term Days',
                'Daily Repayment',
                'Issued Date',
                'Due Date',
                'Created At',
            ]);

            // Data
            foreach ($leases as $lease) {
                fputcsv($file, [
                    $lease->id,
                    $lease->user->name,
                    $lease->user->phone,
                    $lease->principal_amount,
                    $lease->interest_rate . '%',
                    $lease->total_amount,
                    $lease->total_amount - $lease->remaining_balance,
                    $lease->remaining_balance,
                    ucfirst($lease->status),
                    $lease->term_days,
                    $lease->daily_repayment,
                    $lease->issued_at->format('Y-m-d'),
                    $lease->due_date->format('Y-m-d'),
                    $lease->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate overdue report.
     */
    public function overdueReport()
    {
        $overdueLeases = Lease::where('status', 'active')
            ->where('due_date', '<', now())
            ->with(['user', 'repayments'])
            ->orderBy('due_date')
            ->get();

        $stats = [
            'total_overdue' => $overdueLeases->count(),
            'total_amount' => $overdueLeases->sum('remaining_balance'),
            'avg_days_overdue' => $overdueLeases->avg('days_overdue'),
        ];

        return view('admin.leases.reports.overdue', compact('overdueLeases', 'stats'));
    }

    /**
     * Generate performance report.
     */
    public function performanceReport(Request $request)
    {
        $query = Lease::query();

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $leases = $query->with(['user', 'repayments'])->get();

        // Calculate performance metrics
        $metrics = [
            'total_leases' => $leases->count(),
            'total_principal' => $leases->sum('principal_amount'),
            'total_interest' => $leases->sum('interest_amount'),
            'total_revenue' => $leases->sum('total_amount'),
            'avg_interest_rate' => $leases->avg('interest_rate'),
            'avg_term_days' => $leases->avg('term_days'),
            'collection_rate' => $this->calculateCollectionRate($leases),
            'default_rate' => $this->calculateDefaultRate($leases),
        ];

        // Monthly breakdown
        $monthlyData = $leases->groupBy(function($lease) {
            return $lease->created_at->format('Y-m');
        })->map(function($monthLeases) {
            return [
                'count' => $monthLeases->count(),
                'principal' => $monthLeases->sum('principal_amount'),
                'revenue' => $monthLeases->sum('total_amount'),
            ];
        });

        return view('admin.leases.reports.performance', compact('metrics', 'monthlyData'));
    }

    /**
     * Create repayment schedule for a lease.
     */
    private function createRepaymentSchedule(Lease $lease)
    {
        $repayments = [];
        $currentDate = $lease->issued_at;

        for ($i = 1; $i <= $lease->term_days; $i++) {
            $dueDate = $currentDate->copy()->addDays($i);
            
            $repayments[] = [
                'lease_id' => $lease->id,
                'user_id' => $lease->user_id,
                'amount' => $lease->daily_repayment,
                'due_date' => $dueDate,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Repayment::insert($repayments);
    }

    /**
     * Update repayment schedule for a lease.
     */
    private function updateRepaymentSchedule(Lease $lease)
    {
        // Delete existing pending repayments
        $lease->repayments()->where('status', 'pending')->delete();

        // Create new repayment schedule
        $this->createRepaymentSchedule($lease);
    }

    /**
     * Calculate collection rate.
     */
    private function calculateCollectionRate($leases)
    {
        $totalAmount = $leases->sum('total_amount');
        $totalPaid = $leases->sum(function($lease) {
            return $lease->repayments->where('status', 'paid')->sum('amount');
        });

        return $totalAmount > 0 ? ($totalPaid / $totalAmount) * 100 : 0;
    }
public function investors(Lease $lease)
{
    $lease->load(['leaseInvestments.investor.user', 'leaseInvestments.returns']);
    
    return view('admin.leases.investors', compact('lease'));
}

/**
 * Process investor returns.
 */
public function processInvestorReturns(Lease $lease)
{
    if ($lease->status !== 'active') {
        return back()->with('error', 'Only active leases can process returns.');
    }

    // Calculate daily returns for investors
    $dailyReturn = $lease->daily_repayment * ($lease->investor_ownership_percentage / 100);
    
    // Distribute to investors based on their ownership percentage
    $lease->leaseInvestments()->each(function ($investment) use ($lease, $dailyReturn) {
        $investorShare = $dailyReturn * ($investment->percentage_ownership / 100);
        
        if ($investorShare > 0) {
            $investment->recordReturn($investorShare, 'interest');
        }
    });

    return back()->with('success', 'Investor returns processed successfully.');
}
    /**
     * Calculate default rate.
     */
    private function calculateDefaultRate($leases)
    {
        $totalLeases = $leases->count();
        $defaultedLeases = $leases->where('status', 'defaulted')->count();

        return $totalLeases > 0 ? ($defaultedLeases / $totalLeases) * 100 : 0;
    }

    /**
     * Toggle lease status.
     */
    public function toggleStatus(Lease $lease)
    {
        DB::beginTransaction();

        try {
            $newStatus = $lease->status == 'active' ? 'suspended' : 'active';
            
            $lease->update(['status' => $newStatus]);

            // If suspending, release credit
            if ($newStatus == 'suspended') {
                $lease->user->creditLimit->releaseCredit($lease->remaining_balance);
            }

            DB::commit();

            return redirect()->back()
                ->with('success', "Lease {$newStatus} successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to toggle lease status: ' . $e->getMessage());
        }
    }

    /**
     * Get lease statistics for dashboard.
     */
    public function getStats()
    {
        $stats = [
            'total' => Lease::count(),
            'active' => Lease::where('status', 'active')->count(),
            'completed' => Lease::where('status', 'completed')->count(),
            'defaulted' => Lease::where('status', 'defaulted')->count(),
            'overdue' => Lease::where('status', 'active')
                ->where('due_date', '<', now())
                ->count(),
            'total_portfolio' => Lease::sum('total_amount'),
            'total_paid' => Lease::with('repayments')
                ->get()
                ->sum(function($lease) {
                    return $lease->repayments->where('status', 'paid')->sum('amount');
                }),
            'total_interest' => Lease::sum('interest_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Get recent leases for dashboard.
     */
    public function getRecent()
    {
        $leases = Lease::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(function($lease) {
                return [
                    'id' => $lease->id,
                    'user_name' => $lease->user->name,
                    'total_amount' => $lease->total_amount,
                    'status' => $lease->status,
                    'due_date' => $lease->due_date->format('M d, Y'),
                    'days_overdue' => $lease->days_overdue,
                    'created_at' => $lease->created_at->diffForHumans(),
                ];
            });

        return response()->json($leases);
    }
}
