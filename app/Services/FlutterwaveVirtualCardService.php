<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class FlutterwaveVirtualCardService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.flutterwave.secret_key')) !== '';
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

