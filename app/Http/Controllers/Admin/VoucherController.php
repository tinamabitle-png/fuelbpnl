<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\VoucherStatusChanged;
use App\Models\FuelVoucher;
use App\Models\FuelStation;
use App\Models\Lease;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    private function minRepaymentAmount(): float
    {
        return (float) config('credit.min_repayment_amount', 50);
    }

    public function index(Request $request)
    {
        $query = FuelVoucher::with(['user', 'fuelStation', 'lease']);
        
        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('station_id')) {
            $query->where('fuel_station_id', $request->station_id);
        }
        
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('qr_code', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fuelStation', function ($station) use ($search) {
                        $station->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
            });
        }
        
        $vouchers = $query->latest()->paginate(20)->withQueryString();
        $stations = FuelStation::orderBy('name')->get();
        
        return view('admin.vouchers.index', compact('vouchers', 'stations'));
    }

    public function suggestions(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json(['items' => []]);
        }

        $items = FuelVoucher::query()
            ->with(['user:id,name,email,phone', 'fuelStation:id,name,company,city'])
            ->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('qr_code', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fuelStation', function ($station) use ($search) {
                        $station->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (FuelVoucher $voucher) => [
                'label' => $voucher->code ?: ('Voucher #' . $voucher->id),
                'value' => $voucher->code ?: (string) $voucher->id,
                'meta' => trim(sprintf(
                    '%s • %s • ZAR %s',
                    $voucher->user?->name ?: $voucher->user?->email ?: 'Unknown user',
                    $voucher->fuelStation?->name ?: 'No station',
                    number_format((float) $voucher->amount, 2)
                )),
                'badge' => strtoupper((string) $voucher->status),
                'url' => route('admin.vouchers.show', $voucher),
            ]);

        return response()->json(['items' => $items]);
    }

    public function create()
    {
        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'phone', 'status'])
            ->orderBy('name');

        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $usersQuery->whereHas('roles', fn ($q) => $q->where('name', 'driver'));
        }

        $users = $usersQuery
            ->where('status', 'active')
            ->limit(1000)
            ->get();

        $stations = FuelStation::active()
            ->orderBy('name')
            ->get(['id', 'name', 'city']);

        $settingMap = collect();
        if (Schema::hasTable('settings')) {
            $settingMap = DB::table('settings')
                ->whereIn('key', ['lease_interest_rate', 'lease_term_days'])
                ->pluck('value', 'key');
        }

        $baseRate = (float) ($settingMap['lease_interest_rate'] ?? 5);
        $baseTerm = (int) ($settingMap['lease_term_days'] ?? 30);
        $baseTerm = max(7, min(60, $baseTerm));

        $leaseDefaults = [
            'rate' => $baseRate,
            'term_days' => $baseTerm,
        ];

        return view('admin.vouchers.create', compact('users', 'stations', 'leaseDefaults'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fuel_station_id' => 'required|exists:fuel_stations,id',
            'amount' => 'required|numeric|min:10|max:100000',
            'fuel_type' => 'required|in:petrol,diesel,super',
            'liters' => 'nullable|numeric|min:0.001|max:5000',
            'expires_at' => 'nullable|date|after:now',
            'status' => 'nullable|in:issued,approved',
            'create_and_approve' => 'nullable|boolean',
            'create_bnpl_lease' => 'nullable|boolean',
            'lease_rate' => 'nullable|numeric|min:0|max:100',
            'lease_term_days' => 'nullable|integer|min:7|max:60',
        ]);

        $createBnplLease = $request->boolean('create_bnpl_lease');
        $requestedStatus = $request->boolean('create_and_approve')
            ? 'approved'
            : ($validated['status'] ?? 'issued');

        $leaseRate = (float) ($validated['lease_rate'] ?? 5);
        $leaseTermDays = (int) ($validated['lease_term_days'] ?? 30);
        $principal = (float) $validated['amount'];

        if ($createBnplLease) {
            $interestAmount = round($principal * ($leaseRate / 100), 2);
            $totalAmount = round($principal + $interestAmount, 2);
            $dailyRepayment = round($totalAmount / max($leaseTermDays, 1), 2);

            if ($dailyRepayment < $this->minRepaymentAmount()) {
                return back()
                    ->withErrors([
                        'lease_term_days' => sprintf(
                            'Repayment per day cannot be below R%.2f. Increase amount or reduce repayment days.',
                            $this->minRepaymentAmount()
                        ),
                    ])
                    ->withInput();
            }
        }

        $voucher = null;

        DB::transaction(function () use ($validated, $createBnplLease, $leaseRate, $leaseTermDays, $principal, $requestedStatus, &$voucher) {
            $station = FuelStation::whereKey((int) $validated['fuel_station_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertStationCapacityOrFail($station, $principal);

            $leaseId = null;

            if ($createBnplLease) {
                $interestAmount = round($principal * ($leaseRate / 100), 2);
                $totalAmount = round($principal + $interestAmount, 2);
                $dailyRepayment = round($totalAmount / max($leaseTermDays, 1), 2);

                $lease = Lease::create([
                    'user_id' => (int) $validated['user_id'],
                    'principal_amount' => $principal,
                    'interest_rate' => $leaseRate,
                    'interest_amount' => $interestAmount,
                    'total_amount' => $totalAmount,
                    'term_days' => $leaseTermDays,
                    'daily_repayment' => $dailyRepayment,
                    'status' => 'active',
                    'issued_at' => now(),
                    'due_date' => now()->addDays($leaseTermDays)->toDateString(),
                ]);

                $leaseId = $lease->id;
            }

            $liters = isset($validated['liters'])
                ? (float) $validated['liters']
                : round($principal / 25, 3);

            $voucher = FuelVoucher::create([
                'user_id' => (int) $validated['user_id'],
                'fuel_station_id' => (int) $validated['fuel_station_id'],
                'lease_id' => $leaseId,
                'amount' => $principal,
                'liters' => max(0.001, $liters),
                'fuel_type' => $validated['fuel_type'],
                'status' => $requestedStatus,
                'issued_at' => now(),
                'expires_at' => isset($validated['expires_at']) ? $validated['expires_at'] : now()->addHours(24),
            ]);
        });

        if ($voucher) {
            $this->broadcastVoucherStatus(
                $voucher->fresh(['user', 'fuelStation']),
                $voucher->status === 'approved' ? 'approved' : 'issued'
            );
        }

        return redirect()
            ->route('admin.vouchers.show', $voucher)
            ->with('success', 'Voucher created successfully.');
    }

    public function pending(Request $request)
    {
        $vouchers = FuelVoucher::with(['user', 'fuelStation'])
            ->where('status', 'issued')
            ->where('expires_at', '>', now())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('station_id'), fn ($q) => $q->where('fuel_station_id', $request->integer('station_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('issued_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('issued_at', '<=', $request->string('date_to')))
            ->latest()
            ->paginate(20);

        $stations = FuelStation::orderBy('name')->get();
            
        return view('admin.vouchers.pending', compact('vouchers', 'stations'));
    }

    public function show(FuelVoucher $voucher)
    {
        $voucher->load(['user', 'fuelStation', 'lease.repayments', 'settlement']);
        
        return view('admin.vouchers.show', compact('voucher'));
    }

    public function approve(FuelVoucher $voucher)
    {
        if ($voucher->status !== 'issued') {
            return back()->with('error', 'Voucher cannot be approved.');
        }

        try {
            DB::transaction(function () use (&$voucher) {
                $lockedVoucher = FuelVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
                if ($lockedVoucher->status !== 'issued') {
                    throw new \RuntimeException('Voucher is no longer in ISSUED state.');
                }
                $station = FuelStation::whereKey($lockedVoucher->fuel_station_id)->lockForUpdate()->firstOrFail();
                $this->assertStationCapacityOrFail($station, (float) $lockedVoucher->amount, $lockedVoucher->id);

                $lockedVoucher->update(['status' => 'approved']);
                $voucher = $lockedVoucher->fresh();
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->broadcastVoucherStatus($voucher->fresh(['user', 'fuelStation']), 'approved');
        
        // Send notification to user
        // Notification::send($voucher->user, new VoucherApproved($voucher));
        
        return back()->with('success', 'Voucher approved successfully.');
    }

    public function reject(FuelVoucher $voucher, Request $request)
    {
        $request->validate(['reason' => 'required|string']);
        
        $voucher->update([
            'status' => 'cancelled',
            'transaction_reference' => $request->reason,
        ]);
        
        // Refund if BNPL
        if ($voucher->lease_id && $voucher->user && $voucher->user->wallet) {
            $outstanding = (float) $voucher->user->wallet->outstanding_balance;
            $voucher->user->wallet->decrement('outstanding_balance', min($outstanding, (float) $voucher->amount));
        }

        $this->broadcastVoucherStatus($voucher->fresh(['user', 'fuelStation']), 'cancelled');
        
        return back()->with('success', 'Voucher rejected successfully.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,expire',
            'vouchers' => 'required|array',
            'vouchers.*' => 'exists:fuel_vouchers,id',
        ]);
        
        $vouchers = FuelVoucher::whereIn('id', $request->vouchers)->get();
        
        $skipped = 0;
        foreach ($vouchers as $voucher) {
            if ($request->action === 'approve' && $voucher->status === 'issued') {
                try {
                    DB::transaction(function () use (&$voucher) {
                        $lockedVoucher = FuelVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
                        if ($lockedVoucher->status !== 'issued') {
                            return;
                        }

                        $station = FuelStation::whereKey($lockedVoucher->fuel_station_id)->lockForUpdate()->firstOrFail();
                        $this->assertStationCapacityOrFail($station, (float) $lockedVoucher->amount, $lockedVoucher->id);

                        $lockedVoucher->update(['status' => 'approved']);
                        $voucher = $lockedVoucher->fresh();
                    });

                    if ($voucher->status === 'approved') {
                        $this->broadcastVoucherStatus($voucher->fresh(['user', 'fuelStation']), 'approved');
                    }
                } catch (\Throwable $e) {
                    $skipped++;
                }
            } elseif ($request->action === 'reject') {
                $voucher->update(['status' => 'cancelled']);
                $this->broadcastVoucherStatus($voucher->fresh(['user', 'fuelStation']), 'cancelled');
            } elseif ($request->action === 'expire') {
                $voucher->update(['status' => 'expired']);
                $this->broadcastVoucherStatus($voucher->fresh(['user', 'fuelStation']), 'expired');
            }
        }
        
        $message = count($vouchers) . ' vouchers updated successfully.';
        if ($request->action === 'approve' && $skipped > 0) {
            $message .= " {$skipped} skipped due to insufficient station pre-funded balance.";
        }

        return back()->with('success', $message);
    }

    private function assertStationCapacityOrFail(FuelStation $station, float $newVoucherAmount, ?int $excludeVoucherId = null): void
    {
        $openExposure = FuelVoucher::where('fuel_station_id', $station->id)
            ->whereIn('status', ['issued', 'approved'])
            ->when($excludeVoucherId, fn ($q) => $q->where('id', '!=', $excludeVoucherId))
            ->lockForUpdate()
            ->sum('amount');

        $availableCapacity = max(0, (float) $station->wallet_balance - (float) $openExposure);
        if ($availableCapacity < $newVoucherAmount) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Insufficient station pre-funded balance. Available capacity: R%.2f.',
                    $availableCapacity
                ),
            ]);
        }
    }

    public function export(Request $request)
    {
        $query = FuelVoucher::with(['user', 'fuelStation']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $vouchers = $query->get();
        
        $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(['ID', 'Code', 'User', 'Station', 'Amount', 'Status', 'Issued At', 'Expires At']);
        
        foreach ($vouchers as $voucher) {
            $csv->insertOne([
                $voucher->id,
                $voucher->code,
                $voucher->user->name,
                $voucher->fuelStation->name,
                $voucher->amount,
                $voucher->status,
                $voucher->issued_at->format('Y-m-d H:i:s'),
                $voucher->expires_at->format('Y-m-d H:i:s'),
            ]);
        }
        
        $csv->output('vouchers_' . date('Y-m-d') . '.csv');
    }

    private function broadcastVoucherStatus(FuelVoucher $voucher, string $eventType): void
    {
        try {
            event(new VoucherStatusChanged([
                'event' => $eventType,
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->code,
                'status' => $voucher->status,
                'amount' => (float) $voucher->amount,
                'fuel_type' => $voucher->fuel_type,
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
                'redeemed_at' => optional($voucher->redeemed_at)->toIso8601String(),
            ]));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
