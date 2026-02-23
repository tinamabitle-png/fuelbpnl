@extends('layouts.admin')

@section('title', 'Energy Subscriptions')
@section('page-title', 'Energy Subscriptions')
@section('page-description', 'Approve and track driver subscriptions to energy projects')
@section('breadcrumb', 'Energy Subscriptions')

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Energy Subscriptions</h2>
                <p class="text-gray-600">Driver subscriptions awaiting approval and active repayment schedules.</p>
            </div>
        </div>

        <form method="GET" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input name="search" value="{{ request('search') }}" class="px-4 py-2 border border-gray-300 rounded-lg" placeholder="Search driver, project, station" />
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">All Statuses</option>
                @foreach(['pending_approval','active','approved','completed','rejected','cancelled','defaulted'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="station_id" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">All Stations</option>
                @foreach($stations as $station)
                    <option value="{{ $station->id }}" @selected(request('station_id') == $station->id)>{{ $station->name }}</option>
                @endforeach
            </select>
            <select name="project_id" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">All Projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->title }}</option>
                @endforeach
            </select>
            <button class="px-5 py-2 bg-gray-900 text-white rounded-lg">Filter</button>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2">Driver</th>
                        <th class="px-4 py-2">Project</th>
                        <th class="px-4 py-2">Station</th>
                        <th class="px-4 py-2">Total</th>
                        <th class="px-4 py-2">Term</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Requested</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($subscriptions as $subscription)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ $subscription->user?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $subscription->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $subscription->project?->title ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $subscription->station?->name ?? '—' }}</td>
                            <td class="px-4 py-3">ZAR {{ number_format($subscription->total_amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $subscription->term_months }} months</td>
                            <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</td>
                            <td class="px-4 py-3">{{ $subscription->requested_at?->format('Y-m-d') ?? $subscription->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.energy-subscriptions.show', $subscription) }}" class="text-blue-600 mr-3">View</a>
                                @if($subscription->status === 'pending_approval')
                                    <form method="POST" action="{{ route('admin.energy-subscriptions.approve', $subscription) }}" class="inline">
                                        @csrf
                                        <button class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.energy-subscriptions.reject', $subscription) }}" class="inline ml-2">
                                        @csrf
                                        <button class="px-3 py-1.5 border border-rose-300 text-rose-600 rounded-lg text-xs font-semibold">Reject</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">No subscriptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $subscriptions->links() }}</div>
    </div>
</div>
@endsection
