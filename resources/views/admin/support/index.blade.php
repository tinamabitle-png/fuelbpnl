@extends('Layouts.admin')

@section('title', 'Support Inbox')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Support Inbox</h1>
            <p class="text-sm text-gray-500">Customer tickets and internal replies.</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">Open: {{ $stats['open'] }}</span>
            <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100">Pending: {{ $stats['pending'] }}</span>
            <span class="px-3 py-1 rounded-full bg-slate-50 text-slate-700 border border-slate-100">Closed: {{ $stats['closed'] }}</span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
        <form class="grid grid-cols-1 md:grid-cols-4 gap-3" method="GET" action="{{ route('admin.support.tickets.index') }}">
            <input class="w-full rounded-lg border-slate-200" type="text" name="q" value="{{ request('q') }}" placeholder="Search subject, user name, email">
            <select class="w-full rounded-lg border-slate-200" name="status">
                <option value="">All statuses</option>
                @foreach(['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'] as $k => $label)
                    <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="w-full rounded-lg border-slate-200" name="priority">
                <option value="">All priorities</option>
                @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $k => $label)
                    <option value="{{ $k }}" @selected(request('priority') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700" type="submit">Filter</button>
                <a class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50" href="{{ route('admin.support.tickets.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full">
            <thead>
                <tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Ticket</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Assigned</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Last</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Messages</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a class="font-semibold text-slate-900 hover:underline" href="{{ route('admin.support.tickets.show', $ticket) }}">
                                #{{ $ticket->id }} {{ $ticket->subject }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            <div class="font-medium">{{ $ticket->user?->name ?? 'Unknown' }}</div>
                            <div class="text-xs text-slate-500">{{ $ticket->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($ticket->status) {
                                    'open' => 'bg-green-50 text-green-700 border-green-100',
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    default => 'bg-slate-50 text-slate-700 border-slate-100',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-full border text-xs {{ $badge }}">{{ ucfirst($ticket->status) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $p = match($ticket->priority) {
                                    'high' => 'bg-rose-50 text-rose-700 border-rose-100',
                                    'low' => 'bg-sky-50 text-sky-700 border-sky-100',
                                    default => 'bg-slate-50 text-slate-700 border-slate-100',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-full border text-xs {{ $p }}">{{ ucfirst($ticket->priority) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ optional($ticket->last_message_at)->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-700">
                            {{ $ticket->messages_count }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No tickets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
@endsection

