<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\CreditLimit;
use App\Services\DriverUnderwritingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
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
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
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
        $user->load(['wallet', 'creditLimit', 'vouchers', 'leases.repayments', 'roles']);

        $underwritingSummary = null;
        if ($user->hasRole('driver')) {
            $underwritingSummary = $driverUnderwritingService->resolveForUser($user);
        }

        return view('admin.users.show', compact('user', 'underwritingSummary'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
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
            'role' => 'required|exists:roles,name',
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
