<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Repayment;
use App\Models\RepaymentPaymentAttempt;
use App\Services\LunoService;
use App\Services\RepaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RepaymentCryptoController extends Controller
{
    public function show(Request $request, Repayment $repayment, LunoService $luno)
    {
        abort_unless(Auth::check(), 403);
        abort_unless((int) $repayment->user_id === (int) Auth::id(), 403);

        if (!$luno->enabled()) {
            return view('driver.repayments.crypto', [
                'repayment' => $repayment,
                'enabled' => false,
                'asset' => null,
                'address' => null,
                'qr' => null,
                'expectedAssetAmount' => null,
                'rate' => null,
                'pair' => null,
            ]);
        }

        $asset = strtoupper((string) $request->query('asset', 'XBT'));
        $asset = in_array($asset, ['XBT', 'ETH'], true) ? $asset : 'XBT';
        $pair = $asset === 'ETH' ? 'ETHZAR' : 'XBTZAR';

        $ticker = $luno->ticker($pair);
        $rate = (float) ($ticker['last_trade'] ?? 0);
        $rate = $rate > 0 ? $rate : 0.0;

        $amountZar = (float) $repayment->amount;
        $expectedAssetAmount = $rate > 0 ? ($amountZar / $rate) : null;

        $funding = $luno->fundingAddress($asset);

        return view('driver.repayments.crypto', [
            'repayment' => $repayment,
            'enabled' => true,
            'asset' => $asset,
            'pair' => $pair,
            'rate' => $rate,
            'expectedAssetAmount' => $expectedAssetAmount,
            'address' => (string) ($funding['address'] ?? ''),
            'qr' => (string) ($funding['qr_code_uri'] ?? ''),
            'accountId' => (string) ($funding['account_id'] ?? ''),
        ]);
    }

    public function confirm(Request $request, Repayment $repayment, LunoService $luno, RepaymentSettlementService $settlements)
    {
        abort_unless(Auth::check(), 403);
        abort_unless((int) $repayment->user_id === (int) Auth::id(), 403);

        if (!$luno->enabled()) {
            return redirect()
                ->back()
                ->with('error', 'Crypto payments are not enabled on this environment yet. Set LUNO_ENABLED=true and Luno API credentials in .env.');
        }

        $data = $request->validate([
            'asset' => ['required', 'string', 'in:XBT,ETH'],
            'txid' => ['required', 'string', 'min:10', 'max:140'],
        ]);

        $asset = (string) $data['asset'];
        $txid = trim((string) $data['txid']);
        $pair = $asset === 'ETH' ? 'ETHZAR' : 'XBTZAR';

        $ticker = $luno->ticker($pair);
        $rate = (float) ($ticker['last_trade'] ?? 0);
        $amountZar = (float) $repayment->amount;
        $expectedAssetAmount = $rate > 0 ? ($amountZar / $rate) : null;

        $funding = $luno->fundingAddress($asset);
        $accountId = (string) ($funding['account_id'] ?? '');
        $address = (string) ($funding['address'] ?? '');

        $txRef = sprintf('LUNO-RPY-%d-%s-%s', (int) $repayment->id, $asset, now()->format('YmdHis'));

        $attempt = RepaymentPaymentAttempt::create([
            'user_id' => (int) Auth::id(),
            'provider' => 'luno',
            'method' => strtolower($asset) === 'eth' ? 'eth' : 'btc',
            'tx_ref' => $txRef,
            'amount' => $amountZar,
            'currency' => 'ZAR',
            'status' => 'pending',
            'repayment_ids' => [$repayment->id],
            'meta' => [
                'asset' => $asset,
                'pair' => $pair,
                'rate_last_trade' => $rate,
                'expected_asset_amount' => $expectedAssetAmount,
                'txid' => $txid,
                'address' => $address,
                'account_id' => $accountId,
            ],
        ]);

        // Best-effort immediate verification. If it isn't visible yet, the scheduled poller will pick it up.
        try {
            if ($accountId !== '') {
                $transfers = $luno->listTransfers($accountId, 100);
                $list = (array) ($transfers['transfers'] ?? []);
                foreach ($list as $t) {
                    $tid = (string) ($t['transaction_id'] ?? '');
                    if ($tid === '' || $tid !== $txid) {
                        continue;
                    }
                    $incoming = (string) ($t['incoming'] ?? '');
                    $amount = (float) ($t['amount'] ?? 0);
                    $okIncoming = $incoming === '' ? true : filter_var($incoming, FILTER_VALIDATE_BOOLEAN);
                    $okAmount = $expectedAssetAmount === null ? true : ($amount + 1e-12) >= ((float) $expectedAssetAmount * 0.98);

                    if ($okIncoming && $okAmount) {
                        $attempt->update([
                            'status' => 'successful',
                            'provider_response' => $t,
                        ]);

                        $settlements->settleRepayment(
                            $repayment,
                            'luno_' . strtolower($asset),
                            $txRef,
                            ['txid' => $txid, 'asset' => $asset, 'transfer' => $t]
                        );

                        return redirect()
                            ->route('driver.repayments.index')
                            ->with('success', 'Payment received and repayment settled.');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Leave as pending; the poller can retry.
        }

        return redirect()
            ->route('driver.repayments.index')
            ->with('success', 'Transaction submitted. We will confirm it as soon as it reflects on-chain / in Luno.');
    }
}

