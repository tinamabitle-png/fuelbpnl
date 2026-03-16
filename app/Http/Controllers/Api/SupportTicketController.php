<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->withCount('messages')
            ->latest('last_message_at')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'tickets' => $tickets,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|min:3|max:180',
            'message' => 'required|string|min:3|max:8000',
            'priority' => 'nullable|in:low,normal,high',
        ]);

        $user = $request->user();

        /** @var SupportTicket $ticket */
        $ticket = DB::transaction(function () use ($validated, $user) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => trim((string) $validated['subject']),
                'status' => 'open',
                'priority' => $validated['priority'] ?? 'normal',
                'last_message_at' => now(),
            ]);

            SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_user_id' => $user->id,
                'sender_role' => 'user',
                'body' => trim((string) $validated['message']),
            ]);

            return $ticket->fresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created.',
            'data' => [
                'ticket' => $ticket,
            ],
        ], 201);
    }

    public function show(Request $request, int $ticketId)
    {
        $ticket = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($ticketId)
            ->with(['messages' => fn ($q) => $q->oldest()])
            ->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => $ticket,
            ],
        ]);
    }

    public function message(Request $request, int $ticketId)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:2|max:8000',
        ]);

        $user = $request->user();

        $ticket = SupportTicket::query()
            ->where('user_id', $user->id)
            ->whereKey($ticketId)
            ->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json(['success' => false, 'message' => 'Ticket is closed.'], 422);
        }

        DB::transaction(function () use ($ticket, $validated, $user) {
            SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_user_id' => $user->id,
                'sender_role' => 'user',
                'body' => trim((string) $validated['message']),
            ]);

            $ticket->last_message_at = now();
            $ticket->status = 'open';
            $ticket->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
        ]);
    }

    public function close(Request $request, int $ticketId)
    {
        $ticket = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($ticketId)
            ->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json(['success' => true, 'message' => 'Ticket already closed.']);
        }

        $ticket->update(['status' => 'closed']);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed.',
        ]);
    }
}

