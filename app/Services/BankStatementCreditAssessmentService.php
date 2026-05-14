<?php

namespace App\Services;

use App\Models\BankStatementUpload;
use App\Models\CreditDecision;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BankStatementCreditAssessmentService
{
    public function assessAndStore(User $user, BankStatementUpload $upload): CreditDecision
    {
        $threshold = (float) config('credit.system_max_limit', 30000);
        $minimum = (float) config('credit.minimum_limit', 1000);

        $documentAnalysis = $this->analyzeStatementDocument($upload);
        if (!$documentAnalysis['is_bank_statement']) {
            $upload->forceFill([
                'status' => 'failed',
                'processed_at' => now(),
                'ocr_provider' => $documentAnalysis['provider'],
                'ocr_processor_type' => 'bank_statement_classifier',
                'ocr_confidence' => $documentAnalysis['confidence'],
                'error_message' => $documentAnalysis['failure_reason'],
            ])->save();

            throw new \RuntimeException($documentAnalysis['failure_reason']);
        }

        $metrics = $this->buildMetrics($user, $upload);
        $metrics['document_validation'] = [
            'document_type' => $documentAnalysis['document_type'],
            'confidence' => $documentAnalysis['confidence'],
            'signals' => $documentAnalysis['signals'],
            'provider' => $documentAnalysis['provider'],
        ];
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
            'ocr_provider' => strpos((string) $recommendation['model_version'], 'openai-') === 0 ? 'openai' : 'rules',
            'ocr_processor_type' => 'bank_statement_classifier+credit_analyst_agent',
            'ocr_confidence' => min((float) $recommendation['confidence'], (float) $documentAnalysis['confidence']),
            'error_message' => null,
        ])->save();

        return $decision;
    }

    private function analyzeStatementDocument(BankStatementUpload $upload): array
    {
        $text = $this->extractDocumentText($upload);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '' || strlen($text) < 80) {
            return [
                'is_bank_statement' => false,
                'document_type' => 'unreadable',
                'confidence' => 0,
                'signals' => [],
                'provider' => 'text-extractor',
                'failure_reason' => 'Could not read enough text from the uploaded document. Please upload a searchable PDF bank statement, not a scanned/random document.',
            ];
        }

        $ai = $this->tryOpenAiDocumentClassification($text);
        if ($ai !== null) {
            return $this->normalizeDocumentAnalysis($ai, 'openai-document-classifier');
        }

        return $this->classifyDocumentWithRules($text);
    }

    private function extractDocumentText(BankStatementUpload $upload): string
    {
        $path = (string) ($upload->temporary_path ?? '');
        if ($path === '') {
            return '';
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            return '';
        }

        $absolutePath = $disk->path($path);
        $mime = strtolower((string) ($upload->mime_type ?? ''));
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'pdf' || strpos($mime, 'pdf') !== false) {
            $text = $this->extractPdfTextWithPdftotext($absolutePath);
            if (trim($text) !== '') {
                return $text;
            }
        }

        $contents = (string) file_get_contents($absolutePath);

        if ($extension === 'pdf' || strpos($mime, 'pdf') !== false) {
            return $this->extractPdfTextFromRawBytes($contents);
        }

        return $this->extractPrintableText($contents);
    }

    private function extractPdfTextWithPdftotext(string $absolutePath): string
    {
        if (!function_exists('shell_exec')) {
            return '';
        }

        $binary = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));
        if ($binary === '') {
            return '';
        }

        $command = escapeshellcmd($binary) . ' -layout -nopgbrk ' . escapeshellarg($absolutePath) . ' - 2>/dev/null';
        return (string) shell_exec($command);
    }

    private function extractPdfTextFromRawBytes(string $contents): string
    {
        $chunks = [];

        if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $contents, $matches)) {
            foreach ($matches[1] as $stream) {
                $stream = ltrim((string) $stream, "\r\n");
                $decoded = @gzuncompress($stream);
                if ($decoded === false) {
                    $decoded = @gzdecode($stream);
                }
                if ($decoded !== false) {
                    $chunks[] = $this->extractPrintableText($decoded);
                }
            }
        }

        $chunks[] = $this->extractPrintableText($contents);

        return implode(' ', array_filter($chunks));
    }

    private function extractPrintableText(string $contents): string
    {
        if (preg_match_all('/[\x20-\x7E]{4,}/', $contents, $matches)) {
            return implode(' ', $matches[0]);
        }

        return '';
    }

    private function tryOpenAiDocumentClassification(string $text): ?array
    {
        $apiKey = trim((string) config('services.openai.api_key'));
        if ($apiKey === '') {
            return null;
        }

        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $timeout = max(8, (int) config('services.openai.timeout', 20));
        $sample = substr($text, 0, 12000);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout($timeout)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Classify whether extracted document text is a real bank statement. Output strict JSON only: document_type (bank_statement|id_document|drivers_license|payslip|invoice|random_document|unknown), is_bank_statement boolean, confidence 0-100, signals array, failure_reason string. A bank statement must include bank/account/statement language and transaction/balance/date evidence.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $sample,
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                return null;
            }

            $raw = (string) data_get($response->json(), 'choices.0.message.content', '');
            $parsed = json_decode($raw, true);

            return is_array($parsed) ? $parsed : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeDocumentAnalysis(array $analysis, string $provider): array
    {
        $documentType = (string) ($analysis['document_type'] ?? 'unknown');
        $confidence = max(0, min(100, (int) ($analysis['confidence'] ?? 0)));
        $signals = array_values(array_filter(array_map('strval', (array) ($analysis['signals'] ?? []))));
        $isBankStatement = (bool) ($analysis['is_bank_statement'] ?? false);

        if ($documentType !== 'bank_statement' || $confidence < 70 || count($signals) < 2) {
            $isBankStatement = false;
        }

        return [
            'is_bank_statement' => $isBankStatement,
            'document_type' => $documentType,
            'confidence' => $confidence,
            'signals' => array_slice($signals, 0, 8),
            'provider' => $provider,
            'failure_reason' => $isBankStatement
                ? null
                : (string) ($analysis['failure_reason'] ?? 'Uploaded document was not confidently identified as a bank statement.'),
        ];
    }

    private function classifyDocumentWithRules(string $text): array
    {
        $lower = strtolower($text);

        $signalChecks = [
            'bank_keyword' => preg_match('/\b(bank|absa|capitec|fnb|first national bank|nedbank|standard bank|tymebank|african bank|investec)\b/i', $text),
            'statement_keyword' => preg_match('/\b(statement|account statement|transaction history|statement period)\b/i', $text),
            'account_keyword' => preg_match('/\b(account number|account no|account holder|branch code|iban|swift)\b/i', $text),
            'transaction_keyword' => preg_match('/\b(transaction|debit|credit|withdrawal|deposit|payment|purchase|transfer)\b/i', $text),
            'balance_keyword' => preg_match('/\b(balance|opening balance|closing balance|available balance)\b/i', $text),
            'currency_keyword' => preg_match('/\b(zar|r\s?\d|rand|n\$|\$|amount)\b/i', $text),
            'date_rows' => preg_match_all('/\b\d{1,2}[\/\-. ]\d{1,2}[\/\-. ]\d{2,4}\b/', $text) >= 3,
        ];

        $signals = array_keys(array_filter($signalChecks));
        $score = count($signals);
        $hasBankIdentity = !empty($signalChecks['bank_keyword']) || !empty($signalChecks['account_keyword']);
        $hasStatementEvidence = !empty($signalChecks['statement_keyword']);
        $hasMoneyMovement = !empty($signalChecks['transaction_keyword']) && (!empty($signalChecks['balance_keyword']) || !empty($signalChecks['currency_keyword']) || !empty($signalChecks['date_rows']));
        $isBankStatement = $hasBankIdentity && $hasStatementEvidence && $hasMoneyMovement && $score >= 4;
        $confidence = min(92, 35 + ($score * 9));

        $looksLikeId = strpos($lower, 'identity number') !== false
            || strpos($lower, 'national identity') !== false
            || strpos($lower, 'drivers licence') !== false
            || strpos($lower, 'driver licence') !== false;

        return [
            'is_bank_statement' => $isBankStatement,
            'document_type' => $isBankStatement ? 'bank_statement' : ($looksLikeId ? 'id_document' : 'unknown'),
            'confidence' => $isBankStatement ? $confidence : min(65, $confidence),
            'signals' => $signals,
            'provider' => 'rules-document-classifier',
            'failure_reason' => $isBankStatement
                ? null
                : 'Uploaded document was not confidently identified as a bank statement. Required signals include bank/account details, statement wording, transactions, balances, dates, or currency amounts.',
        ];
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
            'avg_monthly_income' => (float) (optional($feature)->avg_monthly_income ?? 0),
            'avg_monthly_expenses' => (float) (optional($feature)->avg_monthly_expenses ?? 0),
            'avg_daily_balance' => (float) (optional($feature)->avg_daily_balance ?? 0),
            'cash_buffer_days' => (float) (optional($feature)->cash_buffer_days ?? 0),
            'nsf_count' => (int) (optional($feature)->nsf_count ?? 0),
            'overdraft_count' => (int) (optional($feature)->overdraft_count ?? 0),
            'risk_score' => optional($feature)->risk_score !== null ? (int) $feature->risk_score : null,
            'risk_band' => (string) (optional($feature)->risk_band ?? ''),
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

        if ($score >= 780) {
            $baseLimit = 30000;
        } elseif ($score >= 700) {
            $baseLimit = 22000;
        } elseif ($score >= 640) {
            $baseLimit = 15000;
        } elseif ($score >= 580) {
            $baseLimit = 9000;
        } else {
            $baseLimit = 3000;
        }

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
