<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Events\VoucherStatusChanged;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\Repayment;
use App\Models\AuditLog;
use App\Services\AuditTrailService;
use App\Services\DriverUnderwritingService;
use App\Services\FuelPriceService;
use App\Services\PaystackService;
use App\Services\RepaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    private const MIN_REPAYMENT_AMOUNT = 30.00;

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

        $nextRepayment = $upcomingRepayments->first(function ($repayment) {
            return !empty($repayment->due_date);
        });
        $nextRepaymentCountdownTarget = $nextRepayment?->due_date
            ? \Illuminate\Support\Carbon::parse($nextRepayment->due_date)->endOfDay()->getTimestampMs()
            : null;

        return view('driver.dashboard', compact(
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
            'nextRepayment'
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

    public function createVoucher()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

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
                'min_daily_repayment' => self::MIN_REPAYMENT_AMOUNT,
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
        if ($dailyRepayment < self::MIN_REPAYMENT_AMOUNT) {
            throw ValidationException::withMessages([
                'repayment_days' => sprintf(
                    'Repayment per day cannot be below R%.2f. Increase amount or reduce repayment days.',
                    self::MIN_REPAYMENT_AMOUNT
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

        return view('driver.repayments.index', compact('repayments', 'summary', 'autopay', 'autopayEvents'));
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
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        if ((int) $repayment->user_id !== (int) $user->id) {
            abort(403);
        }

        if (!in_array($repayment->status, ['pending', 'overdue'], true)) {
            return back()->with('error', 'Repayment has already been processed.');
        }

        $intent = strtolower(trim((string) $request->input('payment_intent', '')));
        if ($intent !== 'force_now') {
            return back()->with('error', 'Manual checkout is disabled unless you explicitly choose Force Pay Now.');
        }

        $method = $request->input('payment_method', 'card');
        if (!in_array($method, ['card'], true)) {
            $method = 'card';
        }

        try {
            $checkout = $this->paystackService->initializeRepaymentCheckout(
                $user,
                $repayment,
                $method,
                route('driver.repayments.paystack.callback')
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
                route('driver.repayments.request.callback'),
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
                "Your repayment of R " . number_format((float) $repayment->amount, 2) . " was received successfully."
            );

            return redirect()
                ->route('driver.repayments.index')
                ->with('success', 'Repayment paid successfully. Daily 24-hour auto-pay is now ready.');
        } catch (\Throwable $e) {
            $this->notifyRepaymentUser(
                $user,
                'Repayment verification failed',
                'We could not verify your Paystack repayment callback. Please retry or contact support.'
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

    private function notifyRepaymentUser($user, string $subject, string $message): void
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            try {
                Mail::raw($message, function ($mail) use ($email, $subject) {
                    $mail->to($email)->subject($subject);
                });
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
