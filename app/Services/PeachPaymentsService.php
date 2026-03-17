<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PeachPaymentsService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.peach.enabled', false);
    }

    /**
     * @param array{
     *  amount:string,
     *  currency:string,
     *  merchant_transaction_id:string,
     *  shopper_result_url:string,
     *  bank:string,
     *  virtual_account_id:string,
     *  given_name:string,
     *  surname:string,
     *  email:string,
     *  mobile:string
     * } $payload
     */
    public function createPayShapPayment(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.peach.base_url', 'https://testapi-v2.peachpayments.com'), '/');
        $timeout = (int) config('services.peach.timeout', 20);

        $entityId = trim((string) config('services.peach.entity_id', ''));
        $userId = trim((string) config('services.peach.user_id', ''));
        $password = trim((string) config('services.peach.password', ''));

        if ($entityId === '' || $userId === '' || $password === '') {
            throw new \RuntimeException('Peach Payments is not configured. Set PEACH_ENTITY_ID, PEACH_USER_ID, and PEACH_PASSWORD.');
        }

        $request = [
            'authentication' => [
                'entityId' => $entityId,
                'userId' => $userId,
                'password' => $password,
            ],
            'amount' => (string) ($payload['amount'] ?? ''),
            'currency' => strtoupper((string) ($payload['currency'] ?? 'ZAR')),
            'paymentType' => 'DB',
            'paymentBrand' => 'PAYSHAP',
            'merchantTransactionId' => (string) ($payload['merchant_transaction_id'] ?? ''),
            'shopperResultUrl' => (string) ($payload['shopper_result_url'] ?? ''),
            'virtualAccount' => [
                'bank' => (string) ($payload['bank'] ?? ''),
                'type' => 'CELLPHONE',
                'accountId' => (string) ($payload['virtual_account_id'] ?? ''),
            ],
            'customer' => [
                'givenName' => (string) ($payload['given_name'] ?? ''),
                'surname' => (string) ($payload['surname'] ?? ''),
                'email' => (string) ($payload['email'] ?? ''),
                'mobile' => (string) ($payload['mobile'] ?? ''),
            ],
        ];

        try {
            $res = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($baseUrl . '/payments', $request);
        } catch (ConnectionException $e) {
            Log::warning('Peach PayShap connection failed', [
                'base_url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to connect to Peach Payments: ' . $e->getMessage());
        }

        $json = $res->json();
        if (!$res->ok()) {
            $message = is_array($json) ? (string) ($json['message'] ?? '') : '';
            $message = $message !== '' ? $message : ('HTTP ' . $res->status());
            throw new \RuntimeException('Peach Payments PayShap initiation failed: ' . $message);
        }

        return is_array($json) ? $json : ['raw' => $json];
    }

    public function getPayment(string $uniqueId): array
    {
        $uniqueId = trim($uniqueId);
        if ($uniqueId === '') {
            throw new \InvalidArgumentException('Missing Peach uniqueId.');
        }

        $baseUrl = rtrim((string) config('services.peach.base_url', 'https://testapi-v2.peachpayments.com'), '/');
        $timeout = (int) config('services.peach.timeout', 20);

        $entityId = trim((string) config('services.peach.entity_id', ''));
        $userId = trim((string) config('services.peach.user_id', ''));
        $password = trim((string) config('services.peach.password', ''));

        if ($entityId === '' || $userId === '' || $password === '') {
            throw new \RuntimeException('Peach Payments is not configured. Set PEACH_ENTITY_ID, PEACH_USER_ID, and PEACH_PASSWORD.');
        }

        $query = [
            // Peach uses nested authentication keys.
            'authentication.entityId' => $entityId,
            'authentication.userId' => $userId,
            'authentication.password' => $password,
        ];

        try {
            $res = Http::timeout($timeout)
                ->acceptJson()
                ->get($baseUrl . '/payments/' . urlencode($uniqueId), $query);
        } catch (ConnectionException $e) {
            Log::warning('Peach PayShap status connection failed', [
                'base_url' => $baseUrl,
                'unique_id' => $uniqueId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to connect to Peach Payments: ' . $e->getMessage());
        }

        $json = $res->json();
        if (!$res->ok()) {
            $message = is_array($json) ? (string) ($json['message'] ?? '') : '';
            $message = $message !== '' ? $message : ('HTTP ' . $res->status());
            throw new \RuntimeException('Peach Payments status lookup failed: ' . $message);
        }

        return is_array($json) ? $json : ['raw' => $json];
    }

    public function extractUniqueId(array $response): string
    {
        // Common field names we might see.
        $candidates = [
            Arr::get($response, 'uniqueId'),
            Arr::get($response, 'id'),
            Arr::get($response, 'data.uniqueId'),
            Arr::get($response, 'data.id'),
        ];

        foreach ($candidates as $c) {
            $c = trim((string) $c);
            if ($c !== '') {
                return $c;
            }
        }

        return '';
    }

    public function extractRedirectUrl(array $response): string
    {
        $candidates = [
            Arr::get($response, 'redirect.url'),
            Arr::get($response, 'redirectUrl'),
            Arr::get($response, 'redirect.url'),
            Arr::get($response, 'links.redirect'),
            Arr::get($response, '_links.redirect.href'),
            Arr::get($response, 'data.redirect.url'),
        ];

        foreach ($candidates as $c) {
            $c = trim((string) $c);
            if ($c !== '') {
                return $c;
            }
        }

        return '';
    }

    public function classifyStatus(array $response): string
    {
        $code = trim((string) Arr::get($response, 'result.code', ''));
        $description = trim((string) Arr::get($response, 'result.description', ''));

        // Best-effort mapping. Persist the raw response for later debugging.
        if ($code !== '') {
            if (str_starts_with($code, '000.000.') || str_starts_with($code, '000.100.')) {
                return 'successful';
            }
            if (str_starts_with($code, '000.200.') || str_starts_with($code, '000.400.')) {
                return 'pending';
            }
            // Most other codes are failures.
            return 'failed';
        }

        $status = strtolower(trim((string) Arr::get($response, 'status', '')));
        if (in_array($status, ['successful', 'success', 'paid'], true)) return 'successful';
        if (in_array($status, ['pending', 'in_progress', 'processing'], true)) return 'pending';
        if ($status !== '') return 'failed';

        // If nothing is present, treat as pending.
        return $description !== '' ? 'pending' : 'pending';
    }
}

