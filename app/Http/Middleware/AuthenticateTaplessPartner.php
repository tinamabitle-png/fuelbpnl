<?php

namespace App\Http\Middleware;

use App\Models\TaplessApiPartner;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTaplessPartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $publicKey = trim((string) $request->header('X-Bwiser-Key', ''));
        $timestamp = trim((string) $request->header('X-Bwiser-Timestamp', ''));
        $signature = trim((string) $request->header('X-Bwiser-Signature', ''));

        if ($publicKey === '' || $timestamp === '' || $signature === '') {
            return $this->error('Partner API authentication headers are required.', 401);
        }

        if (!ctype_digit($timestamp)) {
            return $this->error('Invalid partner timestamp.', 401);
        }

        $timestampValue = (int) $timestamp;
        if (abs(now()->timestamp - $timestampValue) > 300) {
            return $this->error('Partner request timestamp is outside the allowed window.', 401);
        }

        $partner = TaplessApiPartner::query()
            ->where('public_key', $publicKey)
            ->first();

        if (!$partner || !$partner->isActive()) {
            return $this->error('Tapless API partner is invalid or inactive.', 401);
        }

        $allowedIps = array_values(array_filter((array) $partner->allowed_ips));
        if ($allowedIps !== [] && !$this->ipAllowed($request->ip(), $allowedIps)) {
            return $this->error('Partner IP is not allowed.', 403);
        }

        $rawBody = (string) $request->getContent();
        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $partner->decryptSecret());
        if (!hash_equals($expected, $signature)) {
            return $this->error('Partner request signature is invalid.', 401);
        }

        $partner->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('tapless_partner', $partner);

        return $next($request);
    }

    private function ipAllowed(?string $requestIp, array $allowedIps): bool
    {
        if (!$requestIp) {
            return false;
        }

        return in_array($requestIp, $allowedIps, true);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
