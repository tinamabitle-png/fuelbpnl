<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\BankStatementUpload;
use App\Services\AuditTrailService;
use App\Services\BankStatementCreditAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankStatementController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        abort_unless($user && $user->hasAnyRole(['driver', 'merchant']), 403);

        return view('bank-statements.upload');
    }

    public function store(Request $request, BankStatementCreditAssessmentService $assessmentService)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasAnyRole(['driver', 'merchant']), 403);

        $validated = $request->validate([
            'statement' => ['required', 'file', 'mimetypes:application/pdf', 'max:8192'],
        ]);

        $file = $validated['statement'];
        $storedPath = $file->store('driver_documents/bank', 'public');

        $upload = BankStatementUpload::create([
            'user_id' => $user->id,
            'source' => 'web',
            'source_reference' => 'driver-portal',
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => (int) $file->getSize(),
            'temporary_path' => $storedPath,
            'status' => 'processing',
            'ocr_provider' => 'openai',
            'ocr_processor_type' => 'credit_analyst_agent',
        ]);

        $user->forceFill([
            'bank_statement_path' => $storedPath,
            'id_verification_status' => 'pending_review',
        ])->save();

        try {
            $decision = $assessmentService->assessAndStore($user, $upload);

            AuditTrailService::record(
                'bank_statement_uploaded_and_assessed',
                $upload,
                [],
                [
                    'decision_id' => $decision->id,
                    'decision' => $decision->decision,
                    'score' => $decision->score,
                ],
                'Bank statement uploaded and AI credit recommendation generated'
            );
        } catch (\Throwable $e) {
            $upload->forceFill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ])->save();

            AuditTrailService::record(
                'bank_statement_assessment_failed',
                $upload,
                [],
                ['error' => $e->getMessage()],
                'Bank statement assessment failed'
            );

            return back()->withErrors([
                'statement' => 'Upload succeeded, but analysis failed. Admin can still review the document.',
            ]);
        }

        return back()->with('success', 'Bank statement uploaded. AI recommendation is ready for admin review.');
    }
}
