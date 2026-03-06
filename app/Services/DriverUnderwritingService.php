<?php

namespace App\Services;

use App\Models\Repayment;
use App\Models\User;
use Illuminate\Support\Carbon;

class DriverUnderwritingService
{
    public const STARTER_CAP_DAYS = 90;
    public const STARTER_MAX_VOUCHER_AMOUNT = 1500.00;
    public const GROWTH_MAX_VOUCHER_AMOUNT = 3000.00;
    public const LATE_REPAYMENT_RATE_PENALTY = 2.00;

    public function resolveForUser(User $user): array
    {
        $createdAt = $user->created_at ?? now();
        $accountAgeDays = (int) Carbon::parse($createdAt)->diffInDays(now());
        $isStarterWindow = $accountAgeDays < self::STARTER_CAP_DAYS;

        $hasLateRepayment = Repayment::query()
            ->visibleInSystem()
            ->where('user_id', (int) $user->id)
            ->where(function ($q) {
                $q->whereIn('status', ['overdue', 'defaulted'])
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'paid')
                            ->whereNotNull('paid_at')
                            ->whereColumn('paid_at', '>', 'due_date');
                    });
            })
            ->exists();

        $maxAmount = self::STARTER_MAX_VOUCHER_AMOUNT;
        $tier = 'starter';
        if (!$isStarterWindow && !$hasLateRepayment) {
            $maxAmount = self::GROWTH_MAX_VOUCHER_AMOUNT;
            $tier = 'growth';
        }

        return [
            'account_age_days' => $accountAgeDays,
            'is_starter_window' => $isStarterWindow,
            'late_repayment_detected' => $hasLateRepayment,
            'max_amount' => $maxAmount,
            'rate_penalty' => $hasLateRepayment ? self::LATE_REPAYMENT_RATE_PENALTY : 0.0,
            'tier' => $tier,
            'tier_label' => strtoupper($tier),
        ];
    }
}
