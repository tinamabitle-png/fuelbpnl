<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HereMapsService
{
    private const TOKEN_CACHE_KEY = 'here_maps_oauth_token';
    private const TOKEN_TTL_FALLBACK = 3000;

    public function geocode(string $query, int $limit = 5): array
    {
        $url = 'https://geocode.search.hereapi.com/v1/geocode';
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->get($url, [
                'q' => $query,
                'limit' => max(1, min($limit, 10)),
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('HERE geocode request failed with status ' . $response->status());
        }

        $payload = $response->json();
        return is_array($payload) ? $payload : [];
    }

    public function reverseGeocode(float $lat, float $lng, int $limit = 1): array
    {
        $url = 'https://revgeocode.search.hereapi.com/v1/revgeocode';
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->get($url, [
                'at' => sprintf('%.8F,%.8F', $lat, $lng),
                'lang' => 'en-US',
                'limit' => max(1, min($limit, 5)),
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('HERE reverse geocode request failed with status ' . $response->status());
        }

        $payload = $response->json();
        return is_array($payload) ? $payload : [];
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $accessKeyId = trim((string) config('services.here_maps.access_key_id'));
        $accessKeySecret = trim((string) config('services.here_maps.access_key_secret'));
        $clientId = trim((string) config('services.here_maps.client_id'));
        $tokenEndpoint = trim((string) config('services.here_maps.token_endpoint_url', 'https://account.api.here.com/oauth2/token'));

        if ($accessKeyId === '' || $accessKeySecret === '') {
            throw new RuntimeException('HERE access key credentials are not configured.');
        }

        $candidates = array_values(array_unique(array_filter([
            $accessKeyId,
            $clientId,
        ])));

        $lastError = 'Unknown HERE token error';
        foreach ($candidates as $candidateClientId) {
            [$token, $expiresIn, $error] = $this->requestToken($tokenEndpoint, $candidateClientId, $accessKeySecret);
            if (is_string($token) && $token !== '') {
                $ttlSeconds = max(60, ((int) $expiresIn) - 60);
                Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($ttlSeconds));
                return $token;
            }
            $lastError = $error ?: $lastError;
        }

        throw new RuntimeException('HERE token request failed: ' . $lastError);
    }

    /**
     * @return array{0:string,1:int,2:string}
     */
    private function requestToken(string $endpoint, string $clientId, string $clientSecret): array
    {
        $formResponse = Http::asForm()
            ->acceptJson()
            ->timeout(15)
            ->post($endpoint, [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

        $token = $this->extractAccessToken($formResponse->json());
        if ($formResponse->ok() && $token !== '') {
            return [$token, $this->extractExpiresIn($formResponse->json()), ''];
        }

        $basicToken = base64_encode($clientId . ':' . $clientSecret);
        $basicResponse = Http::asForm()
            ->acceptJson()
            ->timeout(15)
            ->withHeaders([
                'Authorization' => 'Basic ' . $basicToken,
            ])
            ->post($endpoint, [
                'grant_type' => 'client_credentials',
            ]);

        $basicAccessToken = $this->extractAccessToken($basicResponse->json());
        if ($basicResponse->ok() && $basicAccessToken !== '') {
            return [$basicAccessToken, $this->extractExpiresIn($basicResponse->json()), ''];
        }

        $errorPayload = $basicResponse->json();
        if (!is_array($errorPayload)) {
            $errorPayload = $formResponse->json();
        }
        $errorText = is_array($errorPayload)
            ? (string) ($errorPayload['error_description'] ?? $errorPayload['error'] ?? '')
            : '';
        if ($errorText === '') {
            $errorText = 'status ' . $basicResponse->status();
        }

        return ['', 0, $errorText];
    }

    private function extractAccessToken(mixed $payload): string
    {
        return is_array($payload) ? (string) ($payload['access_token'] ?? '') : '';
    }

    private function extractExpiresIn(mixed $payload): int
    {
        return is_array($payload) ? (int) ($payload['expires_in'] ?? self::TOKEN_TTL_FALLBACK) : self::TOKEN_TTL_FALLBACK;
    }
}
