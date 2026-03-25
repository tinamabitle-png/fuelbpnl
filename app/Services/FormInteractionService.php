<?php

namespace App\Services;

use App\Models\FormInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FormInteractionService
{
    /**
     * Best-effort: never throw, never block core flows.
     *
     * @param array<string, mixed> $extra
     */
    public static function record(string $form, string $action, Request $request, ?string $outcome = null, array $extra = []): void
    {
        try {
            if (!Schema::hasTable('form_interactions')) {
                return;
            }

            $ip = (string) ($request->header('CF-Connecting-IP') ?: $request->ip());
            $countryCode = (string) ($request->header('CF-IPCountry') ?: '');
            $countryCode = $countryCode !== '' ? $countryCode : null;

            $payload = [
                'form' => $form,
                'action' => $action,
                'outcome' => $outcome,
                'ip' => $ip !== '' ? $ip : null,
                'country_code' => $countryCode,
                'submitted_city' => isset($extra['submitted_city']) ? (string) $extra['submitted_city'] : null,
                'submitted_country' => isset($extra['submitted_country']) ? (string) $extra['submitted_country'] : null,
                'path' => substr((string) $request->path(), 0, 255),
                'referer' => substr((string) $request->headers->get('referer', ''), 0, 255) ?: null,
                'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
            ];

            FormInteraction::create($payload);
        } catch (\Throwable $e) {
            // Intentionally swallow.
        }
    }
}

