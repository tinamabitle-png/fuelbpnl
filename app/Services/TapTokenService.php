<?php

namespace App\Services;

use App\Models\FuelVoucher;
use App\Models\User;

class TapTokenService
{
    public const PREFIX = 'BWT1';
    private const TTL_SECONDS = 180;

    public function issue(FuelVoucher $voucher, User $user, int $ttlSeconds = self::TTL_SECONDS): array
    {
        $issuedAt = now()->timestamp;
        $expiresAt = $issuedAt + max(30, $ttlSeconds);

        $payload = [
            'vid' => (int) $voucher->id,
            'code' => (string) $voucher->code,
            'uid' => (int) $user->id,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'n' => substr(bin2hex(random_bytes(8)), 0, 16),
        ];

        $payloadB64 = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->sign($payloadB64);

        return [
            'token' => self::PREFIX . '.' . $payloadB64 . '.' . $signature,
            'expires_at' => now()->setTimestamp($expiresAt)->toIso8601String(),
            'payload' => $payload,
        ];
    }

    public function verify(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return null;
        }

        [$prefix, $payloadB64, $signature] = $parts;
        $expected = $this->sign($payloadB64);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decodedJson = $this->base64UrlDecode($payloadB64);
        if (!$decodedJson) {
            return null;
        }

        $payload = json_decode($decodedJson, true);
        if (!is_array($payload)) {
            return null;
        }

        $required = ['vid', 'code', 'uid', 'iat', 'exp'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                return null;
            }
        }

        $exp = (int) $payload['exp'];
        if ($exp < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    private function sign(string $payloadB64): string
    {
        $key = (string) config('app.key');
        return $this->base64UrlEncode(hash_hmac('sha256', $payloadB64, $key, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $raw = strtr($value, '-_', '+/');
        $padding = strlen($raw) % 4;
        if ($padding > 0) {
            $raw .= str_repeat('=', 4 - $padding);
        }
        return (string) base64_decode($raw);
    }
}

