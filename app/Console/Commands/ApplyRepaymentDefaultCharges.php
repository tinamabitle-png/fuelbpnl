<?php

namespace App\Console\Commands;

use App\Models\Repayment;
use App\Services\AuditTrailService;
use App\Services\RepaymentPolicyService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplyRepaymentDefaultCharges extends Command
{
    protected $signature = 'repayments:apply-default-charges {--limit=500}';

    protected $description = 'Apply default fees and default interest for overdue repayments without altering core repayment logic.';

    public function handle(RepaymentPolicyService $policyService): int
    {
        $policy = $policyService->get();
        $feesEnabled = (bool) ($policy['enable_default_fees'] ?? true);
        $interestEnabled = (bool) ($policy['enable_default_interest'] ?? true);

        if (!$feesEnabled && !$interestEnabled) {
            $this->info('Default fees and default interest are disabled. Nothing to apply.');
            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $today = now()->startOfDay();

        $leaseIds = Repayment::query()
            ->where('repayment_type', 'regular')
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_date', '<', $today->toDateString())
            ->orderBy('lease_id')
            ->limit($limit)
            ->pluck('lease_id')
            ->unique()
            ->values();

        $createdFees = 0;
        $createdInterest = 0;

        foreach ($leaseIds as $leaseId) {
            [$feesCount, $interestCount] = DB::transaction(function () use (
                $leaseId,
                $today,
                $policy,
                $feesEnabled,
                $interestEnabled
            ) {
                $feesCreated = 0;
                $interestCreated = 0;

                $repayments = Repayment::query()
                    ->where('lease_id', $leaseId)
                    ->lockForUpdate()
                    ->get();

                $regularRepayments = $repayments->where('repayment_type', 'regular');
                if ($regularRepayments->isEmpty()) {
                    return [0, 0];
                }

                $overdueRegular = $regularRepayments->filter(
                    fn (Repayment $r) => in_array((string) $r->status, ['pending', 'overdue'], true)
                        && Carbon::parse($r->due_date)->lt($today)
                );
                if ($overdueRegular->isEmpty()) {
                    return [0, 0];
                }

                $installmentCount = $regularRepayments->count();

                if ($feesEnabled) {
                    $payIn4Fee = round((float) ($policy['default_fee_pay_in_4_weekly'] ?? 95), 2);
                    $payIn4MaxCharges = max(1, (int) ($policy['default_fee_pay_in_4_max_charges'] ?? 3));
                    $payIn3Fee = round((float) ($policy['default_fee_pay_in_3_once'] ?? 125), 2);

                    foreach ($overdueRegular as $baseRepayment) {
                        $existingBaseFees = $repayments
                            ->where('repayment_type', 'default_fee')
                            ->where('base_repayment_id', $baseRepayment->id);

                        if ($installmentCount === 4 && $payIn4Fee > 0) {
                            $daysOverdue = max(
                                1,
                                Carbon::parse($baseRepayment->due_date)->startOfDay()->diffInDays($today)
                            );
                            $chargesDue = min($payIn4MaxCharges, intdiv($daysOverdue, 7) + 1);
                            $alreadyCharged = $existingBaseFees->count();
                            $toCreate = max(0, $chargesDue - $alreadyCharged);

                            for ($i = 0; $i < $toCreate; $i++) {
                                $feeRepayment = Repayment::create([
                                    'lease_id' => $baseRepayment->lease_id,
                                    'user_id' => $baseRepayment->user_id,
                                    'amount' => $payIn4Fee,
                                    'repayment_type' => 'default_fee',
                                    'base_repayment_id' => $baseRepayment->id,
                                    'due_date' => $today->toDateString(),
                                    'charged_for_date' => $today->toDateString(),
                                    'status' => 'pending',
                                    'metadata' => [
                                        'policy' => 'pay_in_4_weekly_default_fee',
                                        'base_repayment_due_date' => (string) $baseRepayment->due_date,
                                        'max_charges' => $payIn4MaxCharges,
                                        'charge_sequence' => $alreadyCharged + $i + 1,
                                    ],
                                ]);

                                $feesCreated++;
                                AuditTrailService::record(
                                    'repayment_default_fee_created',
                                    $feeRepayment,
                                    [],
                                    [
                                        'lease_id' => (int) $baseRepayment->lease_id,
                                        'base_repayment_id' => (int) $baseRepayment->id,
                                        'amount' => (float) $payIn4Fee,
                                        'policy' => 'pay_in_4_weekly_default_fee',
                                    ],
                                    'Weekly default fee created for overdue Pay in 4 installment'
                                );
                            }
                        } elseif ($installmentCount === 3 && $payIn3Fee > 0 && $existingBaseFees->isEmpty()) {
                            $feeRepayment = Repayment::create([
                                'lease_id' => $baseRepayment->lease_id,
                                'user_id' => $baseRepayment->user_id,
                                'amount' => $payIn3Fee,
                                'repayment_type' => 'default_fee',
                                'base_repayment_id' => $baseRepayment->id,
                                'due_date' => $today->toDateString(),
                                'charged_for_date' => $today->toDateString(),
                                'status' => 'pending',
                                'metadata' => [
                                    'policy' => 'pay_in_3_missed_installment_fee',
                                    'base_repayment_due_date' => (string) $baseRepayment->due_date,
                                ],
                            ]);

                            $feesCreated++;
                            AuditTrailService::record(
                                'repayment_default_fee_created',
                                $feeRepayment,
                                [],
                                [
                                    'lease_id' => (int) $baseRepayment->lease_id,
                                    'base_repayment_id' => (int) $baseRepayment->id,
                                    'amount' => (float) $payIn3Fee,
                                    'policy' => 'pay_in_3_missed_installment_fee',
                                ],
                                'One-time default fee created for overdue Pay in 3 installment'
                            );
                        }
                    }
                }

                if ($interestEnabled) {
                    $monthlyRate = (float) ($policy['default_interest_monthly_rate'] ?? 2.0);
                    if ($monthlyRate > 0) {
                        $firstOverdue = $overdueRegular->sortBy('due_date')->first();
                        if ($firstOverdue) {
                            $daysOverdue = Carbon::parse($firstOverdue->due_date)->startOfDay()->diffInDays($today);
                            $cyclesDue = intdiv(max(0, $daysOverdue), 30);

                            $existingInterestCount = $repayments
                                ->where('repayment_type', 'default_interest')
                                ->count();

                            $toCreate = max(0, $cyclesDue - $existingInterestCount);
                            if ($toCreate > 0) {
                                $overduePrincipal = round(
                                    (float) $overdueRegular->sum(fn (Repayment $r) => (float) $r->amount),
                                    2
                                );

                                for ($i = 0; $i < $toCreate; $i++) {
                                    $interestAmount = round($overduePrincipal * ($monthlyRate / 100), 2);
                                    if ($interestAmount <= 0) {
                                        continue;
                                    }

                                    $interestRepayment = Repayment::create([
                                        'lease_id' => $firstOverdue->lease_id,
                                        'user_id' => $firstOverdue->user_id,
                                        'amount' => $interestAmount,
                                        'repayment_type' => 'default_interest',
                                        'base_repayment_id' => null,
                                        'due_date' => $today->toDateString(),
                                        'charged_for_date' => $today->toDateString(),
                                        'status' => 'pending',
                                        'metadata' => [
                                            'policy' => 'monthly_default_interest',
                                            'monthly_rate' => $monthlyRate,
                                            'overdue_principal' => $overduePrincipal,
                                            'cycle_number' => $existingInterestCount + $i + 1,
                                        ],
                                    ]);

                                    $interestCreated++;
                                    AuditTrailService::record(
                                        'repayment_default_interest_created',
                                        $interestRepayment,
                                        [],
                                        [
                                            'lease_id' => (int) $firstOverdue->lease_id,
                                            'amount' => (float) $interestAmount,
                                            'monthly_rate' => (float) $monthlyRate,
                                            'overdue_principal' => (float) $overduePrincipal,
                                        ],
                                        'Monthly default interest created on overdue principal'
                                    );
                                }
                            }
                        }
                    }
                }

                return [$feesCreated, $interestCreated];
            });

            $createdFees += $feesCount;
            $createdInterest += $interestCount;
        }

        Cache::put('repayments:default-charges:last-run', [
            'at' => now()->toDateTimeString(),
            'lease_count' => $leaseIds->count(),
            'created_fees' => $createdFees,
            'created_interest' => $createdInterest,
            'policy' => $policy,
        ], now()->addDays(14));

        $this->info("Default charges applied. Fees: {$createdFees}, interest: {$createdInterest}.");
        return self::SUCCESS;
    }
}
