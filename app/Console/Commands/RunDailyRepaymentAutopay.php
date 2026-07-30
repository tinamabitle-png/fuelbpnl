<?php

namespace App\Console\Commands;

use App\Mail\RepaymentAutopayNotificationMail;
use App\Models\Repayment;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\DebiCheckService;
use App\Services\PaystackService;
use App\Services\RepaymentPolicyService;
use App\Services\RepaymentSettlementService;
use App\Support\StationBrandAssets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class RunDailyRepaymentAutopay extends Command
{
    protected $signature = 'repayments:run-daily-autopay {--limit=250}';

    protected $description = 'Run daily AutoPay charges (DebiCheck + Paystack fallback) for due driver repayments.';

    public function handle(
        PaystackService $paystack,
        DebiCheckService $debiCheck,
        RepaymentSettlementService $repaymentSettlement,
        RepaymentPolicyService $policyService
    ): int
    {
        if (!(bool) config('services.billing.enabled', false)) {
            $this->warn('Billing is disabled for this environment. Skipping daily autopay run.');
            return self::SUCCESS;
        }

        if (!$paystack->configured() && !$debiCheck->configured()) {
            $this->warn('No AutoPay rails configured (Paystack/DebiCheck). Skipping daily autopay run.');
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
            'repayment_processing' => 0,
        ];

        $dueRepaymentIds = Repayment::query()
            ->visibleInSystem()
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
            $repayment = Repayment::query()
                ->visibleInSystem()
                ->with(['user', 'lease.vouchers.fuelStation'])
                ->find($id);
            if (!$repayment) {
                $skipped++;
                $skipReasons['repayment_not_found']++;
                continue;
            }

            /** @var User|null $user */
            $user = $repayment->user;
            if (!$user || !$user->autopay_enabled) {
                $skipped++;
                $skipReasons['user_not_eligible']++;
                continue;
            }

            $details = (array) ($user->autopay_details ?? []);
            $debi = (array) ($details['debicheck'] ?? []);
            $debiStatus = strtolower((string) ($debi['status'] ?? ''));
            $debiMandateId = trim((string) ($debi['mandate_id'] ?? ''));
            $debiReady = $debiCheck->configured()
                && $debiMandateId !== ''
                && in_array($debiStatus, ['active', 'approved', 'accepted', 'verified'], true);
            $paystackReady = $paystack->configured() && trim((string) $user->autopay_token) !== '';
            if (!$debiReady && !$paystackReady) {
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
                    "Your automatic repayments were disabled after {$maxRetries} failed attempts. Please complete a manual payment and re-enable autopay.",
                    $repayment
                );
                continue;
            }

            $reserved = $this->reserveRepaymentForAutopay((int) $repayment->id);
            if ($reserved === null) {
                $skipped++;
                $skipReasons['repayment_processing']++;
                continue;
            }

            /** @var Repayment $repayment */
            $repayment = $reserved['repayment'];
            /** @var User $user */
            $user = $reserved['user'];
            $providerReference = (string) $reserved['reference'];

            try {
                $gatewayUsed = 'paystack';
                $charge = null;
                if ($debiReady) {
                    try {
                        $charge = $debiCheck->collect(
                            $debiMandateId,
                            (float) $repayment->amount,
                            $providerReference
                        );
                        $gatewayUsed = 'debicheck';
                    } catch (\Throwable $debiError) {
                        if (!$paystackReady) {
                            throw $debiError;
                        }
                        $charge = $paystack->chargeAuthorization($user, $repayment, 'daily_24h_cycle', $providerReference);
                        $gatewayUsed = 'paystack_fallback';
                    }
                } else {
                    $charge = $paystack->chargeAuthorization($user, $repayment, 'daily_24h_cycle', $providerReference);
                    $gatewayUsed = 'paystack';
                }

                $repaymentSettlement->settleRepayment(
                    $repayment,
                    $gatewayUsed === 'debicheck' ? 'debicheck_mandate' : 'paystack_subscription',
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
                        'gateway' => $gatewayUsed,
                        'amount' => (float) $repayment->amount,
                        'reference' => (string) ($charge['reference'] ?? ''),
                    ],
                    'Daily repayment autopay succeeded'
                );
                $this->notifyUser(
                    $user,
                    'Repayment auto-paid',
                    "Your repayment of R " . number_format((float) $repayment->amount, 2) . " was automatically paid successfully.",
                    $repayment->fresh(['lease.vouchers.fuelStation'])
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
                    "Auto-pay failed for repayment R " . number_format((float) $repayment->amount, 2) . ". Retry is scheduled for " . $retryAt->format('Y-m-d H:i') . ".",
                    $repayment
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
            . ', repayment_processing=' . $skipReasons['repayment_processing']
        );
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function notifyUser(User $user, string $subject, string $message, ?Repayment $repayment = null): void
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            try {
                $appUrl = rtrim((string) config('app.url', 'https://bwiser.co.za'), '/');
                $payload = [
                    'subject' => $subject,
                    'heading' => $subject,
                    'body' => $message,
                    'preheader' => $subject,
                    'logo_url' => $appUrl . '/images/brand-logo.png',
                    'cta_url' => $appUrl . '/driver/repayments',
                    'cta_label' => 'View repayments',
                ];

                if ($repayment) {
                    $ticket = $this->buildVoucherTicketPayload($user, $repayment);
                    $payload['ticket'] = $ticket;
                    if (!empty($ticket['paystack_url'])) {
                        $payload['paystack_url'] = $ticket['paystack_url'];
                        $payload['paystack_label'] = 'Pay with Paystack';
                    }
                }

                Mail::to($email)->send(new RepaymentAutopayNotificationMail($payload));
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

    /**
     * Reserve before calling an external rail so concurrent runs do not debit the same repayment twice.
     */
    private function reserveRepaymentForAutopay(int $repaymentId): ?array
    {
        return DB::transaction(function () use ($repaymentId) {
            /** @var Repayment|null $locked */
            $locked = Repayment::query()
                ->visibleInSystem()
                ->whereKey($repaymentId)
                ->lockForUpdate()
                ->first();

            if (!$locked || !in_array((string) $locked->status, ['pending', 'overdue'], true)) {
                return null;
            }

            if ($locked->autopay_next_attempt_at && $locked->autopay_next_attempt_at->isFuture()) {
                return null;
            }

            /** @var User|null $user */
            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();
            if (!$user || !$user->autopay_enabled) {
                return null;
            }

            if ($user->autopay_next_attempt_at && $user->autopay_next_attempt_at->isFuture()) {
                return null;
            }

            $attemptNumber = (int) $locked->autopay_attempts + 1;
            $reference = 'AUTO-RPY-' . (int) $locked->id . '-A' . $attemptNumber;
            $metadata = (array) ($locked->metadata ?? []);
            $metadata['autopay_processing'] = [
                'reference' => $reference,
                'attempt' => $attemptNumber,
                'reserved_at' => now()->toDateTimeString(),
            ];

            $locked->forceFill([
                'autopay_status' => 'processing',
                'autopay_last_attempt_at' => now(),
                'autopay_next_attempt_at' => now()->addMinutes(15),
                'metadata' => $metadata,
            ])->save();

            return [
                'repayment' => $locked->fresh(['user', 'lease.vouchers.fuelStation']),
                'user' => $user->fresh(),
                'reference' => $reference,
            ];
        });
    }

    private function buildVoucherTicketPayload(User $user, Repayment $repayment): array
    {
        $repayment->loadMissing('lease.vouchers.fuelStation');

        $voucher = $repayment->lease?->vouchers?->sortByDesc('id')->first();
        $voucherCode = (string) ($voucher?->code ?: ($repayment->lease_id ? ('LEASE-' . (string) $repayment->lease_id) : ('REPAYMENT-' . (string) $repayment->id)));
        $voucherQrValue = (string) ($voucher?->qr_code ?: $voucherCode);
        $voucherQrImage = $voucherQrValue !== ''
            ? ('https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=10&ecc=H&format=png&data=' . urlencode($voucherQrValue))
            : null;

        $stationName = $voucher?->fuelStation?->name
            ?? ($repayment->lease?->vouchers?->first()?->fuelStation?->name ?? 'N/A');
        $stationCompany = trim((string) ($voucher?->fuelStation?->company ?? $repayment->lease?->vouchers?->first()?->fuelStation?->company ?? ''));
        $stationLogoUrl = StationBrandAssets::resolveLogoUrl((string) $stationName, $stationCompany);

        $pendingCount = 0;
        $pendingAmount = 0.0;
        $overdueCount = 0;
        $overdueAmount = 0.0;
        $overdueSince = null;
        $paystackUrl = null;
        $nextDueDate = $repayment->due_date
            ? \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y')
            : 'N/A';

        if ($repayment->lease_id) {
            $pendingForLease = Repayment::query()
                ->visibleInSystem()
                ->where('user_id', (int) $user->id)
                ->where('lease_id', (int) $repayment->lease_id)
                ->whereIn('status', ['pending', 'overdue'])
                ->get(['id', 'amount', 'due_date', 'status']);

            $pendingCount = $pendingForLease->count();
            $pendingAmount = (float) $pendingForLease->sum('amount');

            $today = now()->toDateString();
            $overdue = $pendingForLease->filter(fn ($r) => $r->due_date && (string) $r->due_date < $today);
            $upcoming = $pendingForLease->filter(fn ($r) => $r->due_date && (string) $r->due_date >= $today);

            $overdueCount = $overdue->count();
            $overdueAmount = (float) $overdue->sum('amount');
            $oldestOverdue = $overdue->sortBy('due_date')->first();
            if ($oldestOverdue && $oldestOverdue->due_date) {
                $overdueSince = \Illuminate\Support\Carbon::parse($oldestOverdue->due_date)->format('d M Y');
            }

            $nextUpcoming = $upcoming->sortBy('due_date')->first();
            if ($nextUpcoming && $nextUpcoming->due_date) {
                $nextDueDate = \Illuminate\Support\Carbon::parse($nextUpcoming->due_date)->format('d M Y');
            } elseif ($overdueCount > 0) {
                $nextDueDate = 'Due now';
            } else {
                $nextDueDate = 'N/A';
            }

            $payNowTarget = $overdueCount > 0
                ? $overdue->sortBy('due_date')->first()
                : $upcoming->sortBy('due_date')->first();

            if ($payNowTarget && (int) ($payNowTarget->id ?? 0) > 0) {
                $paystackUrl = URL::temporarySignedRoute(
                    'driver.repayments.request.pay_now',
                    now()->addDays(7),
                    ['repayment' => (int) $payNowTarget->id]
                );
            }
        }

        return [
            'voucher_code' => $voucherCode,
            'voucher_qr_image' => $voucherQrImage,
            'station_name' => Str::limit((string) $stationName, 32),
            'station_logo_url' => $stationLogoUrl,
            'pending_count' => $pendingCount,
            'pending_amount_display' => number_format(abs($pendingAmount), 2),
            'next_due_date' => $nextDueDate,
            'overdue_count' => $overdueCount,
            'overdue_amount_display' => number_format(abs($overdueAmount), 2),
            'overdue_since' => $overdueSince,
            'paystack_url' => $paystackUrl,
            'driver_name' => Str::limit((string) ($user->name ?? 'Driver'), 26),
            'lease_id' => $repayment->lease_id ? (string) $repayment->lease_id : '--',
        ];
    }
}
