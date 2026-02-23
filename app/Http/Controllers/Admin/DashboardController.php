<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\FuelStation;
use App\Models\Settlement;
use App\Models\AdminFeedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

        $recent_feedback = collect();
        if (Schema::hasTable('admin_feedback')) {
            $recent_feedback = AdminFeedback::with('user:id,name,email')
                ->latest()
                ->limit(4)
                ->get();
        }

        // Get current logged in user
        $currentUser = Auth::user();

        $weeklyCycleStatus = [
            'enabled' => $this->weeklyCyclesEnabled(),
            'next_cycle' => $this->getNextCycleInfo(),
        ];

        return view('admin.dashboard.index', compact('stats', 'recent_vouchers', 'recent_users', 'recent_feedback', 'currentUser', 'weeklyCycleStatus'));
    }

    private function weeklyCyclesEnabled(): bool
    {
        $raw = DB::table('settings')->where('key', 'weekly_cycles_enabled')->value('value');
        if ($raw === null) {
            return true;
        }

        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    private function getNextCycleInfo(): ?array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $today = strtolower(now()->format('l'));
        $todayIndex = array_search($today, $days, true);
        $todayIndex = $todayIndex === false ? 0 : (int) $todayIndex;

        $brandCycles = json_decode((string) (DB::table('settings')->where('key', 'weekly_brand_cycles')->value('value') ?? '[]'), true);
        $stationCycles = json_decode((string) (DB::table('settings')->where('key', 'weekly_station_cycles')->value('value') ?? '[]'), true);
        if (!is_array($brandCycles)) {
            $brandCycles = [];
        }
        if (!is_array($stationCycles)) {
            $stationCycles = [];
        }

        $entries = [];
        foreach ($brandCycles as $brand => $cycle) {
            $day = strtolower((string) ($cycle['day'] ?? ''));
            if (!in_array($day, $days, true) || empty($cycle['enabled'])) {
                continue;
            }
            $entries[] = ['type' => 'brand', 'name' => (string) $brand, 'day' => $day];
        }

        $stationNames = FuelStation::query()->pluck('name', 'id');
        foreach ($stationCycles as $stationId => $cycle) {
            $day = strtolower((string) ($cycle['day'] ?? ''));
            if (!in_array($day, $days, true) || empty($cycle['enabled'])) {
                continue;
            }
            $entries[] = [
                'type' => 'station',
                'name' => (string) ($stationNames[(int) $stationId] ?? ('Station #' . $stationId)),
                'day' => $day,
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
                ];
            }
        }

        if (!$best) {
            return null;
        }

        return [
            'type' => $best['type'],
            'name' => $best['name'],
            'label' => $best['at']->format('D, d M Y H:i') . ' SAST',
            'human' => $best['at']->diffForHumans(),
        ];
    }
}
