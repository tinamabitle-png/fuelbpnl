<?php
namespace App\Services;

use App\Models\CreditLimit;

class CreditApprovalService
{
    public function canApprove(int $userId, float $amount): bool
    {
        $limit = CreditLimit::where('user_id', $userId)->first();
        if (!$limit) return false;

        return ($limit->used_amount + $amount) <= $limit->limit_amount;
    }

    public function reserve(int $userId, float $amount): void
    {
        $limit = CreditLimit::where('user_id', $userId)->lockForUpdate()->first();
        $limit->increment('used_amount', $amount);
    }
}
