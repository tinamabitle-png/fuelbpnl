<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FuelVoucher;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pending_approvals' => FuelVoucher::where('status', 'issued')->count(),
            'total_users' => User::role('driver')->count(),
            'flagged_users' => User::where('status', 'flagged')->count(),
            'today_vouchers' => FuelVoucher::whereDate('created_at', today())->count(),
        ];
        
        $pending_vouchers = FuelVoucher::with(['user', 'fuelStation'])
            ->where('status', 'issued')
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
            
        $flagged_users = User::with('wallet')
            ->where('status', 'flagged')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
            
        return view('employee.dashboard', compact('stats', 'pending_vouchers', 'flagged_users'));
    }
}