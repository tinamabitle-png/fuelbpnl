<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Models\User;
use App\Models\Otp;
use App\Models\Device;
use App\Models\Wallet;
use App\Models\CreditLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:driver,merchant',
            'device_id' => 'required|string',
            'device_name' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
            'fcm_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role = (string) $request->input('role', 'driver');

        // Generate OTP for verification
        $otp = Otp::generate($request->phone, 'registration');

        // Create user (inactive until verified)
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'device_fingerprint' => $request->device_id,
            'status' => 'pending', // Will be activated after OTP verification
            'credit_score' => 500, // Default score
        ]);

        // Restrict public registration to driver/merchant only.
        $allowedRole = Role::where('name', $role)->first();
        if ($allowedRole) {
            $user->assignRole($allowedRole);
        }

        // Create wallet
        $user->wallet()->create([
            'balance' => 0,
            'outstanding_balance' => 0,
            'currency' => 'KES',
        ]);

        // Create credit limit (default based on credit score)
        $creditLimit = $this->calculateCreditLimit($user->credit_score);
        $user->creditLimit()->create([
            'limit' => $creditLimit,
            'used' => 0,
            'review_date' => now()->addDays(90),
            'status' => 'active',
        ]);

        // Save device info
        $user->devices()->create([
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'fcm_token' => $request->fcm_token,
            'last_login_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Send OTP via SMS (in production, integrate with SMS gateway)
        // $this->sendSms($request->phone, "Your verification code is: {$otp->code}");

        event(new UserRegistered($user, 'api', $request->ip()));

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify OTP.',
            'user_id' => $user->id,
            'phone' => $user->phone,
            // For development, include OTP
            'otp_code' => config('app.debug') ? $otp->code : null,
        ]);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
            'purpose' => 'required|in:registration,login,reset_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $otp = Otp::verify($request->phone, $request->code, $request->purpose);

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401);
        }

        // If registration OTP, activate user
        if ($request->purpose === 'registration') {
            $user = User::where('phone', $request->phone)->first();
            if ($user) {
                $user->update(['status' => 'active']);
                
                // Generate token
                $token = $user->createToken('mobile')->plainTextToken;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Account verified successfully',
                    'token' => $token,
                    'user' => $user->load(['wallet', 'creditLimit']),
                ]);
            }
        }

        // For login OTP, return success (login will generate token)
        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
        ]);
    }

    /**
     * Login with phone/password
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'device_id' => 'required|string',
            'device_name' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
            'fcm_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user by phone
        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is ' . $user->status
            ], 403);
        }

        // Production hardening: API login channel only supports driver/merchant.
        if (!$user->hasAnyRole(['driver', 'merchant'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Driver or merchant account required.'
            ], 403);
        }

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Save/update device info
        $device = Device::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $request->device_id,
            ],
            [
                'device_name' => $request->device_name,
                'device_type' => $request->device_type,
                'fcm_token' => $request->fcm_token,
                'last_login_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        // Generate token
        $token = $user->createToken('mobile')->plainTextToken;

        event(new UserLoggedIn($user, 'api', $request->ip()));

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => null, // Sanctum tokens don't expire by default
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'credit_score' => $user->credit_score,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
                'wallet' => $user->wallet,
                'credit_limit' => $user->creditLimit,
                'available_credit' => $user->available_credit,
            ],
        ]);
    }

    /**
     * Login with phone OTP
     */
    public function loginWithOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'device_id' => 'required|string',
            'device_name' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
            'fcm_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not registered'
            ], 404);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is ' . $user->status
            ], 403);
        }

        // Generate OTP
        $otp = Otp::generate($request->phone, 'login', $user->id);

        // Send OTP via SMS
        // $this->sendSms($request->phone, "Your login code is: {$otp->code}");

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone',
            'user_id' => $user->id,
            // For development, include OTP
            'otp_code' => config('app.debug') ? $otp->code : null,
        ]);
    }

    /**
     * Complete OTP login
     */
    public function completeOtpLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
            'device_id' => 'required|string',
            'device_name' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
            'fcm_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $otp = Otp::verify($request->phone, $request->code, 'login');

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401);
        }

        $user = User::find($otp->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Save/update device info
        $device = Device::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $request->device_id,
            ],
            [
                'device_name' => $request->device_name,
                'device_type' => $request->device_type,
                'fcm_token' => $request->fcm_token,
                'last_login_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        // Generate token
        $token = $user->createToken('mobile')->plainTextToken;

        event(new UserLoggedIn($user, 'api_otp', $request->ip()));

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'credit_score' => $user->credit_score,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
                'wallet' => $user->wallet,
                'credit_limit' => $user->creditLimit,
                'available_credit' => $user->available_credit,
            ],
        ]);
    }

    /**
     * Forgot password - request reset
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not registered'
            ], 404);
        }

        // Generate OTP
        $otp = Otp::generate($request->phone, 'reset_password', $user->id);

        // Send OTP via SMS
        // $this->sendSms($request->phone, "Your password reset code is: {$otp->code}");

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone for password reset',
            // For development, include OTP
            'otp_code' => config('app.debug') ? $otp->code : null,
        ]);
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $otp = Otp::verify($request->phone, $request->code, 'reset_password');

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401);
        }

        $user = User::find($otp->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now login with your new password.',
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load(['wallet', 'creditLimit', 'devices']);
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'credit_score' => $user->credit_score,
                'status' => $user->status,
                'last_login_at' => $user->last_login_at,
                'roles' => $user->getRoleNames(),
                'wallet' => $user->wallet,
                'credit_limit' => $user->creditLimit,
                'available_credit' => $user->available_credit,
                'default_risk' => $user->getDefaultRisk(),
                'devices' => $user->devices,
                'autopay_enabled' => (bool) $user->autopay_enabled,
                'autopay_gateway' => (string) ($user->autopay_gateway ?? ''),
                'autopay_status' => (string) ($user->autopay_status ?? 'inactive'),
                'autopay_has_token' => trim((string) ($user->autopay_token ?? '')) !== '',
                'autopay_ready' => $user->isAutopayReady(),
                'autopay_last_attempt_at' => $user->autopay_last_attempt_at,
                'autopay_next_attempt_at' => $user->autopay_next_attempt_at,
            ],
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(['wallet', 'creditLimit']),
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Calculate credit limit based on credit score
     */
    private function calculateCreditLimit($creditScore)
    {
        if ($creditScore >= 800) return 50000;
        if ($creditScore >= 700) return 30000;
        if ($creditScore >= 600) return 15000;
        if ($creditScore >= 500) return 8000;
        if ($creditScore >= 400) return 3000;
        return 1000;
    }

    /**
     * Send SMS (placeholder for SMS integration)
     */
    private function sendSms($phone, $message)
    {
        // Integrate with SMS gateway like AfricasTalking, Twilio, etc.
        // For now, just log it
        \Log::info("SMS to {$phone}: {$message}");
        return true;
    }
}
