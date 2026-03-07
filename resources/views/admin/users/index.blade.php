@extends('Layouts.admin')

@section('title', 'Users Management')
@section('page-title', 'User Management')
@section('page-description', 'Manage all system users and their BNPL accounts')
@section('breadcrumb', 'Users')

@php
    // Calculate stats from the controller's query
    $totalUsers = App\Models\User::count();
    $activeUsers = App\Models\User::where('status', 'active')->count();
    $suspendedUsers = App\Models\User::where('status', 'suspended')->count();
    $flaggedUsers = App\Models\User::where('status', 'flagged')->count();
    $blockedUsers = App\Models\User::where('status', 'blocked')->count();
    
    // Calculate total credit limit exposure
    $totalCreditLimit = App\Models\CreditLimit::sum('limit');
    $totalUsedCredit = App\Models\CreditLimit::sum('used');
    $creditUtilization = $totalCreditLimit > 0 ? ($totalUsedCredit / $totalCreditLimit) * 100 : 0;
    
    // Recent registrations
    $recentUsers = App\Models\User::latest()->take(5)->get();
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Users Card -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalUsers) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium text-green-600 flex items-center">
            <i class="fas fa-arrow-up mr-1 text-xs"></i> {{ number_format($recentUsers->count()) }} new this week
        </div>
    </div>

    <!-- Active Users Card -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Active Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($activeUsers) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium">
            <span class="text-gray-600">{{ number_format(($activeUsers / max($totalUsers, 1)) * 100, 1) }}% of total</span>
        </div>
    </div>

    <!-- Credit Exposure Card -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Credit Exposure</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">ZAR {{ number_format($totalCreditLimit) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-credit-card text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full" 
                     style="width: {{ min($creditUtilization, 100) }}%"></div>
            </div>
            <div class="text-xs text-gray-600 mt-1">{{ number_format($creditUtilization, 1) }}% utilized (ZAR {{ number_format($totalUsedCredit) }})</div>
        </div>
    </div>

    <!-- Risk Users Card -->
    <div class="bg-gradient-to-br from-red-50 to-white p-5 rounded-2xl shadow-sm border border-red-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-600 text-sm font-semibold">Risk Accounts</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($suspendedUsers + $flaggedUsers + $blockedUsers) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-xl">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <button onclick="showRiskReport()" class="text-xs bg-red-100 text-red-800 px-3 py-1 rounded-full font-medium hover:bg-red-200 transition-colors">
                Review needed
            </button>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">User Directory</h2>
            <p class="text-gray-600 mt-1">Manage all registered users and their BNPL accounts</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('admin.users.account-approvals') }}" 
               class="px-5 py-3 bg-white border border-blue-200 text-blue-700 rounded-xl font-semibold hover:bg-blue-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-user-check mr-2"></i> Account Approvals
            </a>
            <a href="{{ route('admin.users.create') }}" 
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-user-plus mr-2 group-hover:rotate-90 transition-transform"></i> Add New User
            </a>
            <button onclick="toggleBulkActions()" 
                    class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-cogs mr-2"></i> Bulk Actions
            </button>
            <button onclick="toggleFilters()" 
                    class="px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300">
                <i class="fas fa-filter"></i>
            </button>
        </div>
    </div>

    <!-- Bulk Actions Dropdown -->
    <div id="bulkActions" class="hidden bg-white p-4 rounded-xl shadow-md border border-gray-200 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-gray-700 font-medium">Bulk Actions:</span>
                <select id="bulkActionSelect" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose action...</option>
                    <option value="activate">Activate Users</option>
                    <option value="suspend">Suspend Users</option>
                    <option value="export">Export Selected</option>
                    <option value="assign_role">Assign Role</option>
                </select>
                <button onclick="applyBulkAction()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Apply
                </button>
            </div>
            <button onclick="toggleBulkActions()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Search and Filters Form -->
    <form id="filterForm" action="{{ route('admin.users.index') }}" method="GET" class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="md:col-span-2 relative">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search users by name, email, or phone..." 
                       class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
            </div>
            
            <!-- Role Filter -->
            <select name="role" 
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
            
            <!-- Status Filter -->
            <select name="status" 
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="flagged" {{ request('status') == 'flagged' ? 'selected' : '' }}>Flagged</option>
                <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
            </select>
        </div>
        
        <!-- Filter Buttons -->
        <div class="flex flex-wrap gap-2 mt-4">
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                Apply Filters
            </button>
            <a href="{{ route('admin.users.index') }}" 
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                Clear All
            </a>
            <div class="flex flex-wrap gap-2 ml-4">
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 {{ !request('status') && !request('role') && !request('search') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-blue-200">
                    All ({{ $totalUsers }})
                </a>
                <a href="{{ route('admin.users.index', ['status' => 'active']) }}" 
                   class="px-4 py-2 {{ request('status') == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-green-200">
                    Active ({{ $activeUsers }})
                </a>
                <a href="{{ route('admin.users.index', ['status' => 'suspended']) }}" 
                   class="px-4 py-2 {{ request('status') == 'suspended' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-yellow-200">
                    Suspended ({{ $suspendedUsers }})
                </a>
                <a href="{{ route('admin.users.index', ['status' => 'flagged']) }}" 
                   class="px-4 py-2 {{ request('status') == 'flagged' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-orange-200">
                    Flagged ({{ $flaggedUsers }})
                </a>
                <a href="{{ route('admin.users.index', ['status' => 'blocked']) }}" 
                   class="px-4 py-2 {{ request('status') == 'blocked' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-red-200">
                    Blocked ({{ $blockedUsers }})
                </a>
            </div>
        </div>
    </form>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-3">User</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Credit Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Wallet & Limits
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Role & Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Last Active
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors duration-150" id="user-{{ $user->id }}">
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="selected_users[]" 
                                       value="{{ $user->id }}" 
                                       class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                                <div class="flex-shrink-0">
                                    @if($user->profile_photo)
                                        <img class="w-12 h-12 rounded-xl object-cover" src="{{ $user->profile_photo }}" alt="{{ $user->name }}">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                            <span class="text-blue-600 font-bold text-lg">{{ substr($user->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <a href="{{ route('admin.users.show', $user) }}" class="hover:text-blue-600">
                                            {{ $user->name }}
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $user->email ?? 'No email' }}</div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-phone-alt mr-1"></i> {{ $user->phone }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Credit Status -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            @if($user->creditLimit)
                                <div class="space-y-2">
                                    <div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Limit:</span>
                                            <span class="font-bold text-gray-900">ZAR {{ number_format($user->creditLimit->limit) }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                            @php
                                                $utilization = $user->creditLimit->limit > 0 ? 
                                                    ($user->creditLimit->used / $user->creditLimit->limit) * 100 : 0;
                                                $color = $utilization > 80 ? 'bg-red-500' : 
                                                         ($utilization > 50 ? 'bg-yellow-500' : 'bg-green-500');
                                            @endphp
                                            <div class="h-1.5 rounded-full {{ $color }}" 
                                                 style="width: {{ min($utilization, 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Used: ZAR {{ number_format($user->creditLimit->used) }} ({{ number_format($utilization, 1) }}%)
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-gray-500">No credit limit</span>
                            @endif
                        </td>
                        
                        <!-- Wallet & Limits -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            @if($user->wallet)
                                <div class="space-y-1">
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-wallet text-gray-400 mr-2"></i>
                                        <span class="font-semibold text-gray-900">ZAR {{ number_format($user->wallet->balance, 2) }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Credit Score: 
                                        <span class="font-medium {{ $user->credit_score >= 700 ? 'text-green-600' : 
                                                                   ($user->credit_score >= 600 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $user->credit_score ?? 'N/A' }}
                                        </span>
                                    </div>
                                    @if($user->credit_score)
                                        <div class="text-xs text-gray-500">
  
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-sm text-gray-500">No wallet</span>
                            @endif
                        </td>
                        
                        <!-- Role & Status -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-2">
                                <!-- Status Badge -->
                                @php
                                    $statusColors = [
                                        'active' => ['bg-green-100', 'text-green-800', 'Active', 'fa-check-circle'],
                                        'suspended' => ['bg-yellow-100', 'text-yellow-800', 'Suspended', 'fa-user-slash'],
                                        'flagged' => ['bg-orange-100', 'text-orange-800', 'Flagged', 'fa-flag'],
                                        'blocked' => ['bg-red-100', 'text-red-800', 'Blocked', 'fa-ban'],
                                    ];
                                    $status = $statusColors[$user->status] ?? $statusColors['active'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                    <i class="fas {{ $status[3] }} mr-1.5"></i>
                                    {{ $status[2] }}
                                </span>
                                
                                <!-- Role Badge -->
                                @if($user->roles->count() > 0)
                                    <div class="mt-2">
                                        @foreach($user->roles as $role)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                {{ $role->name == 'admin' ? 'bg-red-100 text-red-800' : 
                                                   ($role->name == 'borrower' ? 'bg-blue-100 text-blue-800' : 
                                                   'bg-gray-100 text-gray-800') }}">
                                                <i class="fas {{ $role->name == 'admin' ? 'fa-shield-alt' : 'fa-user' }} mr-1"></i>
                                                {{ ucfirst($role->name) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Last Active -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $user->last_login_at ? $user->last_login_at->format('M d, Y') : 'Never' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->diffForHumans() }}
                                @endif
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-5 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.users.show', $user) }}" 
                                   class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors group relative"
                                   title="View Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" 
                                   class="text-yellow-600 hover:text-yellow-900 p-2 hover:bg-yellow-50 rounded-lg transition-colors group relative"
                                   title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <!-- Quick Status Toggle -->
                                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="text-gray-600 hover:text-gray-900 p-2 hover:bg-gray-50 rounded-lg transition-colors group relative"
                                            title="{{ $user->status == 'active' ? 'Suspend User' : 'Activate User' }}">
                                        <i class="fas {{ $user->status == 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                </form>
                                
                                <!-- Quick Actions Dropdown -->
                                <div class="relative group">
                                    <button class="text-gray-600 hover:text-gray-900 p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-10">
                                        <a href="#" 
                                           onclick="showCreditLimitModal({{ $user->id }}, '{{ $user->name }}', {{ $user->creditLimit->limit ?? 0 }})"
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-credit-card mr-2 text-blue-500"></i> Adjust Credit Limit
                                        </a>
                                        <a href="#" 
                                           onclick="showWalletModal({{ $user->id }}, '{{ $user->name }}')"
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-wallet mr-2 text-green-500"></i> Adjust Wallet
                                        </a>
                                        <a href="mailto:{{ $user->email }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-envelope mr-2 text-purple-500"></i> Send Email
                                        </a>
                                        <a href="sms:{{ $user->phone }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-sms mr-2 text-yellow-500"></i> Send SMS
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-users text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-700">No users found</p>
                                <p class="text-gray-500 mt-1">Get started by creating your first user</p>
                                <a href="{{ route('admin.users.create') }}" 
                                   class="inline-block mt-4 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i> Add New User
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-semibold">{{ $users->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $users->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $users->total() }}</span> users
                </div>
                <div class="flex space-x-2">
                    @if($users->onFirstPage())
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $users->previousPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    @foreach(range(1, min(5, $users->lastPage())) as $page)
                        <a href="{{ $users->url($page) }}" 
                           class="px-3 py-2 border rounded-lg {{ $users->currentPage() == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            {{ $page }}
                        </a>
                    @endforeach

                    @if($users->hasMorePages())
                        <a href="{{ $users->nextPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Recent Activity Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
        <!-- Recent Registrations -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Registrations</h3>
            <div class="space-y-4">
                @forelse($recentUsers as $recentUser)
                <div class="flex items-center p-3 hover:bg-gray-50 rounded-xl transition-colors">
                    <div class="flex-shrink-0">
                        @if($recentUser->profile_photo)
                            <img class="w-10 h-10 rounded-lg object-cover" src="{{ $recentUser->profile_photo }}" alt="{{ $recentUser->name }}">
                        @else
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                <span class="text-blue-600 font-bold">{{ substr($recentUser->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $recentUser->name }}</p>
                        <p class="text-xs text-gray-500">{{ $recentUser->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full 
                        {{ $recentUser->status == 'active' ? 'bg-green-100 text-green-800' : 
                           ($recentUser->status == 'suspended' ? 'bg-yellow-100 text-yellow-800' : 
                           'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst($recentUser->status) }}
                    </span>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="fas fa-user-plus text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">No recent registrations</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Avg. Credit Score</span>
                    <span class="font-bold text-gray-900">
                        {{ number_format(App\Models\User::avg('credit_score') ?? 0, 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Avg. Wallet Balance</span>
                    <span class="font-bold text-green-600">
                        ZAR {{ number_format(App\Models\Wallet::avg('balance') ?? 0, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Outstanding</span>
                    <span class="font-bold text-red-600">
                        ZAR {{ number_format(App\Models\Wallet::sum('outstanding_balance') ?? 0, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Active Today</span>
                    <span class="font-bold text-blue-600">
                        {{ App\Models\User::whereDate('last_login_at', today())->count() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('admin.users.create') }}" 
                   class="flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-user-plus text-blue-600"></i>
                    </div>
                    <span class="font-medium">Add New User</span>
                </a>
                <button onclick="exportUsers()" 
                        class="w-full flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-file-export text-green-600"></i>
                    </div>
                    <span class="font-medium">Export Users</span>
                </button>
                <button onclick="showBulkMessage()" 
                        class="w-full flex items-center p-3 bg-purple-50 text-purple-700 rounded-xl hover:bg-purple-100 transition-colors">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-envelope text-purple-600"></i>
                    </div>
                    <span class="font-medium">Bulk Message</span>
                </button>
                <button onclick="showRiskReport()" 
                        class="w-full flex items-center p-3 bg-red-50 text-red-700 rounded-xl hover:bg-red-100 transition-colors">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <span class="font-medium">Risk Report</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Credit Limit Modal -->
<div id="creditLimitModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="creditLimitForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Adjust Credit Limit</h3>
                    <button type="button" onclick="closeCreditLimitModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">User: <span id="creditLimitUserName" class="font-semibold text-gray-900"></span></p>
                    <p class="text-sm text-gray-500 mt-1">Current Limit: <span id="currentCreditLimit" class="font-medium"></span></p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Limit (ZAR)
                        </label>
                        <input type="number" 
                               name="limit" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter new credit limit">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for Adjustment
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain the reason for this adjustment..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeCreditLimitModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Limit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Wallet Adjustment Modal -->
<div id="walletModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="walletForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Adjust Wallet Balance</h3>
                    <button type="button" onclick="closeWalletModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">User: <span id="walletUserName" class="font-semibold text-gray-900"></span></p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type
                        </label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="credit" 
                                       checked
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Credit (Add)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="debit" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Debit (Subtract)</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Amount (ZAR)
                        </label>
                        <input type="number" 
                               name="amount" 
                               required
                               step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter amount">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain the reason for this adjustment..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeWalletModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Wallet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Bulk actions toggle
    function toggleBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        bulkActions.classList.toggle('hidden');
    }

    // Toggle filters
    function toggleFilters() {
        const filters = document.getElementById('filterForm');
        filters.classList.toggle('hidden');
    }

    // Select all checkboxes
    document.getElementById('selectAll')?.addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    // Credit Limit Modal
    let currentUserId = null;
    
    function showCreditLimitModal(userId, userName, currentLimit) {
        currentUserId = userId;
        document.getElementById('creditLimitUserName').textContent = userName;
        document.getElementById('currentCreditLimit').textContent = `ZAR ${currentLimit.toLocaleString()}`;
        
        const form = document.getElementById('creditLimitForm');
        form.action = `/admin/users/${userId}/credit-limit`;
        
        document.getElementById('creditLimitModal').classList.remove('hidden');
    }
    
    function closeCreditLimitModal() {
        document.getElementById('creditLimitModal').classList.add('hidden');
        document.getElementById('creditLimitForm').reset();
        currentUserId = null;
    }

    // Wallet Modal
    function showWalletModal(userId, userName) {
        currentUserId = userId;
        document.getElementById('walletUserName').textContent = userName;
        
        const form = document.getElementById('walletForm');
        form.action = `/admin/users/${userId}/wallet`;
        
        document.getElementById('walletModal').classList.remove('hidden');
    }
    
    function closeWalletModal() {
        document.getElementById('walletModal').classList.add('hidden');
        document.getElementById('walletForm').reset();
        currentUserId = null;
    }

    // Bulk Actions
    function applyBulkAction() {
        const action = document.getElementById('bulkActionSelect').value;
        const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
                                 .map(cb => cb.value);
        
        if (selectedUsers.length === 0) {
            alert('Please select at least one user');
            return;
        }
        
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        if (confirm(`Apply "${action}" to ${selectedUsers.length} user(s)?`)) {
            // In a real app, you would make an AJAX request here
            console.log(`Applying ${action} to:`, selectedUsers);
            
            // Show success message
            alert(`Action applied to ${selectedUsers.length} user(s)`);
            toggleBulkActions();
        }
    }

    // Export Users
    function exportUsers() {
        const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
                                 .map(cb => cb.value);
        
        if (selectedUsers.length === 0) {
            if (!confirm('Export all users?')) return;
        }
        
        // In a real app, you would redirect to export endpoint
        const url = selectedUsers.length > 0 
            ? `/admin/users/export?users=${selectedUsers.join(',')}`
            : `/admin/users/export`;
        
        window.location.href = url;
    }

    // Bulk Message
    function showBulkMessage() {
        alert('Bulk message feature would open a modal here');
    }

    // Risk Report
    function showRiskReport() {
        alert('Risk report would open in a new tab');
    }

    // Auto-submit filter form on select change
    document.querySelectorAll('#filterForm select').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });

    // Handle form submissions
    document.getElementById('creditLimitForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        // In a real app, you would submit via AJAX
        alert('Credit limit updated successfully!');
        closeCreditLimitModal();
        location.reload();
    });

    document.getElementById('walletForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        // In a real app, you would submit via AJAX
        alert('Wallet updated successfully!');
        closeWalletModal();
        location.reload();
    });
</script>
@endsection
