<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is ' . $user->status . '. Please contact support.',
                ]);
            }
            
            // Redirect based on role
            if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('employee')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('merchant')) {
                return redirect()->route('merchant.dashboard');
            }  elseif ($user->hasRole('investor')) {
                return redirect()->route('investor.dashboard');
            }
                else {
                return redirect()->route('driver.dashboard');
            }
        }

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
        
        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
