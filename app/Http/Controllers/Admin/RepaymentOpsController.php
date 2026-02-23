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

        $recentAutopayEvents = AuditLog::query()
            ->whereIn('action', [
                'repayment_autopay_succeeded',
                'repayment_autopay_failed',
                'repayment_autopay_disabled_for_user',
                'repayment_autopay_retry_scheduled',
            ])
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.repayments.ops', compact('policy', 'lastRun', 'recentAutopayEvents'));
    }

    public function updatePolicy(Request $request, RepaymentPolicyService $policyService)
    {
        $validated = $request->validate([
            'autopay_max_retries' => 'required|integer|min:1|max:15',
            'autopay_retry_hours' => 'required|integer|min:1|max:168',
            'autopay_grace_days' => 'required|integer|min:0|max:30',
            'autopay_auto_disable_threshold' => 'required|integer|min:1|max:50',
        ]);

        $policyService->update($validated);

        return back()->with('success', 'Repayment autopay policy updated.');
    }

    public function runNow()
    {
        Artisan::call('repayments:run-daily-autopay', ['--limit' => 250]);
        $output = trim((string) Artisan::output());

        return back()->with('success', $output !== '' ? $output : 'Autopay run executed.');
    }
}

