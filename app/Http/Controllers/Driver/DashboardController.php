<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Events\VoucherStatusChanged;
use App\Mail\RepaymentAutopayNotificationMail;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\Repayment;
use App\Models\AuditLog;
use App\Models\VirtualCard;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use App\Services\AuditTrailService;
use App\Services\DriverUnderwritingService;
use App\Services\FuelPriceService;
use App\Services\PaystackService;
use App\Services\RepaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    private function minRepaymentAmount(): float
    {
        return (float) config('credit.min_repayment_amount', 50);
    }

    public function __construct(
        private FuelPriceService $fuelPriceService,
        private PaystackService $paystackService,
        private RepaymentSettlementService $repaymentSettlementService,
        private DriverUnderwritingService $driverUnderwritingService
    )
    {
    }

    public function index()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        $user->loadMissing('driverDocuments');

        $mostOverdue = null;
        $overdueSeconds = 0;

        $virtualCards = $user->virtualCards()
            ->open()
            ->latest()
            ->limit(3)
            ->get();

        $virtualCardsAllocatedTotal = (float) VirtualCard::query()
            ->where('user_id', $user->id)
            ->open()
            ->sum('allocated_amount');

        $retailBrands = collect((array) config('retail_brands', []))
            ->map(fn ($row) => (array) $row)
            ->filter(fn ($row) => !empty($row['slug']) && !empty($row['name']))
            ->values();

        $brandNameToSlug = $retailBrands
            ->mapWithKeys(fn ($row) => [strtolower(trim((string) $row['name'])) => (string) $row['slug']]);

        $stationsByBrandSlug = Cache::remember('driver:stations_by_brand_slug:v1', now()->addMinutes(5), function () use ($brandNameToSlug) {
            $stations = FuelStation::query()
                ->where('status', 'active')
                ->orderBy('company')
                ->orderBy('name')
                ->get(['id', 'name', 'company', 'city']);

            return $stations
                ->map(function ($station) use ($brandNameToSlug) {
                    $normalizedName = $this->normalizePopularBrand((string) ($station->company ?? ''));
                    $slug = $normalizedName !== ''
                        ? (string) ($brandNameToSlug[strtolower($normalizedName)] ?? '')
                        : '';

                    return [
                        'id' => (int) $station->id,
                        'name' => (string) ($station->name ?? ''),
                        'city' => (string) ($station->city ?? ''),
                        'brand_slug' => $slug,
                    ];
                })
                ->filter(fn ($row) => !empty($row['brand_slug']))
                ->groupBy('brand_slug')
                ->map(fn (Collection $rows) => $rows->map(fn ($row) => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'city' => $row['city'],
                ])->values()->all())
                ->toArray();
        });

        $activeVoucherCount = FuelVoucher::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'issued'])
            ->count();

        $pendingRepayments = Repayment::visibleInSystem()->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->count();

        $pendingRepaymentAmount = Repayment::visibleInSystem()->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');

        $activeStationCount = FuelStation::where('status', 'active')->count();

        $redeemedVoucherCount = FuelVoucher::where('user_id', $user->id)
            ->where('status', 'redeemed')
            ->count();

        $redeemedVoucherToday = FuelVoucher::where('user_id', $user->id)
            ->where('status', 'redeemed')
            ->whereDate('redeemed_at', now()->toDateString())
            ->count();

        $latestApprovedVoucher = FuelVoucher::with('fuelStation')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->latest()
            ->first();

        $recentVouchers = FuelVoucher::with('fuelStation')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(6)
            ->get();

        $upcomingRepayments = Repayment::visibleInSystem()->with(['lease.vouchers.fuelStation'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        // Determine the oldest overdue repayment using the same logic the dashboard uses for highlighting:
        // due_date is in the past and not today (end-of-day cutoff).
        $mostOverdue = $upcomingRepayments
            ->filter(function ($repayment) {
                if (empty($repayment->due_date)) {
                    return false;
                }
                try {
                    $dueDate = \Illuminate\Support\Carbon::parse($repayment->due_date);
                    return $dueDate->isPast() && !$dueDate->isToday() && (string) $repayment->status !== 'paid';
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->sortBy('due_date')
            ->first();

        if ($mostOverdue && $mostOverdue->due_date) {
            try {
                $due = \Illuminate\Support\Carbon::parse($mostOverdue->due_date)->endOfDay();
                if ($due->isPast()) {
                    $overdueSeconds = (int) now()->diffInSeconds($due);
                }
            } catch (\Throwable $e) {
                $overdueSeconds = 0;
            }
        }

        $nextRepayment = $upcomingRepayments->first(function ($repayment) {
            return !empty($repayment->due_date);
        });
        $nextRepaymentCountdownTarget = $nextRepayment?->due_date
            ? \Illuminate\Support\Carbon::parse($nextRepayment->due_date)->endOfDay()->getTimestampMs()
            : null;

        $driverCompliance = $this->buildDriverComplianceChecklist($user);

        return view('driver.dashboard', compact(
            'driverCompliance',
            'virtualCards',
            'virtualCardsAllocatedTotal',
            'stationsByBrandSlug',
            'activeVoucherCount',
            'pendingRepayments',
            'pendingRepaymentAmount',
            'activeStationCount',
            'redeemedVoucherCount',
            'redeemedVoucherToday',
            'latestApprovedVoucher',
            'recentVouchers',
            'upcomingRepayments',
            'nextRepaymentCountdownTarget',
            'nextRepayment',
            'mostOverdue',
            'overdueSeconds'
        ));
    }

    public function vouchers(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $stations = FuelStation::where('status', 'active')
            ->orderBy('company')
            ->orderBy('name')
            ->get(['id', 'name', 'company', 'city', 'address']);

        $brands = $stations
            ->pluck('company')
            ->map(fn ($company) => trim((string) $company))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $vouchers = FuelVoucher::with('fuelStation')
            ->where('user_id', $user->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('brand'), function ($q) use ($request) {
                $brand = trim((string) $request->string('brand')->toString());
                $q->whereHas('fuelStation', fn ($stationQuery) => $stationQuery->where('company', $brand));
            })
            ->when($request->filled('station_id'), fn ($q) => $q->where('fuel_station_id', $request->integer('station_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('driver.vouchers.index', compact('vouchers', 'stations', 'brands'));
    }

    public function cancelVoucher(Request $request, FuelVoucher $voucher)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        if ((int) $voucher->user_id !== (int) $user->id) {
            abort(403, 'You cannot cancel this voucher.');
        }

        if ((string) $voucher->status !== 'issued') {
            return back()->with('error', 'Only issued applications can be cancelled.');
        }

        try {
            $cancellation = $voucher->cancel();
            $cancelledRepayments = (int) ($cancellation['cancelled_repayments'] ?? 0);

            $message = 'Application cancelled successfully.';
            if ($cancelledRepayments > 0) {
                $message .= " {$cancelledRepayments} future repayment(s) were also cancelled.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel application: ' . $e->getMessage());
        }
    }

    public function createVoucher()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        if ($redirect = $this->redirectIfDriverDocumentsMissing($user)) {
            return $redirect;
        }
        if ((string) ($user->status ?? '') !== 'active') {
            return redirect()
                ->route('driver.dashboard')
                ->with('error', 'Your account is pending admin approval. Voucher requests are locked for now.');
        }

        $stationsPayload = Cache::remember('driver:voucher:stations-payload:v1', now()->addMinute(), function () {
            $stations = FuelStation::where('status', 'active')
                ->orderBy('company')
                ->orderBy('name')
                ->get(['id', 'name', 'company', 'city', 'address', 'latitude', 'longitude', 'wallet_balance']);

            $defaults = $this->fuelPriceService->defaultPrices();
            $priceRowsByStation = collect();
            $openExposureByStation = collect();

            if ($stations->isNotEmpty()) {
                $openExposureByStation = FuelVoucher::query()
                    ->whereIn('fuel_station_id', $stations->pluck('id')->all())
                    ->whereIn('status', ['issued', 'approved'])
                    ->selectRaw('fuel_station_id, COALESCE(SUM(amount), 0) as open_exposure')
                    ->groupBy('fuel_station_id')
                    ->pluck('open_exposure', 'fuel_station_id');
            }

            if (Schema::hasTable('fuel_station_prices') && $stations->isNotEmpty()) {
                $stationIds = $stations->pluck('id')->all();
                $supportedFuelTypes = array_keys($defaults);
                $hasActiveColumn = Schema::hasColumn('fuel_station_prices', 'is_active');

                // Fetch only the latest row per station + fuel type.
                $latestIds = DB::table('fuel_station_prices as fsp')
                    ->selectRaw('MAX(fsp.id) as id')
                    ->whereIn('fsp.fuel_station_id', $stationIds)
                    ->whereIn('fsp.fuel_type', $supportedFuelTypes)
                    ->when($hasActiveColumn, fn ($q) => $q->where('fsp.is_active', true))
                    ->groupBy('fsp.fuel_station_id', 'fsp.fuel_type');

                $priceRowsByStation = DB::table('fuel_station_prices as fsp')
                    ->joinSub($latestIds, 'latest', function ($join) {
                        $join->on('fsp.id', '=', 'latest.id');
                    })
                    ->select('fsp.fuel_station_id', 'fsp.fuel_type', 'fsp.price_per_liter')
                    ->get()
                    ->groupBy('fuel_station_id');
            }

            return $stations->map(function ($station) use ($defaults, $priceRowsByStation, $openExposureByStation) {
                $prices = $defaults;
                foreach (($priceRowsByStation[$station->id] ?? collect()) as $row) {
                    $fuelType = (string) ($row->fuel_type ?? '');
                    if (!array_key_exists($fuelType, $prices)) {
                        continue;
                    }
                    $prices[$fuelType] = (float) $row->price_per_liter;
                }

                $walletBalance = (float) ($station->wallet_balance ?? 0);
                $openExposure = (float) ($openExposureByStation[$station->id] ?? 0);
                $availableCapacity = max(0, $walletBalance - $openExposure);

                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'brand' => $this->normalizePopularBrand((string) ($station->company ?? '')),
                    'city' => $station->city,
                    'address' => trim((string) ($station->address ?? '')),
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'prices' => $prices,
                    'wallet_balance' => $walletBalance,
                    'open_exposure' => $openExposure,
                    'available_capacity' => $availableCapacity,
                ];
            })->values();
        });

        $leaseDefaults = Cache::remember('driver:voucher:lease-defaults:v1', now()->addMinutes(5), function () {
            $settingMap = DB::table('settings')
                ->whereIn('key', ['lease_interest_rate', 'lease_term_days'])
                ->pluck('value', 'key');

            $baseRate = (float) ($settingMap['lease_interest_rate'] ?? 5);
            $baseTerm = (int) ($settingMap['lease_term_days'] ?? 30);
            $baseTerm = max(7, min(60, $baseTerm));

            return [
                'rate' => $baseRate,
                'term_days' => $baseTerm,
                'min_days' => 7,
                'max_days' => 60,
                'rate_per_day' => 0.05,
                'min_daily_repayment' => $this->minRepaymentAmount(),
            ];
        });

        $underwriting = $this->driverUnderwritingService->resolveForUser($user);
        $maxEligibleAmount = (float) ($underwriting['max_amount'] ?? DriverUnderwritingService::STARTER_MAX_VOUCHER_AMOUNT);
        // Keep policy hidden; only use effective rate for accurate projection math in UI.
        $leaseDefaults['rate'] = round((float) $leaseDefaults['rate'] + (float) ($underwriting['rate_penalty'] ?? 0), 2);

        return view('driver.vouchers.create', compact('stationsPayload', 'leaseDefaults', 'maxEligibleAmount'));
    }

    public function storeVoucher(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        if ($redirect = $this->redirectIfDriverDocumentsMissing($user)) {
            return $redirect;
        }
        if ((string) ($user->status ?? '') !== 'active') {
            return redirect()
                ->route('driver.dashboard')
                ->with('error', 'Your account is pending admin approval. Voucher requests are locked for now.');
        }

        $validated = $request->validate([
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:100|max:100000',
            'fuel_type' => 'required|in:petrol,diesel,super',
            'repayment_frequency' => 'nullable|in:daily,weekly',
            'liters' => 'nullable|numeric|min:0.1|max:5000',
            'repayment_days' => 'nullable|integer|min:7|max:60',
            'voucher_reference' => 'nullable|string|max:120',
            'card_reference' => 'nullable|string|max:50',
        ]);

        $resolvedPrice = $this->fuelPriceService->resolvePriceForStationFuel(
            (int) $validated['fuel_station_id'],
            (string) $validated['fuel_type'],
            true
        );
        $pricePerLiter = (float) ($resolvedPrice['price'] ?? 25.00);

        $liters = (float) ($validated['liters'] ?? 0);
        if ($liters <= 0) {
            $basePrice = (float) ($pricePerLiter ?: 25.00);
            $liters = round(((float) $validated['amount']) / max($basePrice, 0.01), 3);
        }

        $settingMap = DB::table('settings')
            ->whereIn('key', ['lease_interest_rate', 'lease_term_days'])
            ->pluck('value', 'key');

        $baseRate = (float) ($settingMap['lease_interest_rate'] ?? 5);
        $baseTerm = (int) ($settingMap['lease_term_days'] ?? 30);
        $baseTerm = max(7, min(60, $baseTerm));
        $termDays = (int) ($validated['repayment_days'] ?? $baseTerm);
        $termDays = max(7, min(60, $termDays));
        $repaymentFrequency = strtolower((string) ($validated['repayment_frequency'] ?? 'daily'));
        if (!in_array($repaymentFrequency, ['daily', 'weekly'], true)) {
            $repaymentFrequency = 'daily';
        }
        $principal = (float) $validated['amount'];
        $underwriting = $this->driverUnderwritingService->resolveForUser($user);

        if ($principal > (float) ($underwriting['max_amount'] ?? self::STARTER_MAX_VOUCHER_AMOUNT)) {
            AuditTrailService::record(
                'voucher_underwriting_limit_blocked',
                $user,
                [],
                [
                    'requested_amount' => $principal,
                    'max_amount' => (float) ($underwriting['max_amount'] ?? DriverUnderwritingService::STARTER_MAX_VOUCHER_AMOUNT),
                    'account_age_days' => (int) ($underwriting['account_age_days'] ?? 0),
                    'late_repayment_detected' => (bool) ($underwriting['late_repayment_detected'] ?? false),
                ],
                'Voucher blocked by underwriting amount cap'
            );

            throw ValidationException::withMessages([
                'amount' => 'Requested amount exceeds your current approval limit. Please submit a smaller request.',
            ]);
        }

        $rate = $this->calculateLeaseRate($baseRate, $baseTerm, $termDays);
        $rate += (float) ($underwriting['rate_penalty'] ?? 0);
        $rate = round(max(0, min(50, $rate)), 2);
        $interestAmount = round($principal * ($rate / 100), 2);
        $totalAmount = round($principal + $interestAmount, 2);
        $dailyRepayment = round($totalAmount / max($termDays, 1), 2);
        if ($dailyRepayment < $this->minRepaymentAmount()) {
            throw ValidationException::withMessages([
                'repayment_days' => sprintf(
                    'Repayment per day cannot be below R%.2f. Increase amount or reduce repayment days.',
                    $this->minRepaymentAmount()
                ),
            ]);
        }

        $createdVoucher = null;

        DB::transaction(function () use ($user, $validated, $liters, $principal, $rate, $interestAmount, $totalAmount, $termDays, $dailyRepayment, $repaymentFrequency, &$createdVoucher) {
            $station = FuelStation::whereKey((int) $validated['fuel_station_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $openExposure = FuelVoucher::where('fuel_station_id', $station->id)
                ->whereIn('status', ['issued', 'approved'])
                ->lockForUpdate()
                ->sum('amount');
            $availableCapacity = max(0, (float) $station->wallet_balance - (float) $openExposure);
            if ($availableCapacity < $principal) {
                AuditTrailService::record(
                    'voucher_issue_blocked_station_wallet',
                    $station,
                    [],
                    [
                        'driver_id' => (int) $user->id,
                        'requested_amount' => (float) $principal,
                        'available_capacity' => (float) $availableCapacity,
                        'open_exposure' => (float) $openExposure,
                        'wallet_balance' => (float) $station->wallet_balance,
                    ],
                    'Voucher issuance blocked by insufficient station pre-funded balance'
                );
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Station wallet has insufficient pre-funded balance for this voucher. Available capacity: R%.2f.',
                        $availableCapacity
                    ),
                ]);
            }

            $lease = Lease::create([
                'user_id' => $user->id,
                'principal_amount' => $principal,
                'interest_rate' => $rate,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalAmount,
                'term_days' => $termDays,
                'daily_repayment' => $dailyRepayment,
                'repayment_frequency' => $repaymentFrequency,
                'status' => 'active',
                'issued_at' => now(),
                'due_date' => now()->addDays($termDays)->toDateString(),
            ]);

            $createdVoucher = FuelVoucher::create([
                'user_id' => $user->id,
                'fuel_station_id' => $validated['fuel_station_id'],
                'lease_id' => $lease->id,
                'amount' => $principal,
                'liters' => $liters,
                'fuel_type' => $validated['fuel_type'],
                'status' => 'issued',
                'issued_at' => now(),
                'expires_at' => now()->addHours(24),
                'transaction_reference' => $validated['voucher_reference'] ?? null,
                'pump_number' => $validated['card_reference'] ?? null,
            ]);
        });

        if ($createdVoucher) {
            $voucher = $createdVoucher->fresh(['user:id,name,phone', 'fuelStation:id,name,city']);

            try {
                event(new VoucherStatusChanged([
                    'event' => 'issued',
                    'voucher_id' => $voucher->id,
                    'voucher_code' => $voucher->code,
                    'qr_code' => $voucher->qr_code,
                    'status' => $voucher->status,
                    'amount' => (float) $voucher->amount,
                    'fuel_type' => $voucher->fuel_type,
                    'liters' => (float) $voucher->liters,
                    'station_id' => $voucher->fuel_station_id,
                    'station' => [
                        'id' => $voucher->fuelStation?->id,
                        'name' => $voucher->fuelStation?->name,
                        'city' => $voucher->fuelStation?->city,
                    ],
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
                ]));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()
            ->route('driver.vouchers.index')
            ->with('success', 'Voucher request submitted successfully.');
    }

    public function repayments()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $mostOverdue = Repayment::visibleInSystem()
            ->where('user_id', $user->id)
            ->where('status', 'overdue')
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->first();
        $overdueSeconds = 0;
        if ($mostOverdue && $mostOverdue->due_date) {
            try {
                $due = \Illuminate\Support\Carbon::parse($mostOverdue->due_date);
                if ($due->isPast()) {
                    $overdueSeconds = (int) now()->diffInSeconds($due);
                }
            } catch (\Throwable $e) {
                $overdueSeconds = 0;
            }
        }

        $repayments = Repayment::visibleInSystem()->with(['lease.vouchers.fuelStation'])
            ->where('user_id', $user->id)
            ->orderByRaw("FIELD(status, 'overdue','pending','paid','defaulted')")
            ->orderBy('due_date')
            ->paginate(20);

        $summary = [
            'pending_count' => Repayment::visibleInSystem()->where('user_id', $user->id)->whereIn('status', ['pending', 'overdue'])->count(),
            'pending_amount' => Repayment::visibleInSystem()->where('user_id', $user->id)->whereIn('status', ['pending', 'overdue'])->sum('amount'),
            'paid_this_month' => Repayment::visibleInSystem()->where('user_id', $user->id)
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];

        $autopay = [
            'enabled' => (bool) $user->autopay_enabled,
            'status' => $this->resolveAutopayStatus((string) ($user->autopay_status ?? 'inactive')),
            'gateway' => (string) ($user->autopay_gateway ?? ''),
            'has_token' => trim((string) ($user->autopay_token ?? '')) !== '',
            'failures' => (int) ($user->autopay_failures ?? 0),
            'next_attempt_at' => $user->autopay_next_attempt_at,
            'last_attempt_at' => $user->autopay_last_attempt_at,
        ];

        $autopayDetails = (array) ($user->autopay_details ?? []);
        $authorization = (array) ($autopayDetails['authorization'] ?? []);
        $rawLast4 = preg_replace('/\D+/', '', (string) ($authorization['last4'] ?? ''));
        $safeLast4 = strlen($rawLast4) === 4 ? $rawLast4 : '';
        $brand = trim((string) ($authorization['brand'] ?? 'Card'));
        $expMonth = str_pad((string) ($authorization['exp_month'] ?? ''), 2, '0', STR_PAD_LEFT);
        $expYearRaw = trim((string) ($authorization['exp_year'] ?? ''));
        $expYear = strlen($expYearRaw) >= 2 ? substr($expYearRaw, -2) : '';

        $autopay['card'] = [
            'brand' => $brand !== '' ? $brand : 'Card',
            'last4' => $safeLast4,
            'expiry' => ($expMonth !== '' && $expYear !== '') ? ($expMonth . '/' . $expYear) : 'N/A',
            'holder' => (string) ($user->name ?? 'Card Holder'),
            'is_saved' => $safeLast4 !== '' && ($autopay['has_token'] ?? false),
        ];

        $autopayEvents = AuditLog::query()
            ->where('user_id', $user->id)
            ->whereIn('action', [
                'repayment_autopay_succeeded',
                'repayment_autopay_failed',
                'repayment_autopay_retry_scheduled',
                'repayment_autopay_disabled_for_user',
                'repayment_user_notification',
                'repayment_checkout_verified',
                'repayment_checkout_initialized',
            ])
            ->latest()
            ->limit(12)
            ->get();

        return view('driver.repayments.index', compact('repayments', 'summary', 'autopay', 'autopayEvents', 'mostOverdue', 'overdueSeconds'));
    }

    public function profile()
    {
        $user = Auth::user()->load(['wallet', 'creditLimit', 'driverDocuments']);
        $this->authorizeDriverPortal($user);

        $summary = [
            'total_vouchers' => FuelVoucher::where('user_id', $user->id)->count(),
            'redeemed_vouchers' => FuelVoucher::where('user_id', $user->id)->where('status', 'redeemed')->count(),
            'active_leases' => Lease::where('user_id', $user->id)->where('status', 'active')->count(),
            'paid_repayments' => Repayment::visibleInSystem()->where('user_id', $user->id)->where('status', 'paid')->count(),
        ];

        return view('driver.profile', compact('user', 'summary'));
    }

    public function exportUpcomingRepaymentsPdf()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $repayments = Repayment::visibleInSystem()->with(['lease.vouchers.fuelStation'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->get();

        $lines = [
            'Bwiser - Upcoming Repayments',
            'Driver: ' . ($user->name ?? 'N/A'),
            'Generated: ' . now()->format('Y-m-d H:i:s'),
            str_repeat('-', 90),
            'Due Date | Amount (ZAR) | Status | Lease | Station',
            str_repeat('-', 90),
        ];

        foreach ($repayments as $repayment) {
            $due = $repayment->due_date
                ? \Illuminate\Support\Carbon::parse($repayment->due_date)->format('Y-m-d')
                : 'N/A';
            $amount = number_format((float) $repayment->amount, 2);
            $status = strtoupper((string) $repayment->status);
            $leaseId = '#' . (string) $repayment->lease_id;
            $station = optional(optional($repayment->lease)->vouchers->first())->fuelStation->name ?? 'N/A';
            $station = mb_strimwidth((string) $station, 0, 28, '...');

            $lines[] = sprintf('%s | %s | %s | %s | %s', $due, $amount, $status, $leaseId, $station);
        }

        if ($repayments->isEmpty()) {
            $lines[] = 'No upcoming repayments found.';
        }

        $pdf = $this->buildSimpleTextPdf($lines);
        $filename = 'upcoming_repayments_' . now()->format('Ymd_His') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function payRepayment(Request $request, Repayment $repayment)
    {
        $intent = strtolower(trim((string) $request->input('payment_intent', 'force_now')));
        if (!in_array($intent, ['force_now', 'pay_now', 'manual'], true)) {
            // Be permissive for older forms/buttons that may not submit payment_intent.
            $intent = 'force_now';
        }

        $method = $request->input('payment_method', 'card');
        if (!in_array($method, ['card'], true)) {
            $method = 'card';
        }

        return $this->startRepaymentCheckout($request, $repayment, $intent, (string) $method);
    }

    public function payRepaymentNow(Request $request, Repayment $repayment)
    {
        return $this->startRepaymentCheckout($request, $repayment, 'force_now', 'card');
    }

    public function walletTopupPaystackInit(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $validated = $request->validateWithBag('walletTopup', [
            'amount' => 'required|numeric|min:10|max:50000',
            'payer_email' => 'nullable|email|max:255',
        ]);

        if (!$this->paystackService->configured()) {
            return back()->with('error', 'Paystack is not configured yet. Please try again later.')
                ->with('wallet_topup_open', true);
        }

        $amount = (float) $validated['amount'];
        $payerEmail = trim((string) ($validated['payer_email'] ?? ''));

        try {
            $checkout = $this->paystackService->initializeWalletTopupCheckout(
                $user,
                $amount,
                $this->absoluteRouteForCurrentHost($request, 'driver.wallet.topup.paystack.callback'),
                $payerEmail !== '' ? $payerEmail : null
            );

            AuditTrailService::record(
                'wallet_topup_checkout_initialized',
                $user,
                [],
                [
                    'reference' => (string) ($checkout['reference'] ?? ''),
                    'amount' => $amount,
                    'payment_method' => 'paystack_card',
                ],
                'Wallet top-up Paystack checkout initialized'
            );

            $url = (string) ($checkout['authorization_url'] ?? '');
            if ($url === '') {
                return back()->with('error', 'Paystack did not return an authorization URL.')
                    ->with('wallet_topup_open', true);
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to initialize Paystack top-up: ' . $e->getMessage())
                ->with('wallet_topup_open', true);
        }
    }

    /**
     * Pay-now style initializer (GET) to match the existing repayments "Pay Now" behavior.
     */
    public function walletTopupPaystackStart(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $validated = $request->validateWithBag('walletTopup', [
            'amount' => 'required|numeric|min:10|max:50000',
            'payer_email' => 'nullable|email|max:255',
        ]);

        if (!$this->paystackService->configured()) {
            return back()->with('error', 'Paystack is not configured yet. Please try again later.')
                ->with('wallet_topup_open', true);
        }

        $amount = (float) $validated['amount'];
        $payerEmail = trim((string) ($validated['payer_email'] ?? ''));

        try {
            $checkout = $this->paystackService->initializeWalletTopupCheckout(
                $user,
                $amount,
                $this->absoluteRouteForCurrentHost($request, 'driver.wallet.topup.paystack.callback'),
                $payerEmail !== '' ? $payerEmail : null
            );

            AuditTrailService::record(
                'wallet_topup_checkout_initialized',
                $user,
                [],
                [
                    'reference' => (string) ($checkout['reference'] ?? ''),
                    'amount' => $amount,
                    'payment_method' => 'paystack_card',
                    'flow' => 'get_start',
                ],
                'Wallet top-up Paystack checkout initialized'
            );

            $url = (string) ($checkout['authorization_url'] ?? '');
            if ($url === '') {
                return back()->with('error', 'Paystack did not return an authorization URL.')
                    ->with('wallet_topup_open', true);
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to initialize Paystack top-up: ' . $e->getMessage())
                ->with('wallet_topup_open', true);
        }
    }

    public function walletTopupPaystackCallback(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $reference = trim((string) ($request->query('reference') ?: $request->query('trxref') ?: ''));
        if ($reference === '') {
            return redirect()
                ->route('driver.dashboard')
                ->with('error', 'Paystack callback did not include a reference.');
        }

        try {
            $verified = $this->paystackService->verifyTransaction($reference);
            $metadata = (array) ($verified['metadata'] ?? []);
            $scope = (string) ($metadata['scope'] ?? '');
            $userId = (int) ($metadata['user_id'] ?? 0);

            if ($scope !== 'wallet_topup' || $userId <= 0 || $userId !== (int) $user->id) {
                return redirect()
                    ->route('driver.dashboard')
                    ->with('error', 'This Paystack payment is not linked to your wallet top-up.');
            }

            $paidMinor = (int) ($verified['amount'] ?? 0);
            if ($paidMinor <= 0) {
                return redirect()
                    ->route('driver.dashboard')
                    ->with('error', 'Paystack did not return a valid amount.');
            }

            $amount = round($paidMinor / 100, 2);

            // Idempotency: wallet_transactions.reference is unique, so reuse Paystack reference.
            if (WalletTransaction::query()->where('reference', $reference)->exists()) {
                return redirect()
                    ->route('driver.dashboard')
                    ->with('success', 'Wallet top-up already processed.');
            }

            DB::transaction(function () use ($user, $amount, $reference, $verified) {
                $wallet = $user->wallet()->lockForUpdate()->firstOrCreate([], [
                    'balance' => 0,
                    'outstanding_balance' => 0,
                    'total_credit_used' => 0,
                    'total_repayments' => 0,
                    'currency' => strtoupper((string) config('services.paystack.currency', 'ZAR')),
                ]);

                $before = (float) $wallet->balance;
                $after = $before + (float) $amount;

                $wallet->forceFill(['balance' => $after])->save();

                $wallet->transactions()->create([
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'description' => 'Wallet topup via Paystack',
                    'reference' => $reference,
                    'status' => 'completed',
                    'metadata' => [
                        'gateway' => 'paystack',
                        'paystack' => $verified,
                    ],
                ]);
            });

            // Reuse the existing integration behavior: capture authorization/customer for future autopay.
            $this->paystackService->storeAuthorizationFromTransaction($user, $verified);

            AuditTrailService::record(
                'wallet_topup_checkout_verified',
                $user,
                [],
                [
                    'reference' => $reference,
                    'amount' => $amount,
                    'payment_method' => 'paystack_card',
                    'gateway_status' => (string) ($verified['status'] ?? 'success'),
                ],
                'Wallet top-up Paystack checkout verified and credited'
            );

            return redirect()
                ->route('driver.dashboard')
                ->with('success', 'Wallet funded successfully: R ' . number_format((float) $amount, 2));
        } catch (\Throwable $e) {
            return redirect()
                ->route('driver.dashboard')
                ->with('error', 'Paystack top-up verification failed: ' . $e->getMessage());
        }
    }

    private function startRepaymentCheckout(Request $request, Repayment $repayment, string $intent, string $method)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        if ((int) $repayment->user_id !== (int) $user->id) {
            abort(403);
        }

        if (!in_array($repayment->status, ['pending', 'overdue'], true)) {
            return back()->with('error', 'Repayment has already been processed.');
        }

        try {
            $checkout = $this->paystackService->initializeRepaymentCheckout(
                $user,
                $repayment,
                $method,
                $this->absoluteRouteForCurrentHost($request, 'driver.repayments.paystack.callback')
            );

            $repayment->forceFill([
                'transaction_reference' => (string) ($checkout['reference'] ?? ''),
                'autopay_status' => 'checkout_initialized',
                'autopay_last_attempt_at' => now(),
            ])->save();

            AuditTrailService::record(
                'repayment_checkout_initialized',
                $repayment,
                [],
                [
                    'payment_method' => "paystack_{$method}",
                    'payment_intent' => $intent,
                    'reference' => (string) ($checkout['reference'] ?? ''),
                    'amount' => (float) $repayment->amount,
                ],
                'Repayment Paystack checkout initialized (force pay now override)'
            );

            $url = (string) ($checkout['authorization_url'] ?? '');
            if ($url === '') {
                return back()->with('error', 'Paystack did not return an authorization URL.');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to initialize Paystack repayment: ' . $e->getMessage());
        }
    }

    public function publicRepaymentRequest(Request $request, Repayment $repayment)
    {
        $repayment->loadMissing('lease.vouchers');
        $stationName = optional(optional($repayment->lease)->vouchers->first())->fuelStation->name ?? 'N/A';
        $voucherCode = optional($repayment->lease?->vouchers?->sortByDesc('id')?->first())->code;
        $isPayable = in_array((string) $repayment->status, ['pending', 'overdue'], true);
        $payerEmailPrefill = trim((string) $request->query('email', ''));

        $payUrl = URL::temporarySignedRoute(
            'driver.repayments.request.pay',
            now()->addDays(7),
            ['repayment' => $repayment->id]
        );

        return view('driver.repayments.request', compact(
            'repayment',
            'stationName',
            'voucherCode',
            'isPayable',
            'payUrl',
            'payerEmailPrefill'
        ));
    }

    public function publicRepaymentRequestPay(Request $request, Repayment $repayment)
    {
        $validated = $request->validate([
            'payer_email' => 'nullable|email|max:255',
        ]);

        if (!in_array((string) $repayment->status, ['pending', 'overdue'], true)) {
            return back()->with('error', 'This repayment is already settled or not payable.');
        }

        $driver = $repayment->user;
        if (!$driver) {
            return back()->with('error', 'Driver profile is missing for this repayment.');
        }

        $payerEmail = trim((string) ($validated['payer_email'] ?? ''));

        try {
            $checkout = $this->paystackService->initializeRepaymentCheckout(
                $driver,
                $repayment,
                'card',
                $this->absoluteRouteForCurrentHost($request, 'driver.repayments.request.callback'),
                $payerEmail !== '' ? $payerEmail : null,
                'repayment_request'
            );

            $repayment->forceFill([
                'transaction_reference' => (string) ($checkout['reference'] ?? ''),
                'autopay_status' => 'checkout_initialized',
                'autopay_last_attempt_at' => now(),
            ])->save();

            AuditTrailService::record(
                'repayment_public_request_checkout_initialized',
                $repayment,
                [],
                [
                    'reference' => (string) ($checkout['reference'] ?? ''),
                    'amount' => (float) $repayment->amount,
                    'payer_email' => $payerEmail,
                ],
                'Public repayment request checkout initialized'
            );

            $url = (string) ($checkout['authorization_url'] ?? '');
            if ($url === '') {
                return back()->with('error', 'Paystack did not return an authorization URL.');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to initialize repayment request: ' . $e->getMessage());
        }
    }

    public function publicRepaymentRequestCallback(Request $request)
    {
        $reference = trim((string) ($request->query('reference') ?: $request->query('trxref') ?: ''));
        if ($reference === '') {
            return view('driver.repayments.request-result', [
                'success' => false,
                'title' => 'Payment reference missing',
                'message' => 'We could not find a Paystack payment reference in this callback.',
            ]);
        }

        try {
            $verified = $this->paystackService->verifyTransaction($reference);
            $metadata = (array) ($verified['metadata'] ?? []);
            $scope = (string) ($metadata['scope'] ?? '');
            $repaymentId = (int) ($metadata['repayment_id'] ?? 0);

            if ($repaymentId <= 0 || $scope !== 'repayment_request') {
                return view('driver.repayments.request-result', [
                    'success' => false,
                    'title' => 'Invalid payment metadata',
                    'message' => 'This payment is not linked to a valid repayment request.',
                ]);
            }

            $repayment = Repayment::findOrFail($repaymentId);
            $expectedMinor = (int) round((float) $repayment->amount * 100);
            $paidMinor = (int) ($verified['amount'] ?? 0);
            if ($paidMinor < $expectedMinor) {
                return view('driver.repayments.request-result', [
                    'success' => false,
                    'title' => 'Payment amount mismatch',
                    'message' => 'Received amount does not match the repayment request.',
                ]);
            }

            $beforeStatus = (string) $repayment->status;
            $this->repaymentSettlementService->settleRepayment(
                $repayment,
                'paystack_shared_link_card',
                $reference,
                ['source' => 'public_repayment_request_callback']
            );

            $freshRepayment = $repayment->fresh();

            AuditTrailService::record(
                'repayment_public_request_checkout_verified',
                $freshRepayment,
                ['status' => $beforeStatus],
                [
                    'status' => (string) ($freshRepayment->status ?? $beforeStatus),
                    'reference' => $reference,
                    'gateway_status' => (string) ($verified['status'] ?? 'success'),
                    'paid_minor' => $paidMinor,
                ],
                'Public repayment request callback verified and settled'
            );

            return view('driver.repayments.request-result', [
                'success' => true,
                'title' => 'Payment received',
                'message' => 'Thank you. This repayment has been successfully paid.',
                'repayment' => $freshRepayment,
            ]);
        } catch (\Throwable $e) {
            return view('driver.repayments.request-result', [
                'success' => false,
                'title' => 'Payment verification failed',
                'message' => 'We could not verify this payment. Please retry or contact support.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function payRepaymentCallback(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $reference = trim((string) ($request->query('reference') ?: $request->query('trxref') ?: ''));
        if ($reference === '') {
            return redirect()->route('driver.repayments.index')->with('error', 'Missing payment reference from Paystack callback.');
        }

        try {
            $verified = $this->paystackService->verifyTransaction($reference);
            $metadata = (array) ($verified['metadata'] ?? []);
            $repaymentId = (int) ($metadata['repayment_id'] ?? 0);
            if ($repaymentId <= 0) {
                return redirect()->route('driver.repayments.index')->with('error', 'Paystack callback is missing repayment metadata.');
            }

            $repayment = Repayment::findOrFail($repaymentId);
            if ((int) $repayment->user_id !== (int) $user->id) {
                abort(403);
            }

            $this->repaymentSettlementService->settleRepayment(
                $repayment,
                $this->resolvePaystackPaymentMethodFromMetadata($metadata),
                $reference,
                ['source' => 'driver_checkout_callback']
            );

            $this->paystackService->storeAuthorizationFromTransaction($user, $verified);

            $repayment->forceFill([
                'autopay_status' => 'paid',
                'autopay_last_attempt_at' => now(),
                'autopay_next_attempt_at' => null,
            ])->save();

            AuditTrailService::record(
                'repayment_checkout_verified',
                $repayment,
                [],
                [
                    'reference' => $reference,
                    'gateway_status' => (string) ($verified['status'] ?? 'success'),
                ],
                'Repayment Paystack callback verified and settled'
            );

            $this->notifyRepaymentUser(
                $user,
                'Repayment received',
                "Your repayment of R " . number_format((float) $repayment->amount, 2) . " was received successfully.",
                $repayment
            );

            return redirect()
                ->route('driver.repayments.index')
                ->with('success', 'Repayment paid successfully. Daily 24-hour auto-pay is now ready.');
        } catch (\Throwable $e) {
            $this->notifyRepaymentUser(
                $user,
                'Repayment verification failed',
                'We could not verify your Paystack repayment callback. Please retry or contact support.',
                $repayment ?? null
            );
            return redirect()->route('driver.repayments.index')->with('error', 'Paystack callback verification failed: ' . $e->getMessage());
        }
    }

    public function toggleAutopayDaily(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $enable = filter_var($request->input('enabled', false), FILTER_VALIDATE_BOOL);

        if ($enable && trim((string) ($user->autopay_token ?? '')) === '') {
            return back()->with('error', 'No Paystack authorization found. Make one successful card repayment first to activate daily auto-pay.');
        }

        $status = $enable ? 'active' : 'disabled';
        $nextAttemptAt = $enable ? now()->addDay() : null;

        $user->forceFill([
            'autopay_enabled' => $enable,
            'autopay_gateway' => $enable ? 'paystack' : $user->autopay_gateway,
            'autopay_status' => $status,
            'autopay_failures' => $enable ? 0 : (int) $user->autopay_failures,
            'autopay_last_attempt_at' => now(),
            'autopay_next_attempt_at' => $nextAttemptAt,
        ])->save();

        AuditTrailService::record(
            $enable ? 'repayment_autopay_enabled' : 'repayment_autopay_disabled',
            $user,
            [],
            [
                'gateway' => (string) ($user->autopay_gateway ?? ''),
                'next_attempt_at' => $nextAttemptAt?->toDateTimeString(),
            ],
            $enable ? 'Driver enabled daily repayment auto-pay' : 'Driver disabled daily repayment auto-pay'
        );

        return back()->with('success', $enable
            ? 'Daily auto-pay enabled. The system will attempt repayments every 24 hours when due.'
            : 'Daily auto-pay disabled.');
    }

    private function authorizeDriverPortal($user): void
    {
        abort_unless($user && $user->hasAnyRole(['super_admin', 'admin', 'driver']), 403);
    }

    private function redirectIfDriverDocumentsMissing($user)
    {
        if (!$user || $user->hasAnyRole(['super_admin', 'admin'])) {
            return null;
        }

        // Require at least SA ID + driver licence before voucher applications are enabled.
        $requiredTypes = ['sa_id', 'driver_license'];
        $providedTypes = $this->providedDriverDocumentTypes($user, $requiredTypes);
        $missing = array_diff($requiredTypes, $providedTypes);

        if (!empty($missing)) {
            return redirect()
                ->route('driver.profile')
                ->with('error', 'Upload your SA ID and driver licence in your dashboard before applying for vouchers.');
        }

        return null;
    }

    private function providedDriverDocumentTypes($user, array $requiredTypes): array
    {
        $providedTypes = [];

        if (Schema::hasTable('driver_documents')) {
            $providedTypes = $user->driverDocuments()
                ->whereIn('document_type', $requiredTypes)
                ->whereNotNull('document_path')
                ->pluck('document_type')
                ->map(fn ($v) => (string) $v)
                ->unique()
                ->values()
                ->all();
        }

        // Fallback for legacy columns if present.
        if (Schema::hasColumn('users', 'id_document_path') && !empty($user->id_document_path)) {
            $providedTypes[] = 'sa_id';
        }
        if (Schema::hasColumn('users', 'driver_license_path') && !empty($user->driver_license_path)) {
            $providedTypes[] = 'driver_license';
        }

        return array_values(array_unique($providedTypes));
    }

    private function buildDriverComplianceChecklist($user): array
    {
        $uploadUrl = route('registration.complete', ['role' => 'driver'], false);

        $docsByType = collect($user->driverDocuments ?? [])->keyBy('document_type');

        $required = [
            [
                'key' => 'sa_id',
                'label' => 'SA ID',
                'required' => true,
                'hint' => 'Upload a clear photo or PDF of your SA ID.',
            ],
            [
                'key' => 'driver_license',
                'label' => 'Driver Licence',
                'required' => true,
                'hint' => 'Upload your driver licence (front/back).',
            ],
        ];

	        $optional = [
	            [
	                'key' => 'bank_statement',
	                'label' => 'Bank Statement',
	                'required' => false,
	                'hint' => 'Improves your credit assessment and limits.',
	            ],
	        ];

        $items = [];
        foreach (array_merge($required, $optional) as $row) {
            $key = (string) $row['key'];
            $uploaded = false;
            $verified = false;

            if ($key === 'bank_statement') {
                $uploaded = Schema::hasColumn('users', 'bank_statement_path') && !empty($user->bank_statement_path);
                $verified = false;
            } else {
                $doc = $docsByType->get($key);
                $uploaded = $doc && !empty($doc->document_path);
                $verified = (bool) ($doc?->verified ?? false);

                // Legacy fallbacks.
                if (!$uploaded && $key === 'sa_id') {
                    $uploaded = Schema::hasColumn('users', 'id_document_path') && !empty($user->id_document_path);
                }
                if (!$uploaded && $key === 'driver_license') {
                    $uploaded = Schema::hasColumn('users', 'driver_license_path') && !empty($user->driver_license_path);
                }
            }

            $status = $uploaded ? ($verified ? 'verified' : 'uploaded') : 'missing';

            $items[] = [
                'key' => $key,
                'label' => (string) $row['label'],
                'required' => (bool) ($row['required'] ?? false),
                'hint' => (string) ($row['hint'] ?? ''),
                'status' => $status, // missing|uploaded|verified
                'uploaded' => $uploaded,
                'verified' => $verified,
                'upload_url' => $uploadUrl,
            ];
        }

        $ready = collect($items)
            ->where('required', true)
            ->every(fn ($i) => (string) ($i['status'] ?? '') !== 'missing');

        return [
            'ready' => (bool) $ready,
            'upload_url' => $uploadUrl,
            'items' => $items,
        ];
    }

    private function normalizePopularBrand(string $brand): string
    {
        $value = strtolower(trim($brand));
        if ($value === '') {
            return '';
        }

        $map = [
            'shell' => 'Shell',
            'shell sa' => 'Shell',
            'shell south africa' => 'Shell',
            'bp' => 'BP',
            'bp southern africa' => 'BP',
            'engen' => 'Engen',
            'sasol' => 'Sasol',
            'astron' => 'Astron Energy',
            'astron energy' => 'Astron Energy',
            'total' => 'TotalEnergies',
            'total energies' => 'TotalEnergies',
            'totalenergies' => 'TotalEnergies',
        ];

        foreach ($map as $needle => $normalized) {
            if ($value === $needle || str_contains($value, $needle)) {
                return $normalized;
            }
        }

        return '';
    }

    private function calculateLeaseRate(float $baseRate, int $baseTermDays, int $selectedTermDays): float
    {
        $deltaDays = $selectedTermDays - $baseTermDays;
        $rate = $baseRate + ($deltaDays * 0.05);

        return round(max(1, min(35, $rate)), 2);
    }

    private function resolveAutopayStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return 'inactive';
        }

        return in_array($status, ['active', 'retrying', 'disabled', 'inactive', 'failed'], true)
            ? $status
            : 'inactive';
    }

    private function resolvePaystackPaymentMethodFromMetadata(array $metadata): string
    {
        return 'paystack_card';
    }

    private function absoluteRouteForCurrentHost(Request $request, string $routeName, array $parameters = []): string
    {
        $path = route($routeName, $parameters, false);

        return rtrim($request->getSchemeAndHttpHost(), '/') . $path;
    }

    private function notifyRepaymentUser($user, string $subject, string $message, ?Repayment $repayment = null): void
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            try {
                $appUrl = rtrim((string) config('app.url', 'https://bwiser.co.za'), '/');
                $payload = [
                    'subject' => $subject,
                    'heading' => $subject,
                    'body' => $message,
                    'preheader' => $subject,
                    'logo_url' => $appUrl . '/images/brand-logo.png',
                    'cta_url' => $appUrl . '/driver/repayments',
                    'cta_label' => 'View repayments',
                ];

                if ($repayment) {
                    $payload['ticket'] = $this->buildVoucherTicketPayload($user, $repayment);
                }

                Mail::to($email)->send(new RepaymentAutopayNotificationMail($payload));
            } catch (\Throwable $e) {
                // Keep repayment flow non-blocking.
            }
        }

        AuditTrailService::record(
            'repayment_user_notification',
            $user,
            [],
            ['subject' => $subject, 'message' => $message],
            'Repayment notification emitted'
        );
    }

    private function buildVoucherTicketPayload($user, Repayment $repayment): array
    {
        $repayment->loadMissing('lease.vouchers.fuelStation');

        $voucher = $repayment->lease?->vouchers?->sortByDesc('id')->first();
        $voucherCode = (string) ($voucher?->code ?: ($repayment->lease_id ? ('LEASE-' . (string) $repayment->lease_id) : ('REPAYMENT-' . (string) $repayment->id)));
        $voucherQrValue = (string) ($voucher?->qr_code ?: $voucherCode);
        $voucherQrImage = $voucherQrValue !== ''
            ? ('https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=10&ecc=H&format=png&data=' . urlencode($voucherQrValue))
            : null;

        $stationName = $voucher?->fuelStation?->name
            ?? ($repayment->lease?->vouchers?->first()?->fuelStation?->name ?? 'N/A');

        $pendingCount = 0;
        $pendingAmount = 0.0;
        $nextDueDate = $repayment->due_date
            ? \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y')
            : 'N/A';

        if ($repayment->lease_id) {
            $pendingForLease = Repayment::query()
                ->visibleInSystem()
                ->where('user_id', (int) ($user->id ?? 0))
                ->where('lease_id', (int) $repayment->lease_id)
                ->whereIn('status', ['pending', 'overdue'])
                ->get(['amount', 'due_date', 'status']);

            $pendingCount = $pendingForLease->count();
            $pendingAmount = (float) $pendingForLease->sum('amount');

            $nextDue = $pendingForLease->sortBy('due_date')->first();
            if ($nextDue && $nextDue->due_date) {
                $nextDueDate = \Illuminate\Support\Carbon::parse($nextDue->due_date)->format('d M Y');
            }
        }

        return [
            'voucher_code' => $voucherCode,
            'voucher_qr_image' => $voucherQrImage,
            'station_name' => Str::limit((string) $stationName, 32),
            'pending_count' => $pendingCount,
            'pending_amount_display' => number_format(abs($pendingAmount), 2),
            'next_due_date' => $nextDueDate,
            'driver_name' => Str::limit((string) ($user->name ?? 'Driver'), 26),
            'lease_id' => $repayment->lease_id ? (string) $repayment->lease_id : '--',
        ];
    }

    private function buildSimpleTextPdf(array $lines): string
    {
        $objects = [];

        $content = "BT\n/F1 11 Tf\n50 792 Td\n14 TL\n";
        $content .= "0 0 0 rg\n";

        foreach ($lines as $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $line);
            $content .= '(' . $safe . ") Tj\nT*\n";
        }

        $content .= "ET";
        $contentLength = strlen($content);

        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length {$contentLength} >>\nstream\n{$content}\nendstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
