<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Models\User;
use App\Models\OTP;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:driver,merchant',
            'device_id' => 'required|string',
            'device_name' => 'required|string',
            'device_type' => 'required|in:android,ios',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role = (string) $request->input('role', 'driver');

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
        ]);

        // Create wallet for user
        $user->wallet()->create([
            'balance' => 0,
            'outstanding_balance' => 0,
            'currency' => 'KES',
        ]);

        // Restrict public registration to driver/merchant only.
        $user->assignRole($role);

        // Record device
        Device::create([
            'user_id' => $user->id,
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'last_login_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        // Generate OTP
        $otp = OTP::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => rand(100000, 999999),
            'purpose' => 'registration',
            'expires_at' => now()->addMinutes(10),
        ]);

        // In production, send SMS here
        // $this->sendSMS($user->phone, "Your verification code is: {$otp->code}");

        event(new UserRegistered($user, 'api_v1', $request->ip()));

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify OTP.',
            'data' => [
                'user_id' => $user->id,
                'requires_otp' => true,
                'otp_id' => $otp->id,
            ]
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'otp_id' => 'required|exists:o_t_p_s,id',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $otp = OTP::where('id', $request->otp_id)
                  ->where('user_id', $request->user_id)
                  ->where('code', $request->code)
                  ->where('expires_at', '>', now())
                  ->where('used', false)
                  ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $otp->update(['used' => true]);

        $user = User::find($request->user_id);
        $user->update(['email_verified_at' => now()]);

        // Generate token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => [
                'token' => $token,
                'user' => $user->load('wallet'),
                'roles' => $user->getRoleNames(),
            ]
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

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

        // Update device info
        Device::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $request->device_id],
            [
                'last_login_at' => now(),
                'ip_address' => $request->ip(),
                'device_name' => $request->device_name,
                'device_type' => $this->resolveDeviceType($request->device_type),
            ]
        );

        // Update user login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Create token
        $token = $user->createToken('mobile-app')->plainTextToken;

        if (!$user->hasAnyRole(['driver', 'merchant'])) {
            return response()->json([
                'success' => false,
                'message' => 'This API login is limited to driver and merchant accounts.',
            ], 403);
        }

        event(new UserLoggedIn($user, 'api_v1', $request->ip()));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user->load('wallet'),
                'roles' => $user->getRoleNames(),
            ]
        ]);
    }

    public function quickLogin(Request $request)
    {
        if (!App::environment(['local', 'development'])) {
            return response()->json([
                'success' => false,
                'message' => 'Quick login is only enabled in local/development environments.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:driver,merchant',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role = (string) $request->input('role');
        $user = User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->where('name', $role))
            ->orderBy('id')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "No active {$role} account found.",
            ], 404);
        }

        $deviceId = 'quick-login-' . $role . '-' . Str::random(8);
        Device::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'last_login_at' => now(),
                'ip_address' => $request->ip(),
                'device_name' => 'Quick Login',
                'device_type' => 'web',
            ]
        );

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('mobile-quick-login')->plainTextToken;

        event(new UserLoggedIn($user, 'api_v1_quick', $request->ip()));

        return response()->json([
            'success' => true,
            'message' => 'Quick login successful',
            'data' => [
                'token' => $token,
                'user' => $user->load('wallet'),
                'roles' => $user->getRoleNames(),
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load(['wallet', 'creditLimit']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'email']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()->load('wallet')
        ]);
    }

    private function generateDeviceFingerprint(Request $request)
    {
        $data = [
            'device_id' => $request->device_id,
            'user_agent' => $request->header('User-Agent'),
            'ip' => $request->ip(),
        ];

        return hash('sha256', json_encode($data));
    }

    private function resolveDeviceType(?string $deviceType): string
    {
        $normalized = strtolower(trim((string) $deviceType));
        return in_array($normalized, ['android', 'ios', 'web'], true)
            ? $normalized
            : 'android';
    }
}
