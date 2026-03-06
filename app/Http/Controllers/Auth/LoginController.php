<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Events\Auth\UserLoggedIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Services\AuditTrailService;

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

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
            
            // Check user status
            if ($user->status !== 'active') {
                $pendingApproval = Schema::hasTable('account_approvals')
                    ? $user->latestAccountApproval()->where('status', 'pending')->exists()
                    : false;
                Auth::logout();
                return back()->withErrors([
                    'email' => $pendingApproval
                        ? 'Your account is pending admin approval.'
                        : 'Your account is ' . $user->status . '. Please contact support.',
                ]);
            }

            if (!$user->hasAnyRole(['driver', 'merchant', 'admin', 'super_admin', 'employee'])) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'This account role is not enabled for this production login channel.',
                ]);
            }

            event(new UserLoggedIn($user, 'web', $request->ip()));
            
            // Redirect based on role
            if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('employee')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('merchant')) {
                return redirect()->route('merchant.dashboard');
            }  elseif ($user->hasRole('investor')) {
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
        return empty($user->id_number)
            || empty($user->id_document_path)
            || empty($user->driver_license_path);
    }
}
