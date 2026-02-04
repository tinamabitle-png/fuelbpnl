<?php
namespace App\Services;

use App\Models\Lease;

class DefaultDetectionService
{
    public function check(): void
    {
        Lease::where('status','active')
            ->whereHas('repaymentSchedules', function ($q) {
                $q->whereNull('paid_at')->where('due_date','<',now());
            })
            ->update(['status' => 'defaulted']);
    }
}
