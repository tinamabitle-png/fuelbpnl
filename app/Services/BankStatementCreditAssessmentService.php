<?php

namespace App\Services;

use App\Models\BankStatementUpload;
use App\Models\CreditDecision;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class BankStatementCreditAssessmentService
{
    public function assessAndStore(User $user, BankStatementUpload $upload): CreditDecision
    {
        $threshold = (float) config('credit.system_max_limit', 30000);
        $minimum = (float) config('credit.minimum_limit', 1000);

        $metrics = $this->buildMetrics($user, $upload);
        $recommendation = $this->generateRecommendation($metrics, $threshold, $minimum);

        $decision = CreditDecision::create([
            'user_id' => $user->id,
            'upload_id' => $upload->id,
            'score' => $recommendation['score'],
            'decision' => $recommendation['decision'],
            'application_type' => 'voucher_bnpl',
            'reasons' => $recommendation['reasons'],
            'explanation_json' => [
                'agent_recommendation' => [
                    'model' => $recommendation['model_version'],
                    'recommendation' => $recommendation,
                ],
                'metrics' => $metrics,
            ],
            'model_version' => $recommendation['model_version'],
            'policy_version' => 'bank-statement-v1',
            'source' => 'bank_statement',
            'decided_at' => now(),
        ]);

        $upload->forceFill([
            'status' => 'needs_review',
            'processed_at' => now(),
            'ocr_confidence' => $recommendation['confidence'],
            'error_message' => null,
        ])->save();

        return $decision;
    }

    public function applyDecisionToCreditLimit(User $user, CreditDecision $decision): void
    {
        $threshold = (float) config('credit.system_max_limit', 30000);
        $minimum = (float) config('credit.minimum_limit', 1000);

        $recommended = (float) data_get(
            $decision->explanation_json,
            'agent_recommendation.recommendation.recommended_limit',
            0
        );

        $approved = max($minimum, min($threshold, $recommended));

        $user->creditLimit()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'limit' => $approved,
                'status' => 'active',
                'review_date' => now()->addDays(90)->toDateString(),
            ]
        );
    }

    private function buildMetrics(User $user, BankStatementUpload $upload): array
    {
        $feature = $upload->accounts()->with('feature')->get()->pluck('feature')->filter()->first();

        return [
            'credit_score' => (int) ($user->credit_score ?? 500),
            'statement_size_bytes' => (int) ($upload->file_size ?? 0),
            'avg_monthly_income' => (float) ($feature?->avg_monthly_income ?? 0),
            'avg_monthly_expenses' => (float) ($feature?->avg_monthly_expenses ?? 0),
            'avg_daily_balance' => (float) ($feature?->avg_daily_balance ?? 0),
            'cash_buffer_days' => (float) ($feature?->cash_buffer_days ?? 0),
            'nsf_count' => (int) ($feature?->nsf_count ?? 0),
            'overdraft_count' => (int) ($feature?->overdraft_count ?? 0),
            'risk_score' => $feature?->risk_score !== null ? (int) $feature->risk_score : null,
            'risk_band' => (string) ($feature?->risk_band ?? ''),
        ];
    }

    private function generateRecommendation(array $metrics, float $threshold, float $minimum): array
    {
        $ai = $this->tryOpenAiRecommendation($metrics, $threshold, $minimum);
        if ($ai !== null) {
            return $ai;
        }

        $score = (int) $metrics['credit_score'];
        if ($metrics['risk_score'] !== null) {
            $score = (int) round(($score * 0.6) + ((int) $metrics['risk_score'] * 0.4));
        }

        $score -= min(120, ((int) $metrics['nsf_count'] * 15) + ((int) $metrics['overdraft_count'] * 8));
        if ((float) $metrics['avg_monthly_income'] > 0 && (float) $metrics['avg_monthly_expenses'] > 0) {
            $margin = (float) $metrics['avg_monthly_income'] - (float) $metrics['avg_monthly_expenses'];
            if ($margin > 0) {
                $score += 25;
            } else {
                $score -= 35;
            }
        }

        $score = max(300, min(850, $score));

        $baseLimit = match (true) {
            $score >= 780 => 30000,
            $score >= 700 => 22000,
            $score >= 640 => 15000,
            $score >= 580 => 9000,
            default => 3000,
        };

        $recommended = max($minimum, min($threshold, $baseLimit));
        $decision = $score >= 680 ? 'approve' : ($score >= 560 ? 'review' : 'deny');
        $confidence = $decision === 'approve' ? 85 : ($decision === 'review' ? 72 : 90);

        return [
            'decision' => $decision,
            'score' => $score,
            'confidence' => $confidence,
            'recommended_limit' => $recommended,
            'reasons' => [
                'Assessment derived from account score and statement risk indicators.',
                'Final credit limit is capped by system threshold.',
            ],
            'model_version' => 'rules-fallback-v1',
            'threshold_cap' => $threshold,
            'minimum_limit' => $minimum,
        ];
    }

    private function tryOpenAiRecommendation(array $metrics, float $threshold, float $minimum): ?array
    {
        $apiKey = trim((string) config('services.openai.api_key'));
        if ($apiKey === '') {
            return null;
        }

        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $timeout = max(8, (int) config('services.openai.timeout', 20));

        $prompt = [
            'role' => 'system',
            'content' => 'You are a conservative credit-risk assistant. Output strict JSON with keys: decision (approve|review|deny), score (300-850), confidence (0-100), recommended_limit, reasons (array of 2 short strings).',
        ];
        $userMessage = [
            'role' => 'user',
            'content' => json_encode([
                'metrics' => $metrics,
                'policy' => [
                    'threshold_cap' => $threshold,
                    'minimum_limit' => $minimum,
                ],
            ]),
        ];

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout($timeout)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'messages' => [$prompt, $userMessage],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                return null;
            }

            $raw = (string) data_get($response->json(), 'choices.0.message.content', '');
            if ($raw === '') {
                return null;
            }

            $parsed = json_decode($raw, true);
            if (!is_array($parsed)) {
                return null;
            }

            $decision = in_array(($parsed['decision'] ?? ''), ['approve', 'review', 'deny'], true)
                ? (string) $parsed['decision']
                : 'review';

            $score = max(300, min(850, (int) ($parsed['score'] ?? 600)));
            $confidence = max(0, min(100, (int) ($parsed['confidence'] ?? 70)));
            $recommended = max($minimum, min($threshold, (float) ($parsed['recommended_limit'] ?? $minimum)));
            $reasons = array_values(array_filter((array) ($parsed['reasons'] ?? [])));
            if (empty($reasons)) {
                $reasons = ['AI recommendation generated without explicit reasons.'];
            }

            return [
                'decision' => $decision,
                'score' => $score,
                'confidence' => $confidence,
                'recommended_limit' => $recommended,
                'reasons' => array_slice($reasons, 0, 3),
                'model_version' => 'openai-' . $model,
                'threshold_cap' => $threshold,
                'minimum_limit' => $minimum,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}

