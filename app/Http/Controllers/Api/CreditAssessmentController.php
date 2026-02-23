<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CreditLimit;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CreditAssessmentController extends Controller
{
    private const MIN_REPAYMENT_AMOUNT = 30.00;

    /**
     * Check credit eligibility
     */
    public function checkEligibility(Request $request)
    {
        $user = $request->user();
        
        // Calculate eligibility score (0-100)
        $eligibilityScore = $this->calculateEligibilityScore($user);
        
        // Get recommended limit
        $recommendedLimit = $this->calculateRecommendedLimit($user);
        
        // Check if user has active defaulted leases
        $hasActiveDefault = $user->leases()
            ->where('status', 'defaulted')
            ->exists();
            
        // Check if user has overdue repayments
        $hasOverdue = $user->repayments()
            ->where('status', 'overdue')
            ->exists();
            
        // Check current credit utilization
        $currentUtilization = 0;
        if ($user->creditLimit && $user->creditLimit->limit > 0) {
            $currentUtilization = ($user->creditLimit->used / $user->creditLimit->limit) * 100;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'eligible' => $eligibilityScore >= 60 && !$hasActiveDefault,
                'eligibility_score' => $eligibilityScore,
                'recommended_limit' => $recommendedLimit,
                'current_limit' => $user->creditLimit ? $user->creditLimit->limit : 0,
                'current_used' => $user->creditLimit ? $user->creditLimit->used : 0,
                'available_credit' => $user->available_credit,
                'credit_utilization' => round($currentUtilization, 2),
                'credit_score' => $user->credit_score,
                'has_active_default' => $hasActiveDefault,
                'has_overdue_repayments' => $hasOverdue,
                'account_status' => $user->status,
                'can_apply' => $user->status === 'active' && 
                              !$hasActiveDefault && 
                              $eligibilityScore >= 60,
                'reasons' => $this->getEligibilityReasons($user, $eligibilityScore, $hasActiveDefault),
            ],
        ]);
    }
    
    /**
     * Apply for credit increase
     */
    public function applyForIncrease(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'requested_limit' => 'required|numeric|min:1000|max:100000',
            'reason' => 'required|string|max:500',
            'income_proof_url' => 'nullable|url',
            'id_proof_url' => 'nullable|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if user can apply
        $eligibility = $this->checkEligibility($request);
        $eligibilityData = json_decode($eligibility->getContent(), true);
        
        if (!$eligibilityData['data']['can_apply']) {
            return response()->json([
                'success' => false,
                'message' => 'You are not eligible for a credit increase at this time.',
                'reasons' => $eligibilityData['data']['reasons'],
            ], 403);
        }
        
        // Check if requested limit is reasonable
        $recommendedLimit = $eligibilityData['data']['recommended_limit'];
        $requestedLimit = $request->requested_limit;
        
        if ($requestedLimit > $recommendedLimit * 1.5) {
            return response()->json([
                'success' => false,
                'message' => 'Requested limit is too high. Maximum recommended limit is ' . 
                            number_format($recommendedLimit * 1.5, 2),
                'recommended_limit' => $recommendedLimit,
                'max_allowed' => $recommendedLimit * 1.5,
            ], 422);
        }
        
        // Create credit application record (you might want to create a CreditApplication model)
        $application = [
            'user_id' => $user->id,
            'current_limit' => $user->creditLimit->limit,
            'requested_limit' => $requestedLimit,
            'reason' => $request->reason,
            'income_proof_url' => $request->income_proof_url,
            'id_proof_url' => $request->id_proof_url,
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'notes' => null,
        ];
        
        // For now, we'll update directly. In production, this would go through approval
        // $user->creditLimit->update(['limit' => $requestedLimit, 'status' => 'under_review']);
        
        // Log this application
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'current_limit' => $user->creditLimit->limit,
                'requested_limit' => $requestedLimit,
                'reason' => $request->reason,
            ])
            ->log('credit_limit_increase_requested');
        
        // In production, notify admins about new application
        
        return response()->json([
            'success' => true,
            'message' => 'Credit increase application submitted successfully.',
            'application_id' => uniqid('CREDIT-APP-'),
            'status' => 'pending_review',
            'estimated_review_time' => '24-48 hours',
            'current_limit' => $user->creditLimit->limit,
            'requested_limit' => $requestedLimit,
        ]);
    }
    
    /**
     * Get credit score factors
     */
    public function getCreditFactors(Request $request)
    {
        $user = $request->user();
        
        $factors = [
            'payment_history' => [
                'weight' => 35,
                'score' => $this->calculatePaymentHistoryScore($user),
                'description' => 'On-time payments of your BNPL loans',
                'tips' => [
                    'Make all repayments on time',
                    'Set up automatic repayments',
                    'Contact us if you anticipate payment difficulties',
                ],
            ],
            'credit_utilization' => [
                'weight' => 30,
                'score' => $this->calculateUtilizationScore($user),
                'description' => 'How much of your available credit you\'re using',
                'tips' => [
                    'Keep utilization below 30%',
                    'Pay down balances before requesting new credit',
                    'Request credit limit increases responsibly',
                ],
            ],
            'account_age' => [
                'weight' => 15,
                'score' => $this->calculateAccountAgeScore($user),
                'description' => 'Length of time you\'ve been a customer',
                'tips' => [
                    'Maintain your account in good standing',
                    'Continue using our services regularly',
                ],
            ],
            'credit_mix' => [
                'weight' => 10,
                'score' => $this->calculateCreditMixScore($user),
                'description' => 'Types of credit you manage',
                'tips' => [
                    'Successfully manage different types of loans',
                    'Show you can handle various credit products',
                ],
            ],
            'recent_inquiries' => [
                'weight' => 10,
                'score' => $this->calculateInquiriesScore($user),
                'description' => 'Recent credit applications',
                'tips' => [
                    'Space out credit applications',
                    'Only apply for credit when needed',
                ],
            ],
        ];
        
        // Calculate overall score
        $overallScore = 0;
        foreach ($factors as $factor) {
            $overallScore += ($factor['score'] * $factor['weight']) / 100;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'overall_score' => round($overallScore),
                'factors' => $factors,
                'improvement_tips' => $this->getImprovementTips($factors),
            ],
        ]);
    }
    
    /**
     * Simulate credit impact of a purchase
     */
    public function simulatePurchase(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100|max:50000',
            'fuel_type' => 'required|in:petrol,diesel,super',
            'repayment_period' => 'required|integer|min:7|max:90',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $amount = $request->amount;
        $repaymentPeriod = $request->repayment_period;
        
        // Calculate interest (simple interest for simulation)
        $interestRate = $this->getInterestRate($user);
        $interestAmount = ($amount * $interestRate * $repaymentPeriod) / (365 * 100);
        $totalAmount = $amount + $interestAmount;
        $dailyRepayment = $totalAmount / $repaymentPeriod;
        $meetsMinRepayment = $dailyRepayment >= self::MIN_REPAYMENT_AMOUNT;
        
        // Check if user can afford this
        $canAfford = $user->canRequestVoucher($amount) && $meetsMinRepayment;
        
        // Calculate new credit utilization
        $currentLimit = $user->creditLimit->limit;
        $currentUsed = $user->creditLimit->used;
        $newUsed = $currentUsed + $amount;
        $newUtilization = ($newUsed / $currentLimit) * 100;
        
        // Estimate impact on credit score
        $scoreImpact = $this->estimateScoreImpact($user, $amount, $newUtilization);
        
        return response()->json([
            'success' => true,
            'data' => [
                'can_afford' => $canAfford,
                'purchase_details' => [
                    'amount' => $amount,
                    'fuel_type' => $request->fuel_type,
                    'repayment_period' => $repaymentPeriod,
                    'interest_rate' => $interestRate,
                    'interest_amount' => round($interestAmount, 2),
                    'total_amount' => round($totalAmount, 2),
                    'daily_repayment' => round($dailyRepayment, 2),
                    'weekly_repayment' => round($dailyRepayment * 7, 2),
                    'minimum_repayment' => self::MIN_REPAYMENT_AMOUNT,
                    'meets_minimum_repayment' => $meetsMinRepayment,
                ],
                'credit_impact' => [
                    'current_utilization' => round(($currentUsed / $currentLimit) * 100, 2),
                    'new_utilization' => round($newUtilization, 2),
                    'current_available' => $user->available_credit,
                    'new_available' => max(0, $currentLimit - $newUsed),
                    'estimated_score_change' => $scoreImpact,
                    'risk_level' => $this->getRiskLevel($newUtilization),
                ],
                'recommendations' => $this->getPurchaseRecommendations($canAfford, $newUtilization),
            ],
        ]);
    }
    
    /**
     * Get credit limit history
     */
    public function getLimitHistory(Request $request)
    {
        $user = $request->user();
        
        // In production, you'd have a credit_limit_history table
        // For now, we'll return simulated data
        $history = [
            [
                'date' => now()->subMonths(3)->format('Y-m-d'),
                'limit' => 5000,
                'change' => '+2000',
                'reason' => 'Good payment history',
                'status' => 'approved',
            ],
            [
                'date' => now()->subMonths(6)->format('Y-m-d'),
                'limit' => 3000,
                'change' => '+2000',
                'reason' => 'Account anniversary',
                'status' => 'approved',
            ],
            [
                'date' => now()->subMonths(9)->format('Y-m-d'),
                'limit' => 1000,
                'change' => 'Initial',
                'reason' => 'New account',
                'status' => 'approved',
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'current_limit' => $user->creditLimit->limit,
                'history' => $history,
                'next_review_date' => $user->creditLimit->review_date,
            ],
        ]);
    }
    
    /**
     * Calculate eligibility score
     */
    private function calculateEligibilityScore($user)
    {
        $score = 0;
        
        // Base on credit score (50%)
        $score += ($user->credit_score / 850) * 50;
        
        // Payment history (30%)
        $paymentScore = $this->calculatePaymentHistoryScore($user);
        $score += $paymentScore * 0.3;
        
        // Credit utilization (20%)
        if ($user->creditLimit && $user->creditLimit->limit > 0) {
            $utilization = ($user->creditLimit->used / $user->creditLimit->limit) * 100;
            if ($utilization < 30) $score += 20;
            elseif ($utilization < 50) $score += 15;
            elseif ($utilization < 70) $score += 10;
            elseif ($utilization < 90) $score += 5;
        } else {
            $score += 20; // No utilization is good
        }
        
        // Penalties
        if ($user->status !== 'active') {
            $score -= 30;
        }
        
        if ($user->leases()->where('status', 'defaulted')->exists()) {
            $score -= 50;
        }
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Calculate recommended limit
     */
    private function calculateRecommendedLimit($user)
    {
        $baseLimit = $this->calculateCreditLimit($user->credit_score);
        
        // Adjust based on payment history
        $paymentScore = $this->calculatePaymentHistoryScore($user);
        $multiplier = 1.0 + ($paymentScore / 100);
        
        // Adjust based on account age
        $accountAge = $user->created_at->diffInMonths(now());
        if ($accountAge > 12) $multiplier *= 1.5;
        elseif ($accountAge > 6) $multiplier *= 1.25;
        elseif ($accountAge > 3) $multiplier *= 1.1;
        
        // Cap the limit
        $recommended = $baseLimit * $multiplier;
        return min($recommended, 100000); // Max KES 100,000
    }
    
    /**
     * Calculate payment history score
     */
    private function calculatePaymentHistoryScore($user)
    {
        $totalRepayments = $user->repayments()->count();
        $onTimeRepayments = $user->repayments()
            ->where('status', 'paid')
            ->whereColumn('paid_at', '<=', 'due_date')
            ->count();
            
        if ($totalRepayments === 0) return 100; // No history is neutral
        
        return ($onTimeRepayments / $totalRepayments) * 100;
    }
    
    /**
     * Calculate utilization score
     */
    private function calculateUtilizationScore($user)
    {
        if (!$user->creditLimit || $user->creditLimit->limit === 0) {
            return 100;
        }
        
        $utilization = ($user->creditLimit->used / $user->creditLimit->limit) * 100;
        
        if ($utilization < 10) return 100;
        if ($utilization < 30) return 90;
        if ($utilization < 50) return 70;
        if ($utilization < 70) return 50;
        if ($utilization < 90) return 30;
        return 10;
    }
    
    /**
     * Calculate account age score
     */
    private function calculateAccountAgeScore($user)
    {
        $accountAge = $user->created_at->diffInMonths(now());
        
        if ($accountAge > 24) return 100;
        if ($accountAge > 12) return 80;
        if ($accountAge > 6) return 60;
        if ($accountAge > 3) return 40;
        if ($accountAge > 1) return 20;
        return 10;
    }
    
    /**
     * Calculate credit mix score
     */
    private function calculateCreditMixScore($user)
    {
        $totalLeases = $user->leases()->count();
        $completedLeases = $user->leases()->where('status', 'completed')->count();
        
        if ($totalLeases === 0) return 50;
        
        return ($completedLeases / $totalLeases) * 100;
    }
    
    /**
     * Calculate inquiries score
     */
    private function calculateInquiriesScore($user)
    {
        // In production, track credit inquiries
        // For now, assume no recent inquiries
        return 100;
    }
    
    /**
     * Get eligibility reasons
     */
    private function getEligibilityReasons($user, $score, $hasDefault)
    {
        $reasons = [];
        
        if ($user->status !== 'active') {
            $reasons[] = 'Your account is ' . $user->status;
        }
        
        if ($hasDefault) {
            $reasons[] = 'You have defaulted loans';
        }
        
        if ($score < 60) {
            $reasons[] = 'Your credit assessment score is too low (' . $score . '/100)';
        }
        
        if ($user->creditLimit && $user->creditLimit->used >= $user->creditLimit->limit) {
            $reasons[] = 'You have reached your credit limit';
        }
        
        if (empty($reasons)) {
            $reasons[] = 'You are eligible for credit';
        }
        
        return $reasons;
    }
    
    /**
     * Get improvement tips
     */
    private function getImprovementTips($factors)
    {
        $tips = [];
        
        foreach ($factors as $key => $factor) {
            if ($factor['score'] < 70) {
                $tips = array_merge($tips, $factor['tips']);
            }
        }
        
        if (empty($tips)) {
            $tips[] = 'Continue maintaining good credit habits';
            $tips[] = 'Consider diversifying your credit portfolio';
            $tips[] = 'Keep your credit utilization low';
        }
        
        return array_slice(array_unique($tips), 0, 5);
    }
    
    /**
     * Get interest rate based on credit score
     */
    private function getInterestRate($user)
    {
        if ($user->credit_score >= 800) return 5;
        if ($user->credit_score >= 700) return 7;
        if ($user->credit_score >= 600) return 10;
        if ($user->credit_score >= 500) return 15;
        if ($user->credit_score >= 400) return 20;
        return 25;
    }
    
    /**
     * Estimate score impact
     */
    private function estimateScoreImpact($user, $amount, $newUtilization)
    {
        $impact = 0;
        
        // Utilization impact
        $currentUtilization = ($user->creditLimit->used / $user->creditLimit->limit) * 100;
        
        if ($newUtilization > 90) $impact -= 20;
        elseif ($newUtilization > 70) $impact -= 10;
        elseif ($newUtilization > 50) $impact -= 5;
        elseif ($newUtilization < 30 && $currentUtilization >= 30) $impact += 5;
        
        // New credit inquiry impact
        $impact -= 2;
        
        return $impact;
    }
    
    /**
     * Get risk level
     */
    private function getRiskLevel($utilization)
    {
        if ($utilization < 30) return 'low';
        if ($utilization < 50) return 'moderate';
        if ($utilization < 70) return 'high';
        return 'very_high';
    }
    
    /**
     * Get purchase recommendations
     */
    private function getPurchaseRecommendations($canAfford, $newUtilization)
    {
        $recommendations = [];
        
        if (!$canAfford) {
            $recommendations[] = 'Consider a smaller amount or use wallet funds';
        }
        
        if ($newUtilization > 70) {
            $recommendations[] = 'High utilization may impact future credit applications';
        }
        
        if ($newUtilization > 50) {
            $recommendations[] = 'Consider making a repayment before this purchase';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'This purchase is within recommended limits';
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate credit limit based on credit score
     */
    private function calculateCreditLimit($creditScore)
    {
        if ($creditScore >= 800) return 50000;
        if ($creditScore >= 700) return 30000;
        if ($creditScore >= 600) return 15000;
        if ($creditScore >= 500) return 8000;
        if ($creditScore >= 400) return 3000;
        return 1000;
    }
}
