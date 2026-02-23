<?php

namespace App\Console\Commands;

use App\Models\Repayment;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\PaystackService;
use App\Services\RepaymentSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunDailyRepaymentAutopay extends Command
{
    protected $signature = 'repayments:run-daily-autopay {--limit=250}';

    protected $description = 'Run daily Paystack authorization charges for due driver repayments.';

    public function handle(PaystackService $paystack, RepaymentSettlementService $repaymentSettlement): int
    {
        if (!$paystack->configured()) {
            $this->warn('Paystack is not configured. Skipping daily autopay run.');
            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $processed = 0;
        $failed = 0;
        $skipped = 0;

        $dueRepaymentIds = Repayment::query()
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('autopay_next_attempt_at')
                    ->orWhere('autopay_next_attempt_at', '<=', now());
            })
            ->orderBy('due_date')
            ->limit($limit)
            ->pluck('id');

        foreach ($dueRepaymentIds as $id) {
            /** @var Repayment|null $repayment */
            $repayment = Repayment::with('user')->find($id);
            if (!$repayment) {
                $skipped++;
                continue;
            }

            /** @var User|null $user */
            $user = $repayment->user;
            if (!$user || !$user->autopay_enabled || strtolower((string) $user->autopay_gateway) !== 'paystack' || trim((string) $user->autopay_token) === '') {
                $skipped++;
                continue;
            }

            if ($user->autopay_next_attempt_at && $user->autopay_next_attempt_at->isFuture()) {
                $skipped++;
                continue;
            }

            try {
                $charge = $paystack->chargeAuthorization($user, $repayment, 'daily_24h_cycle');
                $repaymentSettlement->settleRepayment(
                    $repayment,
                    'paystack_subscription',
                    (string) ($charge['reference'] ?? ''),
                    ['source' => 'daily_autopay']
                );

                $nextChargeAt = now()->addDay();
                DB::transaction(function () use ($repayment, $user, $nextChargeAt) {
                    $repayment->forceFill([
                        'autopay_status' => 'charged',
                        'autopay_last_attempt_at' => now(),
                        'autopay_attempts' => (int) $repayment->autopay_attempts + 1,
                        'autopay_next_attempt_at' => null,
                    ])->save();

                    $user->forceFill([
                        'autopay_status' => 'active',
                        'autopay_failures' => 0,
                        'autopay_last_attempt_at' => now(),
                        'autopay_next_attempt_at' => $nextChargeAt,
                    ])->save();
                });

                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                $retryAt = now()->addDay();

                DB::transaction(function () use ($repayment, $user, $retryAt, $e) {
                    $repayment->forceFill([
                        'status' => now()->toDateString() > (string) $repayment->due_date ? 'overdue' : $repayment->status,
                        'autopay_status' => 'failed',
                        'autopay_last_attempt_at' => now(),
                        'autopay_attempts' => (int) $repayment->autopay_attempts + 1,
                        'autopay_next_attempt_at' => $retryAt,
                    ])->save();

                    $user->forceFill([
                        'autopay_status' => 'retrying',
                        'autopay_failures' => (int) $user->autopay_failures + 1,
                        'autopay_last_attempt_at' => now(),
                        'autopay_next_attempt_at' => $retryAt,
                    ])->save();
                });

                AuditTrailService::record(
                    'repayment_autopay_failed',
                    $repayment,
                    [],
                    [
                        'error' => $e->getMessage(),
                        'retry_at' => $retryAt->toDateTimeString(),
                    ],
                    'Daily repayment autopay failed'
                );
            }
        }

        $this->info("Daily autopay complete. Processed: {$processed}, failed: {$failed}, skipped: {$skipped}.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

