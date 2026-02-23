@extends('layouts.admin')

@section('title', 'Credit Decisions')
@section('page-title', 'Credit Decisions')
@section('page-description', $decisionType === 'review' ? 'Manual review queue' : ($decisionType === 'approve' ? 'Approved credit decisions' : 'All credit decisions'))
@section('breadcrumb', 'Credit Decisions')

@section('content')
<div class="p-6">
    @php
        $authUser = auth()->user();
        $canReviewQueue = $authUser && method_exists($authUser, 'hasAnyRole')
            ? $authUser->hasAnyRole(['super_admin', 'admin', 'employee'])
            : false;
        $canApprovals = $authUser && method_exists($authUser, 'hasAnyRole')
            ? $authUser->hasAnyRole(['super_admin', 'admin', 'employee', 'auditor'])
            : false;
        $canAll = $canApprovals;
    @endphp
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
        <div class="flex gap-2">
            @if($canReviewQueue)
                <a href="{{ route('admin.credit-decisions.review') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium {{ $decisionType === 'review' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700' }}">
                    Review Queue
                </a>
            @endif
            @if($canApprovals)
                <a href="{{ route('admin.credit-decisions.approvals') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium {{ $decisionType === 'approve' ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 text-gray-700' }}">
                    Approved
                </a>
            @endif
            @if($canAll)
                <a href="{{ route('admin.credit-decisions.all') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium {{ $decisionType === 'all' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700' }}">
                    All Decisions
                </a>
            @endif
        </div>
        <form method="GET" action="" class="flex flex-col md:flex-row gap-2">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Search by id, user, policy..."
                   class="w-full md:w-80 border border-gray-300 rounded-lg px-3 py-2">
            <input type="text"
                   name="application_type"
                   value="{{ request('application_type') }}"
                   placeholder="Application type"
                   class="w-full md:w-48 border border-gray-300 rounded-lg px-3 py-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Score</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Decision</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Application</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($decisions as $decision)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">#{{ $decision->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium">{{ $decision->user?->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $decision->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $decision->score ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $decision->decision === 'approve' ? 'bg-green-100 text-green-700' : ($decision->decision === 'review' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ strtoupper($decision->decision) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $decision->application_type }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($decision->decided_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('admin.credit-decisions.show', $decision) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No decisions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            {{ $decisions->links() }}
        </div>
    </div>
</div>
@endsection
