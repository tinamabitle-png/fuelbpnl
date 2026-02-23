@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stats Cards -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Users</p>
                <p class="text-2xl font-bold">{{ $stats['total_users'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-gas-pump text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Active Stations</p>
                <p class="text-2xl font-bold">{{ $stats['active_stations'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-ticket-alt text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Pending Vouchers</p>
                <p class="text-2xl font-bold">{{ $stats['pending_vouchers'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Settlements</p>
                <p class="text-2xl font-bold">ZAR {{ number_format($stats['total_settlement_amount'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Card -->
<div class="mb-8 bg-white rounded-lg shadow p-6">
    <div class="flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
            <i class="fas fa-user text-2xl"></i>
        </div>
        <div>
            <h3 class="text-lg font-semibold">Welcome, {{ auth()->user()->name }}!</h3>
            <p class="text-gray-600">You are logged in as {{ auth()->user()->getRoleNames()->first() ?? 'User' }}</p>
            <p class="text-sm text-gray-500 mt-1">Email: {{ auth()->user()->email }}</p>
            <p class="text-sm text-gray-500">Last login: {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'First login' }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Vouchers -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Recent Vouchers</h3>
        </div>
        <div class="p-6">
            @if($recent_vouchers->count() > 0)
            <div class="space-y-4">
                @foreach($recent_vouchers as $voucher)
                <div class="flex items-center justify-between border-b pb-3">
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
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.vouchers.index') }}" class="text-blue-600 hover:underline">View All Vouchers</a>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-ticket-alt text-gray-300 text-4xl mb-4"></i>
                <p class="text-gray-500">No vouchers found</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Recent Users</h3>
        </div>
        <div class="p-6">
            @if($recent_users->count() > 0)
            <div class="space-y-4">
                @foreach($recent_users as $user)
                <div class="flex items-center justify-between border-b pb-3">
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
                <i class="fas fa-users text-gray-300 text-4xl mb-4"></i>
                <p class="text-gray-500">No users found</p>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="mt-8 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.users.create') }}" 
           class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-user-plus text-blue-600 text-2xl mb-2"></i>
            <p class="font-medium">Add New User</p>
            <p class="text-sm text-gray-600 mt-1">Create new system user</p>
        </a>
        
        <a href="{{ route('admin.vouchers.pending') }}" 
           class="bg-yellow-50 hover:bg-yellow-100 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-check-circle text-yellow-600 text-2xl mb-2"></i>
            <p class="font-medium">Approve Vouchers</p>
            <p class="text-sm text-gray-600 mt-1">Review pending vouchers</p>
        </a>
        
        <a href="{{ route('admin.settlements.index') }}" 
           class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-center transition duration-200">
            <i class="fas fa-money-check-alt text-green-600 text-2xl mb-2"></i>
            <p class="font-medium">Process Settlements</p>
            <p class="text-sm text-gray-600 mt-1">Manage station payouts</p>
        </a>
    </div>
</div>

<div class="mt-8 bg-white rounded-lg shadow p-6">
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

<div class="mt-8 rounded-lg bg-white p-6 shadow">
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
<div class="mt-8 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">System Status</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                <i class="fas fa-database"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Database</p>
                <p class="font-medium">Online</p>
            </div>
        </div>
        
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                <i class="fas fa-server"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Server</p>
                <p class="font-medium">Running</p>
            </div>
        </div>
        
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Security</p>
                <p class="font-medium">Active</p>
            </div>
        </div>
        
        <div class="flex items-center">
            <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                <i class="fas fa-sync-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Last Updated</p>
                <p class="font-medium">{{ now()->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

@endsection
