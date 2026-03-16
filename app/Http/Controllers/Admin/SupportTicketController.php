<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::query()
            ->with(['user:id,name,email', 'assignedTo:id,name,email'])
            ->withCount('messages')
            ->orderByRaw("FIELD(status, 'open','pending','closed')")
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }
        if ($request->filled('q')) {
            $q = trim($request->string('q')->toString());
            $query->where(function ($qq) use ($q) {
                $qq->where('subject', 'like', '%' . $q . '%')
                    ->orWhereHas('user', function ($uq) use ($q) {
                        $uq->where('name', 'like', '%' . $q . '%')
                            ->orWhere('email', 'like', '%' . $q . '%');
                    });
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        $stats = [
            'open' => SupportTicket::where('status', 'open')->count(),
            'pending' => SupportTicket::where('status', 'pending')->count(),
            'closed' => SupportTicket::where('status', 'closed')->count(),
        ];

        $assignees = User::query()
            ->select(['id', 'name', 'email'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin', 'employee']))
            ->orderBy('name')
            ->get();

        return view('admin.support.index', compact('tickets', 'stats', 'assignees'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load([
            'user:id,name,email,phone',
            'assignedTo:id,name,email',
            'messages' => fn ($q) => $q->with('sender:id,name,email')->oldest(),
        ]);

        $assignees = User::query()
            ->select(['id', 'name', 'email'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin', 'employee']))
            ->orderBy('name')
            ->get();

        return view('admin.support.show', compact('ticket', 'assignees'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:2|max:8000',
            'status' => 'nullable|in:open,pending,closed',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($ticket, $validated, $admin) {
            SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_user_id' => $admin->id,
                'sender_role' => 'admin',
                'body' => trim((string) $validated['message']),
            ]);

            $ticket->last_message_at = now();
            if (!empty($validated['status'])) {
                $ticket->status = $validated['status'];
            } else {
                // When admin replies, mark as pending unless it's already closed.
                if ($ticket->status !== 'closed') {
                    $ticket->status = 'pending';
                }
            }
            $ticket->save();
        });

        return redirect()
            ->route('admin.support.tickets.show', $ticket)
            ->with('success', 'Reply sent.');
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to_user_id' => 'nullable|integer|exists:users,id',
            'priority' => 'nullable|in:low,normal,high',
            'status' => 'nullable|in:open,pending,closed',
        ]);

        $ticket->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'priority' => $validated['priority'] ?? $ticket->priority,
            'status' => $validated['status'] ?? $ticket->status,
        ]);

        return back()->with('success', 'Ticket updated.');
    }
}

