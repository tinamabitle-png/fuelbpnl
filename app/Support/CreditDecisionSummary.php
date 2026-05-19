<?php

namespace App\Support;

use App\Models\CreditDecision;

class CreditDecisionSummary
{
    public static function brief(?CreditDecision $decision): ?string
    {
        if (!$decision) {
            return null;
        }

        $summary = trim((string) data_get($decision->explanation_json, 'agent_recommendation.recommendation.summary', ''));
        if ($summary !== '') {
            return $summary;
        }

        $reasons = array_values(array_filter(array_map('trim', (array) ($decision->reasons ?? []))));
        if (!empty($reasons)) {
            return implode(' ', array_slice($reasons, 0, 2));
        }

        $metrics = (array) data_get($decision->explanation_json, 'metrics', []);
        $parts = [];

        $documentType = (string) data_get($metrics, 'document_validation.document_type', '');
        $documentConfidence = (int) data_get($metrics, 'document_validation.confidence', 0);
        if ($documentType !== '') {
            $parts[] = $documentType === 'bank_statement'
                ? 'Validated as a bank statement' . ($documentConfidence > 0 ? ' at ' . $documentConfidence . '% confidence' : '')
                : 'Document classified as ' . str_replace('_', ' ', $documentType);
        }

        $income = (float) data_get($metrics, 'avg_monthly_income', 0);
        $expenses = (float) data_get($metrics, 'avg_monthly_expenses', 0);
        if ($income > 0 || $expenses > 0) {
            if ($income > $expenses) {
                $parts[] = 'income is above expenses';
            } elseif ($expenses > $income) {
                $parts[] = 'expenses are above income';
            }
        }

        $nsfCount = (int) data_get($metrics, 'nsf_count', 0);
        if ($nsfCount > 0) {
            $parts[] = $nsfCount . ' NSF event' . ($nsfCount === 1 ? '' : 's') . ' detected';
        }

        $overdraftCount = (int) data_get($metrics, 'overdraft_count', 0);
        if ($overdraftCount > 0) {
            $parts[] = $overdraftCount . ' overdraft event' . ($overdraftCount === 1 ? '' : 's') . ' detected';
        }

        $riskBand = trim((string) data_get($metrics, 'risk_band', ''));
        if ($riskBand !== '') {
            $parts[] = 'risk band is ' . strtolower($riskBand);
        }

        $riskScore = data_get($metrics, 'risk_score');
        if ($riskScore !== null && $riskScore !== '') {
            $parts[] = 'risk score contributed at ' . (int) $riskScore;
        }

        if (empty($parts)) {
            return 'Score allocated from AI bank-statement assessment and current credit metrics.';
        }

        return ucfirst(implode('; ', array_slice($parts, 0, 4))) . '.';
    }
}
