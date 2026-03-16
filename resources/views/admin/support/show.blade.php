@extends('Layouts.admin')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
        <div>
            <div class="text-sm text-slate-500">
                <a href="{{ route('admin.support.tickets.index') }}" class="hover:underline">Support Inbox</a>
                <span class="mx-2">/</span>
                <span>Ticket #{{ $ticket->id }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $ticket->subject }}</h1>
            <div class="text-sm text-slate-600 mt-1">
                From <span class="font-medium">{{ $ticket->user?->name ?? 'Unknown' }}</span>
                <span class="text-slate-400">({{ $ticket->user?->email }})</span>
                @if(!empty($ticket->user?->phone))
                    <span class="ml-2 text-slate-400">{{ $ticket->user->phone }}</span>
                @endif
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 w-full lg:w-80">
            <form method="POST" action="{{ route('admin.support.tickets.assign', $ticket) }}" class="space-y-3">
                @csrf
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-700">Ticket</span>
                    <span class="text-xs text-slate-500">{{ optional($ticket->created_at)->toDayDateTimeString() }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-slate-200">
                            @foreach(['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'] as $k => $label)
                                <option value="{{ $k }}" @selected($ticket->status === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Priority</label>
                        <select name="priority" class="w-full rounded-lg border-slate-200">
                            @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $k => $label)
                                <option value="{{ $k }}" @selected($ticket->priority === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Assigned</label>
                    <select name="assigned_to_user_id" class="w-full rounded-lg border-slate-200">
                        <option value="">Unassigned</option>
                        @foreach($assignees as $u)
                            <option value="{{ $u->id }}" @selected((int) $ticket->assigned_to_user_id === (int) $u->id)>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">
                    Update
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50">
            <div class="text-sm font-semibold text-slate-700">Conversation</div>
        </div>
        <div class="p-4 space-y-4">
            @foreach($ticket->messages as $m)
                @php
                    $isAdmin = $m->sender_role === 'admin';
                    $bubble = $isAdmin ? 'bg-blue-50 border-blue-100' : 'bg-slate-50 border-slate-100';
                    $align = $isAdmin ? 'justify-end' : 'justify-start';
                @endphp
                <div class="flex {{ $align }}">
                    <div class="max-w-2xl w-full md:w-3/4 border rounded-xl p-3 {{ $bubble }}">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs font-semibold text-slate-600">
                                {{ $isAdmin ? ($m->sender?->name ?? 'Admin') : ($m->sender?->name ?? 'User') }}
                                <span class="ml-2 font-normal text-slate-400">{{ $m->created_at?->toDayDateTimeString() }}</span>
                            </div>
                            <div class="text-[10px] uppercase tracking-[0.14em] text-slate-400">
                                {{ $m->sender_role }}
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-slate-800 whitespace-pre-wrap">{{ $m->body }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-4 mt-4">
        <form method="POST" action="{{ route('admin.support.tickets.reply', $ticket) }}" class="space-y-3">
            @csrf
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Reply</h2>
                <select name="status" class="rounded-lg border-slate-200 text-sm">
                    <option value="">Auto</option>
                    <option value="open">Open</option>
                    <option value="pending">Pending</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <textarea name="message" rows="4" class="w-full rounded-lg border-slate-200" placeholder="Write a reply..."></textarea>
            <div class="flex justify-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Send Reply</button>
            </div>
        </form>
    </div>
</div>
@endsection

