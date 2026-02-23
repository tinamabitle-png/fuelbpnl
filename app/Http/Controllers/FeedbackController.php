<?php

namespace App\Http\Controllers;

use App\Models\AdminFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        if (!Schema::hasTable('admin_feedback')) {
            return back()->with('error', 'Feedback storage is not ready yet.');
        }

        $user = auth()->user();
        if (!$user || $user->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Only platform users can submit feedback to admins.');
        }

        $validated = $request->validate([
            'feedback' => 'required|string|min:3|max:2000',
            'sentiment' => 'nullable|in:positive,neutral,negative',
        ]);

        AdminFeedback::create([
            'user_id' => $user->id,
            'message' => trim((string) $validated['feedback']),
            'sentiment' => $validated['sentiment'] ?? 'neutral',
        ]);

        return back()->with('success', 'Feedback sent to admin successfully.');
    }
}

