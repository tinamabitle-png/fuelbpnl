<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SettlementController extends Controller
{
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

        return view('admin.settlements.index', compact('settlements', 'fuelStations', 'stats'));
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
            'amount' => 'required|numeric|min:0',
            'voucher_ids' => 'required|array',
            'voucher_ids.*' => 'exists:fuel_vouchers,id',
            'payment_method' => 'required|in:bank_transfer,mpesa,equity,airtel_money',
            'settlement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Validate that all vouchers belong to the selected station and are redeemable
        $vouchers = FuelVoucher::whereIn('id', $validated['voucher_ids'])
            ->where('fuel_station_id', $validated['fuel_station_id'])
            ->where('status', 'redeemed')
            ->whereNull('settlement_id')
            ->get();

        if ($vouchers->count() !== count($validated['voucher_ids'])) {
            return back()->with('error', 'Some vouchers are not available for settlement.');
        }

        // Calculate total amount from vouchers
        $totalAmount = $vouchers->sum('amount');
        
        if ($totalAmount != $validated['amount']) {
            return back()->with('error', 'Amount does not match total voucher amount.');
        }

        DB::transaction(function () use ($validated, $vouchers) {
            // Create the settlement
            $settlement = Settlement::create([
                'fuel_station_id' => $validated['fuel_station_id'],
                'amount' => $validated['amount'],
                'voucher_count' => count($validated['voucher_ids']),
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
            activity()
                ->performedOn($settlement)
                ->causedBy(auth()->user())
                ->withProperties([
                    'voucher_count' => $settlement->voucher_count,
                    'amount' => $settlement->amount,
                    'station' => $settlement->fuelStation->name,
                ])
                ->log('settlement_created');
        });

        return redirect()->route('admin.settlements.show', Settlement::latest()->first())
            ->with('success', 'Settlement created successfully. You can now process it.');
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
            return back()->with('error', 'Only pending settlements can be edited.');
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
            return back()->with('error', 'Only pending settlements can be updated.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,mpesa,equity,airtel_money',
            'settlement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $settlement->update($validated);

        return redirect()->route('admin.settlements.show', $settlement)
            ->with('success', 'Settlement updated successfully.');
    }

    /**
     * Remove the specified settlement.
     */
    public function destroy(Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending settlements can be deleted.');
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
            ->with('success', 'Settlement deleted successfully.');
    }

    /**
     * Process a pending settlement.
     */
    public function process(Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending settlements can be processed.');
        }

        try {
            DB::transaction(function () use ($settlement) {
                $settlement->process();
                
                // Log the processing
                activity()
                    ->performedOn($settlement)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'transaction_reference' => $settlement->transaction_reference,
                        'processed_at' => $settlement->processed_at,
                    ])
                    ->log('settlement_processed');
            });

            return back()->with('success', 'Settlement processed successfully. Station wallet has been credited.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process settlement: ' . $e->getMessage());
        }
    }

    /**
     * Mark settlement as failed.
     */
    public function markAsFailed(Request $request, Settlement $settlement)
    {
        if ($settlement->status !== 'pending') {
            return back()->with('error', 'Only pending settlements can be marked as failed.');
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        DB::transaction(function () use ($settlement, $validated) {
            $settlement->markAsFailed($validated['reason']);
            
            // Log the failure
            activity()
                ->performedOn($settlement)
                ->causedBy(auth()->user())
                ->withProperties(['reason' => $validated['reason']])
                ->log('settlement_failed');
        });

        return back()->with('success', 'Settlement marked as failed.');
    }

    /**
     * Bulk process settlements.
     */
    public function bulkProcess(Request $request)
    {
        $validated = $request->validate([
            'settlements' => 'required|array',
            'settlements.*' => 'exists:settlements,id',
        ]);

        $settlements = Settlement::whereIn('id', $validated['settlements'])
            ->pending()
            ->get();

        if ($settlements->isEmpty()) {
            return back()->with('error', 'No pending settlements selected.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($settlements as $settlement) {
            try {
                DB::transaction(function () use ($settlement) {
                    $settlement->process();
                });
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                \Log::error('Failed to process settlement ' . $settlement->id . ': ' . $e->getMessage());
            }
        }

        $message = "Processed {$successCount} settlements successfully.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} settlements failed.";
        }

        return back()->with('success', $message);
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
}