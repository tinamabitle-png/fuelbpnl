<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\RepaymentPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class RepaymentOpsController extends Controller
{
    public function index(RepaymentPolicyService $policyService)
    {
        $policy = $policyService->get();
        $lastRun = Cache::get('repayments:autopay:last-run');
        $defaultChargesLastRun = Cache::get('repayments:default-charges:last-run');

        $recentAutopayEvents = AuditLog::query()
            ->whereIn('action', [
                'repayment_autopay_succeeded',
                'repayment_autopay_failed',
                'repayment_autopay_disabled_for_user',
                'repayment_autopay_retry_scheduled',
                'repayment_default_fee_created',
                'repayment_default_interest_created',
            ])
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.repayments.ops', compact('policy', 'lastRun', 'defaultChargesLastRun', 'recentAutopayEvents'));
    }

    public function updatePolicy(Request $request, RepaymentPolicyService $policyService)
    {
        $validated = $request->validate([
            'autopay_max_retries' => 'required|integer|min:1|max:15',
            'autopay_retry_hours' => 'required|integer|min:1|max:168',
            'autopay_grace_days' => 'required|integer|min:0|max:30',
            'autopay_auto_disable_threshold' => 'required|integer|min:1|max:50',
            'enable_default_fees' => 'nullable|boolean',
            'enable_default_interest' => 'nullable|boolean',
            'default_fee_pay_in_4_weekly' => 'nullable|numeric|min:0|max:10000',
            'default_fee_pay_in_4_max_charges' => 'nullable|integer|min:1|max:12',
            'default_fee_pay_in_3_once' => 'nullable|numeric|min:0|max:10000',
            'default_interest_monthly_rate' => 'nullable|numeric|min:0|max:20',
        ]);

        $validated['enable_default_fees'] = $request->boolean('enable_default_fees');
        $validated['enable_default_interest'] = $request->boolean('enable_default_interest');

        $policyService->update($validated);

        return back()->with('success', 'Repayment autopay policy updated.');
    }

    public function runNow()
    {
        Artisan::call('repayments:run-daily-autopay', ['--limit' => 250]);
        $output = trim((string) Artisan::output());

        return back()->with('success', $output !== '' ? $output : 'Autopay run executed.');
    }

    public function runDefaultChargesNow()
    {
        Artisan::call('repayments:apply-default-charges', ['--limit' => 500]);
        $output = trim((string) Artisan::output());

        return back()->with('success', $output !== '' ? $output : 'Default charge run executed.');
    }
}
