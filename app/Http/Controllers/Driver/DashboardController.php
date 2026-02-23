<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Events\VoucherStatusChanged;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\Repayment;
use App\Services\AuditTrailService;
use App\Services\FuelPriceService;
use App\Services\PaystackService;
use App\Services\RepaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    private const MIN_REPAYMENT_AMOUNT = 30.00;

    public function __construct(
        private FuelPriceService $fuelPriceService,
        private PaystackService $paystackService,
        private RepaymentSettlementService $repaymentSettlementService
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

        $pendingRepayments = Repayment::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->count();

        $pendingRepaymentAmount = Repayment::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');

        $activeStationCount = FuelStation::where('status', 'active')->count();

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

        $upcomingRepayments = Repayment::with(['lease.vouchers.fuelStation'])
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
            ->orderBy('name')
            ->get(['id', 'name']);

        $vouchers = FuelVoucher::with('fuelStation')
            ->where('user_id', $user->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('station_id'), fn ($q) => $q->where('fuel_station_id', $request->integer('station_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('driver.vouchers.index', compact('vouchers', 'stations'));
    }

    public function createVoucher()
    {
        $this->authorizeDriverPortal(Auth::user());

        $stationsPayload = Cache::remember('driver:voucher:stations-payload:v1', now()->addMinute(), function () {
            $stations = FuelStation::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'city', 'latitude', 'longitude']);

            $defaults = $this->fuelPriceService->defaultPrices();
            $priceRowsByStation = collect();

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

            return $stations->map(function ($station) use ($defaults, $priceRowsByStation) {
                $prices = $defaults;
                foreach (($priceRowsByStation[$station->id] ?? collect()) as $row) {
                    $fuelType = (string) ($row->fuel_type ?? '');
                    if (!array_key_exists($fuelType, $prices)) {
                        continue;
                    }
                    $prices[$fuelType] = (float) $row->price_per_liter;
                }

                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'city' => $station->city,
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'prices' => $prices,
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

        return view('driver.vouchers.create', compact('stationsPayload', 'leaseDefaults'));
    }

    public function storeVoucher(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $validated = $request->validate([
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:100|max:100000',
            'fuel_type' => 'required|in:petrol,diesel,super',
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
        $rate = $this->calculateLeaseRate($baseRate, $baseTerm, $termDays);
        $principal = (float) $validated['amount'];
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

        DB::transaction(function () use ($user, $validated, $liters, $principal, $rate, $interestAmount, $totalAmount, $termDays, $dailyRepayment, &$createdVoucher) {
            $station = FuelStation::whereKey((int) $validated['fuel_station_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $openExposure = FuelVoucher::where('fuel_station_id', $station->id)
                ->whereIn('status', ['issued', 'approved'])
                ->lockForUpdate()
                ->sum('amount');
            $availableCapacity = max(0, (float) $station->wallet_balance - (float) $openExposure);
            if ($availableCapacity < $principal) {
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

        $repayments = Repayment::with(['lease.vouchers.fuelStation'])
            ->where('user_id', $user->id)
            ->orderByRaw("FIELD(status, 'overdue','pending','paid','defaulted')")
            ->orderBy('due_date')
            ->paginate(20);

        $summary = [
            'pending_count' => Repayment::where('user_id', $user->id)->whereIn('status', ['pending', 'overdue'])->count(),
            'pending_amount' => Repayment::where('user_id', $user->id)->whereIn('status', ['pending', 'overdue'])->sum('amount'),
            'paid_this_month' => Repayment::where('user_id', $user->id)
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

        return view('driver.repayments.index', compact('repayments', 'summary', 'autopay'));
    }

    public function profile()
    {
        $user = Auth::user()->load(['wallet', 'creditLimit']);
        $this->authorizeDriverPortal($user);

        $summary = [
            'total_vouchers' => FuelVoucher::where('user_id', $user->id)->count(),
            'redeemed_vouchers' => FuelVoucher::where('user_id', $user->id)->where('status', 'redeemed')->count(),
            'active_leases' => Lease::where('user_id', $user->id)->where('status', 'active')->count(),
            'paid_repayments' => Repayment::where('user_id', $user->id)->where('status', 'paid')->count(),
        ];

        return view('driver.profile', compact('user', 'summary'));
    }

    public function exportUpcomingRepaymentsPdf()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $repayments = Repayment::with(['lease.vouchers.fuelStation'])
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

        $method = $request->input('payment_method', 'card');
        if (!in_array($method, ['apple_pay', 'google_pay', 'card'], true)) {
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
                    'reference' => (string) ($checkout['reference'] ?? ''),
                    'amount' => (float) $repayment->amount,
                ],
                'Repayment Paystack checkout initialized'
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
                'paystack_card',
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

            return redirect()
                ->route('driver.repayments.index')
                ->with('success', 'Repayment paid successfully. Daily 24-hour auto-pay is now ready.');
        } catch (\Throwable $e) {
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
