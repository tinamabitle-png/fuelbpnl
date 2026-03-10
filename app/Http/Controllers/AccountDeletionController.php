<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountDeletionController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.delete');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string'],
            'confirm' => ['required', 'accepted'],
        ]);

        // If user has a local password, require confirmation.
        if (!empty($user->password)) {
            if (empty($validated['password']) || !Hash::check((string) $validated['password'], (string) $user->password)) {
                return back()
                    ->withErrors(['password' => 'Incorrect password.'])
                    ->withInput();
            }
        }

        $activeLeases = $user->leases()->where('status', 'active')->count();
        if ($activeLeases > 0) {
            return back()
                ->withErrors(['confirm' => 'You cannot delete your account while you have active leases.'])
                ->withInput();
        }

        $wallet = $user->wallet;
        $outstanding = (float) ($wallet?->outstanding_balance ?? 0);
        if ($outstanding > 0) {
            return back()
                ->withErrors(['confirm' => 'You cannot delete your account while you have an outstanding balance.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $user, $validated) {
            // Revoke API tokens (Sanctum).
            try {
                $user->tokens()->delete();
            } catch (\Throwable) {
                // best-effort
            }

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'reason' => (string) ($validated['reason'] ?? ''),
                    'requested_at' => now(),
                ])
                ->log('account_soft_deleted');

            $user->delete(); // Soft delete

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        });

        return redirect('/')
            ->with('success', 'Your account has been scheduled for deletion and is no longer accessible.');
    }
}

