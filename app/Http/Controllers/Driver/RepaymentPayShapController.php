<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Repayment;
use App\Models\RepaymentPaymentAttempt;
use App\Services\AuditTrailService;
use App\Services\PeachPaymentsService;
use App\Services\RepaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RepaymentPayShapController extends Controller
{
    public function __construct(
        private PeachPaymentsService $peachPayments,
        private RepaymentSettlementService $repaymentSettlementService
    ) {}

    public function show(Request $request, Repayment $repayment)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('driver'), 403);

        if ((int) $repayment->user_id !== (int) $user->id) {
            abort(403);
        }

        if (!in_array((string) $repayment->status, ['pending', 'overdue'], true)) {
            return redirect()->route('driver.repayments.index')->with('error', 'Repayment has already been processed.');
        }

        $banks = [
            'NEDBANK' => 'Nedbank',
            'FIRSTNATIONALBANK' => 'FNB',
            'DISCOVERYBANK' => 'Discovery Bank',
            'TYMEBANK' => 'TymeBank',
        ];

        return view('driver.repayments.payshap', [
            'repayment' => $repayment,
            'banks' => $banks,
            'defaultBank' => 'NEDBANK',
            'defaultPhone' => (string) ($user->phone ?? ''),
            'peachEnabled' => $this->peachPayments->isEnabled(),
        ]);
    }

    public function init(Request $request, Repayment $repayment)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('driver'), 403);

        if ((int) $repayment->user_id !== (int) $user->id) {
            abort(403);
        }

        if (!$this->peachPayments->isEnabled()) {
            return back()->with('error', 'PayShap is currently disabled.');
        }

        if (!in_array((string) $repayment->status, ['pending', 'overdue'], true)) {
            return back()->with('error', 'Repayment has already been processed.');
        }

        $validated = $request->validate([
            'bank' => ['required', 'in:NEDBANK,FIRSTNATIONALBANK,DISCOVERYBANK,TYMEBANK'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $bank = (string) $validated['bank'];
        $phone = $this->normalizePhone((string) $validated['phone']);
        if ($phone === '') {
            return back()->with('error', 'Invalid phone number.');
        }

        $virtualAccountId = $this->toPayShapVirtualAccountId($phone);
        if ($virtualAccountId === '') {
            return back()->with('error', 'Phone number must be a valid South African mobile number.');
        }

        [$givenName, $surname] = $this->splitName((string) ($user->name ?? ''));
        $email = trim((string) ($user->email ?? ''));
        if ($email === '') {
            return back()->with('error', 'Your account is missing an email address.');
        }

        $txRef = $this->makeTxRef((int) $user->id, (int) $repayment->id);
        $returnUrl = $this->absoluteReturnUrl($request, $txRef);

        $attempt = RepaymentPaymentAttempt::create([
            'user_id' => $user->id,
            'provider' => 'peach',
            'method' => 'payshap',
            'tx_ref' => $txRef,
            'amount' => (float) $repayment->amount,
            'currency' => 'ZAR',
            'status' => 'pending',
            'repayment_ids' => [$repayment->id],
            'meta' => [
                'bank' => $bank,
                'phone' => $phone,
                'virtual_account_id' => $virtualAccountId,
                'repayment_id' => $repayment->id,
            ],
        ]);

        try {
            $response = $this->peachPayments->createPayShapPayment([
                'amount' => number_format((float) $repayment->amount, 2, '.', ''),
                'currency' => 'ZAR',
                'merchant_transaction_id' => $txRef,
                'shopper_result_url' => $returnUrl,
                'bank' => $bank,
                'virtual_account_id' => $virtualAccountId,
                'given_name' => $givenName !== '' ? $givenName : 'Bwiser',
                'surname' => $surname !== '' ? $surname : 'User',
                'email' => $email,
                'mobile' => $phone,
            ]);

            $uniqueId = $this->peachPayments->extractUniqueId($response);
            $redirectUrl = $this->peachPayments->extractRedirectUrl($response);

            $attempt->forceFill([
                'provider_response' => $response,
                'meta' => array_merge((array) ($attempt->meta ?? []), [
                    'unique_id' => $uniqueId,
                    'redirect_url' => $redirectUrl,
                ]),
            ])->save();

            AuditTrailService::record(
                'repayment_payshap_initialized',
                $repayment,
                [],
                [
                    'tx_ref' => $txRef,
                    'unique_id' => $uniqueId,
                    'bank' => $bank,
                    'amount' => (float) $repayment->amount,
                ],
                'Repayment PayShap request initialized'
            );

            if ($redirectUrl !== '') {
                return redirect()->away($redirectUrl);
            }

            return redirect()
                ->route('driver.repayments.index')
                ->with('success', 'PayShap request sent. Approve it in your banking app, then come back to confirm.');
        } catch (\Throwable $e) {
            $attempt->forceFill([
                'status' => 'failed',
                'provider_response' => array_merge((array) ($attempt->provider_response ?? []), [
                    'exception' => $e->getMessage(),
                ]),
            ])->save();

            return back()->with('error', 'Failed to initiate PayShap: ' . $e->getMessage());
        }
    }

    public function handleReturn(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('driver'), 403);

        $txRef = trim((string) $request->query('tx_ref', ''));
        if ($txRef === '') {
            return redirect()->route('driver.repayments.index')->with('error', 'Missing PayShap reference.');
        }

        $attempt = RepaymentPaymentAttempt::query()
            ->where('tx_ref', $txRef)
            ->where('user_id', $user->id)
            ->where('provider', 'peach')
            ->where('method', 'payshap')
            ->first();

        if (!$attempt) {
            return redirect()->route('driver.repayments.index')->with('error', 'Could not find PayShap attempt for this reference.');
        }

        $repaymentId = (int) (($attempt->meta['repayment_id'] ?? null) ?: 0);
        $repayment = $repaymentId > 0 ? Repayment::query()->find($repaymentId) : null;
        if (!$repayment || (int) $repayment->user_id !== (int) $user->id) {
            return redirect()->route('driver.repayments.index')->with('error', 'Repayment not found for this PayShap attempt.');
        }

        $uniqueId = trim((string) ($attempt->meta['unique_id'] ?? ''));
        if ($uniqueId === '') {
            return redirect()->route('driver.repayments.index')->with('error', 'PayShap attempt is missing a provider payment id.');
        }

        try {
            $statusRes = $this->peachPayments->getPayment($uniqueId);
            $classified = $this->peachPayments->classifyStatus($statusRes);

            $attempt->forceFill([
                'provider_response' => $statusRes,
                'status' => $classified === 'successful' ? 'successful' : ($classified === 'failed' ? 'failed' : 'pending'),
            ])->save();

            if ($classified === 'successful') {
                $this->repaymentSettlementService->settleRepayment(
                    $repayment,
                    'peach_payshap',
                    $uniqueId,
                    [
                        'source' => 'payshap_return',
                        'tx_ref' => $txRef,
                        'provider' => 'peach',
                    ]
                );

                return redirect()->route('driver.repayments.index')->with('success', 'PayShap repayment received successfully.');
            }

            if ($classified === 'pending') {
                return redirect()->route('driver.repayments.index')->with('status', 'PayShap payment is still pending. Please approve it in your banking app and retry.');
            }

            return redirect()->route('driver.repayments.index')->with('error', 'PayShap payment failed or was declined.');
        } catch (\Throwable $e) {
            return redirect()->route('driver.repayments.index')->with('error', 'PayShap verification failed: ' . $e->getMessage());
        }
    }

    private function makeTxRef(int $userId, int $repaymentId): string
    {
        $suffix = strtoupper(Str::random(6));
        return substr("PSH-RPY-{$repaymentId}-{$userId}-" . now()->format('YmdHis') . "-{$suffix}", 0, 80);
    }

    private function normalizePhone(string $phone): string
    {
        $clean = trim($phone);
        $clean = preg_replace('/[^\d+]/', '', $clean) ?? $clean;

        if (str_starts_with($clean, '+27')) return $clean;
        if (str_starts_with($clean, '27')) return '+' . $clean;
        if (str_starts_with($clean, '0')) return '+27' . substr($clean, 1);

        return $clean;
    }

    private function toPayShapVirtualAccountId(string $normalizedPhone): string
    {
        $digits = preg_replace('/\D+/', '', $normalizedPhone) ?: '';
        if (str_starts_with($digits, '27')) {
            $digits = substr($digits, 2);
        }
        // Expect SA mobile 9 digits (e.g. 72xxxxxxx)
        if (!preg_match('/^[6-8][0-9]{8}$/', $digits)) {
            return '';
        }

        return '+27-' . $digits;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $parts = explode(' ', $name);
        $first = trim((string) ($parts[0] ?? ''));
        $last = count($parts) > 1 ? trim(implode(' ', array_slice($parts, 1))) : '';
        return [$first, $last];
    }

    private function absoluteReturnUrl(Request $request, string $txRef): string
    {
        // Use absolute URL based on current host to support dev/staging/prod.
        return $request->getSchemeAndHttpHost() . route('driver.repayments.payshap.return', [], false) . '?tx_ref=' . urlencode($txRef);
    }
}

