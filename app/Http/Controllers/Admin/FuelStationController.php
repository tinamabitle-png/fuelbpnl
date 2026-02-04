<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FuelStationController extends Controller
{
    /**
     * Display a listing of fuel stations.
     */
    public function index(Request $request)
    {
        $query = FuelStation::with(['owner', 'vouchers', 'settlements'])
            ->withCount(['vouchers', 'settlements']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%")
                  ->orWhereHas('owner', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $stations = $query->paginate(20);

        // Get unique cities for filter
        $cities = FuelStation::select('city')->distinct()->pluck('city');

        return view('admin.stations.index', compact('stations', 'cities'));
    }

    /**
     * Show the form for creating a new fuel station.
     */
    public function create()
    {
        $owners = User::role('station_owner')->orWhere('id', 1)->get(); // Get station owners or admin
        return view('admin.stations.create', compact('owners'));
    }

    /**
     * Store a newly created fuel station.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'license_number' => 'required|string|unique:fuel_stations,license_number',
            'address' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string',
            'contact_email' => 'nullable|email',
            'status' => 'required|in:active,inactive,pending,suspended',
            'owner_id' => 'nullable|exists:users,id',
            'wallet_balance' => 'nullable|numeric|min:0',
        ]);

        // Set default values
        $validated['wallet_balance'] = $validated['wallet_balance'] ?? 0;
        $validated['total_settlements'] = 0;

        // Create the station
        $station = FuelStation::create($validated);

        return redirect()->route('admin.stations.show', $station)
            ->with('success', 'Fuel station created successfully.');
    }

    /**
     * Display the specified fuel station.
     */
  
public function show(FuelStation $station)
{
    $station->load(['owner', 'vouchers.user', 'settlements']);
    
    // Get statistics
    $stats = [
        'total_vouchers' => $station->vouchers()->count(),
        'active_vouchers' => $station->vouchers()->where('status', 'active')->count(),
        'redeemed_vouchers' => $station->vouchers()->where('status', 'redeemed')->count(),
        'total_settlement_amount' => $station->settlements()->sum('amount'),
        'pending_settlement' => $station->getPendingSettlementAmount(),
        'wallet_balance' => $station->wallet_balance,
    ];

    // Recent vouchers
    $recentVouchers = $station->vouchers()
        ->with('user')
        ->latest()
        ->take(10)
        ->get();

    // Recent settlements
    $recentSettlements = $station->settlements()
        ->latest()
        ->take(10)
        ->get();

    return view('admin.stations.show', compact(
        'station', 
        'stats', 
        'recentVouchers', 
        'recentSettlements'
    ));
}

    /**
     * Show the form for editing the specified fuel station.
     */
    public function edit(FuelStation $station)
    {
        $owners = User::role('station_owner')->orWhere('id', 1)->get();
        return view('admin.stations.edit', compact('station', 'owners'));
    }

    /**
     * Update the specified fuel station.
     */
    public function update(Request $request, FuelStation $station)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'license_number' => [
                'required',
                'string',
                Rule::unique('fuel_stations')->ignore($station->id),
            ],
            'address' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string',
            'contact_email' => 'nullable|email',
            'status' => 'required|in:active,inactive,pending,suspended',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $station->update($validated);

        return redirect()->route('admin.stations.show', $station)
            ->with('success', 'Fuel station updated successfully.');
    }

    /**
     * Remove the specified fuel station.
     */
    public function destroy(FuelStation $station)
    {
        // Check if station has vouchers or settlements
        if ($station->vouchers()->exists()) {
            return back()->with('error', 'Cannot delete station with existing vouchers.');
        }

        if ($station->settlements()->exists()) {
            return back()->with('error', 'Cannot delete station with existing settlements.');
        }

        $station->delete();

        return redirect()->route('admin.stations.index')
            ->with('success', 'Fuel station deleted successfully.');
    }

    /**
     * Update wallet balance.
     */
    public function updateWallet(Request $request, FuelStation $station)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        DB::transaction(function () use ($station, $validated) {
            $oldBalance = $station->wallet_balance;

            if ($validated['type'] === 'credit') {
                $station->increment('wallet_balance', $validated['amount']);
            } else {
                if ($station->wallet_balance < $validated['amount']) {
                    throw new \Exception('Insufficient wallet balance.');
                }
                $station->decrement('wallet_balance', $validated['amount']);
            }

            // Log the transaction
            activity()
                ->performedOn($station)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_balance' => $oldBalance,
                    'new_balance' => $station->wallet_balance,
                    'amount' => $validated['amount'],
                    'type' => $validated['type'],
                    'reason' => $validated['reason'],
                ])
                ->log('wallet_adjusted');
        });

        return back()->with('success', 'Wallet updated successfully.');
    }

    /**
     * Toggle station status.
     */
    public function toggleStatus(FuelStation $station)
    {
        $newStatus = $station->status === 'active' ? 'suspended' : 'active';
        $station->update(['status' => $newStatus]);

        // Log status change
        activity()
            ->performedOn($station)
            ->causedBy(auth()->user())
            ->withProperties(['old_status' => $station->status, 'new_status' => $newStatus])
            ->log('status_changed');

        return back()->with('success', "Station {$newStatus} successfully.");
    }

    /**
     * Get nearby stations (API endpoint).
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);

        $radius = $request->radius ?? 10;
        
        $stations = FuelStation::active()
            ->nearby($request->latitude, $request->longitude, $radius)
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'stations' => $stations,
            'count' => $stations->count(),
        ]);
    }

    /**
     * Get station statistics.
     */
    public function statistics()
    {
        $totalStations = FuelStation::count();
        $activeStations = FuelStation::active()->count();
        $totalWalletBalance = FuelStation::sum('wallet_balance');
        $totalSettlements = FuelStation::sum('total_settlements');
        $pendingSettlements = FuelStation::get()->sum->getPendingSettlementAmount();

        $cities = FuelStation::select('city', DB::raw('count(*) as count'))
            ->groupBy('city')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $statusDistribution = FuelStation::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'total_stations' => $totalStations,
            'active_stations' => $activeStations,
            'total_wallet_balance' => $totalWalletBalance,
            'total_settlements' => $totalSettlements,
            'pending_settlements' => $pendingSettlements,
            'cities' => $cities,
            'status_distribution' => $statusDistribution,
        ]);
    }

    /**
     * Export stations to CSV.
     */
    public function export(Request $request)
    {
        $stations = FuelStation::with('owner')->get();

        $filename = "fuel_stations_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($stations) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 compatibility
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'ID', 'Name', 'Company', 'License Number', 'Address', 'City', 'Country',
                'Latitude', 'Longitude', 'Contact Person', 'Contact Phone', 'Contact Email',
                'Status', 'Owner', 'Wallet Balance', 'Total Settlements', 'Created At'
            ]);

            // Data
            foreach ($stations as $station) {
                fputcsv($file, [
                    $station->id,
                    $station->name,
                    $station->company,
                    $station->license_number,
                    $station->address,
                    $station->city,
                    $station->country,
                    $station->latitude,
                    $station->longitude,
                    $station->contact_person,
                    $station->contact_phone,
                    $station->contact_email,
                    $station->status,
                    $station->owner ? $station->owner->name : 'N/A',
                    $station->wallet_balance,
                    $station->total_settlements,
                    $station->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk update stations.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,suspend,delete',
            'stations' => 'required|array',
            'stations.*' => 'exists:fuel_stations,id',
        ]);

        $stations = FuelStation::whereIn('id', $validated['stations'])->get();

        DB::transaction(function () use ($stations, $validated) {
            foreach ($stations as $station) {
                switch ($validated['action']) {
                    case 'activate':
                        $station->update(['status' => 'active']);
                        break;
                    case 'suspend':
                        $station->update(['status' => 'suspended']);
                        break;
                    case 'delete':
                        // Only delete if no vouchers or settlements
                        if (!$station->vouchers()->exists() && !$station->settlements()->exists()) {
                            $station->delete();
                        }
                        break;
                }
            }
        });

        return back()->with('success', 'Bulk action completed successfully.');
    }
}