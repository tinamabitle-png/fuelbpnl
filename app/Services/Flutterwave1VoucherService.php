<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Flutterwave1VoucherService
{
    public function configured(): bool
    {
        return trim((string) config('services.flutterwave.secret_key')) !== '';
    }

    /**
     * Charge a 1Voucher PIN for the given amount.
     *
     * @return array<string,mixed> Raw Flutterwave response json
     */
    public function charge(string $txRef, float $amount, string $currency, string $email, string $phoneNumber, string $pin): array
    {
        $this->assertBillingEnabled();

        $secretKey = trim((string) config('services.flutterwave.secret_key'));
        if ($secretKey === '') {
            throw new \RuntimeException('Flutterwave secret key is not configured.');
        }

        $baseUrl = rtrim((string) config('services.flutterwave.base_url', 'https://api.flutterwave.com'), '/');
        $timeout = (int) config('services.flutterwave.timeout', 20);

        $payload = [
            'tx_ref' => $txRef,
            // Flutterwave expects amount as number; keep it integer-ish where possible.
            'amount' => max(1, (int) ceil($amount)),
            'currency' => strtoupper(trim($currency)) ?: 'USD',
            'email' => $email,
            'phone_number' => $phoneNumber,
            'pin' => $pin,
        ];

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($secretKey)
                ->post($baseUrl . '/v3/charges?type=voucher_payment', $payload);
        } catch (ConnectionException $e) {
            Log::warning('Flutterwave 1Voucher charge connection failed', [
                'base_url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to connect to Flutterwave: ' . $e->getMessage());
        }

        $json = $response->json();
        if (!$response->ok()) {
            $message = is_array($json) ? (string) ($json['message'] ?? '') : '';
            $message = trim($message) !== '' ? $message : trim((string) $response->body());
            $message = $message !== '' ? $message : 'Flutterwave request failed.';
            throw new \RuntimeException($message);
        }

        return is_array($json) ? $json : [];
    }

    /**
     * Verify a transaction by tx_ref.
     *
     * @return array<string,mixed>
     */
    public function verifyByReference(string $txRef): array
    {
        $this->assertBillingEnabled();

        $secretKey = trim((string) config('services.flutterwave.secret_key'));
        if ($secretKey === '') {
            throw new \RuntimeException('Flutterwave secret key is not configured.');
        }

        $baseUrl = rtrim((string) config('services.flutterwave.base_url', 'https://api.flutterwave.com'), '/');
        $timeout = (int) config('services.flutterwave.timeout', 20);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($secretKey)
                ->get($baseUrl . '/v3/transactions/verify_by_reference', [
                    'tx_ref' => $txRef,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Flutterwave verify_by_reference connection failed', [
                'base_url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to connect to Flutterwave: ' . $e->getMessage());
        }

        $json = $response->json();
        if (!$response->ok()) {
            $message = is_array($json) ? (string) ($json['message'] ?? '') : '';
            $message = trim($message) !== '' ? $message : trim((string) $response->body());
            $message = $message !== '' ? $message : 'Flutterwave request failed.';
            throw new \RuntimeException($message);
        }

        return is_array($json) ? $json : [];
    }

    /**
     * @param array<string,mixed> $response
     */
    public function extractStatus(array $response): string
    {
        $data = Arr::get($response, 'data', []);
        $status = (string) (Arr::get($data, 'status') ?? Arr::get($response, 'status') ?? '');
        return strtolower(trim($status));
    }

    /**
     * @param array<string,mixed> $response
     */
    public function extractFlwRef(array $response): string
    {
        $data = Arr::get($response, 'data', []);
        $ref = (string) (Arr::get($data, 'flw_ref') ?? Arr::get($data, 'reference') ?? Arr::get($data, 'id') ?? '');
        return trim($ref);
    }

    /**
     * Extract the "change voucher" details (new PIN, remaining balance, expiry) from a successful charge response.
     *
     * @param array<string,mixed> $response
     * @return array{amount:?float,pin:?string,serial:?string,expiry:?string}
     */
    public function extractChangeVoucher(array $response): array
    {
        $change = Arr::get($response, 'data.meta_data.change_voucher');
        if (!is_array($change)) {
            $change = [];
        }

        $amount = Arr::get($change, 'amount');
        $amount = is_numeric($amount) ? (float) $amount : null;

        $pin = Arr::get($change, 'pin');
        $pin = is_string($pin) && trim($pin) !== '' ? trim($pin) : null;

        $serial = Arr::get($change, 'serial');
        $serial = is_string($serial) && trim($serial) !== '' ? trim($serial) : null;

        $expiry = Arr::get($change, 'expiry');
        $expiry = is_string($expiry) && trim($expiry) !== '' ? trim($expiry) : null;

        return [
            'amount' => $amount,
            'pin' => $pin,
            'serial' => $serial,
            'expiry' => $expiry,
        ];
    }

    private function assertBillingEnabled(): void
    {
        if ((bool) config('services.billing.enabled', false)) {
            return;
        }

        throw new \RuntimeException('Billing is disabled for this environment. Set BILLING_ENABLED=true only on approved payment environments.');
    }
}
