@php
    $user = auth()->user();
    $show = false;

    if ($user && $user->hasAnyRole(['driver', 'merchant'])) {
        $show = session()->has('pending_admin_approval') ? (bool) session('pending_admin_approval') : false;

        if (!$show) {
            $pendingApproval = \Illuminate\Support\Facades\Schema::hasTable('account_approvals')
                ? $user->latestAccountApproval()->where('status', 'pending')->exists()
                : false;
            $show = $pendingApproval || ((string) ($user->status ?? '') !== 'active');
        }
    }
@endphp

@if($show)
    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 h-9 w-9 rounded-xl bg-amber-100 flex items-center justify-center border border-amber-200">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold uppercase tracking-[0.14em] text-[11px] text-amber-700">Pending approval</p>
                    <p class="mt-1 leading-relaxed">
                        Your account is live, but still waiting for admin approval. You can log in and explore the dashboard.
                        Voucher and credit actions will stay locked until approval is completed.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-800 border border-amber-200">
                    Status: Pending
                </span>
                <a href="mailto:support@bwiser.co.za" class="inline-flex items-center rounded-full bg-amber-700 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-800 transition-colors">
                    Contact support
                </a>
            </div>
        </div>
    </div>
@endif

