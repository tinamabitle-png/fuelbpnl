@extends('layouts.app')

@section('title', 'Energy Subscriptions')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        @include('merchant.partials.nav')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="brand-font text-2xl text-slate-900">Energy Subscriptions</h1>
                <p class="text-slate-600 mt-2">Approve driver subscriptions and track repayments.</p>
            </div>
        </div>

        <form method="GET" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input name="search" value="{{ request('search') }}" class="input" placeholder="Search driver or project" />
            <select name="status" class="input">
                <option value="">All Statuses</option>
                @foreach(['pending_approval','active','approved','completed','rejected','cancelled','defaulted'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="project_id" class="input">
                <option value="">All Projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->title }}</option>
                @endforeach
            </select>
            <button class="btn-primary px-5 py-2 rounded-xl text-sm font-semibold">Filter</button>
        </form>

        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full text-sm text-slate-700">
                <thead class="text-left text-slate-500">
                    <tr>
                        <th class="py-3 pr-4">Driver</th>
                        <th class="py-3 pr-4">Project</th>
                        <th class="py-3 pr-4">Total</th>
                        <th class="py-3 pr-4">Term</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($subscriptions as $subscription)
                        <tr>
                            <td class="py-4 pr-4">
                                <div class="font-semibold text-slate-900">{{ $subscription->user?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $subscription->user?->email }}</div>
                            </td>
                            <td class="py-4 pr-4">{{ $subscription->project?->title ?? '—' }}</td>
                            <td class="py-4 pr-4">ZAR {{ number_format($subscription->total_amount, 2) }}</td>
                            <td class="py-4 pr-4">{{ $subscription->term_months }} months</td>
                            <td class="py-4 pr-4">{{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</td>
                            <td class="py-4 text-right">
                                <a href="{{ route('merchant.energy-subscriptions.show', $subscription) }}" class="text-blue-600">View</a>
                                @if($subscription->status === 'pending_approval')
                                    <form method="POST" action="{{ route('merchant.energy-subscriptions.approve', $subscription) }}" class="inline ml-3">
                                        @csrf
                                        <button class="btn-primary px-3 py-1.5 rounded-lg text-xs font-semibold">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('merchant.energy-subscriptions.reject', $subscription) }}" class="inline ml-2">
                                        @csrf
                                        <button class="btn-ghost px-3 py-1.5 rounded-lg text-xs font-semibold">Reject</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">No subscriptions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $subscriptions->links() }}</div>
    </div>
</section>
@endsection
