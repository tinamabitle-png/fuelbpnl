<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Get wallet balance
     */
    public function balance(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => $user->wallet,
                'credit_limit' => $user->creditLimit,
                'available_credit' => $user->available_credit,
                'total_assets' => $user->wallet->balance + $user->available_credit,
                'outstanding_balance' => $user->wallet->outstanding_balance,
                'credit_utilization' => $user->creditLimit->limit > 0 ? 
                    ($user->creditLimit->used / $user->creditLimit->limit) * 100 : 0,
            ]
        ]);
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type');
        $limit = $request->query('limit', 50);
        $page = $request->query('page', 1);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = $user->walletTransactions()
                     ->orderBy('created_at', 'desc');

        if ($type && in_array($type, ['credit', 'debit'])) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $query->paginate($limit, ['*'], 'page', $page);

        // Calculate summary
        $summary = [
            'total_credits' => $user->walletTransactions()
                                   ->where('type', 'credit')
                                   ->where('status', 'completed')
                                   ->sum('amount'),
            'total_debits' => $user->walletTransactions()
                                  ->where('type', 'debit')
                                  ->where('status', 'completed')
                                  ->sum('amount'),
            'current_month_credits' => $user->walletTransactions()
                                           ->where('type', 'credit')
                                           ->where('status', 'completed')
                                           ->whereMonth('created_at', now()->month)
                                           ->sum('amount'),
            'current_month_debits' => $user->walletTransactions()
                                          ->where('type', 'debit')
                                          ->where('status', 'completed')
                                          ->whereMonth('created_at', now()->month)
                                          ->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'summary' => $summary,
            ]
        ]);
    }

    /**
     * Add funds to wallet
     */
    public function addFunds(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100|max:100000',
            'payment_method' => 'required|in:mpesa,bank_transfer,card',
            'phone' => 'required_if:payment_method,mpesa|string',
            'card_token' => 'required_if:payment_method,card|string',
            'bank_details' => 'required_if:payment_method,bank_transfer|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // In production, integrate with payment gateway
        // For now, simulate payment processing
        
        DB::beginTransaction();

        try {
            // Process payment (simulated)
            $reference = $this->processPayment(
                $request->payment_method,
                $request->amount,
                $request->all()
            );

            // Add funds to wallet
            $transaction = $user->wallet->addFunds(
                $request->amount,
                'Wallet topup via ' . $request->payment_method,
                [
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $reference,
                    'phone' => $request->phone ?? null,
                ]
            );

            // Log the activity
            activity()
                ->performedOn($user->wallet)
                ->causedBy($user)
                ->withProperties([
                    'amount' => $request->amount,
                    'payment_method' => $request->payment_method,
                    'reference' => $reference,
                ])
                ->log('wallet_funds_added');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Funds added successfully',
                'data' => [
                    'transaction' => $transaction,
                    'new_balance' => $user->wallet->fresh()->balance,
                    'receipt' => [
                        'reference' => $reference,
                        'amount' => $request->amount,
                        'date' => now()->format('Y-m-d H:i:s'),
                        'new_balance' => $user->wallet->fresh()->balance,
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
     * Make loan repayment
     */
    public function makePayment(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'lease_id' => 'required_without:amount|exists:leases,id',
            'amount' => 'required_without:lease_id|numeric|min:100',
            'payment_method' => 'required|in:wallet,mpesa,bank_transfer',
            'phone' => 'required_if:payment_method,mpesa|string',
            'repay_full' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            if ($request->filled('lease_id')) {
                // Repay specific lease
                $lease = $user->leases()->find($request->lease_id);
                
                if (!$lease) {
                    throw new \Exception('Lease not found');
                }

                if ($request->repay_full) {
                    $amount = $lease->remaining_balance;
                } else {
                    $amount = $request->amount;
                }

                if ($amount > $lease->remaining_balance) {
                    $amount = $lease->remaining_balance;
                }

                // Process payment
                $paymentResult = $this->processRepayment(
                    $user,
                    $amount,
                    $request->payment_method,
                    $request->all()
                );

                // Apply to lease
                $repayment = $lease->markAsPaid(
                    $amount,
                    $request->payment_method,
                    $paymentResult['reference']
                );

                $message = 'Lease payment successful';

            } else {
                // Make general payment (will be distributed)
                $amount = $request->amount;
                
                // Process payment
                $paymentResult = $this->processRepayment(
                    $user,
                    $amount,
                    $request->payment_method,
                    $request->all()
                );

                // Distribute to active leases
                $distribution = $this->distributePayment($user, $amount);
                
                $message = 'Payment distributed successfully';
                $repayment = $distribution;
            }

            // Log the activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'amount' => $amount,
                    'payment_method' => $request->payment_method,
                    'reference' => $paymentResult['reference'],
                ])
                ->log('loan_payment_made');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'payment_reference' => $paymentResult['reference'],
                    'amount_paid' => $amount,
                    'repayment' => $repayment ?? null,
                    'new_outstanding_balance' => $user->wallet->fresh()->outstanding_balance,
                    'available_credit' => $user->available_credit,
                    'receipt' => [
                        'reference' => $paymentResult['reference'],
                        'amount' => $amount,
                        'date' => now()->format('Y-m-d H:i:s'),
                        'payment_method' => $request->payment_method,
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
     * Withdraw funds from wallet
     */
    public function withdraw(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100|max:50000',
            'withdrawal_method' => 'required|in:mpesa,bank_account',
            'phone' => 'required_if:withdrawal_method,mpesa|string',
            'bank_account' => 'required_if:withdrawal_method,bank_account|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$user->wallet->canAfford($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Deduct from wallet
            $transaction = $user->wallet->deductFunds(
                $request->amount,
                'Withdrawal via ' . $request->withdrawal_method,
                [
                    'withdrawal_method' => $request->withdrawal_method,
                    'phone' => $request->phone ?? null,
                    'bank_account' => $request->bank_account ?? null,
                ]
            );

            // Process withdrawal (simulated)
            $reference = $this->processWithdrawal(
                $request->withdrawal_method,
                $request->amount,
                $request->all()
            );

            // Update transaction with withdrawal reference
            $transaction->update(['reference' => $reference]);

            // Log the activity
            activity()
                ->performedOn($user->wallet)
                ->causedBy($user)
                ->withProperties([
                    'amount' => $request->amount,
                    'withdrawal_method' => $request->withdrawal_method,
                    'reference' => $reference,
                ])
                ->log('wallet_funds_withdrawn');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted',
                'data' => [
                    'transaction' => $transaction,
                    'new_balance' => $user->wallet->fresh()->balance,
                    'withdrawal_details' => [
                        'reference' => $reference,
                        'amount' => $request->amount,
                        'method' => $request->withdrawal_method,
                        'estimated_processing' => '1-3 business days',
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment methods
     */
    public function paymentMethods(Request $request)
    {
        $user = $request->user();
        
        // In production, fetch from user's saved payment methods
        $methods = [
            [
                'id' => 'wallet',
                'name' => 'Wallet Balance',
                'type' => 'wallet',
                'available' => true,
                'balance' => $user->wallet->balance,
            ],
            [
                'id' => 'mpesa',
                'name' => 'M-Pesa',
                'type' => 'mobile_money',
                'available' => true,
                'phone' => substr($user->phone, -4),
            ],
            [
                'id' => 'card_primary',
                'name' => 'Visa **** 1234',
                'type' => 'card',
                'available' => true,
                'expiry' => '12/25',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'payment_methods' => $methods,
                'default_method' => 'mpesa',
            ]
        ]);
    }

    /**
     * Process payment (simulated)
     */
    private function processPayment($method, $amount, $details)
    {
        // In production, integrate with payment gateway
        // For now, generate reference
        
        $prefixes = [
            'mpesa' => 'MPE',
            'bank_transfer' => 'BNK',
            'card' => 'CRD',
        ];

        return ($prefixes[$method] ?? 'PAY') . time() . rand(1000, 9999);
    }

    /**
     * Process repayment
     */
    private function processRepayment($user, $amount, $method, $details)
    {
        if ($method === 'wallet') {
            if (!$user->wallet->canAfford($amount)) {
                throw new \Exception('Insufficient wallet balance');
            }
            
            // Deduct from wallet
            $user->wallet->deductFunds($amount, 'Loan repayment');
            
            return [
                'success' => true,
                'reference' => 'WALLET-' . time(),
            ];
        }

        // Process external payment
        return [
            'success' => true,
            'reference' => $this->processPayment($method, $amount, $details),
        ];
    }

    /**
     * Distribute payment among active leases
     */
    private function distributePayment($user, $amount)
    {
        $activeLeases = $user->leases()
                           ->where('status', 'active')
                           ->orderBy('due_date')
                           ->get();

        $distributions = [];
        $remainingAmount = $amount;

        foreach ($activeLeases as $lease) {
            if ($remainingAmount <= 0) break;

            $leaseBalance = $lease->remaining_balance;
            $toPay = min($remainingAmount, $leaseBalance);

            if ($toPay > 0) {
                $lease->markAsPaid($toPay, 'manual_distributed');
                $distributions[] = [
                    'lease_id' => $lease->id,
                    'amount' => $toPay,
                    'remaining_balance' => $lease->remaining_balance,
                ];
                
                $remainingAmount -= $toPay;
            }
        }

        return $distributions;
    }

    /**
     * Process withdrawal (simulated)
     */
    private function processWithdrawal($method, $amount, $details)
    {
        $prefixes = [
            'mpesa' => 'WMP',
            'bank_account' => 'WBK',
        ];

        return ($prefixes[$method] ?? 'WTH') . time() . rand(1000, 9999);
    }
}