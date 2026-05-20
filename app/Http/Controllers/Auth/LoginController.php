<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Events\Auth\UserLoggedIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\FormInteractionService;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();

            FormInteractionService::record('login', 'submit', $request, 'ok');
            
            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
            
            // Check user status
            if ((string) $user->status === 'suspended') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is suspended. Please contact support.',
                ]);
            }

            // Allow flagged/pending users to login (they can browse the dashboard, but key actions can remain gated).
            $pendingApproval = Schema::hasTable('account_approvals')
                ? $user->latestAccountApproval()->where('status', 'pending')->exists()
                : false;
            $pendingAdminApprovalFlag = $pendingApproval || ((string) $user->status !== 'active');
            if ($user->hasAnyRole(['driver', 'merchant'])) {
                if ($pendingAdminApprovalFlag) {
                    $request->session()->put('pending_admin_approval', true);
                } else {
                    $request->session()->forget('pending_admin_approval');
                }
            }

            if (!$user->hasAnyRole(['driver', 'merchant', 'admin', 'super_admin', 'employee', 'investor'])) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'This account role is not enabled for this production login channel.',
                ]);
            }

            // Drivers still require verified email before web access. Merchants can browse their
            // dashboard before verification, but transactional actions remain gated elsewhere.
            if ($user->hasRole('driver') && method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
                return redirect()
                    ->route('verification.notice', ['email' => (string) $user->email])
                    ->with('status', 'Please verify your email to continue.');
            }

            event(new UserLoggedIn($user, 'web', $request->ip()));
            
            // Redirect based on role
            if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('employee')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('merchant')) {
                return redirect()->route('merchant.dashboard');
            } elseif ($user->hasRole('investor')) {
                return redirect()->route('investor.dashboard');
            }
            else {
                if ($this->needsRegistrationDocuments($user)) {
                    return redirect()
                        ->route('registration.complete', ['role' => 'driver'])
                        ->with('error', 'Please complete your profile and upload verification documents.');
                }
                return redirect()->route('driver.dashboard');
            }
        }

        AuditTrailService::record(
            'auth_login_failed',
            null,
            [],
            ['email' => (string) $request->input('email')],
            'Login failed'
        );

        FormInteractionService::record('login', 'submit', $request, 'fail');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->regenerate();
        
        return redirect('/')->with('success', 'You have been logged out successfully.');
    }

    private function needsRegistrationDocuments(User $user): bool
    {
        return empty($user->id_number);
    }
}
