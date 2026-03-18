<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class LunoService
{
    private function client(): PendingRequest
    {
        $cfg = (array) config('services.luno', []);
        $baseUrl = (string) ($cfg['base_url'] ?? 'https://api.luno.com');
        $keyId = (string) ($cfg['key_id'] ?? '');
        $keySecret = (string) ($cfg['key_secret'] ?? '');
        $timeout = (int) ($cfg['timeout'] ?? 20);

        return Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->withBasicAuth($keyId, $keySecret);
    }

    public function enabled(): bool
    {
        return (bool) config('services.luno.enabled', false);
    }

    public function ticker(string $pair): array
    {
        return $this->client()
            ->get('/api/1/ticker', ['pair' => $pair])
            ->throw()
            ->json() ?? [];
    }

    public function fundingAddress(string $asset): array
    {
        return $this->client()
            ->get('/api/1/funding_address', ['asset' => $asset])
            ->throw()
            ->json() ?? [];
    }

    public function listTransfers(string $accountId, int $limit = 100): array
    {
        return $this->client()
            ->get('/api/exchange/1/transfers', [
                'account_id' => $accountId,
                'limit' => $limit,
            ])
            ->throw()
            ->json() ?? [];
    }
}

