<?php

namespace App\Http\Controllers\Merchant;

use App\Events\VoucherStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\MerchantFranchise;
use App\Models\Settlement;
use App\Services\FuelPriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class DashboardController extends Controller
{
    public static function developerAbilities(): array
    {
        return [
            'stations.read',
            'vouchers.read',
            'vouchers.redeem',
            'repayments.read',
            'settlements.read',
            'sandbox.access',
        ];
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $station = $this->resolveMerchantStation($user, $request);

        if (!$station) {
            return view('merchant.dashboard', [
                'station' => null,
                'summary' => [
                    'issued' => 0,
                    'approved' => 0,
                    'redeemed' => 0,
                    'today_redeemed' => 0,
                    'issued_amount' => 0,
                    'approved_amount' => 0,
                    'redeemed_amount' => 0,
                ],
                'financial' => [
                    'total_voucher_value' => 0,
                    'pending_settlement_amount' => 0,
                    'completed_settlement_amount' => 0,
                ],
                'latestVouchers' => collect(),
                'approvedVouchers' => collect(),
                'initialVouchers' => [],
                'wsConfig' => $this->websocketConfig(),
                'stationPrices' => [],
                'branding' => $this->merchantHeaderBranding(),
            ]);
        }

        $baseQuery = FuelVoucher::where('fuel_station_id', $station->id);

        $summary = [
            'issued' => (clone $baseQuery)->where('status', 'issued')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'redeemed' => (clone $baseQuery)->where('status', 'redeemed')->count(),
            'today_redeemed' => (clone $baseQuery)
                ->where('status', 'redeemed')
                ->whereDate('redeemed_at', now()->toDateString())
                ->count(),
            'issued_amount' => (float) ((clone $baseQuery)->where('status', 'issued')->sum('amount') ?: 0),
            'approved_amount' => (float) ((clone $baseQuery)->where('status', 'approved')->sum('amount') ?: 0),
            'redeemed_amount' => (float) ((clone $baseQuery)->where('status', 'redeemed')->sum('amount') ?: 0),
        ];

        $financial = [
            'total_voucher_value' => (float) ((clone $baseQuery)->sum('amount') ?: 0),
            'pending_settlement_amount' => (float) $station->getPendingSettlementAmount(),
            'completed_settlement_amount' => (float) (Settlement::where('fuel_station_id', $station->id)
                ->where('status', 'completed')
                ->sum('amount') ?: 0),
        ];

        $latestVouchers = FuelVoucher::with(['user:id,name,phone'])
            ->where('fuel_station_id', $station->id)
            ->latest()
            ->limit(4)
            ->get();

        $approvedVouchers = FuelVoucher::with(['user:id,name,phone'])
            ->where('fuel_station_id', $station->id)
            ->where('status', 'approved')
            ->latest()
            ->limit(3)
            ->get();

        $initialVouchers = FuelVoucher::with(['user:id,name,phone'])
            ->where('fuel_station_id', $station->id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (FuelVoucher $voucher) => $this->voucherPayload($voucher))
            ->values();

        $fuelPriceService = app(FuelPriceService::class);
        $stationPrices = $fuelPriceService->resolveStationPrices((int) $station->id, true);

        return view('merchant.dashboard', [
            'station' => $station,
            'summary' => $summary,
            'financial' => $financial,
            'latestVouchers' => $latestVouchers,
            'approvedVouchers' => $approvedVouchers,
            'initialVouchers' => $initialVouchers,
            'wsConfig' => $this->websocketConfig(),
            'stationPrices' => $stationPrices,
            'branding' => $this->merchantHeaderBranding($station),
        ]);
    }

    public function settings(Request $request, FuelPriceService $fuelPriceService)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $station = $this->resolveMerchantStation($user, $request);
        abort_unless($station, 404, 'No station linked to this account.');

        $stationPrices = $fuelPriceService->resolveStationPrices((int) $station->id, true);
        $franchiseBrands = collect($this->merchantBrandCatalog())
            ->map(function ($name, $slug) {
                return [
                    'slug' => $slug,
                    'name' => $name,
                    'logo_url' => is_file(public_path('images/brands/' . $slug . '.png'))
                        ? asset('images/brands/' . $slug . '.png')
                        : null,
                ];
            })
            ->values();

        return view('merchant.settings', [
            'station' => $station,
            'stationPrices' => $stationPrices,
            'franchiseBrands' => $franchiseBrands,
        ]);
    }

    public function updateStationSettings(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $station = $this->resolveMerchantStation($user, $request);
        abort_unless($station, 404, 'No station linked to this account.');

        $validated = $request->validate([
            'company' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:120',
            'country' => 'nullable|string|max:120',
            'payout_method' => 'nullable|string|in:bank_transfer,paystack_transfer,paystack_direct_deposit',
            'payout_bank_name' => 'nullable|string|max:255',
            'payout_bank_code' => 'nullable|string|max:50',
            'payout_account_name' => 'nullable|string|max:255',
            'payout_account_number' => 'nullable|string|max:50',
            'payout_branch_code' => 'nullable|string|max:50',
            'payout_reference' => 'nullable|string|max:255',
            'payout_email' => 'nullable|email|max:255',
        ]);

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = trim($value);
            }
        }

        $station->update($validated);

        return redirect()
            ->route('merchant.settings', $user->hasAnyRole(['super_admin', 'admin']) ? ['station_id' => $station->id] : [])
            ->with('success', 'Merchant settings saved successfully.');
    }

    public function updateFuelPrices(Request $request, FuelPriceService $fuelPriceService)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $station = $this->resolveMerchantStation($user, $request);
        abort_unless($station, 404, 'No station linked to this account.');

        $validated = $request->validate([
            'fuel_type' => ['nullable', Rule::in($fuelPriceService->supportedFuelTypes())],
            'rand' => 'nullable|integer|min:10|max:99',
            'cents' => 'nullable|integer|min:0|max:99',
            'effective_at' => 'nullable|date',
            'prices' => 'nullable|array',
            'prices.petrol' => 'nullable|numeric|min:0|max:999.99',
            'prices.diesel' => 'nullable|numeric|min:0|max:999.99',
            'prices.super' => 'nullable|numeric|min:0|max:999.99',
        ]);

        $saved = 0;
        $effectiveAt = !empty($validated['effective_at']) ? $validated['effective_at'] : null;

        if (!empty($validated['fuel_type']) && isset($validated['rand']) && isset($validated['cents'])) {
            $rand = (int) $validated['rand'];
            $cents = (int) $validated['cents'];
            $price = (float) ($rand + ($cents / 100));

            if ($price > 0) {
                $fuelPriceService->setMerchantCustomPrice(
                    (int) $station->id,
                    (string) $validated['fuel_type'],
                    $price,
                    (int) $user->id,
                    $effectiveAt
                );
                $saved++;
            }
        } else {
            foreach ($fuelPriceService->supportedFuelTypes() as $fuelType) {
                $raw = data_get($validated, 'prices.' . $fuelType);
                if ($raw === null || $raw === '') {
                    continue;
                }
                $price = (float) $raw;
                if ($price <= 0) {
                    continue;
                }

                $fuelPriceService->setMerchantCustomPrice((int) $station->id, $fuelType, $price, (int) $user->id, $effectiveAt);
                $saved++;
            }
        }

        if ($saved === 0) {
            return redirect()
                ->route('merchant.settings', $user->hasAnyRole(['super_admin', 'admin']) ? ['station_id' => $station->id] : [])
                ->with('error', 'No valid fuel prices were provided.');
        }

        return redirect()
            ->route('merchant.settings', $user->hasAnyRole(['super_admin', 'admin']) ? ['station_id' => $station->id] : [])
            ->with('success', 'Fuel prices updated successfully.');
    }

    public function vouchers(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $station = $this->resolveMerchantStation($user, $request);

        $allowedStatuses = ['issued', 'approved', 'redeemed', 'expired', 'cancelled'];
        $validated = $request->validate([
            'status' => ['nullable', Rule::in($allowedStatuses)],
            'search' => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = FuelVoucher::query()
            ->with(['user:id,name,phone', 'fuelStation:id,name,city'])
            ->when($station, fn ($q) => $q->where('fuel_station_id', $station->id))
            ->when(!$station, fn ($q) => $q->whereRaw('1 = 0'))
            ->when(!empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->when(!empty($validated['from']), fn ($q) => $q->whereDate('issued_at', '>=', $validated['from']))
            ->when(!empty($validated['to']), fn ($q) => $q->whereDate('issued_at', '<=', $validated['to']))
            ->when(!empty($validated['search']), function ($q) use ($validated) {
                $search = trim($validated['search']);
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('qr_code', 'like', "%{$search}%")
                        ->orWhere('transaction_reference', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            });

        $vouchers = (clone $query)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $latestFour = $station
            ? FuelVoucher::with(['user:id,name,phone'])
                ->where('fuel_station_id', $station->id)
                ->latest()
                ->limit(4)
                ->get()
            : collect();

        $totals = [
            'count' => (clone $query)->count(),
            'value' => (float) ((clone $query)->sum('amount') ?: 0),
            'redeemed_value' => (float) ((clone $query)->where('status', 'redeemed')->sum('amount') ?: 0),
            'approved_value' => (float) ((clone $query)->where('status', 'approved')->sum('amount') ?: 0),
        ];

        return view('merchant.vouchers.index', [
            'station' => $station,
            'vouchers' => $vouchers,
            'latestFour' => $latestFour,
            'totals' => $totals,
            'filters' => [
                'status' => $validated['status'] ?? '',
                'search' => $validated['search'] ?? '',
                'from' => $validated['from'] ?? '',
                'to' => $validated['to'] ?? '',
            ],
            'statuses' => $allowedStatuses,
        ]);
    }

    public function developerCredentials(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $tokens = $user->tokens()
            ->where('name', 'like', 'merchant-dev:%')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('merchant.developer.credentials', [
            'tokens' => $tokens,
            'abilities' => self::developerAbilities(),
            'defaultExpiryDays' => 90,
            'maxExpiryDays' => 365,
            'newToken' => session('newToken'),
        ]);
    }

    public function storeDeveloperToken(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $abilities = self::developerAbilities();

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:80',
            'abilities' => 'required|array|min:1',
            'abilities.*' => ['string', Rule::in($abilities)],
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $name = 'merchant-dev:' . Str::slug($validated['name']) . ':' . now()->format('YmdHis');
        $expiresAt = now()->addDays((int) ($validated['expires_in_days'] ?? 90));

        $token = $user->createToken($name, $validated['abilities'], $expiresAt);

        return redirect()->route('merchant.developer.credentials')
            ->with('success', 'Developer credential created successfully.')
            ->with('newToken', $token->plainTextToken);
    }

    public function revokeDeveloperToken(Request $request, PersonalAccessToken $token)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        abort_unless(
            $token->tokenable_id === $user->id && $token->tokenable_type === $user->getMorphClass(),
            403,
            'You cannot revoke this credential.'
        );

        $token->delete();

        return redirect()->route('merchant.developer.credentials')
            ->with('success', 'Credential revoked successfully.');
    }

    public function developerDocs(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        return view('merchant.developer.docs', [
            'baseUrl' => url('/'),
            'abilities' => self::developerAbilities(),
        ]);
    }

    public function developerSandbox(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        return view('merchant.developer.sandbox', [
            'baseUrl' => url('/'),
        ]);
    }

    public function stream(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $station = $this->resolveMerchantStation($user, $request);
        abort_unless($station, 404, 'No station linked to this account.');

        $vouchers = FuelVoucher::with(['user:id,name,phone'])
            ->where('fuel_station_id', $station->id)
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (FuelVoucher $voucher) => $this->voucherPayload($voucher))
            ->values();

        return response()->json([
            'success' => true,
            'station_id' => $station->id,
            'items' => $vouchers,
            'summary' => [
                'issued' => FuelVoucher::where('fuel_station_id', $station->id)->where('status', 'issued')->count(),
                'approved' => FuelVoucher::where('fuel_station_id', $station->id)->where('status', 'approved')->count(),
                'redeemed' => FuelVoucher::where('fuel_station_id', $station->id)->where('status', 'redeemed')->count(),
            ],
        ]);
    }

    public function redeem(Request $request)
    {
        $user = Auth::user();
        $this->authorizeMerchantPortal($user);

        $station = $this->resolveMerchantStation($user, $request);
        abort_unless($station, 404, 'No station linked to this account.');

        $validated = $request->validate([
            'scan_input' => 'required|string|max:500',
            'pump_number' => 'nullable|string|max:50',
            'transaction_reference' => 'nullable|string|max:255',
        ]);

        $voucher = $this->resolveVoucherForStation($station->id, $validated['scan_input']);
        if (!$voucher) {
            return back()->with('error', 'Voucher not found for this station.');
        }

        $isLegacyVirtualCardVoucher = ($voucher->status === 'issued') && $voucher->isVirtualCardConvertedVoucher();
        if ($voucher->status !== 'approved' && !$isLegacyVirtualCardVoucher) {
            return back()->with('error', "Voucher must be in APPROVED state before redemption. Current: {$voucher->status}");
        }

        if ($voucher->expires_at && now()->gt($voucher->expires_at)) {
            $voucher->update(['status' => 'expired']);
            return back()->with('error', 'Voucher expired and cannot be redeemed.');
        }

        try {
            DB::transaction(function () use (&$voucher, $station, $validated) {
                $lockedStation = FuelStation::whereKey($station->id)->lockForUpdate()->firstOrFail();
                $lockedVoucher = FuelVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();

                $isLegacyVirtualCardVoucher = ($lockedVoucher->status === 'issued') && $lockedVoucher->isVirtualCardConvertedVoucher();
                if ($lockedVoucher->status !== 'approved' && !$isLegacyVirtualCardVoucher) {
                    throw new \Exception("Voucher must be APPROVED before redemption. Current: {$lockedVoucher->status}");
                }

                if ($lockedVoucher->expires_at && now()->gte($lockedVoucher->expires_at)) {
                    $lockedVoucher->update(['status' => 'expired']);
                    throw new \Exception('Voucher expired and cannot be redeemed.');
                }

                $lockedStation->deductFromWallet(
                    (float) $lockedVoucher->amount,
                    'Voucher redemption: ' . $lockedVoucher->code
                );

                $lockedVoucher->update([
                    'status' => 'redeemed',
                    'redeemed_at' => now(),
                    'pump_number' => $validated['pump_number'] ?: $lockedVoucher->pump_number,
                    'transaction_reference' => $validated['transaction_reference'] ?: $lockedVoucher->transaction_reference,
                ]);

                $voucher = $lockedVoucher;
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Redemption failed: ' . $e->getMessage());
        }

        if ($voucher->lease) {
            $voucher->lease->ensureRepaymentSchedule(now());
        }

        $payload = $this->voucherPayload($voucher->fresh(['user:id,name,phone', 'fuelStation:id,name,city']));
        $payload['event'] = 'redeemed';
        $this->broadcastVoucherPayload($payload);

        return back()->with('success', "Voucher {$voucher->code} redeemed successfully.");
    }

    protected function authorizeMerchantPortal($user): void
    {
        abort_unless($user && $user->hasAnyRole(['super_admin', 'admin', 'merchant']), 403);
    }

    private function merchantHeaderBranding(?FuelStation $station = null): array
    {
        $brandCatalog = $this->merchantBrandCatalog();

        $stationBrandName = trim((string) ($station?->company ?? ''));
        $stationName = trim((string) ($station?->name ?? ''));
        [$brandSlug, $brandName] = $this->resolveBrandFromStation($stationBrandName, $stationName, $brandCatalog);

        $brandLogoUrl = null;
        if ($brandSlug !== '') {
            $local = public_path('images/brands/' . $brandSlug . '.png');
            if (is_file($local)) {
                $brandLogoUrl = asset('images/brands/' . $brandSlug . '.png');
            }
        }

        return [
            'mode' => 'brand',
            'brand_name' => $brandName !== '' ? $brandName : ($stationBrandName !== '' ? $stationBrandName : $stationName),
            'brand_slug' => $brandSlug,
            'brand_logo_url' => $brandLogoUrl,
            'upload_logo_url' => null,
        ];
    }

    private function merchantBrandCatalog(): array
    {
        $fromTable = MerchantFranchise::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->mapWithKeys(fn (MerchantFranchise $item) => [$item->slug => $item->name])
            ->all();

        if (!empty($fromTable)) {
            return $fromTable;
        }

        return [
            'astron-energy' => 'Astron Energy',
            'bp-southern-africa' => 'BP Southern Africa',
            'central-energy-fund' => 'Central Energy Fund',
            'engen' => 'Engen',
            'eskom' => 'Eskom',
            'mulilo' => 'Mulilo',
            'petrosa' => 'PetroSA',
            'puma-energy' => 'Puma Energy',
            'sasol' => 'Sasol',
            'shell-sa' => 'Shell SA',
            'totalenergies' => 'TotalEnergies',
            'vivo-energy' => 'Vivo Energy',
        ];
    }

    private function resolveBrandFromFranchiseName(string $franchiseName, array $catalog): array
    {
        if ($franchiseName === '') {
            return ['', ''];
        }

        $slug = Str::slug($franchiseName);
        if ($slug !== '' && array_key_exists($slug, $catalog)) {
            return [$slug, (string) $catalog[$slug]];
        }

        $normalized = strtolower(preg_replace('/[^a-z0-9]+/', ' ', $franchiseName));
        $normalized = trim((string) $normalized);
        $aliases = [
            'shell' => 'shell-sa',
            'bp' => 'bp-southern-africa',
            'total' => 'totalenergies',
            'totalenergies' => 'totalenergies',
            'engen' => 'engen',
            'sasol' => 'sasol',
            'astron' => 'astron-energy',
            'petrosa' => 'petrosa',
            'petro sa' => 'petrosa',
            'puma' => 'puma-energy',
            'vivo' => 'vivo-energy',
            'eskom' => 'eskom',
            'cef' => 'central-energy-fund',
            'central energy fund' => 'central-energy-fund',
            'mulilo' => 'mulilo',
        ];

        foreach ($aliases as $needle => $aliasSlug) {
            if ($normalized !== '' && str_contains($normalized, $needle) && array_key_exists($aliasSlug, $catalog)) {
                return [$aliasSlug, (string) $catalog[$aliasSlug]];
            }
        }

        return ['', trim($franchiseName)];
    }

    private function resolveBrandFromStation(string $franchiseName, string $stationName, array $catalog): array
    {
        [$slug, $name] = $this->resolveBrandFromFranchiseName($franchiseName, $catalog);
        if ($slug !== '') {
            return [$slug, $name];
        }

        [$slug, $name] = $this->resolveBrandFromFranchiseName($stationName, $catalog);
        if ($slug !== '') {
            return [$slug, $name];
        }

        return ['', ''];
    }

    protected function resolveMerchantStation($user, Request $request): ?FuelStation
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            $stationId = $request->integer('station_id');
            if ($stationId > 0) {
                return FuelStation::find($stationId);
            }
            return FuelStation::whereNotNull('owner_id')->orderBy('name')->first();
        }

        return FuelStation::where('owner_id', $user->id)->orderBy('name')->first();
    }

    protected function resolveVoucherForStation(int $stationId, string $scanInput): ?FuelVoucher
    {
        $scanInput = trim($scanInput);
        $voucherId = null;
        $voucherCode = null;
        $voucherQr = null;

        if (Str::startsWith($scanInput, '{') && Str::endsWith($scanInput, '}')) {
            $decoded = json_decode($scanInput, true);
            if (is_array($decoded)) {
                $voucherId = isset($decoded['voucher_id']) ? (int) $decoded['voucher_id'] : null;
                $voucherCode = $decoded['code'] ?? null;
                $voucherQr = $decoded['qr_code'] ?? null;
            }
        } else {
            $voucherCode = $scanInput;
            $voucherQr = $scanInput;
            if (ctype_digit($scanInput)) {
                $voucherId = (int) $scanInput;
            }
        }

        return FuelVoucher::where('fuel_station_id', $stationId)
            ->where(function ($q) use ($voucherId, $voucherCode, $voucherQr) {
                if ($voucherId) {
                    $q->orWhere('id', $voucherId);
                }
                if ($voucherCode) {
                    $q->orWhere('code', $voucherCode);
                }
                if ($voucherQr) {
                    $q->orWhere('qr_code', $voucherQr);
                }
            })
            ->latest()
            ->first();
    }

    protected function voucherPayload(FuelVoucher $voucher): array
    {
        return [
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'qr_code' => $voucher->qr_code,
            'status' => $voucher->status,
            'amount' => (float) $voucher->amount,
            'fuel_type' => $voucher->fuel_type,
            'liters' => (float) $voucher->liters,
            'station_id' => $voucher->fuel_station_id,
            'driver' => [
                'id' => $voucher->user?->id,
                'name' => $voucher->user?->name,
                'phone' => $voucher->user?->phone,
            ],
            'issued_at' => optional($voucher->issued_at)->toIso8601String(),
            'expires_at' => optional($voucher->expires_at)->toIso8601String(),
            'redeemed_at' => optional($voucher->redeemed_at)->toIso8601String(),
            'pump_number' => $voucher->pump_number,
            'transaction_reference' => $voucher->transaction_reference,
        ];
    }

    protected function websocketConfig(): array
    {
        return [
            'appKey' => env('REVERB_APP_KEY') ?: env('PUSHER_APP_KEY'),
            'host' => env('REVERB_HOST', env('PUSHER_HOST', request()->getHost())),
            'port' => (int) env('REVERB_PORT', env('PUSHER_PORT', 8080)),
            'scheme' => env('REVERB_SCHEME', env('PUSHER_SCHEME', 'http')),
            'authEndpoint' => url('/broadcasting/auth'),
        ];
    }

    protected function broadcastVoucherPayload(array $payload): void
    {
        try {
            event(new VoucherStatusChanged($payload));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
