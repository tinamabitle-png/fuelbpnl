<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountApproval;
use App\Models\BankStatementUpload;
use App\Models\CreditDecision;
use App\Models\FuelStation;
use App\Models\MerchantFranchise;
use App\Models\User;
use App\Models\Wallet;
use App\Models\CreditLimit;
use App\Services\AuditTrailService;
use App\Services\BankStatementCreditAssessmentService;
use App\Services\DriverUnderwritingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ALLOWED_ADMIN_MANAGED_ROLES = ['driver', 'merchant', 'admin', 'super_admin', 'employee'];

    public function registrationDocuments(Request $request)
    {
        $roles = Role::query()
            ->whereIn('name', ['driver', 'merchant', 'admin'])
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->with(['roles', 'bankStatementUploads.creditDecisions', 'driverDocuments'])
            ->where(function ($q) {
                $q->whereNotNull('id_document_path')
                    ->orWhereNotNull('driver_license_path')
                    ->orWhereNotNull('bank_statement_path')
                    ->orWhereHas('driverDocuments')
                    ->orWhereHas('bankStatementUploads');
            })
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->role((string) $request->input('role'));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->input('search'));
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.registration-documents', compact('users', 'roles'));
    }

    public function accountApprovals(Request $request)
    {
        $franchises = MerchantFranchise::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $approvals = AccountApproval::query()
            ->with(['user.roles', 'franchise', 'reviewer'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', (string) $request->input('status'));
            })
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->where('role', (string) $request->input('role'));
            })
            ->when($request->filled('franchise_id'), function ($q) use ($request) {
                $q->where('merchant_franchise_id', (int) $request->input('franchise_id'));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->input('search'));
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.account-approvals', compact('approvals', 'franchises'));
    }

    public function approveAccount(Request $request, AccountApproval $approval)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($approval->status !== 'pending') {
            return back()->with('error', 'This approval request is not pending.');
        }

        $approval->loadMissing(['user', 'franchise']);
        $user = $approval->user;
        if (!$user) {
            return back()->with('error', 'Approval user record no longer exists.');
        }

        DB::transaction(function () use ($approval, $user, $request): void {
            $userData = [
                'status' => 'active',
                'merchant_franchise_id' => $approval->merchant_franchise_id ?: $user->merchant_franchise_id,
            ];
            if ($approval->role === 'driver') {
                $userData['id_verification_status'] = 'verified';
                $userData['id_verified_at'] = now();
                $userData['id_verification_provider'] = 'manual';
            }
            $user->update($userData);

            if ($approval->role === 'merchant') {
                $existingStation = FuelStation::query()
                    ->where('owner_id', $user->id)
                    ->first();
                $approvalMetadata = is_array($approval->metadata) ? $approval->metadata : [];
                $brandName = (string) (optional($approval->franchise)->name ?? 'Independent');
                $address = trim((string) ($approval->business_address ?: ($approvalMetadata['business_address'] ?? '')));
                $city = trim((string) ($approval->city ?: ($approvalMetadata['city'] ?? '')));
                $country = trim((string) ($approval->country ?: ($approvalMetadata['country'] ?? 'South Africa')));
                $latitude = is_numeric($approval->latitude)
                    ? (float) $approval->latitude
                    : (isset($approvalMetadata['latitude']) && is_numeric($approvalMetadata['latitude'])
                        ? (float) $approvalMetadata['latitude']
                        : null);
                $longitude = is_numeric($approval->longitude)
                    ? (float) $approval->longitude
                    : (isset($approvalMetadata['longitude']) && is_numeric($approvalMetadata['longitude'])
                        ? (float) $approvalMetadata['longitude']
                        : null);

                $stationPayload = [
                    'name' => trim($user->name . ' Station'),
                    'company' => $brandName !== '' ? $brandName : 'Independent',
                    'address' => $address !== '' ? $address : 'Pending address',
                    'city' => $city !== '' ? $city : 'Pending city',
                    'country' => $country !== '' ? $country : 'South Africa',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'contact_person' => $user->name,
                    'contact_phone' => $user->phone,
                    'contact_email' => $user->email,
                    'status' => 'active',
                    'owner_id' => $user->id,
                ];

                if ($existingStation) {
                    $existingStation->update($stationPayload);
                } else {
                    $stationPayload['license_number'] = 'LIC-' . strtoupper(Str::random(10));
                    $stationPayload['wallet_balance'] = 0;
                    $stationPayload['total_settlements'] = 0;
                    FuelStation::create($stationPayload);
                }
            }

            $approval->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => $request->input('notes'),
            ]);
        });

        return back()->with('success', 'Account approval completed successfully.');
    }

    public function rejectAccount(Request $request, AccountApproval $approval)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        if ($approval->status !== 'pending') {
            return back()->with('error', 'This approval request is not pending.');
        }

        $approval->loadMissing('user');
        $user = $approval->user;

        DB::transaction(function () use ($approval, $user, $validated): void {
            if ($user) {
                $user->update(['status' => 'suspended']);
            }

            $approval->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => $validated['notes'],
            ]);
        });

        return back()->with('success', 'Account request rejected.');
    }

    public function index(Request $request)
    {
        $query = User::with(['wallet', 'creditLimit']);
        
        // Filter by role
        if ($request->has('role')) {
            $query->role($request->role);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $users = $query->latest()->paginate(20);
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::query()
            ->whereIn('name', self::ALLOWED_ADMIN_MANAGED_ROLES)
            ->orderBy('name')
            ->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(self::ALLOWED_ADMIN_MANAGED_ROLES)],
            'credit_score' => 'nullable|integer|min:300|max:850',
            'status' => 'required|in:active,suspended,flagged,blocked',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'credit_score' => $validated['credit_score'] ?? 500,
            'status' => $validated['status'],
        ]);

        // Create wallet
        $user->wallet()->create([
            'balance' => 0,
            'outstanding_balance' => 0,
            'currency' => 'KES',
        ]);

        // Create credit limit
        $creditLimit = $this->calculateCreditLimit($user->credit_score);
        $user->creditLimit()->create([
            'limit' => $creditLimit,
            'used' => 0,
            'review_date' => now()->addDays(90),
        ]);

        // Assign role
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function show(User $user, DriverUnderwritingService $driverUnderwritingService)
    {
        $user->load([
            'wallet',
            'creditLimit',
            'vouchers',
            'leases.repayments',
            'roles',
            'driverDocuments.verifier',
            'bankStatementUploads.creditDecisions',
            'latestCreditDecision',
        ]);

        $underwritingSummary = null;
        if ($user->hasRole('driver')) {
            $underwritingSummary = $driverUnderwritingService->resolveForUser($user);
        }

        return view('admin.users.show', compact('user', 'underwritingSummary'));
    }

    public function verifyDriverDocument(Request $request, User $user, string $documentType)
    {
        $request->validate([
            'action' => 'required|in:verify,reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (!in_array($documentType, ['driver_license', 'sa_id', 'vehicle_license', 'merchant_ck', 'merchant_bbbee'], true)) {
            abort(404);
        }

        $document = $user->driverDocuments()->where('document_type', $documentType)->first();
        if (!$document) {
            return back()->with('error', 'Driver document not found.');
        }

        $isVerify = $request->string('action')->toString() === 'verify';
        $document->update([
            'verified' => $isVerify,
            'verified_by' => auth()->id(),
            'verified_at' => $isVerify ? now() : null,
            'notes' => $request->input('notes'),
        ]);

        $requiredTypes = ['driver_license', 'sa_id'];
        $verifiedRequiredCount = $user->driverDocuments()
            ->whereIn('document_type', $requiredTypes)
            ->where('verified', true)
            ->count();

        $user->update([
            'id_verification_status' => $verifiedRequiredCount === 2 ? 'verified' : 'pending_review',
            'id_verified_at' => $verifiedRequiredCount === 2 ? now() : null,
            'id_verification_provider' => 'manual',
        ]);

        return back()->with('success', $isVerify ? 'Document verified successfully.' : 'Document marked for re-submission.');
    }

    public function reviewBankStatement(
        Request $request,
        User $user,
        BankStatementCreditAssessmentService $assessmentService
    ) {
        $validated = $request->validate([
            'upload_id' => 'nullable|integer',
            'action' => 'required|in:approve,reject,reassess',
            'apply_recommended_limit' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $upload = BankStatementUpload::query()
            ->where('user_id', $user->id)
            ->when(!empty($validated['upload_id']), function ($q) use ($validated) {
                $q->where('id', (int) $validated['upload_id']);
            })
            ->latest('id')
            ->first();

        if (!$upload) {
            return back()->with('error', 'No bank statement upload found for this user.');
        }

        $latestDecision = CreditDecision::query()
            ->where('user_id', $user->id)
            ->where('upload_id', $upload->id)
            ->latest('decided_at')
            ->first();

        $action = (string) $validated['action'];
        if ($action === 'reassess') {
            $latestDecision = $assessmentService->assessAndStore($user, $upload);
            AuditTrailService::record(
                'bank_statement_reassessed_by_admin',
                $upload,
                [],
                ['decision_id' => $latestDecision->id],
                'Admin triggered bank statement reassessment'
            );

            return back()->with('success', 'Bank statement reassessed successfully.');
        }

        if ($action === 'approve') {
            $upload->forceFill([
                'status' => 'completed',
                'error_message' => null,
                'processed_at' => now(),
            ])->save();

            if ($latestDecision && (bool) ($validated['apply_recommended_limit'] ?? false)) {
                $assessmentService->applyDecisionToCreditLimit($user, $latestDecision);
            }
        } else {
            $upload->forceFill([
                'status' => 'needs_review',
                'error_message' => (string) ($validated['notes'] ?? 'Rejected during admin review.'),
                'processed_at' => now(),
            ])->save();
        }

        AuditTrailService::record(
            'bank_statement_reviewed_by_admin',
            $upload,
            [],
            [
                'action' => $action,
                'notes' => $validated['notes'] ?? null,
                'apply_recommended_limit' => (bool) ($validated['apply_recommended_limit'] ?? false),
            ],
            'Admin reviewed bank statement upload'
        );

        return back()->with('success', $action === 'approve'
            ? 'Bank statement approved successfully.'
            : 'Bank statement marked for further review.');
    }

    public function edit(User $user)
    {
        $roles = Role::query()
            ->whereIn('name', self::ALLOWED_ADMIN_MANAGED_ROLES)
            ->orderBy('name')
            ->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                Rule::unique('users')->ignore($user->id),
            ],
            'credit_score' => 'nullable|integer|min:300|max:850',
            'status' => 'required|in:active,suspended,flagged,blocked',
            'role' => ['required', Rule::in(self::ALLOWED_ADMIN_MANAGED_ROLES)],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'credit_score' => $validated['credit_score'],
            'status' => $validated['status'],
        ]);

        // Update role
        $user->syncRoles([$validated['role']]);

        // Update credit limit if score changed
        if ($request->has('credit_score')) {
            $creditLimit = $this->calculateCreditLimit($validated['credit_score']);
            $user->creditLimit()->update(['limit' => $creditLimit]);
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function updateCreditLimit(Request $request, User $user)
    {
        $request->validate([
            'limit' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $user->creditLimit()->update([
            'limit' => $request->limit,
            'status' => 'under_review',
        ]);

        // Log this action
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_limit' => $user->creditLimit->limit,
                'new_limit' => $request->limit,
                'reason' => $request->reason,
            ])
            ->log('updated_credit_limit');

        return back()->with('success', 'Credit limit updated successfully.');
    }

    public function updateWallet(Request $request, User $user)
    {
        $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $wallet = $user->wallet;

        if ($request->type === 'credit') {
            $wallet->increment('balance', $request->amount);
        } else {
            if ($wallet->balance < $request->amount) {
                return back()->with('error', 'Insufficient wallet balance.');
            }
            $wallet->decrement('balance', $request->amount);
        }

        // Create transaction
        $wallet->transactions()->create([
            'type' => $request->type,
            'amount' => $request->amount,
            'balance_before' => $request->type === 'credit' ? $wallet->balance - $request->amount : $wallet->balance + $request->amount,
            'balance_after' => $wallet->balance,
            'description' => 'Manual adjustment: ' . $request->reason,
            'reference' => 'MANUAL-' . time(),
            'status' => 'completed',
            'metadata' => ['admin_id' => auth()->id(), 'reason' => $request->reason],
        ]);

        return back()->with('success', 'Wallet updated successfully.');
    }

    public function forcePasswordReset(User $user)
    {
        if (empty($user->email)) {
            return back()->with('error', 'User has no email address for password reset.');
        }

        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('error', 'Failed to send password reset email. Please try again.');
        }

        return back()->with('success', 'Password reset link sent successfully.');
    }

    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('success', "User {$newStatus} successfully.");
    }

    private function calculateCreditLimit($creditScore)
    {
        if ($creditScore >= 800) return 50000;
        if ($creditScore >= 700) return 30000;
        if ($creditScore >= 600) return 15000;
        if ($creditScore >= 500) return 8000;
        if ($creditScore >= 400) return 3000;
        return 1000;
    }
}
