<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('admin_feedback')) {
            return view('admin.feedback.index', [
                'feedback' => collect(),
                'stats' => [
                    'total' => 0,
                    'positive' => 0,
                    'neutral' => 0,
                    'negative' => 0,
                ],
            ]);
        }

        $query = AdminFeedback::with('user:id,name,email')
            ->when($request->filled('sentiment'), fn ($q) => $q->where('sentiment', $request->string('sentiment')->toString()))
            ->latest();

        $feedback = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => AdminFeedback::count(),
            'positive' => AdminFeedback::where('sentiment', 'positive')->count(),
            'neutral' => AdminFeedback::where('sentiment', 'neutral')->count(),
            'negative' => AdminFeedback::where('sentiment', 'negative')->count(),
        ];

        return view('admin.feedback.index', compact('feedback', 'stats'));
    }
}

