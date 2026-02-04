<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Idempotency
{
    public function handle(Request $request, Closure $next)
    {
        // Skip for GET, DELETE, OPTIONS, HEAD
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            // For critical financial operations, require idempotency key
            if (in_array($request->route()->getName(), ['vouchers.request', 'wallet.add-funds', 'wallet.make-repayment'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Idempotency-Key header required for this operation'
                ], 400);
            }
            return $next($request);
        }

        $cacheKey = 'idempotency:' . $idempotencyKey;

        // Check if we've processed this request before
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            return response()->json($cachedResponse['data'], $cachedResponse['status']);
        }

        $response = $next($request);

        // Cache successful responses (2xx status codes)
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'data' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode()
            ], now()->addHours(24));
        }

        // Add idempotency key to response headers
        $response->header('Idempotency-Key', $idempotencyKey);

        return $response;
    }
}