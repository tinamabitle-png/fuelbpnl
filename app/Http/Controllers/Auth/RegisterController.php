<?php

namespace App\Http\Controllers\Auth;

use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\AccountApproval;
use App\Models\DriverDocument;
use App\Models\FuelStation;
use App\Models\MerchantFranchise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /** @var array<string, bool> */
    private array $columnCache = [];

    public function showDriver()
    {
        return view('auth.driver.register');
    }

    public function showMerchant()
    {
        $franchises = collect();
        if (Schema::hasTable('merchant_franchises')) {
            $franchises = MerchantFranchise::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function (MerchantFranchise $franchise) {
                    $logoPath = public_path((string) $franchise->logo_path);
                    return [
                        'id' => $franchise->id,
                        'name' => $franchise->name,
                        'slug' => $franchise->slug,
                        'logo_url' => ($franchise->logo_path && is_file($logoPath))
                            ? asset((string) $franchise->logo_path)
                            : null,
                    ];
                });
        }

        return view('auth.merchant.register', compact('franchises'));
    }

    public function storeDriver(Request $request)
    {
        return $this->storeByRole($request, 'driver');
    }

    public function storeMerchant(Request $request)
    {
        return $this->storeByRole($request, 'merchant');
    }

    private function storeByRole(Request $request, string $role): \Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array($role, ['driver', 'merchant'], true), 404);

        $request->merge([
            'phone' => $this->normalizeSouthAfricanPhone((string) $request->input('phone')),
        ]);

        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required_without:name', 'string', 'max:120'],
            'last_name' => ['required_without:name', 'string', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'phone' => ['required', 'regex:/^\+27[6-8][0-9]{8}$/', Rule::unique('users', 'phone')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($role === 'driver') {
            $rules = array_merge($rules, [
                'id_number' => ['required', 'digits:13', Rule::unique('users', 'id_number')],
                'home_address' => ['required', 'string', 'max:500'],
                'city' => ['required', 'string', 'max:120'],
                'country' => ['required', 'string', 'max:120'],
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
                'country' => ['required', 'string', 'max:120'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'station_latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'station_longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'ck_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'bbbee_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            ]);
        }

        $validated = $request->validate($rules);

        $user = DB::transaction(function () use ($request, $validated, $role) {
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

            $gender = strtolower(trim((string) ($validated['gender'] ?? ''))) ?: 'male';
            if (!in_array($gender, ['male', 'female', 'other'], true)) {
                $gender = 'male';
            }

            $dob = null;
            if ($role === 'driver') {
                $derivedDob = $this->parseSouthAfricanIdDob((string) ($validated['id_number'] ?? ''));
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
            } else {
                $dob = trim((string) ($validated['date_of_birth'] ?? '')) ?: null;
                if ($dob === null) {
                    $dob = trim((string) config('services.flutterwave.virtual_cards_date_of_birth', '')) ?: null;
                }
            }

            $userPayload = [
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dob,
                'gender' => $gender,
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'flagged',
                'credit_score' => 500,
                'id_number' => $role === 'driver' ? $validated['id_number'] : null,
                'merchant_franchise_id' => $role === 'merchant'
                    ? (isset($validated['franchise_id']) ? (int) $validated['franchise_id'] : null)
                    : null,
                'home_address' => $role === 'driver' ? ($validated['home_address'] ?? null) : null,
                'city' => $role === 'driver' ? ($validated['city'] ?? null) : null,
                'country' => $role === 'driver' ? ($validated['country'] ?? null) : null,
                'latitude' => $role === 'driver' ? ($validated['latitude'] ?? null) : null,
                'longitude' => $role === 'driver' ? ($validated['longitude'] ?? null) : null,
                'driver_platform' => $role === 'driver' ? ($validated['driver_platform'] ?? null) : null,
                'driver_platform_other' => $role === 'driver'
                    ? (($validated['driver_platform'] ?? null) === 'other' ? ($validated['driver_platform_other'] ?? null) : null)
                    : null,
            ];

            $user = User::create($this->onlyExistingColumns('users', $userPayload));

            $roleModel = Role::query()->where('name', $role)->first();
            if ($roleModel) {
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
                    $user->creditLimit()->firstOrCreate(
                        [],
                        [
                            'limit' => 3000,
                            'used' => 0,
                            'status' => 'active',
                            'review_date' => now()->addDays(90)->toDateString(),
                        ]
                    );
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

                $franchise = Schema::hasTable('merchant_franchises')
                    ? MerchantFranchise::query()->find((int) ($validated['franchise_id'] ?? 0))
                    : null;
                $brandName = (string) ($franchise ? $franchise->name : 'Independent');
                $address = (string) ($validated['business_address'] ?? '');
                $city = (string) ($validated['city'] ?? '');
                $country = (string) ($validated['country'] ?? 'South Africa');
                $stationLatitude = $validated['station_latitude'] ?? ($validated['latitude'] ?? null);
                $stationLongitude = $validated['station_longitude'] ?? ($validated['longitude'] ?? null);

                if (Schema::hasTable('fuel_stations')) {
                    $existingStation = FuelStation::query()
                        ->where('owner_id', $user->id)
                        ->first();

                    $stationPayload = $this->onlyExistingColumns('fuel_stations', [
                        'name' => trim($user->name . ' Station'),
                        'company' => $brandName !== '' ? $brandName : 'Independent',
                        'address' => $address !== '' ? $address : 'Pending address',
                        'city' => $city !== '' ? $city : 'Pending city',
                        'country' => $country !== '' ? $country : 'South Africa',
                        'latitude' => $stationLatitude,
                        'longitude' => $stationLongitude,
                        'contact_person' => $user->name,
                        'contact_phone' => $user->phone,
                        'contact_email' => $user->email,
                        'owner_id' => $user->id,
                        'status' => 'inactive',
                        'wallet_balance' => 0,
                        'total_settlements' => 0,
                    ]);

                    if ($existingStation) {
                        $existingStation->update($stationPayload);
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
                    'country' => $validated['country'] ?? null,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'metadata' => [
                        'registered_via' => 'web_register_' . $role,
                        'ip' => $request->ip(),
                        'business_address' => $role === 'merchant' ? ($validated['business_address'] ?? null) : null,
                        'home_address' => $role === 'driver' ? ($validated['home_address'] ?? null) : null,
                        'city' => $validated['city'] ?? null,
                        'country' => $validated['country'] ?? null,
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

        event(new UserRegistered($user, 'web_register_' . $role, $request->ip()));

        $redirect = redirect()
            ->route('login')
            ->with('status', ucfirst($role) . ' account created and submitted for admin approval.');

        if ($role === 'driver') {
            $redirect->with('driver_registered_popup', [
                'name' => $this->displayDriverName($user->name ?: 'New driver'),
                'message' => 'You are now driving wiser. Your account has been created and submitted for admin approval.',
            ]);
        }

        return $redirect;
    }

    private function normalizeSouthAfricanPhone(string $phone): string
    {
        $clean = trim($phone);
        $clean = preg_replace('/[^\d+]/', '', $clean) ?? $clean;

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

    private function displayDriverName(string $name): string
    {
        $name = trim($name);
        $normalized = strtolower($name);
        $placeholderNames = [
            '',
            'john doe',
            'jane smith',
            'bwiser driver',
            'new driver',
        ];

        if (!in_array($normalized, $placeholderNames, true)) {
            return $name;
        }

        $fallbackNames = [
            'Aphiwe Dlamini',
            'Naledi Mokoena',
            'Thabo Maseko',
            'Lerato Nkosi',
            'Sibusiso Khumalo',
            'Ayanda Mthembu',
        ];

        $hashSeed = $normalized !== '' ? $normalized : 'driver';
        return $fallbackNames[abs(crc32($hashSeed)) % count($fallbackNames)];
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

    private function parseSouthAfricanIdDob(string $idNumber): ?string
    {
        $idNumber = preg_replace('/\D+/', '', $idNumber) ?: '';
        if (!preg_match('/^\d{13}$/', $idNumber)) {
            return null;
        }

        $yy = (int) substr($idNumber, 0, 2);
        $mm = (int) substr($idNumber, 2, 2);
        $dd = (int) substr($idNumber, 4, 2);

        $nowYY = (int) now()->format('y');
        $century = $yy <= $nowYY ? 2000 : 1900;
        $yyyy = $century + $yy;

        try {
            $dob = Carbon::createFromDate($yyyy, $mm, $dd);
        } catch (\Throwable $e) {
            return null;
        }

        if ($dob->isFuture()) {
            return null;
        }

        return $dob->toDateString();
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($_value, $column) => $this->tableHasColumn($table, (string) $column))
            ->all();
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $exists = Schema::hasTable($table) && Schema::hasColumn($table, $column);
        $this->columnCache[$key] = $exists;

        return $exists;
    }
}
