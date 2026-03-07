@extends('Layouts.admin')

@section('title', 'Repayment Ops')
@section('page-title', 'Repayment Ops')
@section('page-description', 'Autopay policy, execution controls, and failure monitoring')
@section('breadcrumb', 'Repayments / Ops')

@section('content')
<div class="p-6 space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Daily Autopay Engine</h3>
                <p class="text-sm text-slate-600 mt-1">Control retries, grace windows, and automatic disable rules.</p>
            </div>
            <form method="POST" action="{{ route('admin.repayments.ops.run-now') }}">
                @csrf
                <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Run Now</button>
            </form>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Last Run</p>
                <p class="font-semibold text-slate-900 mt-1">{{ data_get($lastRun, 'at') ? \Illuminate\Support\Carbon::parse(data_get($lastRun, 'at'))->format('d M Y H:i:s') : 'Never' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Processed</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($lastRun, 'processed', 0) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Failed</p>
                <p class="font-semibold text-rose-700 mt-1">{{ (int) data_get($lastRun, 'failed', 0) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Skipped</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($lastRun, 'skipped', 0) }}</p>
            </div>
        </div>

        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Skip: Repayment Missing</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($lastRun, 'skip_reasons.repayment_not_found', 0) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Skip: User Not Eligible</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($lastRun, 'skip_reasons.user_not_eligible', 0) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Skip: User Backoff</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($lastRun, 'skip_reasons.user_backoff_not_due', 0) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Default Charges Engine</h3>
                <p class="text-sm text-slate-600 mt-1">Applies default fees and monthly default interest to overdue repayments.</p>
            </div>
            <form method="POST" action="{{ route('admin.repayments.ops.default-charges.run-now') }}">
                @csrf
                <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Run Now</button>
            </form>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Last Run</p>
                <p class="font-semibold text-slate-900 mt-1">{{ data_get($defaultChargesLastRun, 'at') ? \Illuminate\Support\Carbon::parse(data_get($defaultChargesLastRun, 'at'))->format('d M Y H:i:s') : 'Never' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Leases Scanned</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($defaultChargesLastRun, 'lease_count', 0) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Default Fees Created</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($defaultChargesLastRun, 'created_fees', 0) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-slate-500">Default Interest Created</p>
                <p class="font-semibold text-slate-900 mt-1">{{ (int) data_get($defaultChargesLastRun, 'created_interest', 0) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h3 class="text-lg font-semibold text-slate-900">Policy</h3>
        <form method="POST" action="{{ route('admin.repayments.ops.policy.update') }}" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Max Retries</label>
                <input type="number" min="1" max="15" name="autopay_max_retries" value="{{ old('autopay_max_retries', $policy['autopay_max_retries']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Retry Interval (Hours)</label>
                <input type="number" min="1" max="168" name="autopay_retry_hours" value="{{ old('autopay_retry_hours', $policy['autopay_retry_hours']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Grace Days</label>
                <input type="number" min="0" max="30" name="autopay_grace_days" value="{{ old('autopay_grace_days', $policy['autopay_grace_days']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Auto-Disable Threshold</label>
                <input type="number" min="1" max="50" name="autopay_auto_disable_threshold" value="{{ old('autopay_auto_disable_threshold', $policy['autopay_auto_disable_threshold']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div class="md:col-span-4 border-t border-slate-200 pt-4 mt-1">
                <h4 class="text-sm font-semibold text-slate-800 mb-3">Default Fee and Interest Model</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="enable_default_fees" value="1" {{ old('enable_default_fees', $policy['enable_default_fees']) ? 'checked' : '' }}>
                        Enable Default Fees
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="enable_default_interest" value="1" {{ old('enable_default_interest', $policy['enable_default_interest']) ? 'checked' : '' }}>
                        Enable Default Interest
                    </label>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pay in 4 Weekly Fee (R)</label>
                        <input type="number" step="0.01" min="0" max="10000" name="default_fee_pay_in_4_weekly" value="{{ old('default_fee_pay_in_4_weekly', $policy['default_fee_pay_in_4_weekly']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pay in 4 Max Charges</label>
                        <input type="number" min="1" max="12" name="default_fee_pay_in_4_max_charges" value="{{ old('default_fee_pay_in_4_max_charges', $policy['default_fee_pay_in_4_max_charges']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pay in 3 Miss Fee (R)</label>
                        <input type="number" step="0.01" min="0" max="10000" name="default_fee_pay_in_3_once" value="{{ old('default_fee_pay_in_3_once', $policy['default_fee_pay_in_3_once']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Default Interest (% per month)</label>
                        <input type="number" step="0.01" min="0" max="20" name="default_interest_monthly_rate" value="{{ old('default_interest_monthly_rate', $policy['default_interest_monthly_rate']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>
            </div>
            <div class="md:col-span-4">
                <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Save Policy</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Recent Autopay Events</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-3">When</th>
                        <th class="py-2 pr-3">Action</th>
                        <th class="py-2 pr-3">Model</th>
                        <th class="py-2 pr-3">Summary</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAutopayEvents as $event)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-3">{{ $event->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="py-2 pr-3">{{ $event->action }}</td>
                            <td class="py-2 pr-3">{{ class_basename((string) $event->model_type) }} #{{ $event->model_id }}</td>
                            <td class="py-2 pr-3 text-slate-600">{{ $event->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-slate-500">No autopay events logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
