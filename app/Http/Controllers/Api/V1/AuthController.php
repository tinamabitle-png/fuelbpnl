<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Models\User;
use App\Models\Otp;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
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
        $otp = Otp::create([
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

        $otp = Otp::where('id', $request->otp_id)
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

    /**
     * Quick login for older one-click mobile builds.
     * Supports selecting an active driver/merchant by user_id or role without phone/password.
     */
    public function quickLogin(Request $request)
    {
        // If full credentials are provided, fall back to the normal login flow.
        if ($request->filled('phone') && $request->filled('password')) {
            return $this->login($request);
        }

        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string',
            'device_name' => 'nullable|string|max:255',
            'device_type' => 'nullable|in:android,ios,web',
            'user_id' => 'nullable|integer|exists:users,id',
            'role' => 'nullable|in:driver,merchant',
            'phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $quickLoginEnabled = (bool) env('QUICK_LOGIN_ENABLED', config('app.debug', false));
        if (!$quickLoginEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'Quick login is disabled.',
            ], 403);
        }

        $query = User::query()->where('status', 'active');

        if ($request->filled('user_id')) {
            $query->where('id', (int) $request->input('user_id'));
        } elseif ($request->filled('phone')) {
            $query->where('phone', (string) $request->input('phone'));
        } elseif ($request->filled('role')) {
            $query->role((string) $request->input('role'));
        } else {
            // Default to any active driver first for one-click builds.
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['driver', 'merchant']);
            })->orderByDesc('last_login_at');
        }

        $user = $query->first();
        if (!$user || !$user->hasAnyRole(['driver', 'merchant'])) {
            return response()->json([
                'success' => false,
                'message' => 'No eligible user found for quick login.',
            ], 404);
        }

        $deviceId = trim((string) $request->input('device_id'));
        if ($deviceId === '') {
            $deviceId = 'quick-' . substr(sha1((string) $request->ip() . '|' . (string) $request->userAgent() . '|' . (string) $user->id), 0, 24);
        }

        Device::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'last_login_at' => now(),
                'ip_address' => $request->ip(),
                'device_name' => (string) ($request->input('device_name') ?: 'Quick Login Device'),
                'device_type' => $this->resolveDeviceType($request->input('device_type')),
            ]
        );

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;
        event(new UserLoggedIn($user, 'api_v1_quick_login', $request->ip()));

        return response()->json([
            'success' => true,
            'message' => 'Quick login successful',
            'data' => [
                'token' => $token,
                'user' => $user->load('wallet'),
                'roles' => $user->getRoleNames(),
            ],
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

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'channel' => 'nullable|in:phone,email,both',
        ]);

        $validator->after(function ($v) use ($request) {
            if (!$request->filled('phone') && !$request->filled('email')) {
                $v->errors()->add('identifier', 'Provide phone or email.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = trim((string) $request->input('phone', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $channel = strtolower(trim((string) $request->input('channel', 'both')));
        if (!in_array($channel, ['phone', 'email', 'both'], true)) {
            $channel = 'both';
        }

        $userQuery = User::query();
        if ($phone !== '') {
            $userQuery->where('phone', $phone);
        }
        if ($email !== '') {
            if ($phone !== '') {
                $userQuery->orWhere('email', $email);
            } else {
                $userQuery->where('email', $email);
            }
        }
        $user = $userQuery->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        }

        $sentVia = [];
        $debugCodes = [];

        if (in_array($channel, ['phone', 'both'], true) && trim((string) $user->phone) !== '') {
            $otp = Otp::generateForChannel((string) $user->phone, 'reset_password', $user->id, 'phone');
            $sentVia[] = 'phone';
            if (config('app.debug')) {
                $debugCodes['phone_otp_code'] = $otp->code;
            }
            Log::info('Password reset OTP generated for phone channel.', ['user_id' => $user->id]);
        }

        if (in_array($channel, ['email', 'both'], true) && trim((string) $user->email) !== '') {
            $otp = Otp::generateForChannel((string) $user->email, 'reset_password', $user->id, 'email');
            Mail::raw(
                "Your Bwiser password reset OTP is {$otp->code}. It expires in 10 minutes.",
                function ($message) use ($user) {
                    $message->to((string) $user->email)->subject('Bwiser Password Reset OTP');
                }
            );
            $sentVia[] = 'email';
            if (config('app.debug')) {
                $debugCodes['email_otp_code'] = $otp->code;
            }
        }

        if (empty($sentVia)) {
            return response()->json([
                'success' => false,
                'message' => 'No delivery channel available for this account.',
            ], 422);
        }

        $response = [
            'success' => true,
            'message' => 'OTP sent for password reset',
            'sent_via' => $sentVia,
        ];

        if (!empty($debugCodes)) {
            $response = array_merge($response, $debugCodes);
        }

        return response()->json($response);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
            'channel' => 'nullable|in:phone,email',
        ]);

        $validator->after(function ($v) use ($request) {
            if (!$request->filled('phone') && !$request->filled('email')) {
                $v->errors()->add('identifier', 'Provide phone or email.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = trim((string) $request->input('phone', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $channel = strtolower(trim((string) $request->input('channel', '')));
        $identifier = $phone !== '' ? $phone : $email;
        $resolvedChannel = $channel !== '' ? $channel : ($phone !== '' ? 'phone' : 'email');

        $otp = Otp::verifyForChannel($identifier, (string) $request->input('code'), 'reset_password', $resolvedChannel);
        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 401);
        }

        $user = User::find($otp->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->update([
            'password' => Hash::make((string) $request->input('password')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
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
