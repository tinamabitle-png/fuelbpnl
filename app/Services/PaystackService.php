<?php

namespace App\Services;

use App\Models\Repayment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    public function configured(): bool
    {
        return trim((string) config('services.paystack.secret_key')) !== '';
    }

    public function initializeRepaymentCheckout(
        User $user,
        Repayment $repayment,
        string $channel,
        string $callbackUrl,
        ?string $payerEmail = null,
        string $scope = 'repayment'
    ): array
    {
        $this->assertConfigured();

        $reference = 'RPY-' . $repayment->id . '-' . strtoupper(Str::random(10));
        $email = $this->resolveCheckoutEmail($user, $payerEmail);
        $callbackUrl = $this->normalizeCallbackUrl($callbackUrl);

        $payload = [
            'email' => $email,
            'amount' => $this->toMinor((float) $repayment->amount),
            'currency' => strtoupper((string) config('services.paystack.currency', 'ZAR')),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'scope' => $scope,
                'repayment_id' => (int) $repayment->id,
                'lease_id' => (int) $repayment->lease_id,
                'user_id' => (int) $user->id,
                'requested_channel' => $channel,
                'requested_by' => $scope === 'repayment_request' ? 'public_share' : 'driver_portal',
            ],
        ];

        if (in_array($channel, ['card', 'apple_pay', 'google_pay'], true)) {
            $payload['channels'] = ['card'];
        }

        $response = $this->http()->post($this->baseUrl() . '/transaction/initialize', $payload);
        if (!$response->successful() || !$response->json('status')) {
            throw new \RuntimeException('Paystack initialize failed: ' . $this->extractError($response));
        }

        return [
            'reference' => (string) $reference,
            'authorization_url' => (string) $response->json('data.authorization_url'),
            'access_code' => (string) $response->json('data.access_code'),
        ];
    }

    public function initializeAutopayAuthorization(
        User $user,
        float $amount,
        string $callbackUrl,
        ?string $payerEmail = null
    ): array
    {
        $this->assertConfigured();

        $reference = 'AUTOSETUP-' . $user->id . '-' . strtoupper(Str::random(10));
        $email = $this->resolveCheckoutEmail($user, $payerEmail);
        $callbackUrl = $this->normalizeCallbackUrl($callbackUrl);

        $payload = [
            'email' => $email,
            'amount' => $this->toMinor(max(1, $amount)),
            'currency' => strtoupper((string) config('services.paystack.currency', 'ZAR')),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'channels' => ['card'],
            'metadata' => [
                'scope' => 'autopay_setup',
                'user_id' => (int) $user->id,
                'requested_by' => 'driver_portal',
            ],
        ];

        $response = $this->http()->post($this->baseUrl() . '/transaction/initialize', $payload);
        if (!$response->successful() || !$response->json('status')) {
            throw new \RuntimeException('Paystack initialize failed: ' . $this->extractError($response));
        }

        return [
            'reference' => (string) $reference,
            'authorization_url' => (string) $response->json('data.authorization_url'),
            'access_code' => (string) $response->json('data.access_code'),
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        $this->assertConfigured();

        $response = $this->http()->get($this->baseUrl() . '/transaction/verify/' . urlencode($reference));
        if (!$response->successful() || !$response->json('status')) {
            throw new \RuntimeException('Paystack verify failed: ' . $this->extractError($response));
        }

        $data = (array) ($response->json('data') ?? []);
        $status = strtolower((string) ($data['status'] ?? ''));
        if ($status !== 'success') {
            throw new \RuntimeException('Paystack transaction not successful. Current status: ' . strtoupper($status ?: 'UNKNOWN'));
        }

        return $data;
    }

    public function chargeAuthorization(User $user, Repayment $repayment, string $reason = 'daily_autopay'): array
    {
        $this->assertConfigured();

        $authorizationCode = trim((string) ($user->autopay_token ?? ''));
        if ($authorizationCode === '') {
            throw new \RuntimeException('Missing saved Paystack authorization token.');
        }

        $reference = 'AUTO-RPY-' . $repayment->id . '-' . strtoupper(Str::random(8));
        $payload = [
            'email' => $this->resolveEmail($user),
            'amount' => $this->toMinor((float) $repayment->amount),
            'authorization_code' => $authorizationCode,
            'reference' => $reference,
            'currency' => strtoupper((string) config('services.paystack.currency', 'ZAR')),
            'metadata' => [
                'scope' => 'repayment_autopay',
                'reason' => $reason,
                'repayment_id' => (int) $repayment->id,
                'lease_id' => (int) $repayment->lease_id,
                'user_id' => (int) $user->id,
            ],
        ];

        $response = $this->http()->post($this->baseUrl() . '/transaction/charge_authorization', $payload);
        if (!$response->successful() || !$response->json('status')) {
            throw new \RuntimeException('Paystack charge authorization failed: ' . $this->extractError($response));
        }

        $data = (array) ($response->json('data') ?? []);
        $status = strtolower((string) ($data['status'] ?? ''));
        if ($status !== 'success') {
            throw new \RuntimeException('Paystack authorization charge not successful. Status: ' . strtoupper($status ?: 'UNKNOWN'));
        }

        return [
            'reference' => (string) ($data['reference'] ?? $reference),
            'data' => $data,
        ];
    }

    public function storeAuthorizationFromTransaction(User $user, array $transactionData): void
    {
        $authorization = (array) ($transactionData['authorization'] ?? []);
        $customer = (array) ($transactionData['customer'] ?? []);
        $authorizationCode = trim((string) ($authorization['authorization_code'] ?? ''));

        if ($authorizationCode === '') {
            return;
        }

        $nextChargeAt = now()->addDay();
        $user->forceFill([
            'autopay_enabled' => true,
            'autopay_gateway' => 'paystack',
            'autopay_token' => $authorizationCode,
            'autopay_email' => (string) ($customer['email'] ?? $user->email ?? $this->resolveEmail($user)),
            'autopay_customer_code' => (string) ($customer['customer_code'] ?? ''),
            'autopay_details' => [
                'authorization' => $authorization,
                'customer' => $customer,
                'last_verified_reference' => (string) ($transactionData['reference'] ?? ''),
            ],
            'autopay_status' => 'active',
            'autopay_failures' => 0,
            'autopay_last_attempt_at' => now(),
            'autopay_next_attempt_at' => $nextChargeAt,
        ])->save();
    }

    private function toMinor(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function resolveEmail(User $user): string
    {
        $email = trim((string) ($user->autopay_email ?? $user->email ?? ''));
        if ($this->isValidEmail($email)) {
            return $email;
        }

        $digits = preg_replace('/\D+/', '', (string) ($user->phone ?? ''));
        $domain = $this->fallbackEmailDomain();
        if ($digits === '') {
            return 'driver+' . $user->id . '@' . $domain;
        }

        return 'driver' . $digits . '@' . $domain;
    }

    private function resolveCheckoutEmail(User $user, ?string $payerEmail = null): string
    {
        $preferred = trim((string) ($payerEmail ?? ''));
        if ($this->isValidEmail($preferred)) {
            return $preferred;
        }

        return $this->resolveEmail($user);
    }

    private function normalizeCallbackUrl(string $callbackUrl): string
    {
        $url = trim($callbackUrl);
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $fallback = rtrim((string) config('app.url', 'https://bwiser.co.za'), '/')
            . '/driver/repayments/paystack/callback';

        return filter_var($fallback, FILTER_VALIDATE_URL)
            ? $fallback
            : 'https://bwiser.co.za/driver/repayments/paystack/callback';
    }

    private function fallbackEmailDomain(): string
    {
        $host = (string) parse_url((string) config('app.url', ''), PHP_URL_HOST);
        $host = strtolower(trim($host));
        if ($host !== '' && $host !== 'localhost' && !preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $host)) {
            return $host;
        }

        return 'bwiser.co.za';
    }

    private function isValidEmail(string $email): bool
    {
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function assertConfigured(): void
    {
        if (!$this->configured()) {
            throw new \RuntimeException('Paystack is not configured. Please set PAYSTACK_SECRET_KEY.');
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
    }

    private function http()
    {
        return Http::withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->timeout(max(5, (int) config('services.paystack.timeout', 15)));
    }

    private function extractError($response): string
    {
        try {
            $json = $response->json();
            if (is_array($json)) {
                $message = trim((string) ($json['message'] ?? ''));
                if ($message !== '') {
                    return $message;
                }
                $dataMessage = trim((string) (($json['data']['message'] ?? '') ?: ''));
                if ($dataMessage !== '') {
                    return $dataMessage;
                }
            }
        } catch (\Throwable $e) {
            // noop
        }

        return 'Unexpected Paystack response.';
    }
}
