<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditAuditLog;
use App\Models\CreditDecision;

class CreditDecisionController extends Controller
{
    public function show(CreditDecision $decision)
    {
        abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);

        if ($decision->user) {
            return redirect()->route('admin.users.show', $decision->user)
                ->with('credit_decision_id', $decision->id);
        }

        return response()->json([
            'id' => $decision->id,
            'decision' => $decision->decision,
            'score' => $decision->score,
        ]);
    }

    public function agentRecommendation(CreditDecision $decision)
    {
        abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);

        $recommendation = [
            'agent' => [
                'name' => 'credit_analyst_agent',
                'version' => 'v1',
            ],
            'recommendation' => [
                'decision' => $decision->decision ?: 'review',
                'confidence' => $decision->decision === 'approve' ? 'high' : 'medium',
                'generated_at' => now()->toIso8601String(),
            ],
        ];

        $explanation = is_array($decision->explanation_json) ? $decision->explanation_json : [];
        $explanation['agent_recommendation'] = $recommendation;

        $decision->forceFill([
            'explanation_json' => $explanation,
        ])->save();

        CreditAuditLog::create([
            'actor_id' => auth()->id(),
            'action' => 'credit_agent_recommendation_generated',
            'entity_type' => CreditDecision::class,
            'entity_id' => $decision->id,
            'payload_json' => $recommendation,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.credit-decisions.show', $decision);
    }
}
