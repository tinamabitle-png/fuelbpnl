<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repayment;
use App\Models\RepaymentPaymentAttempt;
use App\Services\Flutterwave1VoucherService;
use App\Services\RepaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Repayment1VoucherController extends Controller
{
    private function normalizeZaVoucherPhone(string $phone): string
    {
        // Flutterwave 1Voucher docs show phone_number like "27xxxxxxxx7" (no '+').
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '';
        }

        // +27XXXXXXXXX -> 27XXXXXXXXX
        if (str_starts_with($digits, '27') && strlen($digits) === 11) {
            return $digits;
        }

        // 0XXXXXXXXX -> 27XXXXXXXXX
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '27' . substr($digits, 1);
        }

        // If it's 9 digits (missing country code), assume ZA.
        if (strlen($digits) === 9) {
            return '27' . $digits;
        }

        // Fall back to sending digits only.
        return $digits;
    }

    /**
     * Pay the next N days of repayments (default 7) using Flutterwave 1Voucher.
     */
    public function payWeek(Request $request, Flutterwave1VoucherService $flutterwave, RepaymentSettlementService $settler)
    {
        $user = $request->user();

        $validated = $request->validate([
            'pin' => 'required|string|min:4|max:64',
            'days' => 'nullable|integer|min:1|max:14',
            'include_overdue' => 'nullable|boolean',
        ]);

        if (!$flutterwave->configured()) {
            return response()->json([
                'success' => false,
                'message' => 'Flutterwave is not configured.',
            ], 422);
        }

        $days = (int) ($validated['days'] ?? 7);
        $includeOverdue = (bool) ($validated['include_overdue'] ?? true);

        $now = now();
        $to = $now->copy()->addDays($days);

        $currency = (string) config('services.flutterwave.one_voucher_currency', 'ZAR');
        $email = trim((string) ($user->email ?? ''));
        $phone = $this->normalizeZaVoucherPhone((string) ($user->phone ?? ''));
        if ($email === '' || $phone === '') {
            return response()->json([
                'success' => false,
                'message' => 'Email and phone are required to pay with 1Voucher.',
            ], 422);
        }

        $txRef = '1VOUCHER-WEEK-' . (int) $user->id . '-' . $now->format('YmdHis') . '-' . Str::upper(Str::random(6));

        /** @var RepaymentPaymentAttempt $attempt */
        $attempt = DB::transaction(function () use ($user, $to, $now, $includeOverdue, $txRef, $currency, $days) {
            $repaymentsQuery = Repayment::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'overdue'])
                ->whereDate('due_date', '<=', $to->toDateString())
                ->where(function ($query) {
                    $query->whereNull('autopay_status')
                        ->orWhere('autopay_status', '!=', 'processing_1voucher')
                        ->orWhereNull('autopay_next_attempt_at')
                        ->orWhere('autopay_next_attempt_at', '<=', now());
                });

            if (!$includeOverdue) {
                $repaymentsQuery->whereDate('due_date', '>=', $now->toDateString());
            }

            $repayments = $repaymentsQuery
                ->orderBy('due_date')
                ->lockForUpdate()
                ->get();

            if ($repayments->isEmpty()) {
                return null;
            }

            $repaymentIds = $repayments->pluck('id')->all();
            Repayment::query()
                ->whereIn('id', $repaymentIds)
                ->update([
                    'autopay_status' => 'processing_1voucher',
                    'autopay_next_attempt_at' => now()->addMinutes(30),
                    'autopay_last_attempt_at' => now(),
                ]);

            return RepaymentPaymentAttempt::create([
                'user_id' => $user->id,
                'provider' => 'flutterwave',
                'method' => '1voucher',
                'tx_ref' => $txRef,
                'amount' => (float) $repayments->sum('amount'),
                'currency' => strtoupper($currency),
                'status' => 'pending',
                'repayment_ids' => $repaymentIds,
                'meta' => [
                    'days' => $days,
                    'include_overdue' => $includeOverdue,
                ],
            ]);
        });

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'No repayments due within the selected period, or a payment is already processing.',
            ], 422);
        }

        try {
            $charge = $flutterwave->charge(
                $attempt->tx_ref,
                $attempt->amount,
                $attempt->currency,
                $email,
                $phone,
                (string) $validated['pin'],
            );
        } catch (\Throwable $e) {
            $attempt->update([
                'status' => 'failed',
                'provider_response' => ['error' => $e->getMessage()],
            ]);
            $this->releaseProcessingRepayments($attempt);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => ['tx_ref' => $attempt->tx_ref],
            ], 422);
        }

        $status = $flutterwave->extractStatus($charge);
        $flwRef = $flutterwave->extractFlwRef($charge);
        $changeVoucher = $flutterwave->extractChangeVoucher($charge);

        $attempt->update([
            'flw_ref' => $flwRef !== '' ? $flwRef : null,
            'provider_response' => ['charge' => $charge],
            'status' => in_array($status, ['successful', 'success'], true) ? 'successful' : 'failed',
            'meta' => array_merge((array) ($attempt->meta ?? []), [
                'change_voucher' => $changeVoucher,
            ]),
        ]);

        if ($attempt->status !== 'successful') {
            $this->releaseProcessingRepayments($attempt);
            return response()->json([
                'success' => false,
                'message' => '1Voucher payment was not successful.',
                'data' => [
                    'tx_ref' => $attempt->tx_ref,
                    'status' => $status,
                ],
            ], 422);
        }

        // Confirmation: verify final status by reference before providing value.
        try {
            $verify = $flutterwave->verifyByReference($attempt->tx_ref);
            $verifyStatus = $flutterwave->extractStatus($verify);
            $verifyFlwRef = $flutterwave->extractFlwRef($verify);

            $attempt->update([
                'flw_ref' => $attempt->flw_ref ?: ($verifyFlwRef !== '' ? $verifyFlwRef : null),
                'provider_response' => array_merge((array) ($attempt->provider_response ?? []), ['verify' => $verify]),
            ]);

            if (!in_array($verifyStatus, ['successful', 'success'], true)) {
                $attempt->update(['status' => 'pending']);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment initiated. Pending confirmation.',
                    'data' => [
                        'tx_ref' => $attempt->tx_ref,
                        'flw_ref' => $attempt->flw_ref,
                        'status' => $verifyStatus,
                        'change_voucher' => $changeVoucher,
                    ],
                ], 202);
            }
        } catch (\Throwable $e) {
            // If verification is temporarily unavailable, avoid double-settling. Client can retry/confirm later.
            $attempt->update([
                'status' => 'pending',
                'provider_response' => array_merge((array) ($attempt->provider_response ?? []), [
                    'verify_error' => $e->getMessage(),
                ]),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment initiated. Pending confirmation.',
                'data' => [
                    'tx_ref' => $attempt->tx_ref,
                    'flw_ref' => $attempt->flw_ref,
                    'change_voucher' => $changeVoucher,
                ],
            ], 202);
        }

        // Settle all included repayments idempotently.
        $reference = $attempt->flw_ref ?: $attempt->tx_ref;
        DB::transaction(function () use ($attempt, $settler, $reference) {
            $lockedAttempt = RepaymentPaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ((string) $lockedAttempt->status !== 'successful') {
                return;
            }

            $repayments = Repayment::query()
                ->where('user_id', $lockedAttempt->user_id)
                ->whereIn('id', (array) $lockedAttempt->repayment_ids)
                ->get();

            foreach ($repayments as $repayment) {
                $settler->settleRepayment($repayment, 'flutterwave_1voucher', $reference, [
                    'tx_ref' => (string) $lockedAttempt->tx_ref,
                    'flw_ref' => (string) ($lockedAttempt->flw_ref ?? ''),
                ]);
            }
        });

        $paidRepayments = Repayment::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $attempt->repayment_ids)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Weekly repayments paid with 1Voucher.',
            'data' => [
                'tx_ref' => $attempt->tx_ref,
                'flw_ref' => $attempt->flw_ref,
                'amount' => (float) $attempt->amount,
                'currency' => (string) $attempt->currency,
                'repayments' => $paidRepayments,
                'change_voucher' => $changeVoucher,
            ],
        ]);
    }

    /**
     * Confirm a pending 1Voucher weekly payment by tx_ref, then settle repayments if successful.
     */
    public function confirm(Request $request, Flutterwave1VoucherService $flutterwave, RepaymentSettlementService $settler)
    {
        $user = $request->user();

        $validated = $request->validate([
            'tx_ref' => 'required|string|max:80',
        ]);

        $txRef = (string) $validated['tx_ref'];

        /** @var RepaymentPaymentAttempt|null $attempt */
        $attempt = RepaymentPaymentAttempt::query()
            ->where('user_id', $user->id)
            ->where('tx_ref', $txRef)
            ->first();

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Payment attempt not found.',
            ], 404);
        }

        if ((string) $attempt->status === 'successful') {
            $this->settleSuccessfulAttempt($attempt, $settler);

            return response()->json([
                'success' => true,
                'message' => 'Payment already confirmed.',
                'data' => [
                    'tx_ref' => $attempt->tx_ref,
                    'flw_ref' => $attempt->flw_ref,
                    'status' => $attempt->status,
                ],
            ]);
        }

        try {
            $verify = $flutterwave->verifyByReference($attempt->tx_ref);
            $verifyStatus = $flutterwave->extractStatus($verify);
            $verifyFlwRef = $flutterwave->extractFlwRef($verify);

            $attempt->update([
                'flw_ref' => $attempt->flw_ref ?: ($verifyFlwRef !== '' ? $verifyFlwRef : null),
                'provider_response' => array_merge((array) ($attempt->provider_response ?? []), ['verify' => $verify]),
            ]);

            if (!in_array($verifyStatus, ['successful', 'success'], true)) {
                $attempt->update(['status' => 'pending']);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not successful yet.',
                    'data' => [
                        'tx_ref' => $attempt->tx_ref,
                        'flw_ref' => $attempt->flw_ref,
                        'status' => $verifyStatus,
                    ],
                ], 202);
            }
        } catch (\Throwable $e) {
            $attempt->update([
                'status' => 'pending',
                'provider_response' => array_merge((array) ($attempt->provider_response ?? []), [
                    'verify_error' => $e->getMessage(),
                ]),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Try again.',
                'data' => [
                    'tx_ref' => $attempt->tx_ref,
                ],
            ], 202);
        }

        // Settle now that verification is successful.
        $reference = $attempt->flw_ref ?: $attempt->tx_ref;
        DB::transaction(function () use ($attempt, $settler, $reference) {
            $lockedAttempt = RepaymentPaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ((string) $lockedAttempt->status === 'successful') {
                return;
            }

            $repayments = Repayment::query()
                ->where('user_id', $lockedAttempt->user_id)
                ->whereIn('id', (array) $lockedAttempt->repayment_ids)
                ->get();

            foreach ($repayments as $repayment) {
                $settler->settleRepayment($repayment, 'flutterwave_1voucher', $reference, [
                    'tx_ref' => (string) $lockedAttempt->tx_ref,
                    'flw_ref' => (string) ($lockedAttempt->flw_ref ?? ''),
                ]);
            }

            $lockedAttempt->update(['status' => 'successful']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed and repayments settled.',
            'data' => [
                'tx_ref' => $attempt->tx_ref,
                'flw_ref' => $attempt->flw_ref,
                'status' => 'successful',
            ],
        ]);
    }

    private function settleSuccessfulAttempt(RepaymentPaymentAttempt $attempt, RepaymentSettlementService $settler): void
    {
        $reference = $attempt->flw_ref ?: $attempt->tx_ref;

        DB::transaction(function () use ($attempt, $settler, $reference) {
            $lockedAttempt = RepaymentPaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ((string) $lockedAttempt->status !== 'successful') {
                return;
            }

            $repayments = Repayment::query()
                ->where('user_id', $lockedAttempt->user_id)
                ->whereIn('id', (array) $lockedAttempt->repayment_ids)
                ->get();

            foreach ($repayments as $repayment) {
                $settler->settleRepayment($repayment, 'flutterwave_1voucher', $reference, [
                    'tx_ref' => (string) $lockedAttempt->tx_ref,
                    'flw_ref' => (string) ($lockedAttempt->flw_ref ?? ''),
                ]);
            }
        });
    }

    private function releaseProcessingRepayments(RepaymentPaymentAttempt $attempt): void
    {
        Repayment::query()
            ->where('user_id', (int) $attempt->user_id)
            ->whereIn('id', (array) $attempt->repayment_ids)
            ->where('autopay_status', 'processing_1voucher')
            ->whereIn('status', ['pending', 'overdue'])
            ->update([
                'autopay_status' => null,
                'autopay_next_attempt_at' => null,
            ]);
    }
}
