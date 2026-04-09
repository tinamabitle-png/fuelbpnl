<?php

namespace App\Http\Controllers\Auth;

use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\AccountApproval;
use App\Models\DriverDocument;
use App\Models\FuelStation;
use App\Models\MerchantFranchise;
use App\Models\User;
use App\Support\SouthAfricanIdNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    /** @var array<string, bool> */
    private array $columnCache = [];

    public function redirect(Request $request): RedirectResponse
    {
        $clientId = (string) config('services.google.client_id');
        $redirectUri = (string) config('services.google.redirect');

        abort_if($clientId === '' || $redirectUri === '', 500, 'Google OAuth is not configured.');

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'error' => ['nullable', 'string'],
        ]);

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in was cancelled or denied.',
            ]);
        }

        $expectedState = (string) $request->session()->pull('google_oauth_state');
        if ($expectedState === '' || $expectedState !== (string) $request->input('state')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Invalid Google sign-in state. Please try again.',
            ]);
        }

        $code = (string) $request->input('code');
        if ($code === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in did not return an authorization code.',
            ]);
        }

        $tokenResponse = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => (string) config('services.google.client_id'),
                'client_secret' => (string) config('services.google.client_secret'),
                'redirect_uri' => (string) config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);

        if (!$tokenResponse->ok()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in failed while exchanging the auth code.',
            ]);
        }

        $idToken = (string) ($tokenResponse->json('id_token') ?? '');
        if ($idToken === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in did not return a valid ID token.',
            ]);
        }

        $tokenInfo = Http::timeout(15)
            ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);

        if (!$tokenInfo->ok()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in token verification failed.',
            ]);
        }

        $payload = $tokenInfo->json();
        $clientId = (string) config('services.google.client_id');
        $aud = (string) ($payload['aud'] ?? '');
        $sub = (string) ($payload['sub'] ?? '');
        $email = strtolower((string) ($payload['email'] ?? ''));
        $name = (string) ($payload['name'] ?? '');
        $emailVerified = ((string) ($payload['email_verified'] ?? '')) === 'true';

        if ($aud !== $clientId || $sub === '' || $email === '' || !$emailVerified) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in returned an invalid profile.',
            ]);
        }

        $user = User::query()
            ->where('google_sub', $sub)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->update($this->onlyExistingColumns('users', [
                'google_sub' => $sub,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]));
        }

        if ($user && $this->canDirectLogin($user)) {
            Auth::login($user, true);
            $request->session()->regenerate();
            event(new UserLoggedIn($user, 'web_google', $request->ip()));
            return $this->redirectAfterLogin($user);
        }

        $request->session()->put('google_oauth_pending', [
            'sub' => $sub,
            'email' => $email,
            'name' => $name,
            'user_id' => $user?->id,
        ]);

        return redirect()->route('auth.google.complete.form');
    }

    public function showCompleteForm(Request $request)
    {
        $pending = $request->session()->get('google_oauth_pending');
        abort_if(!is_array($pending) || empty($pending['email']), 403, 'Google session expired. Please sign in again.');

        $user = null;
        $role = null;
        if (!empty($pending['user_id'])) {
            $user = User::query()->find((int) $pending['user_id']);
            if ($user) {
                $role = $user->hasRole('merchant') ? 'merchant' : ($user->hasRole('driver') ? 'driver' : null);
            }
        }

        $franchises = collect();
        if (Schema::hasTable('merchant_franchises')) {
            $franchises = MerchantFranchise::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('auth.google-complete', [
            'pending' => $pending,
            'existingUser' => $user,
            'lockedRole' => $role,
            'franchises' => $franchises,
        ]);
    }

    public function completeRegistration(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('google_oauth_pending');
        abort_if(!is_array($pending) || empty($pending['email']) || empty($pending['sub']), 403, 'Google session expired. Please sign in again.');

        $existingUser = !empty($pending['user_id']) ? User::query()->find((int) $pending['user_id']) : null;
        $lockedRole = $existingUser
            ? ($existingUser->hasRole('merchant') ? 'merchant' : ($existingUser->hasRole('driver') ? 'driver' : null))
            : null;

        $role = $lockedRole ?: (string) $request->input('role');
        if (!in_array($role, ['driver', 'merchant'], true)) {
            return back()->withErrors(['role' => 'Select an account type.'])->withInput();
        }

        $request->merge([
            'email' => strtolower((string) $pending['email']),
            'phone' => $this->normalizeSouthAfricanPhone((string) $request->input('phone')),
        ]);

        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required_without:name', 'string', 'max:120'],
            'last_name' => ['required_without:name', 'string', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'phone' => ['required', 'regex:/^\+27[6-8][0-9]{8}$/', Rule::unique('users', 'phone')->ignore($existingUser?->id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($existingUser?->id)],
        ];

        if ($role === 'driver') {
            $rules = array_merge($rules, [
                'id_number' => ['required', 'digits:13', Rule::unique('users', 'id_number')->ignore($existingUser?->id)],
                'home_address' => ['required', 'string', 'max:500'],
                'city' => ['required', 'string', 'max:120'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'driver_platform' => ['required', Rule::in(['checkers_sixty60', 'mr_d', 'takealot', 'indrive', 'uber', 'bolt', 'other'])],
                'driver_platform_other' => ['nullable', 'required_if:driver_platform,other', 'string', 'max:120'],
                'id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'driver_license_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'vehicle_license_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'bank_statement_document' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:8192'],
            ]);
        } else {
            $hasFranchiseTable = Schema::hasTable('merchant_franchises');
            $rules = array_merge($rules, [
                'franchise_id' => $hasFranchiseTable
                    ? ['required', Rule::exists('merchant_franchises', 'id')->where('is_active', true)]
                    : ['nullable'],
                'business_address' => ['required', 'string', 'max:500'],
                'city' => ['required', 'string', 'max:120'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'ck_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'bbbee_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            ]);
        }

        $validated = $request->validate($rules);

        $user = DB::transaction(function () use ($existingUser, $role, $validated, $request, $pending) {
            [$derivedFirst, $derivedLast] = $this->splitName((string) ($validated['name'] ?? ''));
            $firstName = trim((string) ($validated['first_name'] ?? '')) ?: $derivedFirst;
            $lastName = trim((string) ($validated['last_name'] ?? '')) ?: $derivedLast;
            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName === '') {
                throw ValidationException::withMessages([
                    'first_name' => 'First name is required.',
                    'last_name' => 'Last name is required.',
                ]);
            }

            $country = 'South Africa';

            if ($role === 'driver') {
                $gender = strtolower(trim((string) ($validated['gender'] ?? '')));
                if (!in_array($gender, ['male', 'female', 'other'], true)) {
                    $gender = '';
                }

                $idNumber = (string) ($validated['id_number'] ?? '');
                if (!SouthAfricanIdNumber::isValid($idNumber)) {
                    throw ValidationException::withMessages([
                        'id_number' => 'Invalid South African ID number.',
                    ]);
                }

                $derivedDob = SouthAfricanIdNumber::deriveDateOfBirth($idNumber);
                $providedDob = trim((string) ($validated['date_of_birth'] ?? '')) ?: null;
                if ($derivedDob === null) {
                    throw ValidationException::withMessages([
                        'id_number' => 'Invalid South African ID number (date of birth could not be derived).',
                    ]);
                }
                if ($providedDob !== null && $providedDob !== $derivedDob) {
                    throw ValidationException::withMessages([
                        'date_of_birth' => 'Date of birth must match the ID number.',
                    ]);
                }
                $dob = $derivedDob;

                if ($gender === '') {
                    $gender = SouthAfricanIdNumber::deriveGender($idNumber) ?: 'male';
                }
            } else {
                $gender = strtolower(trim((string) ($validated['gender'] ?? ''))) ?: 'male';
                if (!in_array($gender, ['male', 'female', 'other'], true)) {
                    $gender = 'male';
                }

                $dob = trim((string) ($validated['date_of_birth'] ?? '')) ?: null;
                if ($dob === null) {
                    $dob = trim((string) config('services.flutterwave.virtual_cards_date_of_birth', '')) ?: null;
                }
            }

            if ($gender === '') {
                $gender = 'male';
            }

            $userPayload = [
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dob,
                'gender' => $gender,
                'email' => strtolower((string) $pending['email']),
                'phone' => $validated['phone'],
                'google_sub' => (string) $pending['sub'],
                'email_verified_at' => now(),
                'status' => 'flagged',
                'registration_flag' => 'preseed driver',
                'credit_score' => $existingUser?->credit_score ?? 500,
                'id_number' => $role === 'driver' ? $validated['id_number'] : null,
                'merchant_franchise_id' => $role === 'merchant'
                    ? (isset($validated['franchise_id']) ? (int) $validated['franchise_id'] : null)
                    : null,
                'home_address' => $role === 'driver' ? ($validated['home_address'] ?? null) : null,
                'city' => $validated['city'] ?? null,
                'country' => $country,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'driver_platform' => $role === 'driver' ? ($validated['driver_platform'] ?? null) : null,
                'driver_platform_other' => $role === 'driver'
                    ? (($validated['driver_platform'] ?? null) === 'other' ? ($validated['driver_platform_other'] ?? null) : null)
                    : null,
            ];

            if ($existingUser) {
                $existingUser->update($this->onlyExistingColumns('users', $userPayload));
                $user = $existingUser;
            } else {
                $user = User::create($this->onlyExistingColumns('users', array_merge($userPayload, [
                    'password' => bcrypt(Str::random(32)),
                ])));
            }

            $roleModel = Role::query()->where('name', $role)->first();
            if ($roleModel && !$user->hasRole($role)) {
                $user->assignRole($roleModel);
            }

            if (Schema::hasTable('wallets')) {
                $user->wallet()->firstOrCreate([], [
                    'balance' => 0,
                    'outstanding_balance' => 0,
                    'currency' => 'ZAR',
                ]);
            }

            if ($role === 'driver') {
                if (Schema::hasTable('credit_limits')) {
                    $user->creditLimit()->firstOrCreate([], [
                        'limit' => 3000,
                        'used' => 0,
                        'status' => 'active',
                        'review_date' => now()->addDays(90)->toDateString(),
                    ]);
                }

                $idPath = $request->hasFile('id_document')
                    ? $request->file('id_document')->store('driver_documents/id', 'public')
                    : null;
                $driverLicensePath = $request->hasFile('driver_license_document')
                    ? $request->file('driver_license_document')->store('driver_documents/license', 'public')
                    : null;
                $vehicleLicensePath = $request->hasFile('vehicle_license_document')
                    ? $request->file('vehicle_license_document')->store('driver_documents/vehicle_license', 'public')
                    : null;
                $bankPath = $request->hasFile('bank_statement_document')
                    ? $request->file('bank_statement_document')->store('driver_documents/bank', 'public')
                    : null;

                $user->update($this->onlyExistingColumns('users', [
                    'id_document_path' => $idPath,
                    'driver_license_path' => $driverLicensePath,
                    'bank_statement_path' => $bankPath,
                    'id_verification_status' => 'pending_review',
                    'id_verification_provider' => 'manual',
                    'id_verified_at' => null,
                ]));

                if (Schema::hasTable('driver_documents')) {
                    DriverDocument::query()->where('user_id', $user->id)->whereIn('document_type', ['sa_id', 'driver_license', 'vehicle_license'])->delete();
                    if ($idPath) {
                        DriverDocument::create([
                            'user_id' => $user->id,
                            'document_type' => 'sa_id',
                            'document_path' => $idPath,
                            'document_name' => basename($idPath),
                            'document_number' => $validated['id_number'],
                            'verified' => false,
                        ]);
                    }
                    if ($driverLicensePath) {
                        DriverDocument::create([
                            'user_id' => $user->id,
                            'document_type' => 'driver_license',
                            'document_path' => $driverLicensePath,
                            'document_name' => basename($driverLicensePath),
                            'verified' => false,
                        ]);
                    }
                    if ($vehicleLicensePath) {
                        DriverDocument::create([
                            'user_id' => $user->id,
                            'document_type' => 'vehicle_license',
                            'document_path' => $vehicleLicensePath,
                            'document_name' => basename($vehicleLicensePath),
                            'verified' => false,
                        ]);
                    }
                }
            } else {
                $ckPath = $request->hasFile('ck_document')
                    ? $request->file('ck_document')->store('merchant_documents/ck', 'public')
                    : null;
                $bbbeePath = $request->hasFile('bbbee_document')
                    ? $request->file('bbbee_document')->store('merchant_documents/bbbee', 'public')
                    : null;

                if (Schema::hasTable('driver_documents')) {
                    DriverDocument::query()->where('user_id', $user->id)->whereIn('document_type', ['merchant_ck', 'merchant_bbbee'])->delete();
                    if ($ckPath) {
                        DriverDocument::create([
                            'user_id' => $user->id,
                            'document_type' => 'merchant_ck',
                            'document_path' => $ckPath,
                            'document_name' => basename($ckPath),
                            'verified' => false,
                        ]);
                    }
                    if ($bbbeePath) {
                        DriverDocument::create([
                            'user_id' => $user->id,
                            'document_type' => 'merchant_bbbee',
                            'document_path' => $bbbeePath,
                            'document_name' => basename($bbbeePath),
                            'verified' => false,
                        ]);
                    }
                }

                if (Schema::hasTable('fuel_stations')) {
                    $franchise = Schema::hasTable('merchant_franchises')
                        ? MerchantFranchise::query()->find((int) ($validated['franchise_id'] ?? 0))
                        : null;
                    $company = (string) ($franchise?->name ?: 'Independent');
                    $station = FuelStation::query()->where('owner_id', $user->id)->first();

                    $stationPayload = $this->onlyExistingColumns('fuel_stations', [
                        'name' => trim($user->name . ' Station'),
                        'company' => $company,
                        'address' => (string) $validated['business_address'],
                        'city' => (string) $validated['city'],
                        'country' => $country,
                        'latitude' => $validated['latitude'] ?? null,
                        'longitude' => $validated['longitude'] ?? null,
                        'contact_person' => $user->name,
                        'contact_phone' => $user->phone,
                        'contact_email' => $user->email,
                        'owner_id' => $user->id,
                        'status' => 'inactive',
                        'wallet_balance' => 0,
                        'total_settlements' => 0,
                    ]);

                    if ($station) {
                        $station->update($stationPayload);
                    } else {
                        if ($this->tableHasColumn('fuel_stations', 'license_number')) {
                            $stationPayload['license_number'] = 'LIC-' . strtoupper(Str::random(10));
                        }
                        FuelStation::create($stationPayload);
                    }
                }
            }

            if (Schema::hasTable('account_approvals')) {
                AccountApproval::create($this->onlyExistingColumns('account_approvals', [
                    'user_id' => $user->id,
                    'role' => $role,
                    'merchant_franchise_id' => $role === 'merchant'
                        ? (isset($validated['franchise_id']) ? (int) $validated['franchise_id'] : null)
                        : null,
                    'business_address' => $role === 'merchant' ? ($validated['business_address'] ?? null) : null,
                    'city' => $validated['city'] ?? null,
                    'country' => $country,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'metadata' => [
                        'registered_via' => 'web_register_google_' . $role,
                        'ip' => request()->ip(),
                        'business_address' => $role === 'merchant' ? ($validated['business_address'] ?? null) : null,
                        'home_address' => $role === 'driver' ? ($validated['home_address'] ?? null) : null,
                        'city' => $validated['city'] ?? null,
                        'country' => $country,
                        'latitude' => $validated['latitude'] ?? null,
                        'longitude' => $validated['longitude'] ?? null,
                        'driver_platform' => $role === 'driver' ? ($validated['driver_platform'] ?? null) : null,
                        'driver_platform_other' => $role === 'driver'
                            ? (($validated['driver_platform'] ?? null) === 'other' ? ($validated['driver_platform_other'] ?? null) : null)
                            : null,
                    ],
                ]));
            }

            return $user;
        });

        $request->session()->forget('google_oauth_pending');
        event(new UserRegistered($user, 'web_register_google_' . $role, $request->ip()));

        return redirect()->route('login')->with('status', ucfirst($role) . ' account submitted for admin approval.');
    }

    private function canDirectLogin(User $user): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'employee'])) {
            return true;
        }

        if ($user->hasRole('merchant')) {
            return true;
        }

        return $user->hasRole('driver')
            && !empty($user->id_number);
    }

    private function redirectAfterLogin(User $user): RedirectResponse
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('employee')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('merchant')) {
            return redirect()->route('merchant.dashboard');
        }
        return redirect()->route('driver.dashboard');
    }

    private function normalizeSouthAfricanPhone(string $phone): string
    {
        $clean = trim($phone);
        $clean = preg_replace('/[^\d+]/', '', $clean) ?? $clean;
        if (str_starts_with($clean, '+27')) {
            return $clean;
        }
        if (str_starts_with($clean, '27')) {
            return '+' . $clean;
        }
        if (str_starts_with($clean, '0')) {
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
        // Normalize whitespace then explode by a single space (per request).
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $parts = explode(' ', $name);
        $first = trim((string) ($parts[0] ?? ''));
        $last = count($parts) > 1 ? trim(implode(' ', array_slice($parts, 1))) : '';
        return [$first, $last];
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::hasColumn($table, $column);
        }
        return $this->columnCache[$key];
    }

    private function onlyExistingColumns(string $table, array $values): array
    {
        $filtered = [];
        foreach ($values as $column => $value) {
            if ($this->tableHasColumn($table, (string) $column)) {
                $filtered[$column] = $value;
            }
        }
        return $filtered;
    }
}
