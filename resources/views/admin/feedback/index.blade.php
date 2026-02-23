@extends('layouts.admin')

@section('title', 'Feedback Inbox')
@section('page-title', 'Feedback Inbox')
@section('page-description', 'Messages submitted by users to admin')
@section('breadcrumb', 'Feedback')

@section('content')
<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-sm text-slate-500">Total</p>
            <p class="text-2xl font-bold text-slate-900">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-emerald-200 p-4">
            <p class="text-sm text-emerald-600">Positive</p>
            <p class="text-2xl font-bold text-emerald-700">{{ $stats['positive'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-sm text-slate-500">Neutral</p>
            <p class="text-2xl font-bold text-slate-700">{{ $stats['neutral'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-rose-200 p-4">
            <p class="text-sm text-rose-600">Negative</p>
            <p class="text-2xl font-bold text-rose-700">{{ $stats['negative'] ?? 0 }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-900">User Feedback</h3>
            <form method="GET" class="flex items-center gap-2">
                <select name="sentiment" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All sentiments</option>
                    <option value="positive" {{ request('sentiment') === 'positive' ? 'selected' : '' }}>Positive</option>
                    <option value="neutral" {{ request('sentiment') === 'neutral' ? 'selected' : '' }}>Neutral</option>
                    <option value="negative" {{ request('sentiment') === 'negative' ? 'selected' : '' }}>Negative</option>
                </select>
                <button class="px-3 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">Filter</button>
                <a href="{{ route('admin.feedback.index') }}" class="px-3 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-semibold">Reset</a>
            </form>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($feedback as $item)
                <div class="px-6 py-4">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $item->user?->name ?? 'Unknown User' }}</p>
                            <p class="text-xs text-slate-500">{{ $item->user?->email ?? 'No email' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $item->sentiment === 'positive' ? 'bg-emerald-100 text-emerald-700' : ($item->sentiment === 'negative' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ strtoupper($item->sentiment) }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $item->created_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                    <p class="text-sm text-slate-700 mt-2 whitespace-pre-wrap">{{ $item->message }}</p>
                </div>
            @empty
                <div class="px-6 py-12 text-center text-slate-500">No feedback messages yet.</div>
            @endforelse
        </div>

        @if(method_exists($feedback, 'links'))
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $feedback->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

