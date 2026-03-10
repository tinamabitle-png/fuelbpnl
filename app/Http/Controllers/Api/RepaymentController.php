<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lease;
use App\Models\Repayment;
use App\Services\DebiCheckService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RepaymentController extends Controller
{
    public function initializePaystack(Request $request, Repayment $repayment, PaystackService $paystack)
    {
        $user = $request->user();

        if ((int) $repayment->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized repayment access.',
            ], 403);
        }

        if (!in_array((string) $repayment->status, ['pending', 'overdue'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Repayment is not payable.',
            ], 422);
        }

        try {
            $callback = (string) config('app.url');
            if ($callback === '') {
                $callback = 'http://localhost';
            }
            $callback = rtrim($callback, '/') . '/driver/repayments/paystack/callback';

            $checkout = $paystack->initializeRepaymentCheckout(
                $user,
                $repayment,
                'card',
                $callback
            );

            return response()->json([
                'success' => true,
                'message' => 'Paystack checkout initialized.',
                'data' => [
                    'reference' => (string) ($checkout['reference'] ?? ''),
                    'authorization_url' => (string) ($checkout['authorization_url'] ?? ''),
                    'access_code' => (string) ($checkout['access_code'] ?? ''),
                    'repayment_id' => (int) $repayment->id,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize Paystack: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function verifyPaystack(Request $request, PaystackService $paystack)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|max:255',
            'repayment_id' => 'required|integer|exists:repayments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $repayment = Repayment::query()->findOrFail((int) $request->input('repayment_id'));
        if ((int) $repayment->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized repayment access.',
            ], 403);
        }

        if ((string) $repayment->status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Repayment already paid.',
                'data' => [
                    'repayment_id' => (int) $repayment->id,
                    'reference' => (string) ($repayment->transaction_reference ?? ''),
                ],
            ]);
        }

        try {
            $verified = $paystack->verifyTransaction((string) $request->input('reference'));
            $metadata = (array) ($verified['metadata'] ?? []);
            $metadataRepaymentId = (int) ($metadata['repayment_id'] ?? 0);
            if ($metadataRepaymentId > 0 && $metadataRepaymentId !== (int) $repayment->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment reference does not match selected repayment.',
                ], 422);
            }

            DB::transaction(function () use ($repayment, $verified) {
                $locked = Repayment::whereKey($repayment->id)->lockForUpdate()->firstOrFail();
                if ((string) $locked->status !== 'paid') {
                    $locked->markAsPaid(
                        'paystack_card',
                        (string) ($verified['reference'] ?? $locked->transaction_reference ?? '')
                    );
                }
            });

            $paystack->storeAuthorizationFromTransaction($user, $verified);

            return response()->json([
                'success' => true,
                'message' => 'Paystack payment verified.',
                'data' => [
                    'repayment_id' => (int) $repayment->id,
                    'reference' => (string) ($verified['reference'] ?? ''),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paystack verification failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get upcoming repayments
     */
    public function upcoming(Request $request)
    {
        $user = $request->user();
        $limit = $request->query('limit', 10);
        $days = $request->query('days', 30);

        $repayments = $user->repayments()
            ->with('lease')
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days))
            ->orderBy('due_date')
            ->paginate($limit);

        // Calculate summary
        $totalUpcoming = $user->repayments()
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->sum('amount');

        $dueThisWeek = $user->repayments()
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'repayments' => $repayments,
                'summary' => [
                    'total_upcoming' => $totalUpcoming,
                    'due_this_week' => $dueThisWeek,
                    'next_payment_date' => $repayments->isNotEmpty() ? 
                        $repayments->first()->due_date->format('Y-m-d') : null,
                    'next_payment_amount' => $repayments->isNotEmpty() ? 
                        $repayments->first()->amount : null,
                    'average_daily_payment' => $totalUpcoming > 0 ? 
                        $totalUpcoming / $days : 0,
                ],
            ]
        ]);
    }

    /**
     * Get overdue repayments
     */
    public function overdue(Request $request)
    {
        $user = $request->user();

        $repayments = $user->repayments()
            ->with('lease')
            ->where(function ($query) {
                $query->where('status', 'overdue')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('due_date', '<', now());
                      });
            })
            ->orderBy('due_date')
            ->get();

        $totalOverdue = $repayments->sum('amount');
        $oldestOverdue = $repayments->isNotEmpty() ? 
            $repayments->first()->due_date->diffForHumans() : null;

        return response()->json([
            'success' => true,
            'data' => [
                'repayments' => $repayments,
                'summary' => [
                    'total_overdue' => $totalOverdue,
                    'count' => $repayments->count(),
                    'oldest_overdue' => $oldestOverdue,
                    'potential_fees' => $this->calculateLateFees($repayments),
                    'impact_on_credit' => $this->calculateCreditImpact($repayments->count()),
                ],
            ]
        ]);
    }

    /**
     * Get repayment history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $limit = $request->query('limit', 20);
        $page = $request->query('page', 1);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $leaseId = $request->query('lease_id');

        $query = $user->repayments()
            ->with('lease')
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc');

        if ($leaseId) {
            $query->where('lease_id', $leaseId);
        }

        if ($startDate) {
            $query->whereDate('paid_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('paid_at', '<=', $endDate);
        }

        $repayments = $query->paginate($limit, ['*'], 'page', $page);

        // Calculate statistics
        $totalPaid = $repayments->sum('amount');
        $averagePayment = $repayments->isNotEmpty() ? 
            $totalPaid / $repayments->count() : 0;

        // Monthly breakdown
        $monthlyBreakdown = $user->repayments()
            ->where('status', 'paid')
            ->selectRaw('YEAR(paid_at) as year, MONTH(paid_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(6)
            ->get()
            ->map(function ($item) {
                return [
                    'period' => Carbon::create($item->year, $item->month, 1)->format('M Y'),
                    'total' => $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'repayments' => $repayments,
                'statistics' => [
                    'total_paid' => $totalPaid,
                    'average_payment' => round($averagePayment, 2),
                    'total_payments' => $repayments->total(),
                    'on_time_percentage' => $this->calculateOnTimePercentage($user),
                ],
                'monthly_breakdown' => $monthlyBreakdown,
            ]
        ]);
    }

    /**
     * Make a repayment
     */
    public function makePayment(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'repayment_ids' => 'required_without:amount|array',
            'repayment_ids.*' => 'exists:repayments,id',
            'amount' => 'required_without:repayment_ids|numeric|min:100',
            'payment_method' => 'required|in:wallet,mpesa,bank_transfer,paystack',
            'phone' => 'required_if:payment_method,mpesa|string',
            'pay_all_overdue' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $totalPaid = 0;
            $processedRepayments = [];

            if ($request->filled('repayment_ids')) {
                // Pay specific repayments
                $repayments = $user->repayments()
                    ->whereIn('id', $request->repayment_ids)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->get();

                foreach ($repayments as $repayment) {
                    $repayment->markAsPaid(
                        $request->payment_method,
                        'PAY-' . time() . '-' . $repayment->id
                    );
                    
                    $totalPaid += $repayment->amount;
                    $processedRepayments[] = $repayment;
                }

            } elseif ($request->pay_all_overdue) {
                // Pay all overdue repayments
                $overdueRepayments = $user->repayments()
                    ->where(function ($query) {
                        $query->where('status', 'overdue')
                              ->orWhere(function ($q) {
                                  $q->where('status', 'pending')
                                    ->where('due_date', '<', now());
                              });
                    })
                    ->get();

                foreach ($overdueRepayments as $repayment) {
                    $repayment->markAsPaid(
                        $request->payment_method,
                        'PAY-' . time() . '-' . $repayment->id
                    );
                    
                    $totalPaid += $repayment->amount;
                    $processedRepayments[] = $repayment;
                }

            } else {
                // Make a general payment
                $amount = $request->amount;
                $totalPaid = $amount;
                
                // Distribute to oldest overdue first, then upcoming
                $distribution = $this->distributeRepayment($user, $amount);
                $processedRepayments = $distribution['repayments'];
            }

            // Process the payment
            $paymentResult = $this->processPayment(
                $user,
                $totalPaid,
                $request->payment_method,
                $request->all()
            );

            // Update user's wallet if paid from wallet
            if ($request->payment_method === 'wallet') {
                $user->wallet->decrement('balance', $totalPaid);
                $user->wallet->decrement('outstanding_balance', $totalPaid);
                $user->wallet->increment('total_repayments', $totalPaid);
                $user->creditLimit->releaseCredit($totalPaid);
            }

            // Log the activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'total_paid' => $totalPaid,
                    'payment_method' => $request->payment_method,
                    'repayment_count' => count($processedRepayments),
                    'reference' => $paymentResult['reference'],
                ])
                ->log('repayments_made');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => [
                    'payment_reference' => $paymentResult['reference'],
                    'total_paid' => $totalPaid,
                    'processed_repayments' => $processedRepayments,
                    'remaining_overdue' => $user->repayments()
                        ->where('status', 'overdue')
                        ->orWhere(function ($query) {
                            $query->where('status', 'pending')
                                  ->where('due_date', '<', now());
                        })
                        ->sum('amount'),
                    'next_due_date' => $user->repayments()
                        ->where('status', 'pending')
                        ->where('due_date', '>=', now())
                        ->orderBy('due_date')
                        ->first()->due_date ?? null,
                    'receipt' => [
                        'reference' => $paymentResult['reference'],
                        'date' => now()->format('Y-m-d H:i:s'),
                        'amount' => $totalPaid,
                        'method' => $request->payment_method,
                        'items' => count($processedRepayments),
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setup auto-payment
     */
    public function setupAutoPayment(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'payment_method' => 'required_if:enabled,true|in:paystack,debicheck,hybrid',
            'authorization_code' => 'nullable|string|max:255',
            'paystack_email' => 'nullable|email|max:255',
            'threshold_days' => 'required_if:enabled,true|integer|min:1|max:7',
            'max_amount' => 'required_if:enabled,true|numeric|min:100|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ((bool) $request->enabled) {
            $gateway = strtolower((string) ($request->payment_method ?? 'paystack'));
            $details = (array) ($user->autopay_details ?? []);
            $hasPaystackToken = trim((string) ($request->authorization_code ?? $user->autopay_token ?? '')) !== '';
            $debiStatus = strtolower((string) (($details['debicheck']['status'] ?? $details['debicheck_status'] ?? '')));
            $hasDebicheckMandate = trim((string) ($details['debicheck']['mandate_id'] ?? $details['debicheck_mandate_id'] ?? '')) !== ''
                && in_array($debiStatus, ['active', 'approved', 'accepted', 'verified'], true);

            if (in_array($gateway, ['paystack', 'hybrid'], true) && !$hasPaystackToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paystack authorization is required before enabling Paystack/Hybrid AutoPay.',
                ], 422);
            }

            if (in_array($gateway, ['debicheck', 'hybrid'], true) && !$hasDebicheckMandate) {
                return response()->json([
                    'success' => false,
                    'message' => 'DebiCheck mandate must be active before enabling DebiCheck/Hybrid AutoPay.',
                ], 422);
            }
        }

        $settings = [
            'enabled' => $request->enabled,
            'payment_method' => $request->payment_method ?? 'paystack',
            'threshold_days' => $request->threshold_days,
            'max_amount' => $request->max_amount,
            'authorization_code' => $request->authorization_code,
            'paystack_email' => $request->paystack_email,
            'updated_at' => now(),
        ];

        $nextAutoPaymentAt = null;
        if ((bool) $request->enabled) {
            $nextAutoPaymentAt = $this->calculateNextAutoPayment($user, $settings);
        }

        // Persist auto-pay state on user so voucher gating can rely on it.
        $user->update([
            'autopay_enabled' => (bool) $request->enabled,
            'autopay_gateway' => (string) ($request->payment_method ?? $user->autopay_gateway ?? 'paystack'),
            'autopay_token' => (bool) $request->enabled && in_array((string) $request->payment_method, ['paystack', 'hybrid'], true)
                ? (string) ($request->authorization_code ?? $user->autopay_token ?? '')
                : $user->autopay_token,
            'autopay_email' => (bool) $request->enabled && in_array((string) $request->payment_method, ['paystack', 'hybrid'], true)
                ? (string) ($request->paystack_email ?? $user->autopay_email ?? '')
                : $user->autopay_email,
            'autopay_status' => (bool) $request->enabled ? 'active' : 'disabled',
            'autopay_details' => $settings,
            'autopay_last_attempt_at' => now(),
            'autopay_next_attempt_at' => $nextAutoPaymentAt,
        ]);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties($settings)
            ->log('auto_payment_updated');

        return response()->json([
            'success' => true,
            'message' => $request->enabled ? 
                'Auto-payment enabled' : 'Auto-payment disabled',
            'data' => [
                'settings' => $settings,
                'next_auto_payment' => $nextAutoPaymentAt,
                'estimated_savings' => $request->enabled ? 
                    $this->calculateAutoPaymentSavings($user) : 0,
            ]
        ]);
    }

    public function initializeAutopayPaystack(Request $request, PaystackService $paystack)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        try {
            $callback = (string) config('app.url');
            if ($callback === '') {
                $callback = 'http://localhost';
            }
            $callback = rtrim($callback, '/') . '/driver/repayments/paystack/callback';
            $probeAmount = (float) config('services.paystack.autopay_probe_amount', 5.00);
            $checkout = $paystack->initializeAutopayAuthorization(
                $user,
                $probeAmount,
                $callback,
                $request->input('email')
            );

            return response()->json([
                'success' => true,
                'message' => 'Paystack AutoPay authorization started.',
                'data' => [
                    'reference' => (string) ($checkout['reference'] ?? ''),
                    'authorization_url' => (string) ($checkout['authorization_url'] ?? ''),
                    'access_code' => (string) ($checkout['access_code'] ?? ''),
                    'probe_amount' => $probeAmount,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize AutoPay authorization: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function verifyAutopayPaystack(Request $request, PaystackService $paystack)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        try {
            $verified = $paystack->verifyTransaction((string) $request->input('reference'));
            $meta = (array) ($verified['metadata'] ?? []);
            $scope = strtolower((string) ($meta['scope'] ?? ''));
            $metaUserId = (int) ($meta['user_id'] ?? 0);

            if ($scope !== 'autopay_setup') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid AutoPay authorization transaction scope.',
                ], 422);
            }

            if ($metaUserId > 0 && $metaUserId !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization transaction does not belong to this user.',
                ], 403);
            }

            $paystack->storeAuthorizationFromTransaction($user, $verified);

            return response()->json([
                'success' => true,
                'message' => 'AutoPay authorization verified and tokenized.',
                'data' => [
                    'reference' => (string) ($verified['reference'] ?? ''),
                    'autopay_ready' => $user->fresh()->isAutopayReady(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AutoPay authorization verification failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function initializeAutopayDebicheck(Request $request, DebiCheckService $debiCheck)
    {
        $validator = Validator::make($request->all(), [
            // Keep payload open to allow provider-specific mandate structure.
            'mandate' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        try {
            $result = $debiCheck->createMandate($user, (array) $request->input('mandate', []));
            $details = (array) ($user->autopay_details ?? []);
            $details['debicheck'] = [
                'mandate_id' => (string) ($result['mandate_id'] ?? ''),
                'status' => (string) ($result['status'] ?? 'submitted'),
                'reference' => (string) ($result['reference'] ?? ''),
                'raw' => (array) ($result['raw'] ?? []),
                'updated_at' => now()->toDateTimeString(),
            ];

            $user->forceFill([
                'autopay_details' => $details,
                // Keep Paystack as the default rail unless user explicitly switches.
                'autopay_gateway' => (string) ($user->autopay_gateway ?: 'paystack'),
                'autopay_status' => 'pending_mandate',
                'autopay_last_attempt_at' => now(),
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'DebiCheck mandate initiation submitted.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize DebiCheck mandate: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function verifyAutopayDebicheck(Request $request, DebiCheckService $debiCheck)
    {
        $validator = Validator::make($request->all(), [
            'mandate_id' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $details = (array) ($user->autopay_details ?? []);
        $mandateId = trim((string) ($request->input('mandate_id') ?? $details['debicheck']['mandate_id'] ?? ''));
        if ($mandateId === '') {
            return response()->json([
                'success' => false,
                'message' => 'DebiCheck mandate_id is required.',
            ], 422);
        }

        try {
            $result = $debiCheck->getMandate($mandateId);
            $status = strtolower((string) ($result['status'] ?? 'unknown'));
            $isReady = in_array($status, ['active', 'approved', 'accepted', 'verified'], true);

            $details['debicheck'] = [
                'mandate_id' => $mandateId,
                'status' => $status,
                'raw' => (array) ($result['raw'] ?? []),
                'updated_at' => now()->toDateTimeString(),
            ];

            $user->forceFill([
                'autopay_enabled' => $isReady ? true : (bool) $user->autopay_enabled,
                // Keep Paystack as default; gateway changes must be explicit.
                'autopay_gateway' => (string) ($user->autopay_gateway ?: 'paystack'),
                'autopay_details' => $details,
                'autopay_status' => $isReady ? 'active' : 'pending_mandate',
                'autopay_last_attempt_at' => now(),
                'autopay_next_attempt_at' => $isReady ? now()->addDay() : $user->autopay_next_attempt_at,
            ])->save();

            return response()->json([
                'success' => true,
                'message' => $isReady
                    ? 'DebiCheck mandate verified and active.'
                    : 'DebiCheck mandate still pending.',
                'data' => [
                    'mandate_id' => $mandateId,
                    'status' => $status,
                    'autopay_ready' => $user->fresh()->isAutopayReady(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'DebiCheck verification failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get payment reminders
     */
    public function reminders(Request $request)
    {
        $user = $request->user();

        $upcomingRepayments = $user->repayments()
            ->with('lease')
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date')
            ->get();

        $reminders = [];
        $today = now()->format('Y-m-d');

        foreach ($upcomingRepayments as $repayment) {
            $daysUntilDue = now()->diffInDays($repayment->due_date, false);
            
            if ($daysUntilDue <= 3) {
                $reminders[] = [
                    'type' => 'upcoming',
                    'title' => 'Payment Due Soon',
                    'message' => "KES {$repayment->amount} due in {$daysUntilDue} day(s) for lease #{$repayment->lease_id}",
                    'due_date' => $repayment->due_date->format('Y-m-d'),
                    'amount' => $repayment->amount,
                    'lease_id' => $repayment->lease_id,
                    'priority' => $daysUntilDue <= 1 ? 'high' : 'medium',
                ];
            }
        }

        // Check for overdue
        $overdueCount = $user->repayments()
            ->where(function ($query) {
                $query->where('status', 'overdue')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('due_date', '<', now());
                      });
            })
            ->count();

        if ($overdueCount > 0) {
            $reminders[] = [
                'type' => 'overdue',
                'title' => 'Overdue Payments',
                'message' => "You have {$overdueCount} overdue payment(s). Avoid late fees!",
                'priority' => 'urgent',
                'action' => 'pay_now',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reminders' => $reminders,
                'unread_count' => count($reminders),
                'last_checked' => now()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Get repayment statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $allRepayments = $user->repayments()->get();

        $statistics = [
            'total_made' => $allRepayments->where('status', 'paid')->count(),
            'total_amount' => $allRepayments->where('status', 'paid')->sum('amount'),
            'on_time_rate' => $this->calculateOnTimePercentage($user),
            'average_payment' => $allRepayments->where('status', 'paid')->avg('amount') ?? 0,
            'current_overdue' => $allRepayments->where('status', 'overdue')->count(),
            'total_late_fees' => $this->calculateTotalLateFees($user),
            'prepayment_rate' => $this->calculatePrepaymentRate($user),
        ];

        // Monthly trends
        $monthlyTrends = $user->repayments()
            ->where('status', 'paid')
            ->selectRaw('YEAR(paid_at) as year, MONTH(paid_at) as month, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::create($item->year, $item->month, 1)->format('M Y'),
                    'count' => $item->count,
                    'total' => $item->total,
                    'average' => $item->count > 0 ? $item->total / $item->count : 0,
                ];
            });

        // Payment method distribution
        $paymentMethods = $user->repayments()
            ->where('status', 'paid')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'monthly_trends' => $monthlyTrends,
                'payment_methods' => $paymentMethods,
                'credit_impact' => $this->calculateCreditImpactFromRepayments($user),
                'recommendations' => $this->getRepaymentRecommendations($user),
            ]
        ]);
    }

    /**
     * Distribute payment among repayments
     */
    private function distributeRepayment($user, $amount)
    {
        $processedRepayments = [];
        $remainingAmount = $amount;

        // First pay overdue repayments
        $overdueRepayments = $user->repayments()
            ->where(function ($query) {
                $query->where('status', 'overdue')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('due_date', '<', now());
                      });
            })
            ->orderBy('due_date')
            ->get();

        foreach ($overdueRepayments as $repayment) {
            if ($remainingAmount <= 0) break;

            if ($repayment->amount <= $remainingAmount) {
                $repayment->markAsPaid('manual_distributed', 'DIST-' . time());
                $processedRepayments[] = $repayment;
                $remainingAmount -= $repayment->amount;
            }
        }

        // Then pay upcoming repayments (closest due date first)
        if ($remainingAmount > 0) {
            $upcomingRepayments = $user->repayments()
                ->where('status', 'pending')
                ->where('due_date', '>=', now())
                ->orderBy('due_date')
                ->get();

            foreach ($upcomingRepayments as $repayment) {
                if ($remainingAmount <= 0) break;

                if ($repayment->amount <= $remainingAmount) {
                    $repayment->markAsPaid('manual_distributed', 'DIST-' . time());
                    $processedRepayments[] = $repayment;
                    $remainingAmount -= $repayment->amount;
                }
            }
        }

        return [
            'repayments' => $processedRepayments,
            'remaining_amount' => $remainingAmount,
            'total_processed' => $amount - $remainingAmount,
        ];
    }

    /**
     * Process payment
     */
    private function processPayment($user, $amount, $method, $details)
    {
        if ($method === 'wallet') {
            if (!$user->wallet->canAfford($amount)) {
                throw new \Exception('Insufficient wallet balance');
            }
            
            return [
                'success' => true,
                'reference' => 'WALLET-' . time() . rand(1000, 9999),
            ];
        }

        // In production, integrate with payment gateway
        $prefixes = [
            'mpesa' => 'MPE',
            'bank_transfer' => 'BNK',
            'paystack' => 'PST',
        ];

        return [
            'success' => true,
            'reference' => ($prefixes[$method] ?? 'PAY') . time() . rand(1000, 9999),
        ];
    }

    /**
     * Calculate late fees
     */
    private function calculateLateFees($repayments)
    {
        $totalFees = 0;
        
        foreach ($repayments as $repayment) {
            if ($repayment->due_date < now()) {
                $daysLate = now()->diffInDays($repayment->due_date);
                $lateFee = min(1000, $repayment->amount * 0.01 * $daysLate); // 1% per day, max KES 1000
                $totalFees += $lateFee;
            }
        }
        
        return $totalFees;
    }

    /**
     * Calculate credit impact
     */
    private function calculateCreditImpact($overdueCount)
    {
        if ($overdueCount === 0) return 'None';
        if ($overdueCount <= 2) return 'Minor (5-10 point reduction)';
        if ($overdueCount <= 5) return 'Moderate (10-30 point reduction)';
        return 'Severe (30+ point reduction)';
    }

    /**
     * Calculate on-time percentage
     */
    private function calculateOnTimePercentage($user)
    {
        $totalPaid = $user->repayments()->where('status', 'paid')->count();
        $onTimePaid = $user->repayments()
            ->where('status', 'paid')
            ->whereColumn('paid_at', '<=', 'due_date')
            ->count();
        
        if ($totalPaid === 0) return 100;
        
        return ($onTimePaid / $totalPaid) * 100;
    }

    /**
     * Calculate total late fees
     */
    private function calculateTotalLateFees($user)
    {
        // This would be calculated from actual late fee transactions
        // For now, estimate based on overdue repayments
        $overdueRepayments = $user->repayments()
            ->where(function ($query) {
                $query->where('status', 'overdue')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('due_date', '<', now())
                            ->where('paid_at', '>', 'due_date');
                      });
            })
            ->get();
        
        return $this->calculateLateFees($overdueRepayments);
    }

    /**
     * Calculate prepayment rate
     */
    private function calculatePrepaymentRate($user)
    {
        $totalRepayments = $user->repayments()->where('status', 'paid')->count();
        $prepayments = $user->repayments()
            ->where('status', 'paid')
            ->whereColumn('paid_at', '<', 'due_date')
            ->count();
        
        if ($totalRepayments === 0) return 0;
        
        return ($prepayments / $totalRepayments) * 100;
    }

    /**
     * Calculate next auto-payment
     */
    private function calculateNextAutoPayment($user, $settings)
    {
        $nextRepayment = $user->repayments()
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($settings['threshold_days']))
            ->orderBy('due_date')
            ->first();
        
        if (!$nextRepayment) return null;
        
        return [
            'date' => $nextRepayment->due_date->format('Y-m-d'),
            'amount' => $nextRepayment->amount,
            'lease_id' => $nextRepayment->lease_id,
        ];
    }

    /**
     * Calculate auto-payment savings
     */
    private function calculateAutoPaymentSavings($user)
    {
        // Estimate savings from avoiding late fees
        $estimatedLateFees = $this->calculateTotalLateFees($user);
        return $estimatedLateFees * 0.8; // Assume 80% reduction
    }

    /**
     * Calculate credit impact from repayments
     */
    private function calculateCreditImpactFromRepayments($user)
    {
        $onTimeRate = $this->calculateOnTimePercentage($user);
        $prepaymentRate = $this->calculatePrepaymentRate($user);
        
        if ($onTimeRate >= 95 && $prepaymentRate >= 30) {
            return 'Very positive (+30-50 points)';
        } elseif ($onTimeRate >= 90) {
            return 'Positive (+10-30 points)';
        } elseif ($onTimeRate >= 80) {
            return 'Neutral (0-10 points)';
        } elseif ($onTimeRate >= 70) {
            return 'Negative (-10-20 points)';
        } else {
            return 'Very negative (-20-50 points)';
        }
    }

    /**
     * Get repayment recommendations
     */
    private function getRepaymentRecommendations($user)
    {
        $recommendations = [];
        
        $onTimeRate = $this->calculateOnTimePercentage($user);
        
        if ($onTimeRate < 90) {
            $recommendations[] = 'Improve on-time payment rate to boost credit score';
        }
        
        $overdueCount = $user->repayments()
            ->where(function ($query) {
                $query->where('status', 'overdue')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('due_date', '<', now());
                      });
            })
            ->count();
        
        if ($overdueCount > 0) {
            $recommendations[] = "Clear {$overdueCount} overdue payment(s) to avoid late fees";
        }
        
        if ($user->wallet->balance < 1000) {
            $recommendations[] = 'Add funds to wallet for easier repayments';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Consider setting up auto-payments for convenience';
            $recommendations[] = 'Make prepayments to reduce interest costs';
        }
        
        return array_slice($recommendations, 0, 3);
    }
}
