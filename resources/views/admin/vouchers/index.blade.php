@extends('Layouts.admin')

@section('title', 'Vouchers Management')
@section('page-title', 'Fuel Vouchers')
@section('page-description', 'Manage all fuel vouchers in the system')
@section('breadcrumb', 'Vouchers')

@php
    // Calculate stats
    $totalVouchers = App\Models\FuelVoucher::count();
    $issuedVouchers = App\Models\FuelVoucher::where('status', 'issued')->count();
    $approvedVouchers = App\Models\FuelVoucher::where('status', 'approved')->count();
    $redeemedVouchers = App\Models\FuelVoucher::where('status', 'redeemed')->count();
    $expiredVouchers = App\Models\FuelVoucher::where('status', 'expired')->count();
    $cancelledVouchers = App\Models\FuelVoucher::where('status', 'cancelled')->count();
    
    $totalAmount = App\Models\FuelVoucher::sum('amount');
    $issuedAmount = App\Models\FuelVoucher::where('status', 'issued')->sum('amount');
    $redeemedAmount = App\Models\FuelVoucher::where('status', 'redeemed')->sum('amount');
    
    // Recent vouchers
    $recentVouchers = App\Models\FuelVoucher::latest()->take(5)->get();
    
    // Status colors
    $statusColors = [
        'issued' => ['bg-blue-100', 'text-blue-800', 'fa-clock'],
        'approved' => ['bg-yellow-100', 'text-yellow-800', 'fa-check-circle'],
        'redeemed' => ['bg-green-100', 'text-green-800', 'fa-gas-pump'],
        'expired' => ['bg-gray-100', 'text-gray-800', 'fa-calendar-times'],
        'cancelled' => ['bg-red-100', 'text-red-800', 'fa-ban'],
    ];
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Vouchers -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Vouchers</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalVouchers) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            ZAR {{ number_format($totalAmount, 2) }} total amount
        </div>
    </div>

    <!-- Issued Vouchers -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Issued</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($issuedVouchers) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium text-yellow-700">
            ZAR {{ number_format($issuedAmount, 2) }} pending
        </div>
    </div>

    <!-- Redeemed Vouchers -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Redeemed</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($redeemedVouchers) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-gas-pump text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            ZAR {{ number_format($redeemedAmount, 2) }} processed
        </div>
    </div>

    <!-- Expired & Cancelled -->
    <div class="bg-gradient-to-br from-red-50 to-white p-5 rounded-2xl shadow-sm border border-red-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-600 text-sm font-semibold">Inactive</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($expiredVouchers + $cancelledVouchers) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-xl">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            {{ number_format($expiredVouchers) }} expired, {{ number_format($cancelledVouchers) }} cancelled
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Voucher Management</h2>
            <p class="text-gray-600 mt-1">View and manage all fuel vouchers in the system</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('admin.vouchers.create') }}"
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-plus mr-2"></i> Create Voucher
            </a>
            <a href="{{ route('admin.vouchers.pending') }}" 
               class="px-5 py-3 bg-gradient-to-r from-yellow-600 to-yellow-700 text-white rounded-xl font-semibold hover:from-yellow-700 hover:to-yellow-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-clock mr-2"></i> Pending Vouchers
                @if($issuedVouchers > 0)
                <span class="ml-2 bg-white text-yellow-700 text-xs font-bold px-2 py-1 rounded-full">
                    {{ $issuedVouchers }}
                </span>
                @endif
            </a>
            <button onclick="toggleBulkActions()" 
                    class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-cogs mr-2"></i> Bulk Actions
            </button>
            <button onclick="toggleFilters()" 
                    class="px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300">
                <i class="fas fa-filter"></i>
                <span class="sr-only">Focus filters</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">Today's Vouchers</p>
            <p class="text-xl font-bold text-gray-900">
                {{ App\Models\FuelVoucher::whereDate('created_at', today())->count() }}
            </p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">This Week</p>
            <p class="text-xl font-bold text-gray-900">
                {{ App\Models\FuelVoucher::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count() }}
            </p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">Avg. Voucher Amount</p>
            <p class="text-xl font-bold text-gray-900">
                ZAR {{ $totalVouchers > 0 ? number_format($totalAmount / $totalVouchers, 2) : '0.00' }}
            </p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">Approved Today</p>
            <p class="text-xl font-bold text-gray-900">
                {{ App\Models\FuelVoucher::where('status', 'approved')->whereDate('updated_at', today())->count() }}
            </p>
        </div>
    </div>

    <!-- Bulk Actions Dropdown -->
    <div id="bulkActions" class="hidden bg-white p-4 rounded-xl shadow-md border border-gray-200 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-gray-700 font-medium">Bulk Actions:</span>
                <select id="bulkActionSelect" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose action...</option>
                    <option value="approve">Approve Vouchers</option>
                    <option value="reject">Reject Vouchers</option>
                    <option value="expire">Mark as Expired</option>
                    <option value="export">Export Selected</option>
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
    <form id="filterForm" action="{{ route('admin.vouchers.index') }}" method="GET" class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6" data-admin-search-form>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2 relative" data-admin-typeahead>
                <input type="text" 
                       id="voucherSearchInput"
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search by voucher code, user name, or email..." 
                       autocomplete="off"
                       role="combobox"
                       aria-expanded="false"
                       aria-controls="voucherSearchSuggestions"
                       data-suggestions-url="{{ route('admin.vouchers.api.suggestions') }}"
                       class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                <div
                    id="voucherSearchSuggestions"
                    class="admin-search-suggestions hidden"
                    role="listbox"
                    aria-label="Voucher search suggestions"
                ></div>
            </div>
            
            <!-- Status Filter -->
            <select name="status" 
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <option value="">All Status</option>
                <option value="issued" {{ request('status') == 'issued' ? 'selected' : '' }}>Issued</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="redeemed" {{ request('status') == 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            
            <!-- Station Filter -->
            <select name="station_id" 
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <option value="">All Stations</option>
                @foreach($stations as $station)
                    <option value="{{ $station->id }}" {{ request('station_id') == $station->id ? 'selected' : '' }}>
                        {{ $station->name }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <!-- Date Range -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" 
                       name="date_from" 
                       value="{{ request('date_from') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" 
                       name="date_to" 
                       value="{{ request('date_to') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="w-full px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Apply Filters
                </button>
            </div>
        </div>
        
        <!-- Quick Status Filters -->
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ route('admin.vouchers.index') }}" 
               class="px-4 py-2 {{ !request('status') && !request('station_id') && !request('date_from') && !request('date_to') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-blue-200">
                All ({{ $totalVouchers }})
            </a>
            <a href="{{ route('admin.vouchers.index', ['status' => 'issued']) }}" 
               class="px-4 py-2 {{ request('status') == 'issued' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-blue-200">
                Issued ({{ $issuedVouchers }})
            </a>
            <a href="{{ route('admin.vouchers.index', ['status' => 'approved']) }}" 
               class="px-4 py-2 {{ request('status') == 'approved' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-yellow-200">
                Approved ({{ $approvedVouchers }})
            </a>
            <a href="{{ route('admin.vouchers.index', ['status' => 'redeemed']) }}" 
               class="px-4 py-2 {{ request('status') == 'redeemed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-green-200">
                Redeemed ({{ $redeemedVouchers }})
            </a>
            <a href="{{ route('admin.vouchers.index', ['status' => 'expired']) }}" 
               class="px-4 py-2 {{ request('status') == 'expired' ? 'bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-gray-200">
                Expired ({{ $expiredVouchers }})
            </a>
            <a href="{{ route('admin.vouchers.index', ['status' => 'cancelled']) }}" 
               class="px-4 py-2 {{ request('status') == 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-red-200">
                Cancelled ({{ $cancelledVouchers }})
            </a>
        </div>
    </form>

    <!-- Vouchers Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-3">Voucher Details</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            User & Station
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Amount & Type
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Status & Dates
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($vouchers as $voucher)
                    <tr class="hover:bg-gray-50 transition-colors duration-150" id="voucher-{{ $voucher->id }}">
                        <!-- Voucher Details -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="selected_vouchers[]" 
                                       value="{{ $voucher->id }}" 
                                       class="voucher-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        <a href="{{ route('admin.vouchers.show', $voucher) }}" class="hover:text-blue-600">
                                            {{ $voucher->code ?? 'Voucher #' . $voucher->id }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        ID: {{ $voucher->id }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $voucher->created_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- User & Station -->
                        <td class="px-6 py-5">
                            <div class="space-y-2">
                                <!-- User -->
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('admin.users.show', $voucher->user) }}" class="hover:text-blue-600">
                                                {{ $voucher->user->name ?? 'Unknown User' }}
                                            </a>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $voucher->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                
                                <!-- Station -->
                                @if($voucher->fuelStation)
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center">
                                        <i class="fas fa-gas-pump text-green-600 text-sm"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('admin.stations.show', $voucher->fuelStation) }}" class="hover:text-green-600">
                                                {{ $voucher->fuelStation->name }}
                                            </a>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $voucher->fuelStation->city ?? '' }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Amount & Type -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-2">
                                <div>
                                    <div class="text-xl font-bold text-gray-900">
                                        ZAR {{ number_format($voucher->amount, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        @if($voucher->lease)
                                            <span class="inline-flex items-center">
                                                <i class="fas fa-credit-card mr-1 text-purple-500"></i>
                                                BNPL
                                            </span>
                                        @else
                                            <span class="inline-flex items-center">
                                                <i class="fas fa-wallet mr-1 text-green-500"></i>
                                                Wallet
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($voucher->lease)
                                <div class="text-xs text-gray-500">
                                    <div>Lease: #{{ $voucher->lease->id }}</div>
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded-full text-xs">
                                            Installment
                                        </span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Status & Dates -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-2">
                                <!-- Status Badge -->
                                @php
                                    $status = $statusColors[$voucher->status] ?? $statusColors['issued'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                    <i class="fas {{ $status[2] }} mr-1.5"></i>
                                    {{ ucfirst($voucher->status) }}
                                </span>
                                @if($voucher->status === 'issued')
                                    <div class="mt-1">
                                        <span class="inline-flex rounded-md bg-slate-800 py-0.5 px-2.5 border border-transparent text-sm text-white transition-all shadow-sm">
                                            Pending Approval
                                        </span>
                                    </div>
                                @endif
                                
                                <!-- Dates -->
                                <div class="space-y-1 text-xs text-gray-500">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-plus mr-1.5 text-gray-400"></i>
                                        Issued: {{ $voucher->issued_at?->format('M d, Y') ?? 'N/A' }}
                                    </div>
                                    @if($voucher->expires_at)
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-times mr-1.5 text-gray-400"></i>
                                        Expires: {{ $voucher->expires_at->format('M d, Y') }}
                                        @if($voucher->expires_at->isPast() && $voucher->status == 'issued')
                                            <span class="ml-2 text-red-500">
                                                <i class="fas fa-exclamation-circle"></i>
                                            </span>
                                        @endif
                                    </div>
                                    @endif
                                    @if($voucher->redeemed_at)
                                    <div class="flex items-center">
                                        <i class="fas fa-gas-pump mr-1.5 text-gray-400"></i>
                                        Redeemed: {{ $voucher->redeemed_at->format('M d, Y') }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-5 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.vouchers.show', $voucher) }}" 
                                   class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if($voucher->status === 'issued')
                                    <form action="{{ route('admin.vouchers.approve', $voucher) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('Approve voucher {{ $voucher->code ?? $voucher->id }}?')"
                                                class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors"
                                                title="Approve">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                    
                                    <button onclick="showRejectModal({{ $voucher->id }}, '{{ addslashes($voucher->code ?? 'Voucher #' . $voucher->id) }}')" 
                                            class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Reject">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                @endif
                                
                                <!-- Quick Actions Dropdown -->
                                <div class="relative group">
                                    <button class="text-gray-600 hover:text-gray-900 p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-10">
                                        @if($voucher->user)
                                        <a href="{{ route('admin.users.show', $voucher->user) }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-user mr-2 text-blue-500"></i> View User
                                        </a>
                                        @endif
                                        
                                        @if($voucher->fuelStation)
                                        <a href="{{ route('admin.stations.show', $voucher->fuelStation) }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-gas-pump mr-2 text-green-500"></i> View Station
                                        </a>
                                        @endif
                                        
                                        @if($voucher->lease)
                                        <a href="#" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-credit-card mr-2 text-purple-500"></i> View Lease
                                        </a>
                                        @endif
                                        
                                        @if($voucher->settlement)
                                        <a href="{{ route('admin.settlements.show', $voucher->settlement) }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-money-check-alt mr-2 text-yellow-500"></i> View Settlement
                                        </a>
                                        @endif
                                        
                                        @if($voucher->status === 'issued' && $voucher->expires_at && $voucher->expires_at->isPast())
                                        <form action="{{ route('admin.vouchers.bulk-action') }}" method="POST" class="block">
                                            @csrf
                                            <input type="hidden" name="action" value="expire">
                                            <input type="hidden" name="vouchers[]" value="{{ $voucher->id }}">
                                            <button type="submit" 
                                                    class="w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-50">
                                                <i class="fas fa-calendar-times mr-2 text-gray-500"></i> Mark as Expired
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-ticket-alt text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-700">No vouchers found</p>
                                <p class="text-gray-500 mt-1">No vouchers match your filter criteria</p>
                                <a href="{{ route('admin.vouchers.index') }}" 
                                   class="inline-block mt-4 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Clear Filters
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($vouchers->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-semibold">{{ $vouchers->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $vouchers->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $vouchers->total() }}</span> vouchers
                </div>
                <div class="flex space-x-2">
                    @if($vouchers->onFirstPage())
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $vouchers->previousPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    @foreach(range(1, min(5, $vouchers->lastPage())) as $page)
                        <a href="{{ $vouchers->url($page) }}" 
                           class="px-3 py-2 border rounded-lg {{ $vouchers->currentPage() == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            {{ $page }}
                        </a>
                    @endforeach

                    @if($vouchers->hasMorePages())
                        <a href="{{ $vouchers->nextPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
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

    <!-- Additional Information Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Vouchers</h3>
            <div class="space-y-3">
                @forelse($recentVouchers as $recent)
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg {{ $statusColors[$recent->status][0] }} flex items-center justify-center mr-3">
                            <i class="fas {{ $statusColors[$recent->status][2] }} {{ $statusColors[$recent->status][1] }} text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $recent->code ?? 'Voucher #' . $recent->id }}</p>
                            <p class="text-xs text-gray-500">{{ $recent->user->name ?? 'Unknown' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-900">ZAR {{ number_format($recent->amount, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $recent->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="fas fa-ticket-alt text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">No recent vouchers</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('admin.vouchers.pending') }}" 
                   class="flex items-center p-3 bg-yellow-50 text-yellow-700 rounded-xl hover:bg-yellow-100 transition-colors">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <span class="font-medium">Review Pending Vouchers</span>
                    @if($issuedVouchers > 0)
                    <span class="ml-auto bg-yellow-600 text-white text-xs font-bold px-2 py-1 rounded-full">
                        {{ $issuedVouchers }}
                    </span>
                    @endif
                </a>
                
                <button onclick="exportVouchers()" 
                        class="w-full flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-file-export text-green-600"></i>
                    </div>
                    <span class="font-medium">Export All Vouchers</span>
                </button>
                
                <a href="{{ route('admin.users.index') }}" 
                   class="flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                    <span class="font-medium">View All Users</span>
                </a>
                
                <a href="{{ route('admin.stations.index') }}" 
                   class="flex items-center p-3 bg-purple-50 text-purple-700 rounded-xl hover:bg-purple-100 transition-colors">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-gas-pump text-purple-600"></i>
                    </div>
                    <span class="font-medium">View All Stations</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
       <form action="{{ route('admin.vouchers.reject', $voucher) }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Reject Voucher</h3>
                    <button type="button" onclick="closeRejectModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">Voucher: <span id="rejectVoucherCode" class="font-semibold text-gray-900"></span></p>
                    <p class="text-sm text-gray-500 mt-1">Provide a reason for rejecting this voucher.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for Rejection
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain why this voucher is being rejected..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeRejectModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Reject Voucher
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Action Modal -->
<div id="bulkActionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="bulkActionForm" method="POST" action="{{ route('admin.vouchers.bulk-action') }}">
            @csrf
            <input type="hidden" id="bulkActionType" name="action">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900" id="bulkActionTitle"></h3>
                    <button type="button" onclick="closeBulkActionModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600" id="bulkActionDescription"></p>
                    <p class="text-sm text-gray-500 mt-1">
                        Selected: <span id="selectedVouchersCount" class="font-semibold">0</span> vouchers
                    </p>
                </div>
                <div class="space-y-4" id="rejectReasonSection" style="display: none;">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for Rejection
                        </label>
                        <textarea name="reason" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain the reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeBulkActionModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Confirm Action
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .admin-search-suggestions {
        position: absolute;
        z-index: 40;
        top: calc(100% + 0.5rem);
        left: 0;
        right: 0;
        max-height: 21rem;
        overflow-y: auto;
        border: 1px solid #dbe4ef;
        border-radius: 0.875rem;
        background: #fff;
        box-shadow: 0 18px 45px -28px rgba(15, 23, 42, 0.55);
    }

    .admin-search-suggestion {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
        padding: 0.85rem 1rem;
        text-align: left;
        transition: background-color 120ms ease, transform 120ms ease;
    }

    .admin-search-suggestion:last-child {
        border-bottom: 0;
    }

    .admin-search-suggestion:hover,
    .admin-search-suggestion:focus {
        background: #eff6ff;
        outline: none;
    }

    .admin-search-suggestion__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .admin-search-suggestion__label {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.9rem;
        font-weight: 700;
        color: #0f172a;
    }

    .admin-search-suggestion__badge {
        flex: 0 0 auto;
        border-radius: 999px;
        background: #e0f2fe;
        padding: 0.15rem 0.55rem;
        font-size: 0.68rem;
        font-weight: 800;
        color: #075985;
    }

    .admin-search-suggestion__meta {
        margin-top: 0.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.78rem;
        color: #64748b;
    }

    #filterForm.admin-filter-focus {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18), 0 18px 42px -34px rgba(15, 23, 42, 0.55);
    }
</style>

<script>
    // Toggle filters
    function toggleFilters() {
        const filterForm = document.getElementById('filterForm');
        filterForm?.classList.remove('hidden');
        filterForm?.classList.add('admin-filter-focus');
        document.getElementById('voucherSearchInput')?.focus();
        setTimeout(() => filterForm?.classList.remove('admin-filter-focus'), 1400);
    }

    // Toggle bulk actions
    function toggleBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        bulkActions.classList.toggle('hidden');
    }

    // Select all checkboxes
    document.getElementById('selectAll')?.addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.voucher-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });


    // Line around 530:
function showRejectModal(voucherId, voucherCode) {
    document.getElementById('rejectVoucherCode').textContent = voucherCode;
    const form = document.getElementById('rejectForm');
    form.action = `/admin/vouchers/${voucherId}/reject`;
    document.getElementById('rejectModal').classList.remove('hidden');
}

    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectForm').reset();
    }

    // Bulk Action Modal
    function showBulkActionModal(action) {
        const selectedCount = document.querySelectorAll('.voucher-checkbox:checked').length;
        
        if (selectedCount === 0) {
            alert('Please select at least one voucher');
            return;
        }
        
        const titles = {
            'approve': 'Approve Multiple Vouchers',
            'reject': 'Reject Multiple Vouchers',
            'expire': 'Mark Vouchers as Expired',
            'export': 'Export Selected Vouchers'
        };
        
        const descriptions = {
            'approve': 'Are you sure you want to approve the selected vouchers?',
            'reject': 'Are you sure you want to reject the selected vouchers?',
            'expire': 'Are you sure you want to mark the selected vouchers as expired?',
            'export': 'Export selected vouchers to CSV file?'
        };
        
        document.getElementById('bulkActionTitle').textContent = titles[action];
        document.getElementById('bulkActionDescription').textContent = descriptions[action];
        document.getElementById('selectedVouchersCount').textContent = selectedCount;
        document.getElementById('bulkActionType').value = action;
        
        // Show reason field only for reject action
        const reasonSection = document.getElementById('rejectReasonSection');
        reasonSection.style.display = action === 'reject' ? 'block' : 'none';
        
        // Add selected vouchers to form
        const selectedVouchers = Array.from(document.querySelectorAll('.voucher-checkbox:checked'))
                                    .map(cb => cb.value);
        
        // Remove any existing voucher inputs
        const existingInputs = document.querySelectorAll('input[name="vouchers[]"]');
        existingInputs.forEach(input => input.remove());
        
        // Add new voucher inputs
        selectedVouchers.forEach(voucherId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'vouchers[]';
            input.value = voucherId;
            document.getElementById('bulkActionForm').appendChild(input);
        });
        
        
        document.getElementById('bulkActionModal').classList.remove('hidden');
    }
    
    function closeBulkActionModal() {
        document.getElementById('bulkActionModal').classList.add('hidden');
    }

    // Apply bulk action from dropdown
    function applyBulkAction() {
        const action = document.getElementById('bulkActionSelect').value;
        
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        if (action === 'export') {
            exportSelectedVouchers();
        } else {
            showBulkActionModal(action);
        }
    }

    // Export selected vouchers
    function exportSelectedVouchers() {
        const selectedVouchers = Array.from(document.querySelectorAll('.voucher-checkbox:checked'))
                                    .map(cb => cb.value);
        
        if (selectedVouchers.length === 0) {
            if (!confirm('Export all vouchers?')) return;
            window.location.href = "{{ route('admin.vouchers.export') }}";
            return;
        }
        
        window.location.href = "{{ route('admin.vouchers.export') }}?vouchers=" + selectedVouchers.join(',');
    }

    // Export all vouchers
    function exportVouchers() {
        if (confirm('Export all vouchers to CSV?')) {
            window.location.href = "{{ route('admin.vouchers.export') }}";
        }
    }

    // Auto-submit filter form on select change
    document.querySelectorAll('#filterForm select').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });

    function setupAdminSearchTypeahead(inputId, panelId) {
        const input = document.getElementById(inputId);
        const panel = document.getElementById(panelId);
        const form = document.getElementById('filterForm');
        if (!input || !panel || !form) return;

        let timer = null;
        let controller = null;

        const hide = () => {
            panel.classList.add('hidden');
            panel.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
        };

        const render = (items) => {
            if (!items.length) {
                panel.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No matching vouchers yet.</div>';
                panel.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
                return;
            }

            panel.innerHTML = items.map((item) => `
                <button type="button" class="admin-search-suggestion" role="option" data-value="${escapeHtml(item.value || '')}">
                    <span class="admin-search-suggestion__top">
                        <span class="admin-search-suggestion__label">${escapeHtml(item.label || '')}</span>
                        <span class="admin-search-suggestion__badge">${escapeHtml(item.badge || 'MATCH')}</span>
                    </span>
                    <span class="admin-search-suggestion__meta">${escapeHtml(item.meta || '')}</span>
                </button>
            `).join('');

            panel.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
        };

        const fetchSuggestions = () => {
            const query = input.value.trim();
            if (query.length < 2) {
                hide();
                return;
            }

            controller?.abort();
            controller = new AbortController();
            const url = new URL(input.dataset.suggestionsUrl, window.location.origin);
            url.searchParams.set('q', query);

            fetch(url, {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal,
            })
                .then((response) => response.ok ? response.json() : { items: [] })
                .then((payload) => render(Array.isArray(payload.items) ? payload.items : []))
                .catch((error) => {
                    if (error.name !== 'AbortError') hide();
                });
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(fetchSuggestions, 180);
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2) fetchSuggestions();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') hide();
        });

        panel.addEventListener('click', (event) => {
            const option = event.target.closest('.admin-search-suggestion');
            if (!option) return;
            input.value = option.dataset.value || '';
            hide();
            form.submit();
        });

        document.addEventListener('click', (event) => {
            if (!panel.contains(event.target) && event.target !== input) hide();
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    setupAdminSearchTypeahead('voucherSearchInput', 'voucherSearchSuggestions');

    // Handle form submissions
    document.getElementById('rejectForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        submitButton.disabled = true;
        
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Voucher rejected successfully!');
                location.reload();
            } else {
                alert('Failed to reject voucher');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        });
    });
// Line around 1030:

    // Handle bulk action form
    document.getElementById('bulkActionForm')?.addEventListener('submit', function(e) {
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        submitButton.disabled = true;
    });
</script>
@endsection
