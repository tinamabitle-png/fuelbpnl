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
use App\Services\AfricasTalkingSmsService;
use App\Support\SouthAfricanIdNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $request->merge([
            'phone' => $this->normalizeSouthAfricanPhone((string) $request->input('phone')),
            'email' => $request->filled('email') ? strtolower((string) $request->input('email')) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required_without:first_name,last_name|string|max:255',
            'first_name' => 'required_without:name|nullable|string|max:120',
            'last_name' => 'required_without:name|nullable|string|max:120',
            'phone' => ['required', 'regex:/^\\+27[6-8][0-9]{8}$/', 'unique:users,phone'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:driver,merchant',
            'id_number' => [
                'nullable',
                'digits:13',
                'unique:users,id_number',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value) || trim($value) === '') {
                        return;
                    }

                    if (!SouthAfricanIdNumber::isValid($value)) {
                        $fail('Invalid South African ID number.');
                    }
                },
            ],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'in:male,female,other'],
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

        $rawName = (string) $request->input('name', '');
        [$derivedFirst, $derivedLast] = $this->splitName($rawName);
        $firstName = trim((string) $request->input('first_name', '')) ?: $derivedFirst;
        $lastName = trim((string) $request->input('last_name', '')) ?: $derivedLast;
        $fullName = trim($firstName . ' ' . $lastName);
        if ($fullName === '') {
            $fullName = trim($rawName) ?: 'Bwiser User';
        }

        $idNumber = trim((string) $request->input('id_number', ''));
        $dob = trim((string) $request->input('date_of_birth', ''));
        if ($dob === '') {
            $derivedDob = $idNumber !== '' ? SouthAfricanIdNumber::deriveDateOfBirth($idNumber) : null;
            $dob = (string) ($derivedDob ?: '');
        }
        if ($dob === '') {
            $dob = (string) config('services.flutterwave.virtual_cards_date_of_birth', '1990-01-01');
        }

        $gender = $this->normalizeGender((string) $request->input('gender', ''));
        if ($gender === '') {
            $derivedGender = $idNumber !== '' ? SouthAfricanIdNumber::deriveGender($idNumber) : null;
            $gender = $derivedGender ?: 'male';
        }

        // Generate OTP for verification
        $otp = Otp::generate($request->phone, 'registration');

        // Create user (inactive until verified)
        $user = User::create([
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dob,
            'gender' => $gender,
            'phone' => $request->phone,
            'email' => $request->email,
            'id_number' => $idNumber !== '' ? $idNumber : null,
            'password' => Hash::make($request->password),
            'device_fingerprint' => $request->device_id,
            'status' => 'pending', // Will be activated after OTP verification
            'registration_flag' => in_array($role, ['driver', 'merchant'], true) ? 'preseed driver' : null,
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
            'currency' => 'ZAR',
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
                'errors' => $validator->errors()
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
                'message' => 'Account not found'
            ], 404);
        }

        $sentVia = [];
        $debugCodes = [];

        if (in_array($channel, ['phone', 'both'], true) && trim((string) $user->phone) !== '') {
            $otp = Otp::generateForChannel((string) $user->phone, 'reset_password', $user->id, 'phone');
            $sms = $this->sendSms(
                (string) $user->phone,
                "Your Bwiser password reset OTP is {$otp->code}. It expires in 10 minutes."
            );
            if ($sms['success'] ?? false) {
                $sentVia[] = 'phone';
            } else {
                Log::warning('Failed to send forgot-password OTP via Africa\'s Talking', [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'error' => $sms['error'] ?? 'Unknown SMS error',
                ]);
            }
            if (config('app.debug')) {
                $debugCodes['phone_otp_code'] = $otp->code;
            }
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

    /**
     * Reset password with OTP
     */
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
                'errors' => $validator->errors()
            ], 422);
        }

        $phone = trim((string) $request->input('phone', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $channel = strtolower(trim((string) $request->input('channel', '')));
        $identifier = $phone !== '' ? $phone : $email;
        $resolvedChannel = $channel !== '' ? $channel : ($phone !== '' ? 'phone' : 'email');

        $otp = Otp::verifyForChannel($identifier, (string) $request->code, 'reset_password', $resolvedChannel);

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
        /** @var AfricasTalkingSmsService $sms */
        $sms = app(AfricasTalkingSmsService::class);
        return $sms->sendOtp((string) $phone, (string) $message);
    }

    private function normalizeSouthAfricanPhone(string $phone): string
    {
        $clean = trim($phone);
        $clean = preg_replace('/[^\\d+]/', '', $clean) ?? $clean;

        if (substr($clean, 0, 3) === '+27') {
            return $clean;
        }

        if (substr($clean, 0, 2) === '27') {
            return '+' . $clean;
        }

        if (substr($clean, 0, 1) === '0') {
            return '+27' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }

        $name = preg_replace('/\\s+/', ' ', $name) ?? $name;
        $parts = explode(' ', $name);
        $first = trim((string) ($parts[0] ?? ''));
        $last = count($parts) > 1 ? trim(implode(' ', array_slice($parts, 1))) : '';

        return [$first, $last];
    }

    private function normalizeGender(string $gender): string
    {
        $g = strtolower(trim($gender));
        if ($g === 'm') $g = 'male';
        if ($g === 'f') $g = 'female';
        if (in_array($g, ['male', 'female', 'other'], true)) {
            return $g;
        }
        return '';
    }

}
