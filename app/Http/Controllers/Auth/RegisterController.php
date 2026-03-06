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
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
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
            'name' => ['required', 'string', 'max:255'],
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
                'id_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'driver_license_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'vehicle_license_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'bank_statement_document' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:8192'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'franchise_id' => ['required', Rule::exists('merchant_franchises', 'id')->where('is_active', true)],
                'business_address' => ['required', 'string', 'max:500'],
                'city' => ['required', 'string', 'max:120'],
                'country' => ['required', 'string', 'max:120'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'ck_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
                'bbbee_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            ]);
        }

        $validated = $request->validate($rules);

        $user = DB::transaction(function () use ($request, $validated, $role) {
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'flagged',
                'credit_score' => 500,
                'id_number' => $role === 'driver' ? $validated['id_number'] : null,
                'merchant_franchise_id' => $role === 'merchant' ? (int) $validated['franchise_id'] : null,
                'home_address' => $role === 'driver' ? ($validated['home_address'] ?? null) : null,
                'city' => $role === 'driver' ? ($validated['city'] ?? null) : null,
                'country' => $role === 'driver' ? ($validated['country'] ?? null) : null,
                'latitude' => $role === 'driver' ? ($validated['latitude'] ?? null) : null,
                'longitude' => $role === 'driver' ? ($validated['longitude'] ?? null) : null,
                'driver_platform' => $role === 'driver' ? ($validated['driver_platform'] ?? null) : null,
                'driver_platform_other' => $role === 'driver'
                    ? (($validated['driver_platform'] ?? null) === 'other' ? ($validated['driver_platform_other'] ?? null) : null)
                    : null,
            ]);

            $roleModel = Role::query()->where('name', $role)->first();
            if ($roleModel) {
                $user->assignRole($roleModel);
            }

            $user->wallet()->firstOrCreate([], [
                'balance' => 0,
                'outstanding_balance' => 0,
                'currency' => 'ZAR',
            ]);

            if ($role === 'driver') {
                $user->creditLimit()->firstOrCreate(
                    [],
                    [
                        'limit' => 3000,
                        'used' => 0,
                        'status' => 'active',
                        'review_date' => now()->addDays(90)->toDateString(),
                    ]
                );

                $idPath = $request->file('id_document')->store('driver_documents/id', 'public');
                $driverLicensePath = $request->file('driver_license_document')->store('driver_documents/license', 'public');
                $vehicleLicensePath = $request->file('vehicle_license_document')->store('driver_documents/vehicle_license', 'public');
                $bankPath = $request->hasFile('bank_statement_document')
                    ? $request->file('bank_statement_document')->store('driver_documents/bank', 'public')
                    : null;

                $user->update([
                    'id_document_path' => $idPath,
                    'driver_license_path' => $driverLicensePath,
                    'bank_statement_path' => $bankPath,
                    'id_verification_status' => 'pending_review',
                    'id_verification_provider' => 'manual',
                    'id_verified_at' => null,
                ]);

                DriverDocument::create([
                    'user_id' => $user->id,
                    'document_type' => 'sa_id',
                    'document_path' => $idPath,
                    'document_name' => basename($idPath),
                    'document_number' => $validated['id_number'],
                    'verified' => false,
                ]);

                DriverDocument::create([
                    'user_id' => $user->id,
                    'document_type' => 'driver_license',
                    'document_path' => $driverLicensePath,
                    'document_name' => basename($driverLicensePath),
                    'verified' => false,
                ]);

                DriverDocument::create([
                    'user_id' => $user->id,
                    'document_type' => 'vehicle_license',
                    'document_path' => $vehicleLicensePath,
                    'document_name' => basename($vehicleLicensePath),
                    'verified' => false,
                ]);
            } else {
                $ckPath = $request->file('ck_document')->store('merchant_documents/ck', 'public');
                $bbbeePath = $request->file('bbbee_document')->store('merchant_documents/bbbee', 'public');

                DriverDocument::create([
                    'user_id' => $user->id,
                    'document_type' => 'merchant_ck',
                    'document_path' => $ckPath,
                    'document_name' => basename($ckPath),
                    'verified' => false,
                ]);

                DriverDocument::create([
                    'user_id' => $user->id,
                    'document_type' => 'merchant_bbbee',
                    'document_path' => $bbbeePath,
                    'document_name' => basename($bbbeePath),
                    'verified' => false,
                ]);

                $franchise = MerchantFranchise::query()->find((int) $validated['franchise_id']);
                $brandName = (string) ($franchise ? $franchise->name : 'Independent');
                $address = (string) ($validated['business_address'] ?? '');
                $city = (string) ($validated['city'] ?? '');
                $country = (string) ($validated['country'] ?? 'South Africa');

                $existingStation = FuelStation::query()
                    ->where('owner_id', $user->id)
                    ->first();

                $stationPayload = [
                    'name' => trim($user->name . ' Station'),
                    'company' => $brandName !== '' ? $brandName : 'Independent',
                    'address' => $address !== '' ? $address : 'Pending address',
                    'city' => $city !== '' ? $city : 'Pending city',
                    'country' => $country !== '' ? $country : 'South Africa',
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'contact_person' => $user->name,
                    'contact_phone' => $user->phone,
                    'contact_email' => $user->email,
                    'owner_id' => $user->id,
                    'status' => 'inactive',
                    'wallet_balance' => 0,
                    'total_settlements' => 0,
                ];

                if ($existingStation) {
                    $existingStation->update($stationPayload);
                } else {
                    $stationPayload['license_number'] = 'LIC-' . strtoupper(Str::random(10));
                    FuelStation::create($stationPayload);
                }
            }

            AccountApproval::create([
                'user_id' => $user->id,
                'role' => $role,
                'merchant_franchise_id' => $role === 'merchant' ? (int) $validated['franchise_id'] : null,
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
            ]);

            return $user;
        });

        event(new UserRegistered($user, 'web_register_' . $role, $request->ip()));

        return redirect()
            ->route('login')
            ->with('status', ucfirst($role) . ' account created and submitted for admin approval.');
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
}
