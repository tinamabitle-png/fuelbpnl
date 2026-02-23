<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RepaymentSettlementService
{
    public function settleRepayment(Repayment $repayment, string $paymentMethod, string $reference, array $meta = []): Repayment
    {
        return DB::transaction(function () use ($repayment, $paymentMethod, $reference, $meta) {
            $locked = Repayment::whereKey($repayment->id)->lockForUpdate()->firstOrFail();

            if (!in_array((string) $locked->status, ['pending', 'overdue'], true)) {
                return $locked;
            }

            $locked->markAsPaid($paymentMethod, $reference);

            $user = User::whereKey($locked->user_id)->lockForUpdate()->first();
            if ($user?->wallet) {
                $debit = min((float) $locked->amount, (float) $user->wallet->outstanding_balance);
                if ($debit > 0) {
                    $user->wallet->decrement('outstanding_balance', $debit);
                }
                $user->wallet->increment('total_repayments', (float) $locked->amount);
            }

            if ($user?->creditLimit) {
                $user->creditLimit->releaseCredit((float) $locked->amount);
            }

            $lease = Lease::whereKey($locked->lease_id)->lockForUpdate()->first();
            if ($lease && $lease->remaining_balance <= 0 && (string) $lease->status === 'active') {
                $lease->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            AuditTrailService::record(
                'repayment_paid_settlement',
                $locked,
                [],
                [
                    'payment_method' => $paymentMethod,
                    'reference' => $reference,
                    'amount' => (float) $locked->amount,
                    'meta' => $meta,
                ],
                'Repayment settlement posted'
            );

            return $locked->fresh();
        });
    }
}

