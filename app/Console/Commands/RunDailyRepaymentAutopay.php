<?php

namespace App\Console\Commands;

use App\Models\Repayment;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\PaystackService;
use App\Services\RepaymentPolicyService;
use App\Services\RepaymentSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class RunDailyRepaymentAutopay extends Command
{
    protected $signature = 'repayments:run-daily-autopay {--limit=250}';

    protected $description = 'Run daily Paystack authorization charges for due driver repayments.';

    public function handle(
        PaystackService $paystack,
        RepaymentSettlementService $repaymentSettlement,
        RepaymentPolicyService $policyService
    ): int
    {
        if (!$paystack->configured()) {
            $this->warn('Paystack is not configured. Skipping daily autopay run.');
            return self::SUCCESS;
        }

        $policy = $policyService->get();
        $maxRetries = (int) ($policy['autopay_max_retries'] ?? 3);
        $retryHours = (int) ($policy['autopay_retry_hours'] ?? 24);
        $graceDays = (int) ($policy['autopay_grace_days'] ?? 2);
        $autoDisableThreshold = (int) ($policy['autopay_auto_disable_threshold'] ?? 5);

        $limit = max(1, (int) $this->option('limit'));
        $processed = 0;
        $failed = 0;
        $skipped = 0;
        $disabled = 0;
        $skipReasons = [
            'repayment_not_found' => 0,
            'user_not_eligible' => 0,
            'user_backoff_not_due' => 0,
        ];

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
                $skipReasons['repayment_not_found']++;
                continue;
            }

            /** @var User|null $user */
            $user = $repayment->user;
            if (!$user || !$user->autopay_enabled || strtolower((string) $user->autopay_gateway) !== 'paystack' || trim((string) $user->autopay_token) === '') {
                $skipped++;
                $skipReasons['user_not_eligible']++;
                continue;
            }

            if ($user->autopay_next_attempt_at && $user->autopay_next_attempt_at->isFuture()) {
                $skipped++;
                $skipReasons['user_backoff_not_due']++;
                continue;
            }

            $isBeyondGrace = (string) $repayment->due_date <= now()->subDays($graceDays)->toDateString();
            if ((int) $repayment->autopay_attempts >= $maxRetries && $isBeyondGrace) {
                DB::transaction(function () use ($repayment, $user) {
                    $repayment->forceFill([
                        'autopay_status' => 'max_retries_exceeded',
                        'autopay_next_attempt_at' => null,
                    ])->save();

                    $user->forceFill([
                        'autopay_enabled' => false,
                        'autopay_status' => 'disabled',
                        'autopay_next_attempt_at' => null,
                    ])->save();
                });

                $disabled++;
                AuditTrailService::record(
                    'repayment_autopay_disabled_for_user',
                    $repayment,
                    [],
                    [
                        'reason' => 'max_retries_exceeded',
                        'max_retries' => $maxRetries,
                    ],
                    'Autopay disabled after max retries'
                );

                $this->notifyUser(
                    $user,
                    'AutoPay disabled',
                    "Your automatic repayments were disabled after {$maxRetries} failed attempts. Please complete a manual payment and re-enable autopay."
                );
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
                AuditTrailService::record(
                    'repayment_autopay_succeeded',
                    $repayment,
                    [],
                    [
                        'amount' => (float) $repayment->amount,
                        'reference' => (string) ($charge['reference'] ?? ''),
                    ],
                    'Daily repayment autopay succeeded'
                );
                $this->notifyUser(
                    $user,
                    'Repayment auto-paid',
                    "Your repayment of R " . number_format((float) $repayment->amount, 2) . " was automatically paid successfully."
                );
            } catch (\Throwable $e) {
                $failed++;
                $retryAt = now()->addHours($retryHours);

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
                    'repayment_autopay_retry_scheduled',
                    $repayment,
                    [],
                    [
                        'retry_at' => $retryAt->toDateTimeString(),
                        'attempts' => (int) $repayment->autopay_attempts + 1,
                        'error' => $e->getMessage(),
                    ],
                    'Autopay retry scheduled'
                );
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
                if ((int) $user->autopay_failures + 1 >= $autoDisableThreshold && $isBeyondGrace) {
                    $user->forceFill([
                        'autopay_enabled' => false,
                        'autopay_status' => 'disabled',
                        'autopay_next_attempt_at' => null,
                    ])->save();
                    $disabled++;
                    AuditTrailService::record(
                        'repayment_autopay_disabled_for_user',
                        $repayment,
                        [],
                        [
                            'reason' => 'failure_threshold',
                            'threshold' => $autoDisableThreshold,
                        ],
                        'Autopay disabled due to repeated failures'
                    );
                }
                $this->notifyUser(
                    $user,
                    'Repayment auto-pay failed',
                    "Auto-pay failed for repayment R " . number_format((float) $repayment->amount, 2) . ". Retry is scheduled for " . $retryAt->format('Y-m-d H:i') . "."
                );
            }
        }

        Cache::put('repayments:autopay:last-run', [
            'at' => now()->toDateTimeString(),
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
            'skip_reasons' => $skipReasons,
            'disabled' => $disabled,
            'policy' => $policy,
        ], now()->addDays(14));

        $this->info("Daily autopay complete. Processed: {$processed}, failed: {$failed}, skipped: {$skipped}, disabled: {$disabled}.");
        $this->line('Skip breakdown: '
            . 'repayment_not_found=' . $skipReasons['repayment_not_found']
            . ', user_not_eligible=' . $skipReasons['user_not_eligible']
            . ', user_backoff_not_due=' . $skipReasons['user_backoff_not_due']
        );
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function notifyUser(User $user, string $subject, string $message): void
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            try {
                Mail::raw($message, function ($mail) use ($email, $subject) {
                    $mail->to($email)->subject($subject);
                });
            } catch (\Throwable $e) {
                // Keep autopay flow non-blocking.
            }
        }

        AuditTrailService::record(
            'repayment_user_notification',
            $user,
            [],
            ['subject' => $subject, 'message' => $message],
            'Repayment user notification emitted'
        );
    }
}
