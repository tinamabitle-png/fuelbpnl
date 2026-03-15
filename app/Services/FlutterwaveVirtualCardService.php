<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class FlutterwaveVirtualCardService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.flutterwave.secret_key')) !== '';
    }

    public function virtualCardsEnabled(): bool
    {
        return (bool) config('services.flutterwave.virtual_cards_enabled', false);
    }

    /**
     * Reveal card details from Flutterwave.
     *
     * @return array{pan:string,cvv:?string,expiry_month:?int,expiry_year:?int,card_scheme:?string}
     */
    public function reveal(string $providerCardId): array
    {
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
                ->get($baseUrl . '/v3/virtual-cards/' . urlencode($providerCardId));
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Failed to connect to Flutterwave.');
        }

        if (!$response->ok()) {
            $message = (string) ($response->json('message') ?? $response->body());
            $message = trim($message) !== '' ? $message : 'Flutterwave request failed.';
            throw new \RuntimeException($message);
        }

        $payload = $response->json();
        $data = Arr::get($payload, 'data', $payload);

        $panRaw = (string) (Arr::get($data, 'card_pan')
            ?? Arr::get($data, 'cardpan')
            ?? Arr::get($data, 'card_number')
            ?? Arr::get($data, 'cardpan')
            ?? '');
        $pan = $this->formatPan($panRaw);
        if ($pan === '') {
            throw new \RuntimeException('Flutterwave response missing card PAN.');
        }

        $cvvRaw = (string) (Arr::get($data, 'cvv') ?? Arr::get($data, 'card_cvv') ?? '');
        $cvv = $this->sanitizeDigits($cvvRaw);
        $cvv = $cvv === '' ? null : $cvv;

        [$expiryMonth, $expiryYear] = $this->extractExpiry($data);

        $scheme = (string) (Arr::get($data, 'card_type')
            ?? Arr::get($data, 'card_scheme')
            ?? Arr::get($data, 'type')
            ?? '');
        $scheme = trim($scheme) === '' ? null : strtolower(trim($scheme));

        return [
            'pan' => $pan,
            'cvv' => $cvv,
            'expiry_month' => $expiryMonth,
            'expiry_year' => $expiryYear,
            'card_scheme' => $scheme,
        ];
    }

    /**
     * Create a virtual card on Flutterwave.
     *
     * Notes:
     * - Flutterwave may require this feature to be enabled on your merchant account.
     * - We do not persist PAN/CVV; we only persist last4/masked/expiry/scheme + provider id.
     *
     * @return array{
     *   provider_card_id:string,
     *   last4:?string,
     *   masked_pan:?string,
     *   expiry_month:?int,
     *   expiry_year:?int,
     *   card_scheme:?string,
     *   raw:array<string,mixed>
     * }
     */
    public function create(User $user, array $billing = [], float $amount = 1.0, string $currency = 'ZAR'): array
    {
        $secretKey = trim((string) config('services.flutterwave.secret_key'));
        if ($secretKey === '') {
            throw new \RuntimeException('Flutterwave secret key is not configured.');
        }

        $baseUrl = rtrim((string) config('services.flutterwave.base_url', 'https://api.flutterwave.com'), '/');
        $timeout = (int) config('services.flutterwave.timeout', 20);

        $payload = array_merge([
            'currency' => $currency,
            'amount' => max(1.0, $amount),
            'billing_name' => trim((string) ($user->name ?? 'Bwiser User')) ?: 'Bwiser User',
            'billing_address' => (string) ($billing['address'] ?? config('services.flutterwave.virtual_cards_billing_address', 'Unknown')),
            'billing_city' => (string) ($billing['city'] ?? config('services.flutterwave.virtual_cards_billing_city', 'Johannesburg')),
            'billing_state' => (string) ($billing['state'] ?? config('services.flutterwave.virtual_cards_billing_state', 'Gauteng')),
            'billing_postal_code' => (string) ($billing['postal_code'] ?? config('services.flutterwave.virtual_cards_billing_postal_code', '0001')),
            'billing_country' => (string) ($billing['country'] ?? config('services.flutterwave.virtual_cards_billing_country', 'ZA')),
            'callback_url' => (string) ($billing['callback_url'] ?? config('services.flutterwave.virtual_cards_callback_url', config('app.url'))),
        ], $billing);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($secretKey)
                ->post($baseUrl . '/v3/virtual-cards', $payload);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Failed to connect to Flutterwave.');
        }

        if (!$response->ok()) {
            $message = (string) ($response->json('message') ?? $response->body());
            $message = trim($message) !== '' ? $message : 'Flutterwave request failed.';
            throw new \RuntimeException($message);
        }

        $raw = $response->json();
        $data = Arr::get($raw, 'data', $raw);
        if (!is_array($data)) {
            throw new \RuntimeException('Flutterwave response missing card payload.');
        }

        $providerId = (string) (Arr::get($data, 'id') ?? Arr::get($data, 'card_id') ?? '');
        $providerId = trim($providerId);
        if ($providerId === '') {
            throw new \RuntimeException('Flutterwave response missing virtual card id.');
        }

        $panLike = (string) (Arr::get($data, 'masked_pan') ?? Arr::get($data, 'maskedpan') ?? '');
        $maskedPan = trim($panLike) !== '' ? trim($panLike) : null;

        $last4 = (string) (Arr::get($data, 'last_4digits')
            ?? Arr::get($data, 'last4')
            ?? Arr::get($data, 'last_4')
            ?? '');
        $last4 = $this->sanitizeDigits($last4);
        $last4 = strlen($last4) === 4 ? $last4 : ($maskedPan ? substr(preg_replace('/\D+/', '', $maskedPan) ?: '', -4) : '');
        $last4 = $last4 !== '' ? $last4 : null;

        [$expiryMonth, $expiryYear] = $this->extractExpiry($data);

        $scheme = (string) (Arr::get($data, 'card_type')
            ?? Arr::get($data, 'card_scheme')
            ?? Arr::get($data, 'type')
            ?? '');
        $scheme = trim($scheme) === '' ? null : strtolower(trim($scheme));

        return [
            'provider_card_id' => $providerId,
            'last4' => $last4,
            'masked_pan' => $maskedPan,
            'expiry_month' => $expiryMonth,
            'expiry_year' => $expiryYear,
            'card_scheme' => $scheme,
            'raw' => is_array($raw) ? $raw : [],
        ];
    }

    /**
     * Fund an existing virtual card on Flutterwave.
     *
     * @return array<string,mixed>
     */
    public function fund(string $providerCardId, float $amount, string $debitCurrency = 'ZAR'): array
    {
        $secretKey = trim((string) config('services.flutterwave.secret_key'));
        if ($secretKey === '') {
            throw new \RuntimeException('Flutterwave secret key is not configured.');
        }
        $providerCardId = trim($providerCardId);
        if ($providerCardId === '') {
            throw new \RuntimeException('Flutterwave card id is missing.');
        }

        $baseUrl = rtrim((string) config('services.flutterwave.base_url', 'https://api.flutterwave.com'), '/');
        $timeout = (int) config('services.flutterwave.timeout', 20);

        $payload = [
            'amount' => max(1.0, $amount),
            'debit_currency' => $debitCurrency,
        ];

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($secretKey)
                ->post($baseUrl . '/v3/virtual-cards/' . urlencode($providerCardId) . '/fund', $payload);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Failed to connect to Flutterwave.');
        }

        if (!$response->ok()) {
            $message = (string) ($response->json('message') ?? $response->body());
            $message = trim($message) !== '' ? $message : 'Flutterwave request failed.';
            throw new \RuntimeException($message);
        }

        $raw = $response->json();
        return is_array($raw) ? $raw : [];
    }

    private function sanitizeDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    private function formatPan(string $value): string
    {
        $digits = $this->sanitizeDigits($value);
        if ($digits === '') {
            return '';
        }
        return trim(implode(' ', str_split($digits, 4)));
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0:?int,1:?int}
     */
    private function extractExpiry(array $data): array
    {
        $month = Arr::get($data, 'expiry_month') ?? Arr::get($data, 'expiration_month');
        $year = Arr::get($data, 'expiry_year') ?? Arr::get($data, 'expiration_year');

        $monthInt = is_numeric($month) ? (int) $month : null;
        $yearInt = is_numeric($year) ? (int) $year : null;

        if ($monthInt && $yearInt) {
            if ($yearInt < 100) {
                $yearInt = 2000 + $yearInt;
            }
            return [$monthInt, $yearInt];
        }

        $expiry = (string) (Arr::get($data, 'expiry')
            ?? Arr::get($data, 'expiration')
            ?? Arr::get($data, 'expiry_date')
            ?? Arr::get($data, 'expiration_date')
            ?? '');
        $expiry = trim($expiry);
        if ($expiry === '') {
            return [null, null];
        }

        if (preg_match('/^(?<m>\d{1,2})\s*\/\s*(?<y>\d{2,4})$/', $expiry, $m)) {
            $mm = (int) $m['m'];
            $yy = (int) $m['y'];
            if ($yy < 100) {
                $yy = 2000 + $yy;
            }
            return [$mm, $yy];
        }

        return [null, null];
    }
}
