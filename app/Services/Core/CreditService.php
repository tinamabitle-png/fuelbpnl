<?php

namespace App\Services\Core;

use App\Models\User;
use App\Models\CreditLimit;
use App\Services\Security\FraudDetectionService;

class CreditService
{
    protected $fraudDetectionService;

    public function __construct(FraudDetectionService $fraudDetectionService)
    {
        $this->fraudDetectionService = $fraudDetectionService;
    }

    public function checkApproval(User $user, float $amount): array
    {
        // Check user status
        if ($user->status !== 'active') {
            return [
                'approved' => false,
                'message' => 'Account is ' . $user->status,
                'requires_approval' => false,
                'is_bnpl' => false,
            ];
        }

        // Fraud detection
        $fraudCheck = $this->fraudDetectionService->checkVoucherRequest($user, $amount);
        
        if ($fraudCheck['blocked']) {
            return [
                'approved' => false,
                'message' => 'Request flagged for review',
                'requires_approval' => true,
                'is_bnpl' => false,
                'fraud_flags' => $fraudCheck['flags'],
            ];
        }

        // Get credit limit
        $creditLimit = $user->creditLimit ?? $this->createDefaultCreditLimit($user);
        
        // Check if user has wallet balance
        $hasWalletBalance = $user->wallet->balance >= $amount;
        
        // Check if BNPL is needed
        $needsBnpl = !$hasWalletBalance;
        
        if ($needsBnpl) {
            // Check BNPL eligibility
            $bnplCheck = $this->checkBnplEligibility($user, $amount, $creditLimit);
            
            if (!$bnplCheck['approved']) {
                return $bnplCheck;
            }
            
            return [
                'approved' => true,
                'message' => 'BNPL approved',
                'requires_approval' => $fraudCheck['requires_approval'],
                'is_bnpl' => true,
                'credit_limit' => $creditLimit->limit,
                'available_credit' => $creditLimit->limit - $user->wallet->outstanding_balance,
            ];
        }
        
        // User has wallet balance
        return [
            'approved' => true,
            'message' => 'Approved (wallet balance)',
            'requires_approval' => $fraudCheck['requires_approval'],
            'is_bnpl' => false,
        ];
    }

    private function checkBnplEligibility(User $user, float $amount, CreditLimit $creditLimit): array
    {
        $availableCredit = $creditLimit->limit - $user->wallet->outstanding_balance;
        
        // Check if amount exceeds available credit
        if ($amount > $availableCredit) {
            return [
                'approved' => false,
                'message' => 'Amount exceeds available credit limit',
                'requires_approval' => false,
                'is_bnpl' => false,
            ];
        }
        
        // Check if user has good credit score
        if ($user->credit_score < 400) {
            return [
                'approved' => false,
                'message' => 'Low credit score',
                'requires_approval' => true,
                'is_bnpl' => false,
            ];
        }
        
        // Check if user has active defaults
        $hasActiveDefaults = $user->leases()
            ->where('status', 'defaulted')
            ->where('defaulted_at', '>', now()->subDays(90))
            ->exists();
            
        if ($hasActiveDefaults) {
            return [
                'approved' => false,
                'message' => 'Account has active defaults',
                'requires_approval' => false,
                'is_bnpl' => false,
            ];
        }
        
        // Check debt-to-income ratio (simplified)
        $debtRatio = $user->wallet->outstanding_balance / max($creditLimit->limit, 1);
        if ($debtRatio > 0.7) {
            return [
                'approved' => false,
                'message' => 'High debt ratio',
                'requires_approval' => true,
                'is_bnpl' => false,
            ];
        }
        
        return [
            'approved' => true,
            'message' => 'BNPL eligible',
            'requires_approval' => false,
            'is_bnpl' => true,
        ];
    }

    private function createDefaultCreditLimit(User $user): CreditLimit
    {
        $baseLimit = 5000; // Base limit in KES
        
        // Adjust based on credit score
        $scoreMultiplier = $user->credit_score / 1000;
        $adjustedLimit = $baseLimit * $scoreMultiplier;
        
        // Cap the limit
        $finalLimit = min(max($adjustedLimit, 1000), 50000);
        
        return CreditLimit::create([
            'user_id' => $user->id,
            'limit' => $finalLimit,
            'used' => 0,
            'available' => $finalLimit,
            'review_date' => now()->addDays(90),
        ]);
    }

    public function updateCreditScore(User $user, array $factors)
    {
        $baseScore = $user->credit_score;
        $adjustment = 0;
        
        // Positive factors
        if ($factors['on_time_repayments'] ?? false) {
            $adjustment += 20;
        }
        
        if ($factors['no_defaults'] ?? false) {
            $adjustment += 30;
        }
        
        if ($factors['long_history'] ?? false) {
            $adjustment += 15;
        }
        
        // Negative factors
        if ($factors['late_repayment'] ?? false) {
            $adjustment -= 40;
        }
        
        if ($factors['default'] ?? false) {
            $adjustment -= 100;
        }
        
        if ($factors['fraud_flag'] ?? false) {
            $adjustment -= 80;
        }
        
        $newScore = max(300, min(850, $baseScore + $adjustment));
        
        $user->update(['credit_score' => $newScore]);
        
        // Update credit limit if score changed significantly
        if (abs($newScore - $baseScore) >= 50) {
            $this->adjustCreditLimit($user, $newScore);
        }
    }

    private function adjustCreditLimit(User $user, int $newScore)
    {
        $creditLimit = $user->creditLimit;
        
        if (!$creditLimit) {
            return;
        }
        
        $scoreRatio = $newScore / 850;
        $newLimit = 5000 * $scoreRatio;
        
        // Only increase limit, never decrease (for customer satisfaction)
        if ($newLimit > $creditLimit->limit) {
            $creditLimit->update([
                'limit' => $newLimit,
                'available' => $newLimit - $user->wallet->outstanding_balance,
            ]);
        }
    }
}