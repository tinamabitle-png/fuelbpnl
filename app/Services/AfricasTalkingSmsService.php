<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AfricasTalkingSmsService
{
    public function sendOtp(string $phone, string $message): array
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === null) {
            return [
                'success' => false,
                'reference' => null,
                'error' => 'Invalid phone number format.',
            ];
        }

        $dryRun = (bool) config('services.africastalking.sms_dry_run', false);
        if ($dryRun) {
            Log::info('AfricaTalking SMS dry-run', [
                'phone' => $normalized,
                'message_preview' => Str::limit($message, 60),
            ]);

            return [
                'success' => true,
                'reference' => 'SMS-DRYRUN-' . strtoupper(Str::random(8)),
                'error' => null,
            ];
        }

        $apiKey = trim((string) config('services.africastalking.api_key', ''));
        $username = trim((string) config('services.africastalking.username', 'sandbox'));
        $from = trim((string) config('services.africastalking.sms_from', ''));
        $baseUrl = trim((string) config('services.africastalking.sms_base_url', 'https://api.africastalking.com/version1/messaging'));
        $timeoutSeconds = max(2, (int) config('services.africastalking.sms_timeout_seconds', 12));

        if ($apiKey === '') {
            return [
                'success' => false,
                'reference' => null,
                'error' => 'Africa\'s Talking API key is missing.',
            ];
        }

        try {
            $payload = [
                'username' => $username,
                'to' => $normalized,
                'message' => $message,
            ];
            if ($from !== '') {
                $payload['from'] = $from;
            }

            $response = Http::asForm()
                ->timeout($timeoutSeconds)
                ->withHeaders([
                    'apiKey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($baseUrl, $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'reference' => null,
                    'error' => 'HTTP ' . $response->status(),
                ];
            }

            $body = $response->json();
            $recipients = is_array($body['SMSMessageData']['Recipients'] ?? null)
                ? $body['SMSMessageData']['Recipients']
                : [];
            $first = $recipients[0] ?? [];
            $status = strtolower((string) ($first['status'] ?? ''));
            $messageId = (string) ($first['messageId'] ?? '');

            $ok = in_array($status, ['success', 'sent', 'queued'], true)
                || (int) ($body['SMSMessageData']['Recipients'][0]['statusCode'] ?? 0) >= 100;

            if (!$ok) {
                return [
                    'success' => false,
                    'reference' => $messageId !== '' ? $messageId : null,
                    'error' => (string) ($first['status'] ?? 'SMS gateway rejected request.'),
                ];
            }

            return [
                'success' => true,
                'reference' => $messageId !== '' ? $messageId : ('SMS-' . strtoupper(Str::random(10))),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'reference' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '27' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '27')) {
            $digits = '27' . $digits;
        }

        if (strlen($digits) < 11 || strlen($digits) > 15) {
            return null;
        }

        return '+' . $digits;
    }
}

