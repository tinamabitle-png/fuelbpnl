<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaystackOtpRequiredException;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class SettlementController extends Controller
{
    private const IMMEDIATE_TOPUP_NOTE_MARKER = 'Immediate pre-funded top-up';

    private const WEEK_DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * Display a listing of settlements.
     */
    public function index(Request $request)
    {
        $query = Settlement::with(['fuelStation'])
            ->withCount('vouchers');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('transaction_reference', 'like', "%{$search}%")
                  ->orWhereHas('fuelStation', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by fuel station
        if ($request->has('fuel_station_id')) {
            $query->where('fuel_station_id', $request->fuel_station_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('settlement_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('settlement_date', '<=', $request->date_to);
        }

        // History filter for immediate payout runs.
        $historyFilter = strtolower((string) $request->get('history', ''));
        if ($historyFilter === 'immediate') {
            $query->where('notes', 'like', '%' . self::IMMEDIATE_TOPUP_NOTE_MARKER . '%');
        } elseif ($historyFilter === 'standard') {
            $query->where(function ($q) {
                $q->whereNull('notes')
                    ->orWhere('notes', 'not like', '%' . self::IMMEDIATE_TOPUP_NOTE_MARKER . '%');
            });
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $settlements = $query->paginate(20);
        $fuelStations = FuelStation::active()->get();

        // Calculate statistics
        $stats = [
            'total_settlements' => Settlement::count(),
            'total_amount' => Settlement::sum('amount'),
            'pending_amount' => Settlement::pending()->sum('amount'),
            'completed_amount' => Settlement::completed()->sum('amount'),
            'pending_count' => Settlement::pending()->count(),
            'completed_count' => Settlement::completed()->count(),
            'failed_count' => Settlement::failed()->count(),
        ];

        $immediateHistoryCount = Settlement::query()
            ->where('notes', 'like', '%' . self::IMMEDIATE_TOPUP_NOTE_MARKER . '%')
            ->count();
        $standardHistoryCount = max(0, (int) $stats['total_settlements'] - (int) $immediateHistoryCount);

        $brandPayouts = $this->buildBrandPayoutSummary();
        $weeklyCycles = [
            'days' => self::WEEK_DAYS,
            'brand_cycles' => $this->getBrandCycles(),
            'station_cycles' => $this->getStationCycles(),
            'today' => strtolower(now()->format('l')),
            'enabled' => $this->weeklyCyclesEnabled(),
            'next_cycle' => $this->getNextCycleInfo(),
        ];

        return view('admin.settlements.index', compact(
            'settlements',
            'fuelStations',
            'stats',
            'brandPayouts',
            'weeklyCycles',
            'immediateHistoryCount',
            'standardHistoryCount'
        ));
    }

    /**
     * Show the form for creating a new settlement.
     */
    public function create(Request $request)
    {
        $fuelStations = FuelStation::active()->get();
        
        // If station_id is provided, get its pending vouchers
        $selectedStation = null;
        $pendingVouchers = collect();
        
        if ($request->has('station_id')) {
            $selectedStation = FuelStation::findOrFail($request->station_id);
            $pendingVouchers = $selectedStation->vouchers()
                ->where('status', 'redeemed')
                ->whereNull('settlement_id')
                ->latest()
                ->get();
        }

        return view('admin.settlements.create', compact('fuelStations', 'selectedStation', 'pendingVouchers'));
    }

    /**
     * Store a newly created settlement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:0.01',
            'voucher_ids' => 'nullable|array',
            'voucher_ids.*' => 'exists:fuel_vouchers,id',
            'payment_method' => 'required|in:paystack_transfer',
            'settlement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $voucherIds = array_values($validated['voucher_ids'] ?? []);
        $vouchers = collect();

        if (!empty($voucherIds)) {
            // Optional: link existing redeemed vouchers to this top-up.
            $vouchers = FuelVoucher::whereIn('id', $voucherIds)
                ->where('fuel_station_id', $validated['fuel_station_id'])
                ->where('status', 'redeemed')
                ->whereNull('settlement_id')
                ->get();

            if ($vouchers->count() !== count($voucherIds)) {
                return back()->with('error', 'Some vouchers are not available for direct bank deposit processing.');
            }

            $totalAmount = (float) $vouchers->sum('amount');
            if (round($totalAmount, 2) !== round((float) $validated['amount'], 2)) {
                return back()->with('error', 'Amount does not match total voucher amount.');
            }
        }

        DB::transaction(function () use ($validated, $vouchers, $voucherIds) {
            // Create the settlement
            $settlement = Settlement::create([
                'fuel_station_id' => $validated['fuel_station_id'],
                'amount' => $validated['amount'],
                'voucher_count' => count($voucherIds),
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'settlement_date' => $validated['settlement_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Associate vouchers with this settlement
            $vouchers->each(function ($voucher) use ($settlement) {
                $voucher->update([
                    'settlement_id' => $settlement->id,
                    'settled_at' => now(),
                ]);
            });

            // Log the creation
            if (function_exists('activity')) {
                activity()
                    ->performedOn($settlement)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'voucher_count' => $settlement->voucher_count,
                        'amount' => $settlement->amount,
                        'station' => $settlement->fuelStation->name,
                    ])
                    ->log('settlement_created');
            } else {
                \Log::info('settlement_created', [
                    'settlement_id' => $settlement->id,
                    'voucher_count' => $settlement->voucher_count,
                    'amount' => (float) $settlement->amount,
                    'station_id' => $settlement->fuel_station_id,
                    'actor_id' => optional(auth()->user())->id,
                ]);
            }
        });

        return redirect()->route('admin.settlements.show', Settlement::latest()->first())
            ->with('success', 'Direct bank deposit created successfully. You can now process it.');
    }

    /**
     * Quick create a pre-funded settlement (before vouchers) from dashboard.
     */
    public function quickTopup(Request $request)
    {
        $validated = $request->validate([
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:0.01',
            'settlement_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $station = FuelStation::active()->find($validated['fuel_station_id']);
        if (!$station) {
            return back()->with('error', 'Selected station must be active.');
        }
        if (!$this->stationReadyForPayout($station)) {
            return back()->with('error', 'Station payout profile is incomplete. Set bank code/name and account number before immediate payment.');
        }

        $settlement = Settlement::create([
            'fuel_station_id' => (int) $validated['fuel_station_id'],
            'amount' => (float) $validated['amount'],
            'voucher_count' => 0,
            'status' => 'pending',
            'payment_method' => 'paystack_transfer',
            'settlement_date' => $validated['settlement_date'] ?? now()->toDateString(),
            'notes' => trim((string) ($validated['notes'] ?? '') . "\nPre-funded franchise/station top-up (created from dashboard)."),
        ]);

        AuditTrailService::record(
            'settlement_created_prefund',
            $settlement,
            [],
            [
                'fuel_station_id' => (int) $settlement->fuel_station_id,
                'amount' => (float) $settlement->amount,
                'status' => (string) $settlement->status,
                'payment_method' => (string) $settlement->payment_method,
            ],
            'Pre-funded settlement created from dashboard'
        );

        return redirect()
            ->route('admin.settlements.show', $settlement)
            ->with('success', 'Pre-funded settlement created. Process it via Paystack when ready.');
    }

    /**
     * Create and immediately process a pre-funded settlement via Paystack direct bank transfer.
     */
    public function quickTopupImmediate(Request $request)
    {
        $validated = $request->validate([
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:0.01',
            'settlement_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'force_weekly_duplicate' => 'nullable|boolean',
            'payout_account_name' => 'nullable|string|max:255',
            'payout_account_number' => 'nullable|string|max:50',
            'payout_bank_name' => 'nullable|string|max:255',
            'payout_bank_code' => 'nullable|string|max:50',
        ]);

        $forceWeeklyDuplicate = (bool) ($validated['force_weekly_duplicate'] ?? false);
        $station = FuelStation::active()->find($validated['fuel_station_id']);
        if (!$station) {
            return back()->with('error', 'Selected station must be active.');
        }

        $updatePayload = [];
        foreach (['payout_account_name', 'payout_account_number', 'payout_bank_name', 'payout_bank_code'] as $field) {
            $value = trim((string) ($validated[$field] ?? ''));
            if ($value !== '') {
                $updatePayload[$field] = $value;
            }
        }
        if (!empty($updatePayload)) {
            $station->update($updatePayload);
            $station->refresh();
        }

        try {
            $settlement = Settlement::create([
                'fuel_station_id' => (int) $validated['fuel_station_id'],
                'amount' => (float) $validated['amount'],
                'voucher_count' => 0,
                'status' => 'pending',
                'payment_method' => 'paystack_transfer',
                'settlement_date' => $validated['settlement_date'] ?? now()->toDateString(),
                'notes' => trim((string) ($validated['notes'] ?? '') . "\n" . self::IMMEDIATE_TOPUP_NOTE_MARKER . ' (created + processed from dashboard).'),
            ]);

            AuditTrailService::record(
                'settlement_created_immediate',
                $settlement,
                [],
                [
                    'fuel_station_id' => (int) $settlement->fuel_station_id,
                    'amount' => (float) $settlement->amount,
                    'status' => (string) $settlement->status,
                    'payment_method' => (string) $settlement->payment_method,
                ],
                'Immediate settlement created from dashboard'
            );

            $this->processSettlementWithReason(
                $settlement,
                $station,
                'Immediate direct deposit settlement',
                null,
                'Immediate payout executed via dashboard.',
                optional(auth()->user())->id,
                $forceWeeklyDuplicate
            );

            return redirect()
                ->route('admin.settlements.show', $settlement)
                ->with('success', 'Immediate direct bank deposit processed successfully via Paystack.');
        } catch (PaystackOtpRequiredException $e) {
            $settlementId = (int) ($e->context['settlement_id'] ?? 0);
            if ($settlementId > 0) {
                return redirect()
                    ->route('admin.settlements.show', $settlementId)
                    ->with('warning', 'Paystack requires OTP to finalize this transfer.')
                    ->with('paystack_otp_required', $e->context);
            }
            return back()->with('warning', 'Paystack requires OTP to finalize this transfer.')->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Immediate payment failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified settlement.
     */
    public function show(Settlement $settlement)
    {
        $settlement->load(['fuelStation', 'vouchers.user']);
        
        return view('admin.settlements.show', compact('settlement'));
    }

    /**
     * Show the form for editing the specified settlement.
     */
    public function edit(Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending direct bank deposits can be edited.');
        }

        $fuelStations = FuelStation::active()->get();
        $settlement->load('vouchers');
        
        return view('admin.settlements.edit', compact('settlement', 'fuelStations'));
    }

    /**
     * Update the specified settlement.
     */
    public function update(Request $request, Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending direct bank deposits can be updated.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:paystack_transfer',
            'settlement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $settlement->update($validated);

        return redirect()->route('admin.settlements.show', $settlement)
            ->with('success', 'Direct bank deposit updated successfully.');
    }

    /**
     * Remove the specified settlement.
     */
    public function destroy(Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending direct bank deposits can be deleted.');
        }

        DB::transaction(function () use ($settlement) {
            // Release vouchers back to unsettled state
            $settlement->vouchers()->update([
                'settlement_id' => null,
                'settled_at' => null,
            ]);

            // Delete the settlement
            $settlement->delete();
        });

        return redirect()->route('admin.settlements.index')
            ->with('success', 'Direct bank deposit deleted successfully.');
    }

    /**
     * Process a pending settlement.
     */
    public function process(Request $request, Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending direct bank deposits can be processed.');
        }

        $validated = $request->validate([
            'force_weekly_duplicate' => 'nullable|boolean',
        ]);
        $forceWeeklyDuplicate = (bool) ($validated['force_weekly_duplicate'] ?? false);

        try {
            $station = $settlement->fuelStation;
            if (!$station) {
                throw new \Exception('Settlement station not found.');
            }

            $this->processSettlementWithReason(
                $settlement,
                $station,
                'Direct deposit settlement',
                null,
                'Wallet top-up completed.',
                optional(auth()->user())->id,
                $forceWeeklyDuplicate
            );

            return back()->with('success', 'Direct bank deposit processed successfully. Station wallet has been credited.');
        } catch (PaystackOtpRequiredException $e) {
            return redirect()
                ->route('admin.settlements.show', $settlement)
                ->with('warning', 'Paystack requires OTP to finalize this transfer.')
                ->with('paystack_otp_required', $e->context);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process direct bank deposit: ' . $e->getMessage());
        }
    }

    /**
     * Mark settlement as failed.
     */
    public function markAsFailed(Request $request, Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending direct bank deposits can be marked as failed.');
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        DB::transaction(function () use ($settlement, $validated) {
            $settlement->markAsFailed($validated['reason']);
            
            // Log the failure
            if (function_exists('activity')) {
                activity()
                    ->performedOn($settlement)
                    ->causedBy(auth()->user())
                    ->withProperties(['reason' => $validated['reason']])
                    ->log('settlement_failed');
            } else {
                \Log::info('settlement_failed', [
                    'settlement_id' => $settlement->id,
                    'reason' => $validated['reason'],
                    'actor_id' => optional(auth()->user())->id,
                ]);
            }
        });

        return back()->with('success', 'Direct bank deposit marked as failed.');
    }

    /**
     * Bulk process settlements.
     */
    public function bulkProcess(Request $request)
    {
        $validated = $request->validate([
            'settlements' => 'required|array',
            'settlements.*' => 'exists:settlements,id',
            'force_weekly_duplicate' => 'nullable|boolean',
        ]);
        $forceWeeklyDuplicate = (bool) ($validated['force_weekly_duplicate'] ?? false);

        $settlements = Settlement::whereIn('id', $validated['settlements'])
            ->pending()
            ->with('fuelStation')
            ->get();

        if ($settlements->isEmpty()) {
            return back()->with('error', 'No pending direct bank deposits selected.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($settlements as $settlement) {
            try {
                DB::transaction(function () use ($settlement, $forceWeeklyDuplicate) {
                    $station = $settlement->fuelStation;
                    if (!$station) {
                        throw new \Exception('Settlement station not found.');
                    }

                    $this->processSettlementWithReason(
                        $settlement,
                        $station,
                        'Bulk direct deposit settlement',
                        null,
                        'Processed via bulk payout action.',
                        optional(auth()->user())->id,
                        $forceWeeklyDuplicate
                    );
                });
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                \Log::error('Failed to process settlement ' . $settlement->id . ': ' . $e->getMessage());
            }
        }

        $message = "Processed {$successCount} direct bank deposits successfully.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} direct bank deposits failed.";
        }

        return back()->with('success', $message);
    }

    /**
     * Process pending settlements for a selected retail brand (or all brands).
     */
    public function processBrand(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'station_id' => 'nullable|integer|exists:fuel_stations,id',
            'force_weekly_duplicate' => 'nullable|boolean',
        ]);

        $requestedBrand = trim((string) $validated['brand']);
        $requestedStationId = isset($validated['station_id']) ? (int) $validated['station_id'] : null;
        $forceWeeklyDuplicate = (bool) ($validated['force_weekly_duplicate'] ?? false);

        $pendingSettlements = Settlement::with('fuelStation')
            ->pending()
            ->whereHas('fuelStation')
            ->get();

        if ($pendingSettlements->isEmpty()) {
            return back()->with('error', 'No pending direct bank deposits available for brand processing.');
        }

        $targets = $pendingSettlements->filter(function (Settlement $settlement) use ($requestedBrand) {
            if ($requestedBrand === '__all__') {
                return true;
            }

            return strcasecmp($this->brandFromStation($settlement->fuelStation), $requestedBrand) === 0;
        });

        if ($requestedStationId) {
            $targets = $targets->filter(fn (Settlement $settlement) => (int) $settlement->fuel_station_id === $requestedStationId);
        }

        if ($targets->isEmpty()) {
            return back()->with('error', 'No pending direct bank deposits found for the selected retail brand.');
        }

        $partnerStationIds = $this->getPartnerStationIds();

        $processed = 0;
        $skipped = 0;
        $nonPartnerSkipped = 0;
        $weeklyDuplicateSkipped = 0;
        $failed = 0;
        $totalAmount = 0.0;

        foreach ($targets as $settlement) {
            try {
                $station = $settlement->fuelStation;
                if (!$station) {
                    $skipped++;
                    continue;
                }

                if (!$this->isPartnerStationId((int) $station->id, $partnerStationIds)) {
                    $nonPartnerSkipped++;
                    continue;
                }

                if (!$this->stationReadyForPayout($station)) {
                    $skipped++;
                    continue;
                }

                if (
                    !$forceWeeklyDuplicate
                    && $this->findWeeklyDuplicateSettlementForStation($station, $settlement->settlement_date, $settlement->id)
                ) {
                    $weeklyDuplicateSkipped++;
                    continue;
                }

                DB::transaction(function () use ($settlement, $station, $forceWeeklyDuplicate) {
                    $this->processSettlementWithReason(
                        $settlement,
                        $station,
                        'Brand payout for ' . $this->brandFromStation($station),
                        $this->buildBrandTransferReference($settlement, $this->brandFromStation($station)),
                        'Brand payout processed via direct deposit.',
                        optional(auth()->user())->id,
                        $forceWeeklyDuplicate
                    );
                });

                $processed++;
                $totalAmount += (float) $settlement->amount;
            } catch (\Throwable $e) {
                $failed++;
                report($e);
            }
        }

        $label = $requestedBrand === '__all__' ? 'all brands' : $requestedBrand;
        $stationLabel = $requestedStationId ? " (station #{$requestedStationId})" : '';
        $message = sprintf(
            'Brand payout run complete for %s: %d processed (ZAR %s), %d skipped, %d weekly-duplicate skipped, %d non-partner skipped, %d failed.',
            $label . $stationLabel,
            $processed,
            number_format($totalAmount, 2),
            $skipped,
            $weeklyDuplicateSkipped,
            $nonPartnerSkipped,
            $failed
        );

        return back()->with($failed > 0 ? 'error' : 'success', $message);
    }

    public function saveBrandCycle(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'day' => ['required', Rule::in(self::WEEK_DAYS)],
            'enabled' => 'nullable|boolean',
        ]);

        $brand = trim((string) $validated['brand']);
        if ($brand === '') {
            return back()->with('error', 'Invalid brand supplied for cycle setup.');
        }

        $cycles = $this->getBrandCycles();
        $cycles[$brand] = [
            'day' => strtolower((string) $validated['day']),
            'enabled' => (bool) ($validated['enabled'] ?? false),
        ];
        $this->setBrandCycles($cycles);

        return back()->with('success', "Weekly payout cycle saved for {$brand}.");
    }

    public function saveStationCycle(Request $request)
    {
        $validated = $request->validate([
            'station_id' => 'required|integer|exists:fuel_stations,id',
            'day' => ['required', Rule::in(self::WEEK_DAYS)],
            'enabled' => 'nullable|boolean',
        ]);

        $station = FuelStation::findOrFail((int) $validated['station_id']);
        if ($station->status !== 'active') {
            return back()->with('error', 'Only active stations can be configured for weekly payout cycles.');
        }

        $cycles = $this->getStationCycles();
        $cycles[(string) $station->id] = [
            'day' => strtolower((string) $validated['day']),
            'enabled' => (bool) ($validated['enabled'] ?? false),
        ];
        $this->setStationCycles($cycles);

        return back()->with('success', "Weekly payout cycle saved for station {$station->name}.");
    }

    public function runDueWeeklyCycles()
    {
        $result = $this->executeDueWeeklyCycles(optional(auth()->user())->id);

        if ((int) ($result['total_pending'] ?? 0) === 0) {
            return back()->with('error', 'No pending direct bank deposits are available for weekly cycle processing.');
        }

        $message = $result['message'];

        return back()->with(((int) ($result['failed'] ?? 0)) > 0 ? 'error' : 'success', $message);
    }

    public function toggleWeeklyCycles(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        $enabled = (int) $validated['enabled'] === 1;
        $this->setWeeklyCyclesEnabled($enabled);

        return back()->with('success', $enabled ? 'Weekly cycle automation enabled.' : 'Weekly cycle automation disabled.');
    }

    public function executeDueWeeklyCycles(?int $actorUserId = null): array
    {
        if (!$this->weeklyCyclesEnabled()) {
            return [
                'today' => strtolower(now()->format('l')),
                'total_pending' => 0,
                'brand_processed' => 0,
                'station_processed' => 0,
                'skipped_blocked' => 0,
                'skipped_non_partner' => 0,
                'skipped_not_due' => 0,
                'failed' => 0,
                'total_amount' => 0.0,
                'message' => 'Weekly cycle automation is currently disabled.',
                'disabled' => true,
            ];
        }

        $today = strtolower(now()->format('l'));
        $brandCycles = $this->getBrandCycles();
        $stationCycles = $this->getStationCycles();
        $partnerStationIds = $this->getPartnerStationIds();

        $pendingSettlements = Settlement::with('fuelStation')
            ->pending()
            ->whereHas('fuelStation', fn ($query) => $query->where('status', 'active'))
            ->get();

        if ($pendingSettlements->isEmpty()) {
            return [
                'today' => $today,
                'total_pending' => 0,
                'brand_processed' => 0,
                'station_processed' => 0,
                'skipped_blocked' => 0,
                'skipped_non_partner' => 0,
                'skipped_not_due' => 0,
                'failed' => 0,
                'total_amount' => 0.0,
                'message' => 'No pending direct bank deposits are available for weekly cycle processing.',
            ];
        }

        $brandProcessed = 0;
        $stationProcessed = 0;
        $skippedNotDue = 0;
        $skippedNonPartner = 0;
        $skippedBlocked = 0;
        $skippedWeeklyDuplicate = 0;
        $failed = 0;
        $totalAmount = 0.0;

        foreach ($pendingSettlements as $settlement) {
            $station = $settlement->fuelStation;
            if (!$station || !$this->stationReadyForPayout($station)) {
                $skippedBlocked++;
                continue;
            }

            $stationCycle = $stationCycles[(string) $station->id] ?? null;
            $processed = false;

            if ($this->isCycleDue($stationCycle, $today)) {
                try {
                    DB::transaction(function () use ($settlement, $station, $actorUserId) {
                        $this->processSettlementWithReason(
                            $settlement,
                            $station,
                            'Weekly station cycle payout',
                            null,
                            'Processed by weekly station cycle.',
                            $actorUserId,
                            false
                        );
                    });
                    $stationProcessed++;
                    $totalAmount += (float) $settlement->amount;
                    $processed = true;
                } catch (\Throwable $e) {
                    $failed++;
                    report($e);
                    continue;
                }
            }

            if ($processed) {
                continue;
            }

            $brand = $this->brandFromStation($station);
            $brandCycle = $brandCycles[$brand] ?? null;
            if (!$this->isCycleDue($brandCycle, $today)) {
                $skippedNotDue++;
                continue;
            }

            if (!$this->isPartnerStationId((int) $station->id, $partnerStationIds)) {
                $skippedNonPartner++;
                continue;
            }

            if ($this->findWeeklyDuplicateSettlementForStation($station, $settlement->settlement_date, $settlement->id)) {
                $skippedWeeklyDuplicate++;
                continue;
            }

            try {
                DB::transaction(function () use ($settlement, $station, $brand, $actorUserId) {
                    $this->processSettlementWithReason(
                        $settlement,
                        $station,
                        'Weekly franchise payout for ' . $brand,
                        $this->buildBrandTransferReference($settlement, $brand),
                        'Processed by weekly franchise cycle.',
                        $actorUserId,
                        false
                    );
                });
                $brandProcessed++;
                $totalAmount += (float) $settlement->amount;
            } catch (\Throwable $e) {
                $failed++;
                report($e);
            }
        }

        $message = sprintf(
            'Weekly cycle run (%s): franchise processed %d, station processed %d, blocked %d, weekly-duplicate skipped %d, non-partner skipped %d, not-due %d, failed %d. Total ZAR %s.',
            ucfirst($today),
            $brandProcessed,
            $stationProcessed,
            $skippedBlocked,
            $skippedWeeklyDuplicate,
            $skippedNonPartner,
            $skippedNotDue,
            $failed,
            number_format($totalAmount, 2)
        );

        return [
            'today' => $today,
            'total_pending' => $pendingSettlements->count(),
            'brand_processed' => $brandProcessed,
            'station_processed' => $stationProcessed,
            'skipped_blocked' => $skippedBlocked,
            'skipped_weekly_duplicate' => $skippedWeeklyDuplicate,
            'skipped_non_partner' => $skippedNonPartner,
            'skipped_not_due' => $skippedNotDue,
            'failed' => $failed,
            'total_amount' => $totalAmount,
            'message' => $message,
        ];
    }

    public function setPartnerStation(Request $request, FuelStation $station)
    {
        $validated = $request->validate([
            'is_partner' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        if ($station->status !== 'active') {
            return back()->with('error', 'Only active stations can be assigned as partner stations.');
        }

        $isPartner = (int) $validated['is_partner'] === 1;
        $partnerStationIds = $this->getPartnerStationIds();

        if ($isPartner) {
            $partnerStationIds[] = (int) $station->id;
        } else {
            $partnerStationIds = array_values(array_filter(
                $partnerStationIds,
                fn (int $id) => $id !== (int) $station->id
            ));
        }

        $this->setPartnerStationIds($partnerStationIds);

        return back()->with(
            'success',
            $isPartner
                ? "{$station->name} marked as Partner Station for brand/franchise bulk payouts."
                : "{$station->name} removed from Partner Station bulk payout list."
        );
    }

    /**
     * Export settlements to CSV.
     */
    public function export(Request $request)
    {
        $settlements = Settlement::with('fuelStation')->get();

        $filename = "settlements_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($settlements) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 compatibility
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'Reference', 'Station', 'Amount', 'Voucher Count', 'Status',
                'Payment Method', 'Settlement Date', 'Processed At',
                'Transaction Reference', 'Notes', 'Created At'
            ]);

            // Data
            foreach ($settlements as $settlement) {
                fputcsv($file, [
                    $settlement->reference,
                    $settlement->fuelStation->name,
                    $settlement->amount,
                    $settlement->voucher_count,
                    $settlement->status,
                    $settlement->payment_method,
                    $settlement->settlement_date->format('Y-m-d'),
                    $settlement->processed_at ? $settlement->processed_at->format('Y-m-d H:i:s') : '',
                    $settlement->transaction_reference ?? '',
                    $settlement->notes ?? '',
                    $settlement->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get pending settlement amount for a station.
     */
    public function stationSearch(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'brand' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:30',
        ]);

        $limit = (int) ($validated['limit'] ?? 12);
        $term = trim((string) ($validated['q'] ?? ''));
        $brand = trim((string) ($validated['brand'] ?? ''));
        $partnerStationIds = $this->getPartnerStationIds();

        $query = FuelStation::query()
            ->active()
            ->orderBy('company')
            ->orderBy('name');

        if ($brand !== '') {
            $query->where(function ($q) use ($brand) {
                $q->where('company', 'like', "%{$brand}%")
                    ->orWhere('name', 'like', "%{$brand}%");
            });
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%");
            });
        }

        $items = $query
            ->limit($limit)
            ->get(['id', 'name', 'company', 'city', 'payout_account_name', 'payout_account_number', 'payout_bank_code', 'payout_bank_name'])
            ->map(function (FuelStation $station) use ($partnerStationIds) {
                $label = trim(((string) $station->company !== '' ? $station->company . ' - ' : '') . $station->name);

                return [
                    'id' => (int) $station->id,
                    'name' => (string) $station->name,
                    'company' => (string) ($station->company ?? ''),
                    'city' => (string) ($station->city ?? ''),
                    'label' => $label !== '' ? $label : ('Station #' . $station->id),
                    'partner' => $this->isPartnerStationId((int) $station->id, $partnerStationIds),
                    'payout_ready' => $this->stationReadyForPayout($station),
                    'payout_account_name' => (string) ($station->payout_account_name ?? ''),
                    'payout_account_number' => (string) ($station->payout_account_number ?? ''),
                    'payout_bank_name' => (string) ($station->payout_bank_name ?? ''),
                    'payout_bank_code' => (string) ($station->payout_bank_code ?? ''),
                ];
            })
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * Get pending settlement amount for a station.
     */
    public function getPendingAmount(Request $request)
    {
        $request->validate([
            'fuel_station_id' => 'required|exists:fuel_stations,id',
        ]);

        $station = FuelStation::findOrFail($request->fuel_station_id);
        $pendingAmount = $station->getPendingSettlementAmount();
        $pendingVouchers = $station->vouchers()
            ->where('status', 'redeemed')
            ->whereNull('settlement_id')
            ->count();

        return response()->json([
            'pending_amount' => $pendingAmount,
            'pending_vouchers' => $pendingVouchers,
        ]);
    }

    /**
     * Get settlement statistics.
     */
    public function statistics()
    {
        $totalSettlements = Settlement::count();
        $totalAmount = Settlement::sum('amount');
        
        $statusStats = Settlement::select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as amount'))
            ->groupBy('status')
            ->get();
            
        $dailyStats = Settlement::selectRaw('DATE(settlement_date) as date, count(*) as count, sum(amount) as amount')
            ->where('settlement_date', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $stationStats = Settlement::with('fuelStation')
            ->select('fuel_station_id', DB::raw('count(*) as count'), DB::raw('sum(amount) as amount'))
            ->groupBy('fuel_station_id')
            ->orderBy('amount', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_settlements' => $totalSettlements,
            'total_amount' => $totalAmount,
            'status_stats' => $statusStats,
            'daily_stats' => $dailyStats,
            'station_stats' => $stationStats,
        ]);
    }

    private function brandFromStation(?FuelStation $station): string
    {
        if (!$station) {
            return 'Unassigned';
        }

        $brand = trim((string) ($station->company ?: $station->name ?: 'Unassigned'));
        return $brand !== '' ? $brand : 'Unassigned';
    }

    private function stationReadyForPayout(?FuelStation $station): bool
    {
        if (!$station) {
            return false;
        }

        $hasAccountNumber = trim((string) $station->payout_account_number) !== '';
        $hasBankInfo = trim((string) $station->payout_bank_code) !== '' || trim((string) $station->payout_bank_name) !== '';

        return $hasAccountNumber && $hasBankInfo;
    }

    private function buildBrandTransferReference(Settlement $settlement, string $brand): string
    {
        $slug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $brand), 0, 8));
        $slug = $slug !== '' ? $slug : 'BRAND';

        return sprintf(
            'BRAND-%s-%s-%s',
            $slug,
            now()->format('YmdHis'),
            str_pad((string) $settlement->id, 4, '0', STR_PAD_LEFT)
        );
    }

    private function buildBrandPayoutSummary()
    {
        $partnerStationIds = $this->getPartnerStationIds();

        $stations = FuelStation::query()
            ->active()
            ->orderBy('company')
            ->orderBy('name')
            ->get();

        $pending = Settlement::with('fuelStation')
            ->pending()
            ->whereHas('fuelStation', function ($query) {
                $query->where('status', 'active');
            })
            ->get();

        return $stations
            ->groupBy(fn (FuelStation $station) => $this->brandFromStation($station))
            ->map(function ($brandStations, $brand) use ($pending, $partnerStationIds) {
                $brandStationIds = $brandStations->pluck('id')->map(fn ($id) => (int) $id)->all();
                $items = $pending->filter(fn (Settlement $settlement) => in_array((int) $settlement->fuel_station_id, $brandStationIds, true));
                $count = $items->count();
                $amount = (float) $items->sum('amount');

                $eligibleCount = $items->filter(function (Settlement $settlement) use ($partnerStationIds) {
                    return $this->isPartnerStationId((int) $settlement->fuel_station_id, $partnerStationIds)
                        && $this->stationReadyForPayout($settlement->fuelStation);
                })->count();

                $partnerStationCount = $brandStations->filter(
                    fn (FuelStation $station) => $this->isPartnerStationId((int) $station->id, $partnerStationIds)
                )->count();

                return [
                    'brand' => $brand,
                    'count' => $count,
                    'amount' => $amount,
                    'ready_count' => $eligibleCount,
                    'blocked_count' => max($count - $eligibleCount, 0),
                    'partner_station_count' => $partnerStationCount,
                    'total_station_count' => $brandStations->count(),
                    'stations' => $brandStations
                        ->map(function (FuelStation $station) use ($items, $partnerStationIds) {
                            $stationSettlementItems = $items->filter(
                                fn (Settlement $settlement) => (int) $settlement->fuel_station_id === (int) $station->id
                            );
                            $isPartner = $this->isPartnerStationId((int) $station->id, $partnerStationIds);
                            $isReady = $this->stationReadyForPayout($station);

                            return [
                                'id' => (int) $station->id,
                                'name' => (string) $station->name,
                                'partner' => $isPartner,
                                'ready' => $isReady,
                                'pending_count' => $stationSettlementItems->count(),
                                'pending_amount' => (float) $stationSettlementItems->sum('amount'),
                                'bulk_eligible' => $isPartner && $isReady,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    private function getPartnerStationIds(): array
    {
        $row = DB::table('settings')->where('key', 'partner_station_ids')->first();
        if (!$row || trim((string) $row->value) === '') {
            return [];
        }

        $decoded = json_decode((string) $row->value, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $decoded)));
    }

    private function setPartnerStationIds(array $ids): void
    {
        $cleanIds = array_values(array_unique(array_map('intval', $ids)));
        DB::table('settings')->updateOrInsert(
            ['key' => 'partner_station_ids'],
            [
                'value' => json_encode($cleanIds),
                'group' => 'settlements',
            ]
        );
    }

    private function isPartnerStationId(int $stationId, array $partnerStationIds): bool
    {
        return in_array($stationId, $partnerStationIds, true);
    }

    private function initiateSettlementPayout(
        Settlement $settlement,
        FuelStation $station,
        string $reason,
        ?string $preferredReference = null
    ): array {
        $secret = (string) config('services.paystack.secret_key');
        $baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
        $currency = strtoupper((string) config('services.paystack.currency', 'ZAR'));
        $transferSource = (string) config('services.paystack.transfer_source', 'balance');
        $timeout = max(5, (int) config('services.paystack.timeout', 15));
        $recipientCode = trim((string) $station->payout_recipient_code);
        $accountNumber = trim((string) $station->payout_account_number);
        $accountName = trim((string) ($station->payout_account_name ?: $station->name));
        $bankCode = trim((string) $station->payout_bank_code);
        $bankName = trim((string) $station->payout_bank_name);

        if ($bankCode === '' && $bankName !== '' && $this->paystackConfigured()) {
            $resolved = $this->lookupPaystackBankCode($secret, $baseUrl, $timeout, $bankName);
            if ($resolved !== null) {
                $bankCode = $resolved;
                $station->update(['payout_bank_code' => $bankCode]);
            }
        }

        $missing = [];
        if (!$this->paystackConfigured()) {
            $missing[] = 'PAYSTACK_SECRET_KEY';
        }
        if ($accountNumber === '') {
            $missing[] = 'station payout_account_number';
        }
        if ($bankCode === '') {
            $missing[] = $bankName !== ''
                ? "station payout_bank_code (could not resolve bank name '{$bankName}' on Paystack)"
                : 'station payout_bank_code (or payout_bank_name)';
        }
        if ($accountName === '') {
            $missing[] = 'station payout_account_name';
        }

        if (!empty($missing)) {
            throw new \Exception(
                'Paystack payout is required. Missing: ' . implode(', ', $missing) . '.'
            );
        }

        if ($recipientCode === '') {
            $recipientPayload = [
                'type' => 'nuban',
                'name' => $accountName,
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'currency' => $currency,
            ];

            $recipientResp = Http::withToken($secret)
                ->acceptJson()
                ->timeout($timeout)
                ->post($baseUrl . '/transferrecipient', $recipientPayload);

            if (!$recipientResp->successful() || !$recipientResp->json('status')) {
                throw new \Exception('Paystack recipient setup failed: ' . $this->extractPaystackError($recipientResp));
            }

            $recipientCode = (string) $recipientResp->json('data.recipient_code');
            if ($recipientCode === '') {
                throw new \Exception('Paystack recipient code missing.');
            }

            $station->update(['payout_recipient_code' => $recipientCode]);
        }

        $transferReference = $preferredReference ?: ('BW-PSTK-' . now()->format('YmdHis') . '-' . str_pad((string) $settlement->id, 4, '0', STR_PAD_LEFT));

        $transferPayload = [
            'source' => $transferSource,
            'amount' => (int) round(((float) $settlement->amount) * 100),
            'recipient' => $recipientCode,
            'reason' => $reason,
            'reference' => $transferReference,
        ];

        $transferResp = Http::withToken($secret)
            ->acceptJson()
            ->timeout($timeout)
            ->post($baseUrl . '/transfer', $transferPayload);

        if (!$transferResp->successful() || !$transferResp->json('status')) {
            throw new \Exception('Paystack transfer initiation failed: ' . $this->extractPaystackError($transferResp));
        }

        $transferData = (array) $transferResp->json('data', []);
        $finalRef = (string) ($transferData['reference'] ?? $transferReference);
        $providerStatus = strtolower((string) ($transferData['status'] ?? ''));
        $providerMessage = (string) ($transferData['message'] ?? '');

        \Log::info('paystack_transfer_initiated', [
            'settlement_id' => $settlement->id,
            'reference' => $finalRef,
            'provider_status' => $providerStatus,
            'provider_message' => $providerMessage,
            'station_id' => $station->id,
            'amount' => (float) $settlement->amount,
        ]);

        AuditTrailService::record(
            'paystack_transfer_initiated',
            $settlement,
            [],
            [
                'reference' => $finalRef,
                'provider_status' => $providerStatus,
                'provider_message' => $providerMessage,
                'amount' => (float) $settlement->amount,
                'station_id' => (int) $station->id,
            ],
            'Paystack transfer initiated for settlement'
        );

        return [
            'method' => 'paystack_transfer',
            'reference' => $finalRef,
            'provider_status' => $providerStatus,
            'provider_message' => $providerMessage,
            'transfer_code' => (string) ($transferData['transfer_code'] ?? ''),
        ];
    }

    private function paystackConfigured(): bool
    {
        return trim((string) config('services.paystack.secret_key')) !== '';
    }

    private function lookupPaystackBankCode(string $secret, string $baseUrl, int $timeout, string $bankName): ?string
    {
        try {
            $banks = $this->getPaystackBanks($secret, $baseUrl, $timeout);
            if ($banks->isEmpty()) {
                return null;
            }

            $needle = strtolower(trim($bankName));
            if ($needle === '') {
                return null;
            }

            $exact = $banks->first(function ($bank) use ($needle) {
                $name = strtolower(trim((string) ($bank['name'] ?? '')));
                return $name !== '' && $name === $needle;
            });
            if (is_array($exact) && !empty($exact['code'])) {
                return (string) $exact['code'];
            }

            $partial = $banks->first(function ($bank) use ($needle) {
                $name = strtolower(trim((string) ($bank['name'] ?? '')));
                return $name !== '' && (str_contains($name, $needle) || str_contains($needle, $name));
            });
            if (is_array($partial) && !empty($partial['code'])) {
                return (string) $partial['code'];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    public function paystackBanks(Request $request)
    {
        if (!$this->paystackConfigured()) {
            return response()->json([
                'ok' => false,
                'configured' => false,
                'items' => [],
                'message' => 'PAYSTACK_SECRET_KEY is missing.',
            ], 422);
        }

        $secret = (string) config('services.paystack.secret_key');
        $baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
        $timeout = max(5, (int) config('services.paystack.timeout', 15));
        $query = strtolower(trim((string) $request->input('q', '')));

        try {
            $banks = $this->getPaystackBanks($secret, $baseUrl, $timeout)
                ->filter(function ($bank) use ($query) {
                    if ($query === '') {
                        return true;
                    }
                    $name = strtolower(trim((string) ($bank['name'] ?? '')));
                    $code = strtolower(trim((string) ($bank['code'] ?? '')));
                    return str_contains($name, $query) || str_contains($code, $query);
                })
                ->map(function ($bank) {
                    return [
                        'name' => (string) ($bank['name'] ?? ''),
                        'code' => (string) ($bank['code'] ?? ''),
                    ];
                })
                ->filter(fn ($bank) => $bank['name'] !== '' && $bank['code'] !== '')
                ->sortBy('name')
                ->values()
                ->take(300);

            return response()->json([
                'ok' => true,
                'configured' => true,
                'items' => $banks,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'configured' => true,
                'items' => [],
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function getPaystackBanks(string $secret, string $baseUrl, int $timeout)
    {
        $cacheKey = 'paystack:banks:za:' . substr(sha1($secret . '|' . $baseUrl), 0, 16);

        return cache()->remember($cacheKey, now()->addHours(6), function () use ($secret, $baseUrl, $timeout) {
            $resp = Http::withToken($secret)
                ->acceptJson()
                ->timeout($timeout)
                ->get($baseUrl . '/bank', [
                    'country' => 'south africa',
                    'perPage' => 500,
                ]);

            if (!$resp->successful() || !$resp->json('status')) {
                throw new \Exception('Unable to fetch Paystack bank list: ' . $this->extractPaystackError($resp));
            }

            return collect((array) $resp->json('data'));
        });
    }

    public function paystackHealth(Request $request)
    {
        if (!$this->paystackConfigured()) {
            return response()->json([
                'ok' => false,
                'configured' => false,
                'message' => 'PAYSTACK_SECRET_KEY is missing.',
            ], 422);
        }

        $secret = (string) config('services.paystack.secret_key');
        $baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
        $timeout = max(5, (int) config('services.paystack.timeout', 15));

        $resp = Http::withToken($secret)
            ->acceptJson()
            ->timeout($timeout)
            ->get($baseUrl . '/balance');

        if (!$resp->successful() || !$resp->json('status')) {
            return response()->json([
                'ok' => false,
                'configured' => true,
                'message' => $this->extractPaystackError($resp),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'configured' => true,
            'currency' => strtoupper((string) config('services.paystack.currency', 'ZAR')),
            'message' => 'Paystack connection is healthy.',
        ]);
    }

    public function verifyPaystackTransfer(Settlement $settlement)
    {
        $reference = trim((string) $settlement->transaction_reference);
        if ($reference === '') {
            return back()->with('error', 'This settlement has no Paystack transaction reference to verify.');
        }

        if (!$this->paystackConfigured()) {
            return back()->with('error', 'PAYSTACK_SECRET_KEY is missing.');
        }

        $secret = (string) config('services.paystack.secret_key');
        $baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
        $timeout = max(5, (int) config('services.paystack.timeout', 15));

        try {
            $resp = Http::withToken($secret)
                ->acceptJson()
                ->timeout($timeout)
                ->get($baseUrl . '/transfer/verify/' . urlencode($reference));

            if (!$resp->successful() || !$resp->json('status')) {
                return back()->with('error', 'Paystack verify failed: ' . $this->extractPaystackError($resp));
            }

            $data = (array) $resp->json('data', []);
            $paystackStatus = (string) ($data['status'] ?? 'unknown');
            $amountMinor = (int) ($data['amount'] ?? 0);
            $amount = $amountMinor > 0 ? $amountMinor / 100 : null;

            AuditTrailService::record(
                'paystack_transfer_verified',
                $settlement,
                [],
                [
                    'reference' => (string) ($data['reference'] ?? $reference),
                    'status' => (string) ($data['status'] ?? 'unknown'),
                    'amount' => $amount,
                    'currency' => (string) ($data['currency'] ?? config('services.paystack.currency', 'ZAR')),
                ],
                'Manual Paystack transfer verification'
            );

            return back()
                ->with('success', 'Paystack verification completed for reference ' . $reference . '.')
                ->with('paystack_verify', [
                    'reference' => (string) ($data['reference'] ?? $reference),
                    'status' => $paystackStatus,
                    'amount' => $amount,
                    'currency' => (string) ($data['currency'] ?? config('services.paystack.currency', 'ZAR')),
                    'recipient' => (string) ($data['recipient']['details']['account_name'] ?? $data['recipient']['recipient_code'] ?? ''),
                    'reason' => (string) ($data['reason'] ?? ''),
                    'transferred_at' => (string) ($data['transferred_at'] ?? ''),
                ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Paystack verify failed: ' . $e->getMessage());
        }
    }

    public function finalizePaystackOtp(Request $request, Settlement $settlement)
    {
        $validated = $request->validate([
            'otp' => 'required|string|min:4|max:12',
        ]);

        if (!$this->paystackConfigured()) {
            return back()->with('error', 'PAYSTACK_SECRET_KEY is missing.');
        }

        $cacheKey = 'settlement:otp:' . $settlement->id;
        $otpCtx = cache()->get($cacheKey);
        if (!is_array($otpCtx) || empty($otpCtx['transfer_code'])) {
            return back()->with('error', 'OTP context expired or missing. Re-run Process via Paystack to generate a fresh OTP prompt.');
        }

        $secret = (string) config('services.paystack.secret_key');
        $baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
        $timeout = max(5, (int) config('services.paystack.timeout', 15));
        $transferCode = (string) $otpCtx['transfer_code'];
        $reference = (string) ($otpCtx['reference'] ?? $settlement->transaction_reference);

        try {
            $finalizeResp = Http::withToken($secret)
                ->acceptJson()
                ->timeout($timeout)
                ->post($baseUrl . '/transfer/finalize_transfer', [
                    'transfer_code' => $transferCode,
                    'otp' => trim((string) $validated['otp']),
                ]);

            if (!$finalizeResp->successful() || !$finalizeResp->json('status')) {
                AuditTrailService::record(
                    'paystack_otp_finalize_failed',
                    $settlement,
                    [],
                    [
                        'reference' => (string) $reference,
                        'transfer_code' => (string) $transferCode,
                        'error' => $this->extractPaystackError($finalizeResp),
                    ],
                    'Paystack OTP finalize failed'
                );
                return back()->with('error', 'Paystack OTP finalize failed: ' . $this->extractPaystackError($finalizeResp));
            }

            $verified = $this->verifyPaystackTransferReferenceOrFail($reference);

            DB::transaction(function () use ($settlement, $verified, $reference) {
                if ($settlement->status === 'completed') {
                    return;
                }

                $station = FuelStation::whereKey($settlement->fuel_station_id)->lockForUpdate()->firstOrFail();

                $settlement->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'payment_method' => 'paystack_transfer',
                    'transaction_reference' => $reference,
                    'notes' => trim((string) $settlement->notes . "\nOTP finalized. Paystack verify: " . strtoupper((string) ($verified['status'] ?? 'success'))),
                ]);

                $station->addToWallet((float) $settlement->amount, 'Settlement top-up (OTP): ' . $settlement->reference);
            });

            cache()->forget($cacheKey);

            AuditTrailService::record(
                'paystack_otp_finalized',
                $settlement,
                ['status' => 'pending'],
                [
                    'status' => 'completed',
                    'reference' => (string) $reference,
                    'amount' => (float) $settlement->amount,
                ],
                'Paystack OTP finalized and settlement completed'
            );

            return redirect()
                ->route('admin.settlements.show', $settlement)
                ->with('success', 'OTP finalized and payout verified successfully.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'OTP finalize failed: ' . $e->getMessage());
        }
    }

    private function extractPaystackError($response): string
    {
        try {
            $json = $response->json();
            if (is_array($json)) {
                $message = (string) ($json['message'] ?? '');
                if ($message !== '') {
                    return $message;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to status text.
        }

        return 'HTTP ' . (string) $response->status();
    }

    private function verifyPaystackTransferReferenceOrFail(string $reference): array
    {
        if ($reference === '') {
            throw new \Exception('Missing Paystack transfer reference for verification.');
        }

        if (!$this->paystackConfigured()) {
            throw new \Exception('PAYSTACK_SECRET_KEY is missing.');
        }

        $secret = (string) config('services.paystack.secret_key');
        $baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
        $timeout = max(5, (int) config('services.paystack.timeout', 15));

        $resp = Http::withToken($secret)
            ->acceptJson()
            ->timeout($timeout)
            ->get($baseUrl . '/transfer/verify/' . urlencode($reference));

        if (!$resp->successful() || !$resp->json('status')) {
            throw new \Exception('Paystack verify failed: ' . $this->extractPaystackError($resp));
        }

        $data = (array) $resp->json('data', []);
        $status = strtolower((string) ($data['status'] ?? ''));
        if (!in_array($status, ['success', 'successful'], true)) {
            throw new \Exception('Paystack transfer not successful yet. Current status: ' . strtoupper($status ?: 'UNKNOWN'));
        }

        return $data;
    }

    private function processSettlementWithReason(
        Settlement $settlement,
        FuelStation $station,
        string $reason,
        ?string $preferredReference,
        string $noteLine,
        ?int $actorUserId = null,
        bool $forceWeeklyDuplicate = false
    ): void {
        $duplicate = $this->findWeeklyDuplicateSettlementForStation($station, $settlement->settlement_date, $settlement->id);
        if ($duplicate && !$forceWeeklyDuplicate) {
            throw new \Exception(
                "Station already paid this week ({$duplicate->reference}). Use force to allow another payout this week."
            );
        }

        $payout = $this->initiateSettlementPayout(
            $settlement,
            $station,
            $reason,
            $preferredReference
        );

        if (($payout['provider_status'] ?? '') === 'otp') {
            cache()->put('settlement:otp:' . $settlement->id, [
                'transfer_code' => (string) ($payout['transfer_code'] ?? ''),
                'reference' => (string) ($payout['reference'] ?? ''),
            ], now()->addMinutes(20));

            $settlement->update([
                'payment_method' => 'paystack_transfer',
                'transaction_reference' => $payout['reference'],
                'notes' => trim((string) $settlement->notes . "\nPaystack OTP required for reference " . $payout['reference']),
            ]);

            AuditTrailService::record(
                'paystack_transfer_otp_required',
                $settlement,
                ['status' => (string) $settlement->status],
                [
                    'status' => 'pending',
                    'reference' => (string) ($payout['reference'] ?? ''),
                    'transfer_code' => (string) ($payout['transfer_code'] ?? ''),
                ],
                'Paystack transfer requires OTP'
            );

            throw new PaystackOtpRequiredException('Paystack OTP required.', [
                'settlement_id' => $settlement->id,
                'reference' => (string) ($payout['reference'] ?? ''),
                'transfer_code' => (string) ($payout['transfer_code'] ?? ''),
            ]);
        }

        $verified = $this->verifyPaystackTransferReferenceOrFail((string) $payout['reference']);
        $verifiedStatus = strtoupper((string) ($verified['status'] ?? 'success'));
        $verifiedAt = (string) ($verified['transferred_at'] ?? now()->toIso8601String());

        DB::transaction(function () use ($settlement, $station, $payout, $noteLine, $verifiedStatus, $verifiedAt) {
            $oldStatus = (string) $settlement->status;
            $settlement->update([
                'status' => 'completed',
                'processed_at' => now(),
                'payment_method' => $payout['method'],
                'transaction_reference' => $payout['reference'],
                'notes' => trim((string) $settlement->notes . "\n" . $noteLine . "\nPaystack verify: {$verifiedStatus} at {$verifiedAt}"),
            ]);

            $station->addToWallet((float) $settlement->amount, 'Settlement top-up: ' . $settlement->reference);

            AuditTrailService::record(
                'settlement_completed',
                $settlement,
                ['status' => $oldStatus],
                [
                    'status' => 'completed',
                    'transaction_reference' => (string) $payout['reference'],
                    'verified_status' => $verifiedStatus,
                    'verified_at' => $verifiedAt,
                    'amount' => (float) $settlement->amount,
                ],
                'Settlement completed and wallet credited'
            );
        });

        if (function_exists('activity')) {
            $actor = null;
            if ($actorUserId) {
                $actor = \App\Models\User::find($actorUserId);
            }
            if (!$actor && auth()->check()) {
                $actor = auth()->user();
            }

            $log = activity()->performedOn($settlement);
            if ($actor) {
                $log->causedBy($actor);
            }

            $log
                ->withProperties([
                    'station_id' => $station->id,
                    'amount' => (float) $settlement->amount,
                    'transaction_reference' => $payout['reference'],
                    'reason' => $reason,
                ])
                ->log('weekly_cycle_settlement_processed');
        } else {
            \Log::info('weekly_cycle_settlement_processed', [
                'settlement_id' => $settlement->id,
                'station_id' => $station->id,
                'amount' => (float) $settlement->amount,
                'transaction_reference' => $payout['reference'],
                'reason' => $reason,
                'actor_id' => $actorUserId ?: optional(auth()->user())->id,
            ]);
        }
    }

    private function getBrandCycles(): array
    {
        $raw = DB::table('settings')->where('key', 'weekly_brand_cycles')->value('value');
        if (!$raw) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $brand => $cycle) {
            $name = trim((string) $brand);
            $day = strtolower((string) ($cycle['day'] ?? ''));
            if ($name === '' || !in_array($day, self::WEEK_DAYS, true)) {
                continue;
            }

            $normalized[$name] = [
                'day' => $day,
                'enabled' => (bool) ($cycle['enabled'] ?? false),
            ];
        }

        return $normalized;
    }

    private function setBrandCycles(array $cycles): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'weekly_brand_cycles'],
            [
                'value' => json_encode($cycles),
                'group' => 'settlements',
            ]
        );
    }

    private function getStationCycles(): array
    {
        $raw = DB::table('settings')->where('key', 'weekly_station_cycles')->value('value');
        if (!$raw) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $stationId => $cycle) {
            $id = (int) $stationId;
            $day = strtolower((string) ($cycle['day'] ?? ''));
            if ($id <= 0 || !in_array($day, self::WEEK_DAYS, true)) {
                continue;
            }

            $normalized[(string) $id] = [
                'day' => $day,
                'enabled' => (bool) ($cycle['enabled'] ?? false),
            ];
        }

        return $normalized;
    }

    private function setStationCycles(array $cycles): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'weekly_station_cycles'],
            [
                'value' => json_encode($cycles),
                'group' => 'settlements',
            ]
        );
    }

    private function isCycleDue(?array $cycle, string $today): bool
    {
        if (!$cycle) {
            return false;
        }

        return (bool) ($cycle['enabled'] ?? false)
            && strtolower((string) ($cycle['day'] ?? '')) === strtolower($today);
    }

    private function findWeeklyDuplicateSettlementForStation(
        FuelStation $station,
        $anchorDate = null,
        ?int $excludeSettlementId = null
    ): ?Settlement {
        $anchor = $anchorDate ? \Illuminate\Support\Carbon::parse($anchorDate) : now();
        $weekStart = $anchor->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->startOfDay();
        $weekEnd = $anchor->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY)->endOfDay();

        $query = Settlement::query()
            ->where('fuel_station_id', $station->id)
            ->where('status', 'completed')
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('processed_at', [$weekStart, $weekEnd])
                    ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                        $q2->whereNull('processed_at')
                            ->whereBetween('settlement_date', [
                                $weekStart->toDateString(),
                                $weekEnd->toDateString(),
                            ]);
                    });
            });

        if ($excludeSettlementId) {
            $query->where('id', '!=', $excludeSettlementId);
        }

        return $query->orderByDesc('processed_at')->orderByDesc('id')->first();
    }

    private function weeklyCyclesEnabled(): bool
    {
        $raw = DB::table('settings')->where('key', 'weekly_cycles_enabled')->value('value');
        if ($raw === null) {
            return true;
        }

        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    private function setWeeklyCyclesEnabled(bool $enabled): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'weekly_cycles_enabled'],
            [
                'value' => $enabled ? '1' : '0',
                'group' => 'settlements',
            ]
        );
    }

    private function getNextCycleInfo(): ?array
    {
        $days = self::WEEK_DAYS;
        $today = strtolower(now()->format('l'));
        $todayIndex = array_search($today, $days, true);
        $todayIndex = $todayIndex === false ? 0 : (int) $todayIndex;

        $entries = [];
        foreach ($this->getBrandCycles() as $brand => $cycle) {
            if (empty($cycle['enabled']) || !in_array($cycle['day'], $days, true)) {
                continue;
            }
            $entries[] = [
                'type' => 'brand',
                'name' => (string) $brand,
                'day' => (string) $cycle['day'],
            ];
        }

        $stationNames = FuelStation::query()->pluck('name', 'id');
        foreach ($this->getStationCycles() as $stationId => $cycle) {
            if (empty($cycle['enabled']) || !in_array($cycle['day'], $days, true)) {
                continue;
            }
            $entries[] = [
                'type' => 'station',
                'name' => (string) ($stationNames[(int) $stationId] ?? ('Station #' . $stationId)),
                'day' => (string) $cycle['day'],
            ];
        }

        if (empty($entries)) {
            return null;
        }

        $best = null;
        foreach ($entries as $entry) {
            $dayIndex = array_search($entry['day'], $days, true);
            if ($dayIndex === false) {
                continue;
            }

            $offset = ($dayIndex - $todayIndex + 7) % 7;
            $candidate = now()->copy()->startOfDay()->addDays($offset)->setTime(6, 10, 0);
            if ($offset === 0 && now()->greaterThanOrEqualTo($candidate)) {
                $candidate->addWeek();
            }

            if (!$best || $candidate->lt($best['at'])) {
                $best = [
                    'at' => $candidate,
                    'type' => $entry['type'],
                    'name' => $entry['name'],
                    'day' => $entry['day'],
                ];
            }
        }

        if (!$best) {
            return null;
        }

        return [
            'type' => $best['type'],
            'name' => $best['name'],
            'day' => $best['day'],
            'at' => $best['at']->toDateTimeString(),
            'human' => $best['at']->diffForHumans(),
            'label' => $best['at']->format('D, d M Y H:i') . ' SAST',
        ];
    }
}
