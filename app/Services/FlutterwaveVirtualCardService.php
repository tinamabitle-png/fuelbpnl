<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $panRaw = (string) ($this->firstByPaths($data, [
            'card_pan',
            'cardpan',
            'card_number',
            'card.number',
            'card.card_pan',
            'card.cardpan',
            'card_details.card_pan',
            'card_details.card_number',
        ]) ?? '');
        $pan = $this->formatPan($panRaw);
        if ($pan === '') {
            throw new \RuntimeException('Flutterwave response missing card PAN.');
        }

        $cvvRaw = (string) ($this->firstByPaths($data, [
            'cvv',
            'card_cvv',
            'security_code',
            'card.cvv',
            'card.card_cvv',
            'card.security_code',
            'card_details.cvv',
            'card_details.card_cvv',
            'card_details.security_code',
        ]) ?? '');
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
     * @param mixed $data
     * @param array<int, string> $paths
     */
    private function firstByPaths($data, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = Arr::get($data, $path);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
        }
        return null;
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

        $currency = strtoupper(trim($currency)) ?: 'USD';
        $fullName = trim((string) ($user->name ?? 'Bwiser User')) ?: 'Bwiser User';

        // Keep only known keys, and drop null/empty values to avoid provider validation failures.
        $allowedKeys = [
            'currency',
            'amount',
            'billing_name',
            'billing_address',
            'billing_city',
            'billing_state',
            'billing_postal_code',
            'billing_country',
            'callback_url',
            // Optional identity keys (only sent when enabled below)
            'first_name',
            'last_name',
            'date_of_birth',
            'email',
            'phone',
            'phone_number',
            'gender',
            'title',
        ];
        $billing = Arr::only($billing, $allowedKeys);
        $billingFiltered = array_filter($billing, static function ($value) {
            if ($value === null) {
                return false;
            }
            if (is_string($value) && trim($value) === '') {
                return false;
            }
            return true;
        });

        $payload = array_merge([
            'currency' => $currency,
            // Flutterwave examples use integer amounts; avoid floats.
            'amount' => max(1, (int) ceil($amount)),
            'billing_name' => $fullName,
            'billing_address' => (string) ($billingFiltered['billing_address'] ?? config('services.flutterwave.virtual_cards_billing_address', 'Unknown')),
            'billing_city' => (string) ($billingFiltered['billing_city'] ?? config('services.flutterwave.virtual_cards_billing_city', 'Johannesburg')),
            'billing_state' => (string) ($billingFiltered['billing_state'] ?? config('services.flutterwave.virtual_cards_billing_state', 'Gauteng')),
            'billing_postal_code' => (string) ($billingFiltered['billing_postal_code'] ?? config('services.flutterwave.virtual_cards_billing_postal_code', '0001')),
            'billing_country' => (string) ($billingFiltered['billing_country'] ?? config('services.flutterwave.virtual_cards_billing_country', 'ZA')),
            'callback_url' => (string) ($billingFiltered['callback_url'] ?? config('services.flutterwave.virtual_cards_callback_url', config('app.url'))),
        ], Arr::only($billingFiltered, [
            // Allow overriding these with known keys if provided.
            'currency',
            'amount',
            'billing_name',
            'billing_address',
            'billing_city',
            'billing_state',
            'billing_postal_code',
            'billing_country',
            'callback_url',
        ]));

        // Optional identity fields: only include if explicitly enabled (some accounts require it).
        if ((bool) config('services.flutterwave.virtual_cards_include_identity', false)) {
            $parts = preg_split('/\s+/', $fullName) ?: [];
            $derivedFirst = $parts[0] ?? 'Bwiser';
            $derivedLast = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

            $firstName = trim((string) ($billingFiltered['first_name']
                ?? ($user->first_name ?? null)
                ?? config('services.flutterwave.virtual_cards_first_name')
                ?? $derivedFirst));
            $lastName = trim((string) ($billingFiltered['last_name']
                ?? ($user->last_name ?? null)
                ?? config('services.flutterwave.virtual_cards_last_name')
                ?? $derivedLast));

            $userDob = null;
            if (!empty($user->date_of_birth)) {
                try {
                    $userDob = $user->date_of_birth instanceof \Carbon\CarbonInterface
                        ? $user->date_of_birth->toDateString()
                        : (string) $user->date_of_birth;
                } catch (\Throwable $e) {
                    $userDob = null;
                }
            }
            $dob = trim((string) ($billingFiltered['date_of_birth']
                ?? $userDob
                ?? $this->parseSouthAfricanIdDob((string) ($user->id_number ?? ''))
                ?? config('services.flutterwave.virtual_cards_date_of_birth')
                ?? ''));

            $email = trim((string) ($billingFiltered['email'] ?? config('services.flutterwave.virtual_cards_email') ?? (string) ($user->email ?? '')));
            $phone = trim((string) ($billingFiltered['phone_number']
                ?? $billingFiltered['phone']
                ?? config('services.flutterwave.virtual_cards_phone')
                ?? (string) ($user->phone ?? '')));

            $gender = $this->normalizeGender(trim((string) ($billingFiltered['gender']
                ?? ($user->gender ?? null)
                ?? config('services.flutterwave.virtual_cards_gender')
                ?? '')));
            $title = trim((string) ($billingFiltered['title']
                ?? config('services.flutterwave.virtual_cards_title')
                ?? $this->deriveTitleFromGender($gender)
                ?? ''));

            // Only include non-empty identity keys to avoid validation failures.
            foreach ([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dob,
                'email' => $email,
                'phone_number' => $phone,
                'gender' => $gender,
                'title' => $title,
            ] as $k => $v) {
                if (is_string($v) && trim($v) !== '') {
                    $payload[$k] = $v;
                }
            }
        }

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($secretKey)
                ->post($baseUrl . '/v3/virtual-cards', $payload);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Failed to connect to Flutterwave.');
        }

        if (!$response->ok()) {
            $json = $response->json();
            $message = is_array($json) ? (string) ($json['message'] ?? '') : '';
            $errors = is_array($json) ? ($json['errors'] ?? $json['data']['errors'] ?? null) : null;

            $details = '';
            if (is_array($errors)) {
                // Render common error formats into a single line.
                $flat = [];
                foreach ($errors as $k => $v) {
                    if (is_string($v)) {
                        $flat[] = $k . ': ' . $v;
                    } elseif (is_array($v)) {
                        $flat[] = $k . ': ' . implode(', ', array_map('strval', $v));
                    }
                }
                $details = implode(' | ', array_filter($flat));
            }

            $out = trim($message);
            if ($details !== '') {
                $out = ($out !== '' ? $out . ' - ' : '') . $details;
            }
            if ($out === '') {
                $out = trim((string) $response->body());
            }
            $out = $out !== '' ? $out : 'Flutterwave request failed.';

            Log::warning('Flutterwave virtual card create failed', [
                'status' => $response->status(),
                'message' => $message,
                'errors' => $errors,
                'response_body' => is_string($response->body()) ? substr($response->body(), 0, 2000) : null,
                'payload' => $this->redactPayloadForLog($payload),
                'user_id' => $user->id,
            ]);

            throw new \RuntimeException($out);
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

    private function normalizeGender(string $gender): string
    {
        $g = strtolower(trim($gender));
        if ($g === 'm' || $g === 'male' || $g === 'man') {
            return 'male';
        }
        if ($g === 'f' || $g === 'female' || $g === 'woman') {
            return 'female';
        }
        return $g;
    }

    private function deriveTitleFromGender(string $gender): ?string
    {
        $g = strtolower(trim($gender));
        if ($g === 'male') {
            return 'Mr';
        }
        if ($g === 'female') {
            return 'Ms';
        }
        return null;
    }

    /**
     * Do not leak PII into logs; keep enough context to debug provider validation issues.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function redactPayloadForLog(array $payload): array
    {
        $redacted = $payload;
        foreach (['billing_address', 'email', 'date_of_birth', 'phone', 'phone_number'] as $key) {
            if (array_key_exists($key, $redacted)) {
                $val = $redacted[$key];
                $redacted[$key] = is_string($val) && $val !== '' ? '[redacted]' : $val;
            }
        }
        return $redacted;
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

    private function parseSouthAfricanIdDob(string $idNumber): ?string
    {
        $idNumber = preg_replace('/\D+/', '', $idNumber) ?: '';
        if (!preg_match('/^\d{13}$/', $idNumber)) {
            return null;
        }

        $yy = (int) substr($idNumber, 0, 2);
        $mm = (int) substr($idNumber, 2, 2);
        $dd = (int) substr($idNumber, 4, 2);

        $nowYY = (int) now()->format('y');
        $century = $yy <= $nowYY ? 2000 : 1900;
        $yyyy = $century + $yy;

        try {
            $dob = \Carbon\Carbon::createFromDate($yyyy, $mm, $dd);
        } catch (\Throwable $e) {
            return null;
        }

        if ($dob->isFuture()) {
            return null;
        }

        return $dob->toDateString();
    }
}
