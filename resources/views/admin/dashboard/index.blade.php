@extends('Layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="space-y-8 adminhub-dashboard">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Stats Cards -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-blue-50 text-blue-600 mr-3">
                <i class="fas fa-users text-base"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Users</p>
                <p class="text-2xl font-bold">{{ $stats['total_users'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-green-50 text-green-600 mr-3">
                <i class="fas fa-gas-pump text-base"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Active Stations</p>
                <p class="text-2xl font-bold">{{ $stats['active_stations'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-amber-50 text-amber-600 mr-3">
                <i class="fas fa-ticket-alt text-base"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Pending Vouchers</p>
                <p class="text-2xl font-bold">{{ $stats['pending_vouchers'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-rose-50 text-rose-600 mr-3">
                <i class="fas fa-money-bill-wave text-base"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Settlements</p>
                <p class="text-2xl font-bold">ZAR {{ number_format($stats['total_settlement_amount'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <div class="flex items-center">
        <div class="p-2 rounded-lg bg-blue-50 text-blue-600 mr-3">
            <i class="fas fa-user text-base"></i>
        </div>
        <div>
            <h3 class="text-base font-semibold text-slate-900">Welcome, {{ auth()->user()->name }}!</h3>
            <p class="text-gray-600">You are logged in as {{ auth()->user()->getRoleNames()->first() ?? 'User' }}</p>
            <p class="text-sm text-gray-500 mt-1">Email: {{ auth()->user()->email }}</p>
            <p class="text-sm text-gray-500">Last login: {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'First login' }}</p>
        </div>
    </div>
</div>

<div class="rounded-[1.75rem] bg-slate-950 p-5 shadow-xl shadow-slate-900/10 overflow-hidden">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-5">
        <div>
            <p class="text-sm uppercase tracking-[0.24em] text-blue-300">Analytics</p>
            <h3 class="text-2xl font-bold text-white mt-1">Operations Pulse</h3>
            <p class="text-sm text-slate-400 mt-1">Hover cards styled like the requested analytics pattern, using live dashboard data.</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15">
            Open Reports
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="admin-analytics-card group">
            <div class="relative z-10 p-5">
                <p class="text-sm font-semibold text-blue-300">Voucher Flow</p>
                <h4 class="mt-1 text-lg font-bold text-slate-100">Daily voucher creation</h4>
                <p class="mt-1 text-sm text-slate-400">Tracks issued demand over the last 7 days.</p>
            </div>
            <div class="relative z-10 px-4 pb-4 transition-transform duration-500 ease-out group-hover:-translate-y-1" style="height: 170px;">
                <canvas id="adminVoucherPulseChart" height="150"></canvas>
            </div>
        </div>

        <div class="admin-analytics-card group">
            <div class="relative z-10 p-5">
                <p class="text-sm font-semibold text-emerald-300">Lease Pipeline</p>
                <h4 class="mt-1 text-lg font-bold text-slate-100">New leases</h4>
                <p class="mt-1 text-sm text-slate-400">Shows finance origination activity by day.</p>
            </div>
            <div class="relative z-10 px-4 pb-4 transition-transform duration-500 ease-out group-hover:-translate-y-1" style="height: 170px;">
                <canvas id="adminLeasePulseChart" height="150"></canvas>
            </div>
        </div>

        <div class="admin-analytics-card group">
            <div class="relative z-10 p-5">
                <p class="text-sm font-semibold text-amber-300">Portfolio Risk</p>
                <h4 class="mt-1 text-lg font-bold text-slate-100">Lease status mix</h4>
                <p class="mt-1 text-sm text-slate-400">Quick read of active, completed, defaulted and cancelled exposure.</p>
            </div>
            <div class="relative z-10 px-4 pb-4 transition-transform duration-500 ease-out group-hover:-translate-y-1" style="height: 170px;">
                <canvas id="adminLeaseStatusChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.22em] text-blue-600">Finance Desk</p>
            <h3 class="text-xl font-bold text-slate-900 mt-1">Fund Companies & Send Leases</h3>
            <p class="text-sm text-slate-600 mt-1">Choose an elected finance company, fund its capital account or wallet, then allocate eligible subprime voucher leases.</p>
        </div>
        <a href="{{ route('admin.investors.index') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Manage Finance Companies
        </a>
    </div>

    <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
        @forelse(($financeCompanies ?? collect())->take(3) as $company)
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h4 class="font-bold text-slate-900">{{ $company->company_name }}</h4>
                        <p class="text-xs text-slate-500 mt-1">{{ $company->user?->email ?? $company->contact_email }}</p>
                    </div>
                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $company->lease_investments_count }} leases</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-white border border-slate-200 p-3">
                        <p class="text-slate-500">Capital</p>
                        <p class="font-bold text-slate-900">R {{ number_format((float) $company->available_capital, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-white border border-slate-200 p-3">
                        <p class="text-slate-500">Wallet</p>
                        <p class="font-bold text-slate-900">R {{ number_format((float) $company->wallet_balance, 2) }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.investors.capital.update', $company) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="add">
                    <input type="hidden" name="reason" value="Dashboard finance company funding">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="amount" min="100" step="0.01" required placeholder="Amount" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <select name="destination" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="capital">Capital</option>
                            <option value="wallet">Wallet</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Fund Account</button>
                </form>

                <form method="POST" action="{{ route('admin.investors.invest', $company) }}" class="mt-3 space-y-3">
                    @csrf
                    <select name="lease_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Send eligible lease...</option>
                        @foreach(($financeAssignableLeases ?? collect()) as $lease)
                            <option value="{{ $lease->id }}">
                                #{{ $lease->id }} • {{ $lease->user?->name ?? 'Driver' }} • Remaining R {{ number_format((float) $lease->investor_funding_remaining, 2) }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" name="amount" min="1000" step="0.01" required placeholder="Investment amount" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Send Lease To Company</button>
                </form>
            </div>
        @empty
            <div class="lg:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                No active finance companies found. Create an investor company first.
            </div>
        @endforelse
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Recent Vouchers -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Recent Vouchers</h3>
        </div>
        <div class="p-5">
            @if($recent_vouchers->count() > 0)
            <div class="space-y-4">
                @foreach($recent_vouchers as $voucher)
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <p class="font-medium">{{ $voucher->code }}</p>
                        <p class="text-sm text-gray-600">{{ $voucher->user->name ?? 'Unknown User' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold">ZAR {{ number_format($voucher->amount, 2) }}</p>
                        <span class="text-xs px-2 py-1 rounded-full 
                            @if($voucher->status == 'issued') bg-yellow-100 text-yellow-800
                            @elseif($voucher->status == 'redeemed') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($voucher->status) }}
                        </span>
                        @if($voucher->status === 'issued')
                            <div class="mt-1">
                                <span class="inline-flex rounded-md bg-slate-800 py-0.5 px-2.5 border border-transparent text-sm text-white transition-all shadow-sm">
                                    Pending Approval
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.vouchers.index') }}" class="text-blue-600 hover:underline">View All Vouchers</a>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-ticket-alt text-gray-300 text-2xl mb-3"></i>
                <p class="text-gray-500">No vouchers found</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Recent Users</h3>
        </div>
        <div class="p-5">
            @if($recent_users->count() > 0)
            <div class="space-y-4">
                @foreach($recent_users as $user)
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <p class="font-medium">{{ $user->name }}</p>
                        <p class="text-sm text-gray-600">{{ $user->email }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs px-2 py-1 rounded-full 
                            @if($user->status == 'active') bg-green-100 text-green-800
                            @elseif($user->status == 'suspended') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($user->status) }}
                        </span>
                        <p class="text-sm text-gray-600 mt-1">Score: {{ $user->credit_score }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">View All Users</a>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-users text-gray-300 text-2xl mb-3"></i>
                <p class="text-gray-500">No users found</p>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <h3 class="text-base font-semibold text-slate-900 mb-3">Quick Actions</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.users.create') }}" 
           class="bg-slate-50 hover:bg-slate-100 border border-slate-200 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-user-plus text-blue-600 text-base mb-2"></i>
            <p class="font-medium text-slate-900">Add New User</p>
            <p class="text-sm text-gray-600 mt-1">Create new system user</p>
        </a>
        
        <a href="{{ route('admin.vouchers.pending') }}" 
           class="bg-slate-50 hover:bg-slate-100 border border-slate-200 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-check-circle text-amber-600 text-base mb-2"></i>
            <p class="font-medium text-slate-900">Approve Vouchers</p>
            <p class="text-sm text-gray-600 mt-1">Review pending vouchers</p>
        </a>
        
        <a href="{{ route('admin.settlements.index') }}" 
           class="bg-slate-50 hover:bg-slate-100 border border-slate-200 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-money-check-alt text-emerald-600 text-base mb-2"></i>
            <p class="font-medium text-slate-900">Process Settlements</p>
            <p class="text-sm text-gray-600 mt-1">Manage station payouts</p>
        </a>

        <a href="{{ route('admin.repayments.ops') }}"
           class="bg-slate-50 hover:bg-slate-100 border border-slate-200 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-repeat text-blue-600 text-base mb-2"></i>
            <p class="font-medium text-slate-900">Repayment Ops</p>
            <p class="text-sm text-gray-600 mt-1">Autopay policy and retries</p>
        </a>

        <a href="{{ route('admin.communications.investor-outreach.create') }}"
           class="bg-slate-50 hover:bg-slate-100 border border-slate-200 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-envelope-open-text text-indigo-600 text-base mb-2"></i>
            <p class="font-medium text-slate-900">Investor Outreach</p>
            <p class="text-sm text-gray-600 mt-1">Compose and send VC emails</p>
        </a>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-gray-900">Weekly Payout Cycles</h3>
        <a href="{{ route('admin.settlements.index') }}" class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">
            Open Settlements
        </a>
    </div>
    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-lg border {{ !empty($weeklyCycleStatus['enabled']) ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-4">
            <p class="text-sm text-slate-600">Automation</p>
            <p class="text-xl font-bold {{ !empty($weeklyCycleStatus['enabled']) ? 'text-emerald-700' : 'text-rose-700' }}">
                {{ !empty($weeklyCycleStatus['enabled']) ? 'ON' : 'OFF' }}
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-600">Next Run</p>
            @if(!empty($weeklyCycleStatus['next_cycle']))
                <p class="text-sm font-semibold text-slate-900 mt-1">{{ $weeklyCycleStatus['next_cycle']['label'] }}</p>
                <p class="text-xs text-slate-600 mt-1">
                    {{ $weeklyCycleStatus['next_cycle']['type'] === 'brand' ? 'Brand' : 'Station' }}:
                    {{ $weeklyCycleStatus['next_cycle']['name'] }} • {{ $weeklyCycleStatus['next_cycle']['human'] }}
                </p>
            @else
                <p class="text-sm font-semibold text-slate-500 mt-1">Not configured</p>
            @endif
        </div>
    </div>
</div>

<div class="rounded-xl bg-white p-5 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">User Feedback Inbox</h3>
            <a href="{{ route('admin.feedback.index') }}" class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">Open inbox</a>
        </div>
        <div class="space-y-3">
            @forelse(($recent_feedback ?? collect())->take(4) as $item)
                <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800">{{ $item->user?->name ?? 'System User' }}</p>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $item->sentiment === 'positive' ? 'bg-emerald-100 text-emerald-700' : ($item->sentiment === 'negative' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700') }}">
                            {{ strtoupper($item->sentiment) }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 mt-2">{{ $item->message }}</p>
                    <p class="text-xs text-slate-400 mt-2">{{ $item->created_at?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No feedback submitted yet.</p>
            @endforelse
        </div>
</div>

<!-- System Status -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <h3 class="text-base font-semibold text-slate-900 mb-3">System Status</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600 mr-3">
                <i class="fas fa-database text-sm"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Database</p>
                <p class="font-medium">Online</p>
            </div>
        </div>
        
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600 mr-3">
                <i class="fas fa-server text-sm"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Server</p>
                <p class="font-medium">Running</p>
            </div>
        </div>
        
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-blue-50 text-blue-600 mr-3">
                <i class="fas fa-shield-alt text-sm"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Security</p>
                <p class="font-medium">Active</p>
            </div>
        </div>
        
        <div class="flex items-center">
            <div class="p-2 rounded-lg bg-blue-50 text-blue-600 mr-3">
                <i class="fas fa-sync-alt text-sm"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Last Updated</p>
                <p class="font-medium">{{ now()->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5" id="adminLiveFeed">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Live Form Feed</h3>
            <p class="text-sm text-slate-600 mt-1">New logins and registrations (IP and reported location).</p>
        </div>
        <button type="button" class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-200" data-livefeed-clear>
            Clear
        </button>
    </div>
    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
        <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-slate-100" data-livefeed-list>
            <div class="p-4 text-sm text-slate-500">Waiting for activity...</div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .adminhub-dashboard {
        --adminhub-blue: #3c91e6;
        --adminhub-ink: #342e37;
        --adminhub-surface: #ffffff;
        --adminhub-line: rgba(203, 213, 225, 0.72);
    }

    .adminhub-dashboard .rounded-xl.bg-white {
        border-radius: 1.35rem;
        border-color: var(--adminhub-line);
        box-shadow: 0 20px 46px -36px rgba(15, 23, 42, 0.38);
    }

    .adminhub-dashboard .rounded-lg {
        border-radius: 1rem;
    }

    .adminhub-dashboard .grid:first-child > div {
        transition: transform 180ms ease, box-shadow 180ms ease;
    }

    .adminhub-dashboard .grid:first-child > div:hover {
        transform: translateY(-2px);
        box-shadow: 0 26px 54px -36px rgba(60, 145, 230, 0.42);
    }

    .adminhub-dashboard .grid:first-child i {
        color: currentColor;
    }

    .adminhub-dashboard h3,
    .adminhub-dashboard .font-bold,
    .adminhub-dashboard .font-semibold {
        color: var(--adminhub-ink);
    }

    .adminhub-dashboard .admin-analytics-card {
        position: relative;
        overflow: hidden;
        border: 1px solid transparent;
        border-radius: 1.25rem;
        background:
            linear-gradient(#0f172a, #0f172a) padding-box,
            linear-gradient(45deg, rgba(30, 41, 59, 0.95), rgba(148, 163, 184, 0.45), rgba(30, 41, 59, 0.95)) border-box;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        min-height: 310px;
    }

    .adminhub-dashboard .admin-analytics-card::before {
        content: "";
        position: absolute;
        inset: 0;
        opacity: 0.26;
        background-image:
            radial-gradient(circle at 20% 10%, rgba(59, 130, 246, 0.28), transparent 28%),
            radial-gradient(circle at 80% 0%, rgba(16, 185, 129, 0.16), transparent 30%),
            linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
        background-size: auto, auto, 28px 28px, 28px 28px;
        pointer-events: none;
    }

    .adminhub-dashboard .admin-analytics-card::after {
        content: "";
        position: absolute;
        left: 12%;
        right: 12%;
        bottom: -52px;
        height: 120px;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.15);
        filter: blur(30px);
        transition: opacity 240ms ease, transform 240ms ease;
    }

    .adminhub-dashboard .admin-analytics-card:hover::after {
        opacity: 0.9;
        transform: translateY(-10px);
    }

    .adminhub-dashboard .admin-analytics-card h4,
    .adminhub-dashboard .admin-analytics-card .font-bold,
    .adminhub-dashboard .admin-analytics-card .font-semibold {
        color: #f8fafc;
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    if (typeof Chart === 'undefined') return;

    const analytics = @json($analytics ?? []);
    const labels = analytics.labels || [];

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#020617',
                borderColor: 'rgba(148, 163, 184, 0.25)',
                borderWidth: 1,
                padding: 10,
                titleColor: '#f8fafc',
                bodyColor: '#cbd5e1',
            },
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
            y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.12)' }, ticks: { color: '#94a3b8', precision: 0 } },
        },
    };

    const makeLineChart = (id, data, color, fillColor) => {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: data || [],
                    borderColor: color,
                    backgroundColor: fillColor,
                    tension: 0.42,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: color,
                    borderWidth: 2,
                }],
            },
            options: chartDefaults,
        });
    };

    makeLineChart('adminVoucherPulseChart', analytics.voucher_counts, '#60a5fa', 'rgba(96, 165, 250, 0.16)');
    makeLineChart('adminLeasePulseChart', analytics.lease_counts, '#34d399', 'rgba(52, 211, 153, 0.14)');

    const statusCanvas = document.getElementById('adminLeaseStatusChart');
    if (statusCanvas) {
        new Chart(statusCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: analytics.lease_status_labels || [],
                datasets: [{
                    data: analytics.lease_status_counts || [],
                    backgroundColor: ['#60a5fa', '#34d399', '#f43f5e', '#f59e0b', '#94a3b8'],
                    borderColor: '#0f172a',
                    borderWidth: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#cbd5e1', boxWidth: 10, padding: 12 } },
                    tooltip: chartDefaults.plugins.tooltip,
                },
                cutout: '62%',
            },
        });
    }
})();

(() => {
    const endpoint = @json(route('admin.live.form-interactions'));
    const list = document.querySelector('[data-livefeed-list]');
    const clearBtn = document.querySelector('[data-livefeed-clear]');
    if (!list) return;

    const KEY = 'bwiser:admin:livefeed:lastId';
    let lastId = Number(localStorage.getItem(KEY) || 0) || 0;

    const toast = (() => {
        const node = document.createElement('div');
        node.className = 'mt-3 hidden w-[min(420px,calc(100vw-2rem))] rounded-xl border border-slate-200 bg-white p-4 shadow-xl';
        node.innerHTML = `
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-[1px] text-blue-600">New activity</p>
              <p class="mt-1 text-sm font-semibold text-slate-900" data-toast-title></p>
              <p class="mt-1 text-xs text-slate-600" data-toast-sub></p>
            </div>
            <button class="rounded-lg bg-slate-100 px-2 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-200" type="button" data-toast-close>Close</button>
          </div>
        `;
        const titleBlock = document.querySelector('#mainContent header h1')?.parentElement;
        (titleBlock || document.body).appendChild(node);
        const close = node.querySelector('[data-toast-close]');
        close.addEventListener('click', () => node.classList.add('hidden'));
        let timer = null;

        return (title, sub) => {
            node.querySelector('[data-toast-title]').textContent = title;
            node.querySelector('[data-toast-sub]').textContent = sub || '';
            node.classList.remove('hidden');
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => node.classList.add('hidden'), 6000);
        };
    })();

    const renderRow = (item) => {
        const div = document.createElement('div');
        div.className = 'p-3 flex items-start justify-between gap-4';
        const left = document.createElement('div');
        left.innerHTML = `
          <p class="text-sm font-semibold text-slate-900">${escapeHtml(item.form)} • ${escapeHtml(item.action)}${item.outcome ? ' • ' + escapeHtml(item.outcome) : ''}</p>
          <p class="text-xs text-slate-600 mt-1">${escapeHtml(item.ip || '')}${item.location ? ' • ' + escapeHtml(item.location) : ''}${item.path ? ' • ' + escapeHtml(item.path) : ''}</p>
        `;
        const right = document.createElement('div');
        right.className = 'text-right';
        right.innerHTML = `
          <p class="text-xs font-semibold text-slate-700">${escapeHtml(item.at_human || '')}</p>
        `;
        div.appendChild(left);
        div.appendChild(right);
        return div;
    };

    const escapeHtml = (s) => String(s || '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    const appendItems = (items) => {
        if (!items.length) return;

        if (list.firstElementChild && list.firstElementChild.textContent?.includes('Waiting for activity')) {
            list.innerHTML = '';
        }

        items.forEach((item) => list.prepend(renderRow(item)));

        // Trim to 60 rows.
        while (list.children.length > 60) {
            list.removeChild(list.lastElementChild);
        }
    };

    const poll = async () => {
        try {
            const url = new URL(endpoint, window.location.origin);
            if (lastId > 0) url.searchParams.set('after_id', String(lastId));
            url.searchParams.set('limit', '30');

            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
            if (!res.ok) return;
            const data = await res.json();
            const items = Array.isArray(data.items) ? data.items : [];
            if (!items.length) return;

            const maxId = items.reduce((m, it) => Math.max(m, Number(it.id) || 0), lastId);
            lastId = maxId;
            localStorage.setItem(KEY, String(lastId));

            appendItems(items);
            const newest = items[items.length - 1];
            toast(`${newest.form} • ${newest.action}`, `${newest.ip || ''}${newest.location ? ' • ' + newest.location : ''}`);
        } catch (_) {}
    };

    clearBtn?.addEventListener('click', () => {
        list.innerHTML = '<div class="p-4 text-sm text-slate-500">Waiting for activity...</div>';
        lastId = 0;
        localStorage.removeItem(KEY);
    });

    poll();
    setInterval(poll, 5000);
})();
</script>
@endpush
