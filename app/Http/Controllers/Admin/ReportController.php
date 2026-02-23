<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\Repayment;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        [$labels, $days] = $this->buildDaySeries($from, $to);

        $voucherByDay = FuelVoucher::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count, COALESCE(SUM(amount),0) as amount')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('count', 'day');

        $voucherAmountByDay = FuelVoucher::query()
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(amount),0) as amount')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('amount', 'day');

        $settlementByDay = Settlement::query()
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(amount),0) as amount')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('amount', 'day');

        $charts = [
            'labels' => $labels,
            'voucherCounts' => $days->map(fn ($d) => (int) ($voucherByDay[$d] ?? 0))->all(),
            'voucherAmounts' => $days->map(fn ($d) => round((float) ($voucherAmountByDay[$d] ?? 0), 2))->all(),
            'settlementAmounts' => $days->map(fn ($d) => round((float) ($settlementByDay[$d] ?? 0), 2))->all(),
        ];

        $summary = [
            'voucher_total' => FuelVoucher::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count(),
            'voucher_amount' => (float) FuelVoucher::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount'),
            'approved_count' => FuelVoucher::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->where('status', 'approved')->count(),
            'redeemed_count' => FuelVoucher::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->where('status', 'redeemed')->count(),
            'settlement_total' => Settlement::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count(),
            'settlement_amount' => (float) Settlement::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount'),
        ];

        $topStations = FuelStation::query()
            ->leftJoin('fuel_vouchers', 'fuel_stations.id', '=', 'fuel_vouchers.fuel_station_id')
            ->whereBetween('fuel_vouchers.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('fuel_vouchers.status', 'redeemed')
            ->groupBy('fuel_stations.id', 'fuel_stations.name', 'fuel_stations.city')
            ->selectRaw('fuel_stations.id, fuel_stations.name, fuel_stations.city, COUNT(fuel_vouchers.id) as redeemed_count, COALESCE(SUM(fuel_vouchers.amount),0) as redeemed_amount')
            ->orderByDesc('redeemed_amount')
            ->limit(10)
            ->get();

        $guardrails = [
            'voucher_blocks' => AuditLog::where('action', 'voucher_issue_blocked_station_wallet')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count(),
            'autopay_failures' => AuditLog::where('action', 'repayment_autopay_failed')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count(),
            'autopay_disables' => AuditLog::where('action', 'repayment_autopay_disabled_for_user')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count(),
            'duplicate_payout_prevented' => AuditLog::where('action', 'settlement_duplicate_prevented')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count(),
        ];

        $statusDistribution = FuelVoucher::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('admin.reports.index', compact('from', 'to', 'summary', 'charts', 'topStations', 'statusDistribution', 'guardrails'));
    }

    public function financial(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        [$labels, $days] = $this->buildDaySeries($from, $to);

        $issued = FuelVoucher::query()
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(amount),0) as amount')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('amount', 'day');

        $redeemed = FuelVoucher::query()
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(amount),0) as amount')
            ->where('status', 'redeemed')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('amount', 'day');

        $repayments = Repayment::query()
            ->selectRaw('DATE(paid_at) as day, COALESCE(SUM(amount),0) as amount')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('amount', 'day');

        $charts = [
            'labels' => $labels,
            'issuedAmounts' => $days->map(fn ($d) => round((float) ($issued[$d] ?? 0), 2))->all(),
            'redeemedAmounts' => $days->map(fn ($d) => round((float) ($redeemed[$d] ?? 0), 2))->all(),
            'repaymentAmounts' => $days->map(fn ($d) => round((float) ($repayments[$d] ?? 0), 2))->all(),
        ];

        $summary = [
            'paid_repayments' => (float) Repayment::where('status', 'paid')->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount'),
            'overdue_amount' => (float) Repayment::whereIn('status', ['pending', 'overdue'])->whereDate('due_date', '<', now()->toDateString())->sum('amount'),
            'lease_exposure' => (float) Lease::sum('total_amount'),
            'active_lease_exposure' => (float) Lease::where('status', 'active')->sum('total_amount'),
        ];

        $stationWallets = FuelStation::query()
            ->leftJoin('settlements', 'fuel_stations.id', '=', 'settlements.fuel_station_id')
            ->groupBy('fuel_stations.id', 'fuel_stations.name', 'fuel_stations.city', 'fuel_stations.wallet_balance')
            ->selectRaw('fuel_stations.id, fuel_stations.name, fuel_stations.city, fuel_stations.wallet_balance, COALESCE(SUM(settlements.amount),0) as total_settlements')
            ->orderByDesc('fuel_stations.wallet_balance')
            ->limit(12)
            ->get();

        return view('admin.reports.financial', compact('from', 'to', 'summary', 'charts', 'stationWallets'));
    }

    public function risk(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        $userRiskCounts = [
            'flagged' => User::where('status', 'flagged')->count(),
            'blocked' => User::where('status', 'blocked')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
        ];

        $leaseRiskCounts = [
            'active' => Lease::where('status', 'active')->count(),
            'defaulted' => Lease::where('status', 'defaulted')->count(),
            'completed' => Lease::where('status', 'completed')->count(),
        ];

        $overdueRepayments = Repayment::with('user')
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        $defaultRows = Lease::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(*) as total, SUM(CASE WHEN status = "defaulted" THEN 1 ELSE 0 END) as defaulted')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $defaultLabels = $defaultRows->pluck('month_key')->map(fn ($m) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M Y'))->all();
        $defaultRates = $defaultRows->map(function ($row) {
            $total = max(1, (int) $row->total);
            return round(((int) $row->defaulted / $total) * 100, 2);
        })->all();

        $anomalyEnabled = Schema::hasTable('voucher_anomaly_checks');
        $anomalyLabels = [];
        $anomalyFlagged = [];
        $anomalyTotal = [];
        if ($anomalyEnabled) {
            $anomalyRows = DB::table('voucher_anomaly_checks')
                ->selectRaw('DATE(created_at) as day, SUM(CASE WHEN flagged = 1 THEN 1 ELSE 0 END) as flagged_count, COUNT(*) as total_count')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $anomalyLabels = $anomalyRows->pluck('day')->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d M'))->all();
            $anomalyFlagged = $anomalyRows->pluck('flagged_count')->map(fn ($v) => (int) $v)->all();
            $anomalyTotal = $anomalyRows->pluck('total_count')->map(fn ($v) => (int) $v)->all();
        }

        $charts = [
            'defaultLabels' => $defaultLabels,
            'defaultRates' => $defaultRates,
            'anomalyLabels' => $anomalyLabels,
            'anomalyFlagged' => $anomalyFlagged,
            'anomalyTotal' => $anomalyTotal,
        ];

        return view('admin.reports.risk', compact('from', 'to', 'userRiskCounts', 'leaseRiskCounts', 'overdueRepayments', 'charts', 'anomalyEnabled'));
    }

    public function export(Request $request, string $type)
    {
        [$from, $to] = $this->resolveRange($request);
        $rows = [];

        if ($type === 'financial') {
            $rows[] = ['metric', 'value'];
            $rows[] = ['paid_repayments', (string) Repayment::where('status', 'paid')->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount')];
            $rows[] = ['overdue_amount', (string) Repayment::whereIn('status', ['pending', 'overdue'])->whereDate('due_date', '<', now()->toDateString())->sum('amount')];
            $rows[] = ['lease_exposure', (string) Lease::sum('total_amount')];
            $rows[] = ['active_lease_exposure', (string) Lease::where('status', 'active')->sum('total_amount')];
        } elseif ($type === 'risk') {
            $rows[] = ['metric', 'value'];
            $rows[] = ['flagged_users', (string) User::where('status', 'flagged')->count()];
            $rows[] = ['blocked_users', (string) User::where('status', 'blocked')->count()];
            $rows[] = ['defaulted_leases', (string) Lease::where('status', 'defaulted')->count()];
            $rows[] = ['overdue_repayments', (string) Repayment::whereIn('status', ['pending', 'overdue'])->whereDate('due_date', '<', now()->toDateString())->count()];
        } else {
            $rows[] = ['metric', 'value'];
            $rows[] = ['voucher_total', (string) FuelVoucher::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count()];
            $rows[] = ['voucher_amount', (string) FuelVoucher::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount')];
            $rows[] = ['settlement_total', (string) Settlement::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count()];
            $rows[] = ['settlement_amount', (string) Settlement::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount')];
            $rows[] = ['voucher_blocks', (string) AuditLog::where('action', 'voucher_issue_blocked_station_wallet')->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count()];
            $rows[] = ['autopay_failures', (string) AuditLog::where('action', 'repayment_autopay_failed')->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count()];
        }

        $filename = "report_{$type}_" . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, $headers);
    }

    private function resolveRange(Request $request): array
    {
        $to = $request->filled('to')
            ? \Illuminate\Support\Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();
        $from = $request->filled('from')
            ? \Illuminate\Support\Carbon::parse($request->input('from'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function buildDaySeries($from, $to): array
    {
        $days = collect();
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $days->push($cursor->format('Y-m-d'));
            $cursor->addDay();
        }

        return [$days->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d M'))->all(), $days];
    }
}
