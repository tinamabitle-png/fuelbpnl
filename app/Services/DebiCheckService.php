<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DebiCheckService
{
    public function configured(): bool
    {
        return trim((string) config('services.rapidpay.base_url')) !== ''
            && trim((string) config('services.rapidpay.username')) !== ''
            && trim((string) config('services.rapidpay.password')) !== '';
    }

    public function createMandate(User $user, array $payload): array
    {
        $this->assertConfigured();
        $reference = (string) ($payload['clientReference'] ?? ('DBC-' . $user->id . '-' . strtoupper(Str::random(8))));

        $body = array_merge($payload, [
            'clientReference' => $reference,
        ]);

        $response = $this->authorizedJson('POST', '/mandates/import', $body);
        $data = $this->extractData($response);

        return [
            'reference' => $reference,
            'mandate_id' => (string) ($data['mandateId'] ?? $data['id'] ?? $reference),
            'status' => strtolower((string) ($data['status'] ?? 'submitted')),
            'raw' => $data,
        ];
    }

    public function getMandate(string $mandateId): array
    {
        $this->assertConfigured();
        $response = $this->authorizedJson('GET', '/mandates/' . urlencode($mandateId));
        $data = $this->extractData($response);

        return [
            'mandate_id' => (string) ($data['mandateId'] ?? $data['id'] ?? $mandateId),
            'status' => strtolower((string) ($data['status'] ?? 'unknown')),
            'raw' => $data,
        ];
    }

    public function collect(string $mandateId, float $amount, string $reference): array
    {
        $this->assertConfigured();
        $path = (string) config('services.rapidpay.collection_path', '/mandates/collect');
        $payload = [
            'mandateId' => $mandateId,
            'amount' => round($amount, 2),
            'reference' => $reference,
        ];

        $response = $this->authorizedJson('POST', $path, $payload);
        $data = $this->extractData($response);
        $status = strtolower((string) ($data['status'] ?? ''));
        if (!in_array($status, ['success', 'successful', 'accepted', 'processed'], true)) {
            throw new \RuntimeException('DebiCheck collection did not succeed. Status: ' . strtoupper($status === '' ? 'UNKNOWN' : $status));
        }

        return [
            'reference' => (string) ($data['reference'] ?? $reference),
            'status' => $status,
            'raw' => $data,
        ];
    }

    private function getToken(): string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->timeout(max(5, (int) config('services.rapidpay.timeout', 20)))
            ->post(
                rtrim((string) config('services.rapidpay.base_url'), '/') . '/token',
                [
                    'grant_type' => 'password',
                    'userName' => (string) config('services.rapidpay.username'),
                    'password' => (string) config('services.rapidpay.password'),
                ]
            );

        if (!$response->successful()) {
            throw new \RuntimeException('DebiCheck token request failed: ' . $this->extractError($response));
        }

        $token = trim((string) ($response->json('access_token') ?? $response->json('token') ?? ''));
        if ($token === '') {
            throw new \RuntimeException('DebiCheck token was not returned.');
        }

        return $token;
    }

    private function authorizedJson(string $method, string $path, array $payload = []): Response
    {
        $token = $this->getToken();
        $url = rtrim((string) config('services.rapidpay.base_url'), '/') . '/' . ltrim($path, '/');

        $client = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(max(5, (int) config('services.rapidpay.timeout', 20)));

        $method = strtoupper($method);
        if ($method === 'GET') {
            $response = $client->get($url, $payload);
        } else {
            $response = $client->post($url, $payload);
        }

        if (!$response->successful()) {
            throw new \RuntimeException('DebiCheck API request failed: ' . $this->extractError($response));
        }

        return $response;
    }

    private function extractData(Response $response): array
    {
        $json = $response->json();
        if (is_array($json)) {
            if (isset($json['data']) && is_array($json['data'])) {
                return $json['data'];
            }
            return $json;
        }
        return [];
    }

    private function extractError(Response $response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            foreach (['error_description', 'message', 'error', 'detail'] as $key) {
                $value = trim((string) ($json[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return 'HTTP ' . $response->status();
    }

    private function assertConfigured(): void
    {
        if (!(bool) config('services.billing.enabled', false)) {
            throw new \RuntimeException('Billing is disabled for this environment. Set BILLING_ENABLED=true only on approved payment environments.');
        }

        if (!$this->configured()) {
            throw new \RuntimeException('DebiCheck is not configured. Set RAPIDPAY_BASE_URL, RAPIDPAY_USERNAME, RAPIDPAY_PASSWORD.');
        }
    }
}
