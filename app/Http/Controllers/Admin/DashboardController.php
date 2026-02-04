<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\FuelStation;
use App\Models\Settlement;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_drivers' => User::where('status', 'active')->whereHas('roles', function($q) {
                $q->where('name', 'driver');
            })->count(),
            'total_stations' => FuelStation::count(),
            'active_stations' => FuelStation::where('status', 'active')->count(),
            'total_vouchers' => FuelVoucher::count(),
            'pending_vouchers' => FuelVoucher::where('status', 'issued')->count(),
            'total_leases' => Lease::count(),
            'active_leases' => Lease::where('status', 'active')->count(),
            'defaulted_leases' => Lease::where('status', 'defaulted')->count(),
            'pending_settlements' => Settlement::where('status', 'pending')->count(),
            'total_settlement_amount' => Settlement::where('status', 'completed')->sum('amount'),
        ];

        // Recent activity
        $recent_vouchers = FuelVoucher::with(['user', 'fuelStation'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recent_users = User::with('wallet')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get current logged in user
        $currentUser = Auth::user();

        return view('admin.dashboard.index', compact('stats', 'recent_vouchers', 'recent_users', 'currentUser'));
    }
}