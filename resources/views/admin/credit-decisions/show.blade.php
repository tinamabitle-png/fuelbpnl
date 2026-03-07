@extends('Layouts.admin')

@section('title', 'Credit Decision Details')
@section('page-title', 'Credit Decision Details')
@section('page-description', 'Review scoring context and audit trail')
@section('breadcrumb', 'Credit Decisions')

@section('content')
@php
    $agentRecommendation = data_get($decision->explanation_json, 'agent_recommendation');
    $agentDecision = data_get($agentRecommendation, 'recommendation.decision');
    $agentConfidence = data_get($agentRecommendation, 'recommendation.confidence');
    $agentSignals = data_get($agentRecommendation, 'signals', []);
    $agentActions = data_get($agentRecommendation, 'actions', []);
    $authUser = auth()->user();
    $canReviewQueue = $authUser && method_exists($authUser, 'hasAnyRole')
        ? $authUser->hasAnyRole(['super_admin', 'admin', 'employee'])
        : false;
    $canApprovals = $authUser && method_exists($authUser, 'hasAnyRole')
        ? $authUser->hasAnyRole(['super_admin', 'admin', 'employee', 'auditor'])
        : false;
@endphp
<div class="p-6 space-y-6">
    <div class="flex flex-wrap gap-2">
        @if($canReviewQueue)
            <a href="{{ route('admin.credit-decisions.review') }}"
               class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700">Back To Review</a>
        @endif
        @if($canApprovals)
            <a href="{{ route('admin.credit-decisions.approvals') }}"
               class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700">Back To Approvals</a>
        @endif
        @if($canApprovals)
            <a href="{{ route('admin.credit-decisions.all') }}"
               class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700">Back To All Decisions</a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Decision #{{ $decision->id }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">User</p>
                    <p class="font-medium text-gray-900">{{ $decision->user?->name ?? 'Unknown' }}</p>
                    <p class="text-gray-600">{{ $decision->user?->email }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Current Decision</p>
                    <p class="font-medium text-gray-900">{{ strtoupper($decision->decision) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Score</p>
                    <p class="font-medium text-gray-900">{{ $decision->score ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Application Type</p>
                    <p class="font-medium text-gray-900">{{ $decision->application_type }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Policy Version</p>
                    <p class="font-medium text-gray-900">{{ $decision->policy_version }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Decided At</p>
                    <p class="font-medium text-gray-900">{{ optional($decision->decided_at)->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>

            <div>
                <p class="text-sm text-gray-500 mb-2">Reasons</p>
                <div class="flex flex-wrap gap-2">
                    @forelse(($decision->reasons ?? []) as $reason)
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">{{ $reason }}</span>
                    @empty
                        <span class="text-sm text-gray-500">No reasons captured.</span>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="text-sm text-gray-500 mb-2">Explanation JSON</p>
                <pre class="bg-gray-900 text-green-200 text-xs p-3 rounded-lg overflow-x-auto">{{ json_encode($decision->explanation_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Credit Analyst Agent</h3>
                        <p class="text-xs text-gray-500 mt-1">Decision support only. Human override remains final.</p>
                    </div>
                    @if(auth()->user() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'employee']))
                        <form method="POST" action="{{ route('admin.credit-decisions.agent-recommendation', $decision) }}">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold">
                                {{ $agentRecommendation ? 'Refresh Recommendation' : 'Run Recommendation' }}
                            </button>
                        </form>
                    @endif
                </div>

                @if($agentRecommendation)
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="border border-gray-200 rounded-lg p-2">
                            <p class="text-gray-500">Recommended Decision</p>
                            <p class="font-semibold text-gray-900 uppercase">{{ $agentDecision ?? 'review' }}</p>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-2">
                            <p class="text-gray-500">Confidence</p>
                            <p class="font-semibold text-gray-900">{{ $agentConfidence ?? '-' }}%</p>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-2">
                            <p class="text-gray-500">Risk Score</p>
                            <p class="font-semibold text-gray-900">{{ data_get($agentRecommendation, 'risk_score', '-') }}</p>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-2">
                            <p class="text-gray-500">Risk Level</p>
                            <p class="font-semibold text-gray-900 uppercase">{{ data_get($agentRecommendation, 'risk_level', '-') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Summary</p>
                        <p class="text-sm text-gray-800">{{ data_get($agentRecommendation, 'recommendation.summary', 'No summary available.') }}</p>
                    </div>

                    @if($agentDecision && $agentDecision !== $decision->decision)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                            Agent suggests <strong class="uppercase">{{ $agentDecision }}</strong> while current decision is
                            <strong class="uppercase">{{ $decision->decision }}</strong>. Review before overriding.
                        </div>
                    @endif

                    <div>
                        <p class="text-xs text-gray-500 mb-2">Risk Signals</p>
                        <div class="space-y-2">
                            @foreach($agentSignals as $signal)
                                <div class="border border-gray-200 rounded-lg p-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-semibold text-gray-800">{{ data_get($signal, 'code', 'SIGNAL') }}</p>
                                        <p class="text-[11px] text-gray-500 uppercase">{{ data_get($signal, 'severity', 'info') }}</p>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1">{{ data_get($signal, 'detail', '-') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if(!empty($agentActions))
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Suggested Actions</p>
                            <ul class="text-xs text-gray-700 list-disc pl-5 space-y-1">
                                @foreach($agentActions as $action)
                                    <li>{{ $action }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-600">No recommendation generated yet.</p>
                @endif
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Manual Override</h3>
            @if(auth()->user() && auth()->user()->hasAnyRole(['super_admin', 'admin']))
                <form method="POST" action="{{ route('admin.credit-decisions.override', $decision) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">New Decision</label>
                        <select name="decision" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach(['approve', 'review', 'deny'] as $option)
                                <option value="{{ $option }}" @selected(old('decision', $decision->decision) === $option)>
                                    {{ strtoupper($option) }}
                                </option>
                            @endforeach
                        </select>
                        @error('decision')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Override Reason</label>
                        <textarea name="override_reason" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>{{ old('override_reason') }}</textarea>
                        @error('override_reason')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Notes (Optional)</label>
                        <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium">
                        Save Override
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-600">Read-only access.</p>
            @endif
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h3 class="text-base font-semibold text-gray-900 mb-3">Audit Trail</h3>
        <div class="space-y-3">
            @forelse($auditLogs as $log)
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex flex-wrap justify-between gap-2">
                        <div class="text-sm font-medium text-gray-900">{{ $log->action }}</div>
                        <div class="text-xs text-gray-500">{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</div>
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        Actor: {{ $log->actor?->name ?? 'System' }} ({{ $log->actor?->email ?? 'n/a' }})
                    </div>
                    <pre class="mt-2 bg-gray-900 text-green-200 text-xs p-2 rounded overflow-x-auto">{{ json_encode($log->payload_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @empty
                <p class="text-sm text-gray-500">No audit entries recorded.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
