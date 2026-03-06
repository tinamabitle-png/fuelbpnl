<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BankStatementUpload;
use App\Models\DriverDocument;
use App\Services\BankStatementCreditAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegistrationDocumentsController extends Controller
{
    public function show(Request $request, ?string $role = null)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $role = $role ?: ($user->getRoleNames()->first() ?: 'driver');
        $allowedRoles = ['driver', 'merchant'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'driver';
        }

        return view('auth.complete-registration', compact('role'));
    }

    public function store(Request $request, BankStatementCreditAssessmentService $assessmentService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'role' => ['nullable', Rule::in(['driver', 'merchant'])],
            'id_number' => ['required', 'digits:13', Rule::unique('users', 'id_number')->ignore($user->id)],
            'id_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'driver_license_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bank_statement_document' => 'nullable|file|mimetypes:application/pdf|max:8192',
            'payment_method_preference' => 'nullable|in:bank_transfer,card,mobile_money',
            'payment_account_name' => 'nullable|string|max:255',
            'payment_account_number' => 'nullable|string|max:255',
            'payment_bank_name' => 'nullable|string|max:255',
            'payment_branch_code' => 'nullable|string|max:255',
        ]);

        $role = $validated['role'] ?? ($user->getRoleNames()->first() ?: 'driver');

        $upload = DB::transaction(function () use ($request, $user, $validated): ?BankStatementUpload {
            $idPath = $request->file('id_document')->store('driver_documents/id', 'public');
            $licensePath = $request->file('driver_license_document')->store('driver_documents/license', 'public');
            $bankPath = $request->hasFile('bank_statement_document')
                ? $request->file('bank_statement_document')->store('driver_documents/bank', 'public')
                : $user->bank_statement_path;

            $user->update([
                'id_number' => $validated['id_number'],
                'id_document_path' => $idPath,
                'driver_license_path' => $licensePath,
                'bank_statement_path' => $bankPath,
                'payment_method_preference' => $validated['payment_method_preference'] ?? null,
                'payment_account_name' => $validated['payment_account_name'] ?? null,
                'payment_account_number' => $validated['payment_account_number'] ?? null,
                'payment_bank_name' => $validated['payment_bank_name'] ?? null,
                'payment_branch_code' => $validated['payment_branch_code'] ?? null,
                'id_verification_status' => 'pending_review',
                'id_verification_provider' => 'manual',
                'id_verified_at' => null,
            ]);

            DriverDocument::updateOrCreate(
                ['user_id' => $user->id, 'document_type' => 'sa_id'],
                [
                    'document_path' => $idPath,
                    'document_name' => basename($idPath),
                    'document_number' => $validated['id_number'],
                    'verified' => false,
                    'verified_by' => null,
                    'verified_at' => null,
                    'notes' => null,
                ]
            );

            DriverDocument::updateOrCreate(
                ['user_id' => $user->id, 'document_type' => 'driver_license'],
                [
                    'document_path' => $licensePath,
                    'document_name' => basename($licensePath),
                    'verified' => false,
                    'verified_by' => null,
                    'verified_at' => null,
                    'notes' => null,
                ]
            );

            if (!$request->hasFile('bank_statement_document')) {
                return null;
            }

            $bankFile = $request->file('bank_statement_document');

            return BankStatementUpload::create([
                'user_id' => $user->id,
                'source' => 'web',
                'source_reference' => 'registration-complete',
                'original_filename' => $bankFile->getClientOriginalName(),
                'mime_type' => $bankFile->getMimeType(),
                'file_size' => (int) $bankFile->getSize(),
                'temporary_path' => $bankPath,
                'status' => 'processing',
                'ocr_provider' => 'document_ai',
            ]);
        });

        if ($upload) {
            try {
                $assessmentService->assessAndStore($user, $upload);
            } catch (\Throwable $e) {
                $upload->forceFill([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => now(),
                ])->save();
            }
        }

        return redirect()
            ->route($this->postRegistrationRoute($role))
            ->with('success', 'Documents submitted successfully. Verification is now pending review.');
    }

    private function postRegistrationRoute(string $role): string
    {
        if ($role === 'merchant') {
            return 'merchant.dashboard';
        }

        return 'driver.dashboard';
    }
}
