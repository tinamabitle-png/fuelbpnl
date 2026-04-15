<?php

namespace App\Services;

use App\Events\VoucherStatusChanged;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Repayment;
use App\Models\UssdRedemptionEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AfricasTalkingUssdService
{
    public function handle(array $input): string
    {
        if (!$this->isAuthorized($input)) {
            return 'END Unauthorized USSD request.';
        }

        $sessionId = trim((string) ($input['sessionId'] ?? $input['session_id'] ?? ''));
        $serviceCode = trim((string) ($input['serviceCode'] ?? $input['service_code'] ?? ''));
        $networkCode = trim((string) ($input['networkCode'] ?? $input['network_code'] ?? ''));
        $phoneRaw = trim((string) ($input['phoneNumber'] ?? $input['phone_number'] ?? ''));
        $text = trim((string) ($input['text'] ?? ''));
        $steps = $this->splitSteps($text);

        $phoneNormalized = $this->normalizePhone($phoneRaw);
        if ($phoneNormalized === '') {
            return 'END Missing phone number.';
        }

        if (count($steps) === 0) {
            return "CON Fuel Voucher USSD\n1. Redeem Voucher\n2. Help";
        }

        $command = strtolower($steps[0]);
        if (in_array($command, ['2', 'help'], true)) {
            return "END Enter your voucher number or voucher code and confirm redemption.\nContact support if voucher is not found.";
        }

        if (!in_array($command, ['1', 'redeem'], true)) {
            return 'END Invalid option.';
        }

        $secondStep = trim((string) ($steps[1] ?? ''));
        $isAutoSelectionChoice = in_array($secondStep, ['1', '2', '3'], true) && count($steps) === 2;

        $pendingEvent = $isAutoSelectionChoice
            ? $this->findPendingEventForSession($sessionId, $phoneNormalized)
            : null;

        $voucherCode = $pendingEvent?->voucher_code
            ? strtoupper(trim((string) $pendingEvent->voucher_code))
            : strtoupper($secondStep);

        $voucher = $voucherCode !== ''
            ? $this->findVoucherByIdentifierAndPhone($voucherCode, $phoneNormalized)
            : $this->findLatestApprovedVoucherByPhone($phoneNormalized);

        if (!$voucher && $voucherCode === '') {
            return 'END No approved, unexpired voucher available for this driver.';
        }

        if ($voucher) {
            $voucherCode = (string) $voucher->code;
        }

        $event = $this->resolveOrCreateEvent(
            $sessionId,
            $serviceCode,
            $networkCode,
            $phoneRaw,
            $phoneNormalized,
            $text,
            $voucherCode
        );

        if (!$voucher) {
            $event->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Voucher not found for this driver.',
            ]);
            return 'END No matching voucher found for this driver.';
        }

        $event->update([
            'user_id' => $voucher->user_id,
            'fuel_station_id' => $voucher->fuel_station_id,
            'fuel_voucher_id' => $voucher->id,
            'merchant_user_id' => $voucher->fuelStation?->owner_id,
        ]);

        if ($voucher->status !== 'approved') {
            $event->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Voucher must be approved before USSD redemption.',
            ]);
            return "END Voucher {$voucher->code} is {$voucher->status} and cannot be redeemed.";
        }

        if ($voucher->expires_at && now()->gte($voucher->expires_at)) {
            $voucher->update(['status' => 'expired']);
            $event->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Voucher expired and cannot be redeemed.',
            ]);
            return 'END Voucher expired and cannot be redeemed.';
        }

        $choiceStep = $isAutoSelectionChoice
            ? $secondStep
            : trim((string) ($steps[2] ?? ''));

        if ($choiceStep === '') {
            $amount = number_format((float) $voucher->amount, 2);
            $stationName = (string) ($voucher->fuelStation?->name ?? 'Station');
            if ($this->airtimeSplitEnabled()) {
                $splitDetails = $this->calculateSplitAmounts((float) $voucher->amount);
                if ($splitDetails !== null) {
                    $airtimeFormatted = number_format($splitDetails['airtime_amount'], 2);
                    return "CON Voucher {$voucher->code}\nAmount: R{$amount}\nStation: {$stationName}\n1. Fuel only\n2. Fuel + Airtime (R{$airtimeFormatted} airtime)\n3. Cancel";
                }
            }

            return "CON Voucher {$voucher->code}\nAmount: R{$amount}\nStation: {$stationName}\n1. Fuel only\n2. Cancel";
        }

        $isSplitChoice = $choiceStep === '2' && $this->airtimeSplitEnabled();
        $isCancelChoice = $this->airtimeSplitEnabled() ? $choiceStep === '3' : $choiceStep === '2';

        if (!$isSplitChoice && !$isCancelChoice && $choiceStep !== '1') {
            $event->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Invalid USSD option selected.',
            ]);
            return 'END Invalid option.';
        }

        if ($isCancelChoice) {
            $event->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'error_message' => 'User cancelled redemption.',
            ]);
            return 'END Redemption cancelled.';
        }

        $airtimeAmount = 0.0;
        $fuelAmount = (float) $voucher->amount;
        $airtimeReference = null;
        $airtimeStatus = 'not_requested';

        if ($isSplitChoice) {
            $splitDetails = $this->calculateSplitAmounts((float) $voucher->amount);
            if ($splitDetails === null) {
                $event->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => 'Split redemption is unavailable for this voucher amount.',
                ]);
                return 'END Split redemption is unavailable for this voucher amount.';
            }

            $airtimeAmount = $splitDetails['airtime_amount'];
            $fuelAmount = $splitDetails['fuel_amount'];

            $airtime = $this->sendAirtime($phoneNormalized, $airtimeAmount, $voucher->code);
            if (!$airtime['success']) {
                $event->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => 'Airtime transfer failed: ' . $airtime['error'],
                ]);
                return 'END Airtime transfer failed. Please try again.';
            }

            $airtimeReference = $airtime['reference'];
            $airtimeStatus = 'sent';
        }

        try {
            DB::transaction(function () use (&$voucher, $fuelAmount, $airtimeAmount, $phoneNormalized, $airtimeReference, $airtimeStatus) {
                $lockedVoucher = FuelVoucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();
                if ($lockedVoucher->status !== 'approved') {
                    throw new \RuntimeException("Voucher must be APPROVED before redemption. Current status: {$lockedVoucher->status}.");
                }

                if ($lockedVoucher->expires_at && now()->gte($lockedVoucher->expires_at)) {
                    $lockedVoucher->update(['status' => 'expired']);
                    throw new \RuntimeException('Voucher expired and cannot be redeemed.');
                }

                $lockedStation = FuelStation::query()->whereKey($lockedVoucher->fuel_station_id)->lockForUpdate()->firstOrFail();
                $lockedStation->deductFromWallet($fuelAmount, 'USSD redemption: ' . $lockedVoucher->code);

                $lockedVoucher->update([
                    'status' => 'redeemed',
                    'redeemed_at' => now(),
                    'transaction_reference' => 'USSD-' . strtoupper(Str::random(8)),
                    'redeemed_fuel_amount' => round($fuelAmount, 2),
                    'redeemed_airtime_amount' => round($airtimeAmount, 2),
                    'airtime_phone' => $airtimeAmount > 0 ? $phoneNormalized : null,
                    'airtime_reference' => $airtimeReference,
                    'airtime_status' => $airtimeStatus,
                ]);

                $voucher = $lockedVoucher;
            });
        } catch (\Throwable $e) {
            $event->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            return 'END Redemption failed: ' . $e->getMessage();
        }

        if ($voucher->lease) {
            $voucher->lease->ensureRepaymentSchedule(now());
        }

        $fresh = $voucher->fresh([
            'user:id,name,phone',
            'fuelStation:id,name,city,address',
            'lease:id,total_amount,daily_repayment,due_date,status,repayment_frequency',
        ]);
        $payload = $this->voucherPayload($fresh);
        $payload['event'] = 'redeemed';
        $payload['transaction_status'] = 'successful';
        $payload['source'] = 'ussd_africastalking';
        $payload['ussd_session_id'] = $sessionId;

        try {
            event(new VoucherStatusChanged($payload));
        } catch (\Throwable $e) {
            report($e);
        }

        $event->update([
            'status' => 'success',
            'dispatched_at' => now(),
            'dispatch_token' => (string) Str::uuid(),
            'completed_at' => now(),
            'receipt_payload' => $payload,
        ]);

        return 'END Voucher redeemed successfully. Ref: ' . ($fresh->transaction_reference ?? $fresh->code);
    }

    private function airtimeSplitEnabled(): bool
    {
        return (bool) config('services.africastalking.airtime_split_enabled', false);
    }

    private function isAuthorized(array $input): bool
    {
        $expected = trim((string) config('services.africastalking.ussd_token', ''));
        if ($expected === '') {
            return true;
        }

        $provided = trim((string) ($input['token'] ?? $input['ussd_token'] ?? ''));
        return hash_equals($expected, $provided);
    }

    private function splitSteps(string $text): array
    {
        if ($text === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode('*', $text)), static fn ($part) => $part !== ''));
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        if (Str::startsWith($digits, '0') && strlen($digits) === 10) {
            return '27' . substr($digits, 1);
        }

        if (Str::startsWith($digits, '27')) {
            return $digits;
        }

        if (strlen($digits) === 9) {
            return '27' . $digits;
        }

        return $digits;
    }

    private function findVoucherByIdentifierAndPhone(string $voucherIdentifier, string $phoneNormalized): ?FuelVoucher
    {
        $voucherIdentifier = trim($voucherIdentifier);
        $normalizedIdentifier = strtoupper($voucherIdentifier);

        $candidates = FuelVoucher::query()
            ->with(['user:id,name,phone', 'fuelStation:id,name,city,owner_id', 'fuelStation.owner:id,phone'])
            ->where(function ($query) use ($normalizedIdentifier, $voucherIdentifier) {
                $query->where('code', $normalizedIdentifier)
                    ->orWhere('qr_code', $normalizedIdentifier);

                if (ctype_digit($voucherIdentifier)) {
                    $query->orWhereKey((int) $voucherIdentifier);
                }
            })
            ->latest()
            ->get();

        foreach ($candidates as $voucher) {
            if ($this->callerMatchesVoucher($voucher, $phoneNormalized)) {
                return $voucher;
            }
        }

        return null;
    }

    private function callerMatchesVoucher(FuelVoucher $voucher, string $phoneNormalized): bool
    {
        if ($phoneNormalized === '') {
            return false;
        }

        $driverPhone = $this->normalizePhone((string) ($voucher->user?->phone ?? ''));
        if ($driverPhone !== '' && $driverPhone === $phoneNormalized) {
            return true;
        }

        $merchantPhone = $this->normalizePhone((string) ($voucher->fuelStation?->owner?->phone ?? ''));

        return $merchantPhone !== '' && $merchantPhone === $phoneNormalized;
    }

    private function findLatestApprovedVoucherByPhone(string $phoneNormalized): ?FuelVoucher
    {
        $candidates = FuelVoucher::query()
            ->with(['user:id,name,phone', 'fuelStation:id,name,city,owner_id'])
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('issued_at')
            ->latest('id')
            ->get();

        foreach ($candidates as $voucher) {
            if (!$voucher->user) {
                continue;
            }

            $driverPhone = $this->normalizePhone((string) $voucher->user->phone);
            if ($driverPhone !== '' && $driverPhone === $phoneNormalized) {
                return $voucher;
            }
        }

        return null;
    }

    private function findPendingEventForSession(string $sessionId, string $phoneNormalized): ?UssdRedemptionEvent
    {
        $query = UssdRedemptionEvent::query()
            ->where('status', 'pending')
            ->where('phone_normalized', $phoneNormalized);

        if ($sessionId !== '') {
            $query->where('session_id', $sessionId);
        }

        return $query->latest()->first();
    }

    private function calculateSplitAmounts(float $totalAmount): ?array
    {
        $splitPercent = (float) config('services.africastalking.airtime_split_percent', 20);
        $minimumAirtime = (float) config('services.africastalking.airtime_split_min_amount', 5);

        if ($splitPercent <= 0 || $splitPercent >= 100 || $totalAmount <= 0) {
            return null;
        }

        $airtimeAmount = round($totalAmount * ($splitPercent / 100), 2);
        if ($airtimeAmount < $minimumAirtime) {
            return null;
        }

        $fuelAmount = round($totalAmount - $airtimeAmount, 2);
        if ($fuelAmount <= 0) {
            return null;
        }

        return [
            'fuel_amount' => $fuelAmount,
            'airtime_amount' => $airtimeAmount,
        ];
    }

    private function sendAirtime(string $phoneNormalized, float $amount, string $voucherCode): array
    {
        $dryRun = (bool) config('services.africastalking.airtime_dry_run', true);
        if ($dryRun) {
            return [
                'success' => true,
                'reference' => 'AIRTIME-DRYRUN-' . strtoupper(Str::random(8)),
                'error' => null,
            ];
        }

        $apiKey = trim((string) config('services.africastalking.api_key', ''));
        $username = trim((string) config('services.africastalking.username', 'sandbox'));
        $baseUrl = trim((string) config('services.africastalking.airtime_base_url', 'https://api.africastalking.com/version1/airtime'));
        $currency = trim((string) config('services.africastalking.airtime_currency', 'ZAR'));
        $timeoutSeconds = max(2, (int) config('services.africastalking.airtime_timeout_seconds', 12));

        if ($apiKey === '') {
            return [
                'success' => false,
                'reference' => null,
                'error' => 'Africa\'s Talking API key is missing.',
            ];
        }

        try {
            $response = Http::asForm()
                ->timeout($timeoutSeconds)
                ->withHeaders([
                    'apiKey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($baseUrl, [
                    'username' => $username,
                    'recipients' => json_encode([
                        [
                            'phoneNumber' => '+' . ltrim($phoneNormalized, '+'),
                            'amount' => sprintf('%s %.2f', $currency, $amount),
                            'metadata' => [
                                'voucher_code' => $voucherCode,
                            ],
                        ],
                    ], JSON_UNESCAPED_SLASHES),
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'reference' => null,
                    'error' => 'HTTP ' . $response->status(),
                ];
            }

            $body = $response->json();
            $firstResponse = is_array($body['responses'] ?? null) ? ($body['responses'][0] ?? null) : null;
            $status = strtolower((string) ($firstResponse['status'] ?? ''));
            $requestId = (string) ($firstResponse['requestId'] ?? $body['requestId'] ?? '');

            $isSuccess = in_array($status, ['success', 'queued', 'sent'], true);
            if (!$isSuccess) {
                return [
                    'success' => false,
                    'reference' => $requestId !== '' ? $requestId : null,
                    'error' => (string) ($firstResponse['errorMessage'] ?? $body['errorMessage'] ?? 'Airtime API rejected request.'),
                ];
            }

            return [
                'success' => true,
                'reference' => $requestId !== '' ? $requestId : 'AIRTIME-' . strtoupper(Str::random(10)),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'reference' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function resolveOrCreateEvent(
        string $sessionId,
        string $serviceCode,
        string $networkCode,
        string $phoneRaw,
        string $phoneNormalized,
        string $text,
        string $voucherCode
    ): UssdRedemptionEvent {
        $query = UssdRedemptionEvent::query()
            ->where('phone_normalized', $phoneNormalized)
            ->where('voucher_code', $voucherCode);

        if ($sessionId !== '') {
            $query->where('session_id', $sessionId);
        }

        $event = $query->latest()->first();
        if (!$event || $event->status !== 'pending') {
            $event = new UssdRedemptionEvent();
            $event->status = 'pending';
        }

        $event->session_id = $sessionId !== '' ? $sessionId : null;
        $event->service_code = $serviceCode !== '' ? $serviceCode : null;
        $event->network_code = $networkCode !== '' ? $networkCode : null;
        $event->phone_raw = $phoneRaw !== '' ? $phoneRaw : $phoneNormalized;
        $event->phone_normalized = $phoneNormalized;
        $event->ussd_text = $text !== '' ? $text : null;
        $event->voucher_code = $voucherCode;
        $event->save();

        return $event;
    }

    private function voucherPayload(FuelVoucher $voucher): array
    {
        return [
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'qr_code' => $voucher->qr_code,
            'status' => $voucher->status,
            'amount' => (float) $voucher->amount,
            'fuel_type' => $voucher->fuel_type,
            'liters' => (float) $voucher->liters,
            'station_id' => $voucher->fuel_station_id,
            'station' => [
                'id' => $voucher->fuelStation?->id ?? $voucher->fuel_station_id,
                'name' => $voucher->fuelStation?->name,
                'city' => $voucher->fuelStation?->city,
                'address' => $voucher->fuelStation?->address,
            ],
            'driver' => [
                'id' => $voucher->user?->id,
                'name' => $voucher->user?->name,
                'phone' => $voucher->user?->phone,
            ],
            'lease' => $voucher->lease ? [
                'id' => $voucher->lease->id,
                'status' => $voucher->lease->status,
                'total_amount' => (float) $voucher->lease->total_amount,
                'daily_repayment' => (float) $voucher->lease->daily_repayment,
                'due_date' => optional($voucher->lease->due_date)->toDateString(),
                'remaining_balance' => (float) $voucher->lease->remaining_balance,
                'repayment_frequency' => (string) ($voucher->lease->repayment_frequency ?? 'daily'),
            ] : null,
            'upcoming_repayments' => $this->upcomingRepaymentsPayload($voucher),
            'issued_at' => optional($voucher->issued_at)->toIso8601String(),
            'expires_at' => optional($voucher->expires_at)->toIso8601String(),
            'redeemed_at' => optional($voucher->redeemed_at)->toIso8601String(),
            'redeemed_fuel_amount' => $voucher->redeemed_fuel_amount !== null ? (float) $voucher->redeemed_fuel_amount : null,
            'redeemed_airtime_amount' => $voucher->redeemed_airtime_amount !== null ? (float) $voucher->redeemed_airtime_amount : null,
            'airtime_phone' => $voucher->airtime_phone,
            'airtime_reference' => $voucher->airtime_reference,
            'airtime_status' => $voucher->airtime_status,
            'pump_number' => $voucher->pump_number,
            'transaction_reference' => $voucher->transaction_reference,
        ];
    }

    private function upcomingRepaymentsPayload(FuelVoucher $voucher): array
    {
        if (!$voucher->lease_id) {
            return [];
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $rows = Repayment::query()
            ->where('lease_id', $voucher->lease_id)
            ->where('status', 'pending')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->get(['id', 'amount', 'due_date', 'status']);

        return $rows->map(function (Repayment $repayment) use ($baseUrl) {
            return [
                'id' => $repayment->id,
                'amount' => (float) $repayment->amount,
                'due_date' => optional($repayment->due_date)->toDateString(),
                'status' => $repayment->status,
                'pay_url' => $baseUrl !== '' ? "{$baseUrl}/driver/repayments?repayment_id={$repayment->id}" : null,
            ];
        })->toArray();
    }
}
