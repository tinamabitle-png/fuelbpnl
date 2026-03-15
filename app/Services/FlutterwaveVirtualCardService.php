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
            Log::warning('Flutterwave reveal connection failed', [
                'base_url' => $baseUrl,
                'endpoint' => '/v3/virtual-cards/{id}',
                'error' => $e->getMessage(),
            ]);
            $msg = trim((string) $e->getMessage());
            $msg = $msg !== '' ? (': ' . $msg) : '';
            throw new \RuntimeException('Failed to connect to Flutterwave' . $msg);
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
            // Identity keys (some Flutterwave accounts require these)
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

        // Flutterwave may enforce these fields. Always derive+send them so we don't depend on env toggles.
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
            ?? '1990-01-01'));
        $dob = $this->sanitizeDob($dob) ?? '';

        if ($firstName === '' || $lastName === '' || $dob === '') {
            throw new \RuntimeException(
                'Flutterwave card creation requires first_name, last_name, and date_of_birth. ' .
                'Populate users.first_name/users.last_name/users.date_of_birth (run migrations/backfill), ' .
                'or set FLUTTERWAVE_VIRTUAL_CARDS_FIRST_NAME/LAST_NAME/DATE_OF_BIRTH.'
            );
        }

        $billingCountry = strtoupper(trim((string) ($billingFiltered['billing_country'] ?? config('services.flutterwave.virtual_cards_billing_country', 'ZA')))) ?: 'ZA';
        $phone = trim((string) ($billingFiltered['phone_number']
            ?? $billingFiltered['phone']
            ?? (string) ($user->phone ?? '')
            ?? (string) config('services.flutterwave.virtual_cards_phone', '')));
        $phone = $this->normalizePhone($phone, $billingCountry);
        if ($phone === '') {
            throw new \RuntimeException(
                'Flutterwave card creation requires phone. ' .
                'Save a phone number on the user profile (users.phone) or set FLUTTERWAVE_VIRTUAL_CARDS_PHONE.'
            );
        }

        $payload = array_merge([
            'currency' => $currency,
            // Flutterwave examples use integer amounts; avoid floats.
            'amount' => max(1, (int) ceil($amount)),
            'billing_name' => $fullName,
            'billing_address' => (string) ($billingFiltered['billing_address'] ?? config('services.flutterwave.virtual_cards_billing_address', 'Unknown')),
            'billing_city' => (string) ($billingFiltered['billing_city'] ?? config('services.flutterwave.virtual_cards_billing_city', 'Johannesburg')),
            'billing_state' => (string) ($billingFiltered['billing_state'] ?? config('services.flutterwave.virtual_cards_billing_state', 'Gauteng')),
            // Flutterwave validations vary by account/product; provide common aliases.
            'billing_postal_code' => (string) ($billingFiltered['billing_postal_code'] ?? config('services.flutterwave.virtual_cards_billing_postal_code', '0001')),
            'billing_zip' => (string) ($billingFiltered['billing_postal_code'] ?? config('services.flutterwave.virtual_cards_billing_postal_code', '0001')),
            'billing_zip_code' => (string) ($billingFiltered['billing_postal_code'] ?? config('services.flutterwave.virtual_cards_billing_postal_code', '0001')),
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

        // Required identity keys: send last so they cannot be overridden.
        $payload['first_name'] = $firstName;
        $payload['last_name'] = $lastName;
        $payload['date_of_birth'] = $dob;

        // Optional identity keys: include if non-empty (some accounts enforce these too).
        $email = trim((string) ($billingFiltered['email'] ?? config('services.flutterwave.virtual_cards_email') ?? (string) ($user->email ?? '')));
        if ($email !== '') {
            $payload['email'] = $email;
        }
        // Phone is required on some Flutterwave accounts; always send it.
        $payload['phone'] = $phone;
        $payload['phone_number'] = $phone;
        $gender = $this->normalizeGender(trim((string) ($billingFiltered['gender']
            ?? ($user->gender ?? null)
            ?? config('services.flutterwave.virtual_cards_gender')
            ?? '')));
        if ($gender !== '') {
            $payload['gender'] = $gender;
        }
        $title = trim((string) ($billingFiltered['title']
            ?? config('services.flutterwave.virtual_cards_title')
            ?? $this->deriveTitleFromGender($gender)
            ?? ''));
        if ($title !== '') {
            $payload['title'] = $title;
        }

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($secretKey)
                ->post($baseUrl . '/v3/virtual-cards', $payload);
        } catch (ConnectionException $e) {
            Log::warning('Flutterwave create connection failed', [
                'base_url' => $baseUrl,
                'endpoint' => '/v3/virtual-cards',
                'error' => $e->getMessage(),
            ]);
            $msg = trim((string) $e->getMessage());
            $msg = $msg !== '' ? (': ' . $msg) : '';
            throw new \RuntimeException('Failed to connect to Flutterwave' . $msg);
        }

        if (!$response->ok()) {
            $json = $response->json();
            $message = is_array($json) ? (string) ($json['message'] ?? '') : '';
            $errors = null;
            if (is_array($json)) {
                $errors = $json['errors'] ?? null;
                if ($errors === null) {
                    $errors = Arr::get($json, 'data.errors');
                }
                if ($errors === null) {
                    $errors = Arr::get($json, 'data.data.errors');
                }
            }

            $details = '';
            if (is_array($errors)) {
                // Render common error formats into a single line.
                $flat = [];
                $isList = array_keys($errors) === range(0, count($errors) - 1);
                if ($isList) {
                    foreach ($errors as $item) {
                        if (!is_array($item)) {
                            $flat[] = (string) $item;
                            continue;
                        }
                        $field = (string) ($item['field'] ?? $item['name'] ?? $item['key'] ?? '');
                        $msg = (string) ($item['message'] ?? $item['msg'] ?? $item['error'] ?? '');
                        $msg = trim($msg) !== '' ? $msg : json_encode($item);
                        $flat[] = ($field !== '' ? ($field . ': ') : '') . $msg;
                    }
                } else {
                    foreach ($errors as $k => $v) {
                        if (is_string($v)) {
                            $flat[] = $k . ': ' . $v;
                        } elseif (is_array($v)) {
                            $flat[] = $k . ': ' . implode(', ', array_map('strval', $v));
                        } else {
                            $flat[] = $k . ': ' . (string) $v;
                        }
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

    private function sanitizeDob(string $dob): ?string
    {
        $dob = trim($dob);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            return null;
        }

        try {
            $c = \Carbon\Carbon::createFromFormat('Y-m-d', $dob);
        } catch (\Throwable $e) {
            return null;
        }

        if ($c->isFuture()) {
            return null;
        }

        return $c->toDateString();
    }

    private function normalizePhone(string $phone, string $billingCountry = 'ZA'): string
    {
        $p = trim($phone);
        if ($p === '') {
            return '';
        }

        // Remove common formatting characters, keep leading '+' if present.
        $p = preg_replace('/[()\s\-\.]+/', '', $p) ?? $p;

        // Convert leading 00 to +
        if (str_starts_with($p, '00')) {
            $p = '+' . substr($p, 2);
        }

        // Best-effort ZA normalization.
        if (strtoupper($billingCountry) === 'ZA') {
            if (str_starts_with($p, '0') && preg_match('/^0\d{9}$/', $p)) {
                $p = '+27' . substr($p, 1);
            } elseif (preg_match('/^27\d{9}$/', $p)) {
                $p = '+' . $p;
            }
        }

        // If it still has no '+', keep digits only.
        if (!str_starts_with($p, '+')) {
            $p = preg_replace('/\D+/', '', $p) ?: $p;
        } else {
            $digits = preg_replace('/\D+/', '', substr($p, 1)) ?: '';
            $p = $digits !== '' ? ('+' . $digits) : '';
        }

        // Minimal sanity check.
        $digitsOnly = preg_replace('/\D+/', '', $p) ?: '';
        if (strlen($digitsOnly) < 7) {
            return '';
        }

        return $p;
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
            Log::warning('Flutterwave fund connection failed', [
                'base_url' => $baseUrl,
                'endpoint' => '/v3/virtual-cards/{id}/fund',
                'error' => $e->getMessage(),
            ]);
            $msg = trim((string) $e->getMessage());
            $msg = $msg !== '' ? (': ' . $msg) : '';
            throw new \RuntimeException('Failed to connect to Flutterwave' . $msg);
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
