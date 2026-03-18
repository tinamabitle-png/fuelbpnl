<?php

namespace App\Console\Commands;

use App\Models\Repayment;
use App\Models\RepaymentPaymentAttempt;
use App\Services\LunoService;
use App\Services\RepaymentSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LunoPollRepaymentPayments extends Command
{
    protected $signature = 'luno:poll-repayment-payments {--limit=250 : Max attempts to process per run}';

    protected $description = 'Poll Luno transfers to confirm pending crypto repayment attempts';

    public function handle(LunoService $luno, RepaymentSettlementService $settlements): int
    {
        if (!$luno->enabled()) {
            $this->info('Luno disabled.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $limit = $limit > 0 ? min($limit, 2000) : 250;

        $attempts = RepaymentPaymentAttempt::query()
            ->where('provider', 'luno')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        $confirmed = 0;

        foreach ($attempts as $attempt) {
            $processed++;

            $meta = (array) ($attempt->meta ?? []);
            $txid = trim((string) ($meta['txid'] ?? ''));
            $asset = strtoupper((string) ($meta['asset'] ?? ''));
            $accountId = (string) ($meta['account_id'] ?? '');
            $expected = isset($meta['expected_asset_amount']) ? (float) $meta['expected_asset_amount'] : null;

            if ($txid === '' || $accountId === '' || !in_array($asset, ['XBT', 'ETH'], true)) {
                continue;
            }

            try {
                $transfers = $luno->listTransfers($accountId, 100);
                $list = (array) ($transfers['transfers'] ?? []);
            } catch (\Throwable $e) {
                continue;
            }

            $match = null;
            foreach ($list as $t) {
                $tid = (string) ($t['transaction_id'] ?? '');
                if ($tid === $txid) {
                    $match = $t;
                    break;
                }
            }

            if ($match === null) {
                continue;
            }

            $incoming = (string) ($match['incoming'] ?? '');
            $amount = (float) ($match['amount'] ?? 0);
            $okIncoming = $incoming === '' ? true : filter_var($incoming, FILTER_VALIDATE_BOOLEAN);
            $okAmount = $expected === null ? true : ($amount + 1e-12) >= ((float) $expected * 0.98);
            if (!$okIncoming || !$okAmount) {
                continue;
            }

            DB::transaction(function () use ($attempt, $match, $asset, $txid, $settlements, &$confirmed) {
                $locked = RepaymentPaymentAttempt::whereKey($attempt->id)->lockForUpdate()->first();
                if (!$locked || (string) $locked->status !== 'pending') {
                    return;
                }

                $locked->update([
                    'status' => 'successful',
                    'provider_response' => $match,
                ]);

                $repaymentIds = (array) ($locked->repayment_ids ?? []);
                foreach ($repaymentIds as $rid) {
                    $repayment = Repayment::whereKey((int) $rid)->first();
                    if (!$repayment) {
                        continue;
                    }
                    $settlements->settleRepayment(
                        $repayment,
                        'luno_' . strtolower($asset),
                        (string) $locked->tx_ref,
                        ['txid' => $txid, 'asset' => $asset, 'transfer' => $match]
                    );
                }

                $confirmed++;
            });
        }

        $this->info("Processed: {$processed}, Confirmed: {$confirmed}");
        return self::SUCCESS;
    }
}

