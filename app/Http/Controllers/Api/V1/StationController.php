<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function settlements(Request $request)
    {
        $user = $request->user();
        $stationIds = FuelStation::where('owner_id', $user->id)->pluck('id');

        return response()->json([
            'success' => true,
            'data' => \App\Models\Settlement::with('fuelStation')
                ->whereIn('fuel_station_id', $stationIds)
                ->latest()
                ->paginate(20),
        ]);
    }
}
