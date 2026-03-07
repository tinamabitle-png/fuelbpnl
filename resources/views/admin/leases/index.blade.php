@extends('Layouts.admin')

@section('title', 'Lease Management')
@section('page-title', 'Lease Management')
@section('page-description', 'Manage all BNPL lease agreements')
@section('breadcrumb', 'Leases')

@php
    // Calculate stats
    $totalLeases = App\Models\Lease::count();
    $activeLeases = App\Models\Lease::where('status', 'active')->count();
    $completedLeases = App\Models\Lease::where('status', 'completed')->count();
    $defaultedLeases = App\Models\Lease::where('status', 'defaulted')->count();
    $overdueLeases = App\Models\Lease::where('status', 'active')
        ->where('due_date', '<', now())
        ->count();
    
    $totalLoanAmount = App\Models\Lease::sum('principal_amount');
    $totalInterest = App\Models\Lease::sum('interest_amount');
    $totalRevenue = App\Models\Lease::sum('total_amount');
    $totalPaid = App\Models\Lease::with('repayments')
        ->get()
        ->sum(function($lease) {
            return $lease->repayments->where('status', 'paid')->sum('amount');
        });
    
    $recentLeases = App\Models\Lease::latest()->take(5)->get();
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Leases -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Leases</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalLeases) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-file-contract text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium text-green-600 flex items-center">
            <i class="fas fa-arrow-up mr-1 text-xs"></i> {{ number_format($recentLeases->count()) }} new this week
        </div>
    </div>

    <!-- Active Leases -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Active Leases</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($activeLeases) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-chart-line text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium">
            <span class="text-gray-600">{{ number_format(($activeLeases / max($totalLeases, 1)) * 100, 1) }}% of total</span>
        </div>
    </div>

    <!-- Overdue Leases -->
    <div class="bg-gradient-to-br from-red-50 to-white p-5 rounded-2xl shadow-sm border border-red-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-600 text-sm font-semibold">Overdue Leases</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overdueLeases) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-xl">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <button onclick="showOverdueReport()" 
                    class="text-xs bg-red-100 text-red-800 px-3 py-1 rounded-full font-medium hover:bg-red-200 transition-colors">
                Review needed
            </button>
        </div>
    </div>

    <!-- Total Portfolio -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Total Portfolio</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">ZAR {{ number_format($totalLoanAmount) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Paid: <span class="font-semibold">ZAR {{ number_format($totalPaid, 2) }}</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Lease Directory</h2>
            <p class="text-gray-600 mt-1">Manage all BNPL lease agreements and track repayments</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="#" 
               onclick="showCreateLeaseModal()"
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-plus-circle mr-2 group-hover:rotate-90 transition-transform"></i> New Lease
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
                    <option value="extend">Extend Due Dates</option>
                    <option value="mark_paid">Mark as Paid</option>
                    <option value="mark_defaulted">Mark as Defaulted</option>
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
    <form id="filterForm" action="{{ route('admin.leases.index') }}" method="GET" class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="md:col-span-2 relative">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search by user name, lease ID..." 
                       class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
            </div>
            
            <!-- Status Filter -->
            <select name="status" 
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="defaulted" {{ request('status') == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            
            <!-- User Filter -->
            <select name="user_id" 
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                <option value="">All Users</option>
                @foreach(App\Models\User::whereHas('leases')->take(20)->get() as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <!-- Advanced Filters -->
        <div id="advancedFilters" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 hidden">
            <!-- Date Range -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <div class="flex space-x-2">
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="py-2">to</span>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <!-- Amount Range -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount Range</label>
                <select name="amount_range" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Any Amount</option>
                    <option value="0-1000" {{ request('amount_range') == '0-1000' ? 'selected' : '' }}>ZAR 0 - 1,000</option>
                    <option value="1000-5000" {{ request('amount_range') == '1000-5000' ? 'selected' : '' }}>ZAR 1,000 - 5,000</option>
                    <option value="5000+" {{ request('amount_range') == '5000+' ? 'selected' : '' }}>ZAR 5,000+</option>
                </select>
            </div>
        </div>
        
        <!-- Filter Buttons -->
        <div class="flex flex-wrap gap-2 mt-4">
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                Apply Filters
            </button>
            <a href="{{ route('admin.leases.index') }}" 
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                Clear All
            </a>
            <button type="button" 
                    onclick="toggleAdvancedFilters()" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                <i class="fas fa-sliders-h mr-2"></i> Advanced
            </button>
            <div class="flex flex-wrap gap-2 ml-4">
                <a href="{{ route('admin.leases.index') }}" 
                   class="px-4 py-2 {{ !request('status') && !request('search') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-blue-200">
                    All ({{ $totalLeases }})
                </a>
                <a href="{{ route('admin.leases.index', ['status' => 'active']) }}" 
                   class="px-4 py-2 {{ request('status') == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-green-200">
                    Active ({{ $activeLeases }})
                </a>
                <a href="{{ route('admin.leases.index', ['status' => 'completed']) }}" 
                   class="px-4 py-2 {{ request('status') == 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-blue-200">
                    Completed ({{ $completedLeases }})
                </a>
                <a href="{{ route('admin.leases.index', ['status' => 'defaulted']) }}" 
                   class="px-4 py-2 {{ request('status') == 'defaulted' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-red-200">
                    Defaulted ({{ $defaultedLeases }})
                </a>
                <a href="{{ route('admin.leases.index') }}?status=active&overdue=true" 
                   class="px-4 py-2 {{ request('overdue') ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-orange-200">
                    Overdue ({{ $overdueLeases }})
                </a>
            </div>
        </div>
    </form>

    <!-- Leases Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-3">Lease Details</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Amount & Terms
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Status & Progress
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Dates
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leases as $lease)
                    <tr class="hover:bg-gray-50 transition-colors duration-150" id="lease-{{ $lease->id }}">
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="selected_leases[]" 
                                       value="{{ $lease->id }}" 
                                       class="lease-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                        <i class="fas fa-file-contract text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <a href="{{ route('admin.leases.show', $lease) }}" class="hover:text-blue-600">
                                            Lease #{{ $lease->id }}
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        @foreach($lease->vouchers as $voucher)
                                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 rounded mr-1">
                                                {{ $voucher->code }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- User -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($lease->user->profile_photo)
                                    <img class="w-8 h-8 rounded-full mr-3" src="{{ $lease->user->profile_photo }}" alt="{{ $lease->user->name }}">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mr-3">
                                        <span class="text-gray-600 font-bold text-sm">{{ substr($lease->user->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('admin.users.show', $lease->user) }}" class="hover:text-blue-600">
                                            {{ $lease->user->name }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Score: {{ $lease->user->credit_score ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Amount & Terms -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="text-sm">
                                    <span class="text-gray-600">Principal:</span>
                                    <span class="font-bold text-gray-900 ml-1">ZAR {{ number_format($lease->principal_amount, 2) }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">Interest:</span>
                                    <span class="font-bold text-red-600 ml-1">ZAR {{ number_format($lease->interest_amount, 2) }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">Total:</span>
                                    <span class="font-bold text-green-600 ml-1">ZAR {{ number_format($lease->total_amount, 2) }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $lease->term_days }} days • ZAR {{ number_format($lease->daily_repayment, 2) }}/day
                                </div>
                            </div>
                        </td>
                        
                        <!-- Status & Progress -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-2">
                                <!-- Status Badge -->
                                @php
                                    $statusColors = [
                                        'active' => ['bg-green-100', 'text-green-800', 'Active', 'fa-spinner'],
                                        'completed' => ['bg-blue-100', 'text-blue-800', 'Completed', 'fa-check-circle'],
                                        'defaulted' => ['bg-red-100', 'text-red-800', 'Defaulted', 'fa-exclamation-triangle'],
                                        'cancelled' => ['bg-gray-100', 'text-gray-800', 'Cancelled', 'fa-ban'],
                                    ];
                                    $status = $statusColors[$lease->status] ?? $statusColors['active'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                    <i class="fas {{ $status[3] }} mr-1.5"></i>
                                    {{ $status[2] }}
                                    @if($lease->days_overdue > 0)
                                        <span class="ml-1 text-red-600">(+{{ $lease->days_overdue }} days)</span>
                                    @endif
                                </span>
                                
                                <!-- Progress Bar -->
                                <div class="mt-2">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">Progress</span>
                                        <span class="font-medium">{{ number_format($lease->progress_percentage, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-600" 
                                             style="width: {{ $lease->progress_percentage }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Remaining: ZAR {{ number_format($lease->remaining_balance, 2) }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Dates -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="text-sm">
                                    <span class="text-gray-600">Issued:</span>
                                    <span class="font-medium text-gray-900">{{ $lease->issued_at->format('M d, Y') }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">Due:</span>
                                    <span class="font-medium {{ $lease->due_date < now() && $lease->status == 'active' ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $lease->due_date->format('M d, Y') }}
                                    </span>
                                </div>
                                @if($lease->completed_at)
                                    <div class="text-sm">
                                        <span class="text-gray-600">Completed:</span>
                                        <span class="font-medium text-green-600">{{ $lease->completed_at->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-5 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.leases.show', $lease) }}" 
                                   class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors group relative"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <!-- Record Payment -->
                                <button onclick="showPaymentModal({{ $lease->id }}, {{ $lease->remaining_balance }})" 
                                        class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors group relative"
                                        title="Record Payment">
                                    <i class="fas fa-money-bill-wave"></i>
                                </button>
                                
                                <!-- Extend Lease -->
                                <button onclick="showExtendModal({{ $lease->id }})" 
                                        class="text-yellow-600 hover:text-yellow-900 p-2 hover:bg-yellow-50 rounded-lg transition-colors group relative"
                                        title="Extend Lease">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                                
                                <!-- Quick Actions Dropdown -->
                                <div class="relative group">
                                    <button class="text-gray-600 hover:text-gray-900 p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-10">
                                        @if($lease->status == 'active')
                                            <button onclick="markAsDefaulted({{ $lease->id }})" 
                                                    class="w-full text-left px-4 py-3 text-red-700 hover:bg-red-50">
                                                <i class="fas fa-exclamation-triangle mr-2"></i> Mark as Defaulted
                                            </button>
                                            <button onclick="showAdjustmentModal({{ $lease->id }})" 
                                                    class="w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-50">
                                                <i class="fas fa-edit mr-2"></i> Adjust Terms
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.leases.repayment-history', $lease) }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-history mr-2"></i> Payment History
                                        </a>
                                        @if($lease->vouchers->count() > 0)
                                            <a href="#" 
                                               onclick="showVouchersModal({{ $lease->id }})"
                                               class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                                <i class="fas fa-ticket-alt mr-2"></i> View Vouchers
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-file-contract text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-700">No leases found</p>
                                <p class="text-gray-500 mt-1">Get started by creating your first lease agreement</p>
                                <button onclick="showCreateLeaseModal()" 
                                        class="inline-block mt-4 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i> Create New Lease
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($leases->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-semibold">{{ $leases->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $leases->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $leases->total() }}</span> leases
                </div>
                <div class="flex space-x-2">
                    {{ $leases->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Recent Activity Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
        <!-- Recent Leases -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Leases</h3>
            <div class="space-y-4">
                @forelse($recentLeases as $recentLease)
                <div class="flex items-center p-3 hover:bg-gray-50 rounded-xl transition-colors">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                            <i class="fas fa-file-contract text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $recentLease->user->name }}</p>
                        <p class="text-xs text-gray-500">ZAR {{ number_format($recentLease->total_amount, 2) }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full 
                        {{ $recentLease->status == 'active' ? 'bg-green-100 text-green-800' : 
                           ($recentLease->status == 'completed' ? 'bg-blue-100 text-blue-800' : 
                           'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst($recentLease->status) }}
                    </span>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="fas fa-file-contract text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">No recent leases</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Avg. Loan Amount</span>
                    <span class="font-bold text-gray-900">
                        ZAR {{ number_format($totalLeases > 0 ? $totalLoanAmount / $totalLeases : 0, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Avg. Interest Rate</span>
                    <span class="font-bold text-red-600">
                        {{ number_format($totalLeases > 0 ? ($totalInterest / $totalLoanAmount) * 100 : 0, 1) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Interest</span>
                    <span class="font-bold text-purple-600">
                        ZAR {{ number_format($totalInterest, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Collection Rate</span>
                    <span class="font-bold text-green-600">
                        {{ number_format($totalRevenue > 0 ? ($totalPaid / $totalRevenue) * 100 : 0, 1) }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <button onclick="showCreateLeaseModal()" 
                        class="w-full flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-blue-600"></i>
                    </div>
                    <span class="font-medium">Create New Lease</span>
                </button>
                <button onclick="exportLeases()" 
                        class="w-full flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-file-export text-green-600"></i>
                    </div>
                    <span class="font-medium">Export Leases</span>
                </button>
                <button onclick="showOverdueReport()" 
                        class="w-full flex items-center p-3 bg-red-50 text-red-700 rounded-xl hover:bg-red-100 transition-colors">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <span class="font-medium">Overdue Report</span>
                </button>
                <button onclick="showPerformanceReport()" 
                        class="w-full flex items-center p-3 bg-purple-50 text-purple-700 rounded-xl hover:bg-purple-100 transition-colors">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-chart-bar text-purple-600"></i>
                    </div>
                    <span class="font-medium">Performance Report</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Lease Modal -->
<div id="createLeaseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <form id="createLeaseForm" method="POST" action="{{ route('admin.leases.store') }}">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Create New Lease</h3>
                    <button type="button" onclick="closeCreateLeaseModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- User Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select User *
                        </label>
                        <select name="user_id" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Choose a user</option>
                            @foreach(App\Models\User::where('status', 'active')->get() as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ $user->email ?? $user->phone }}) - Credit: ZAR {{ number_format($user->available_credit) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Principal Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Principal Amount (ZAR) *
                            </label>
                            <input type="number" 
                                   name="principal_amount" 
                                   required
                                   min="100"
                                   step="0.01"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter loan amount">
                        </div>
                        
                        <!-- Interest Rate -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Interest Rate (%) *
                            </label>
                            <input type="number" 
                                   name="interest_rate" 
                                   required
                                   min="0"
                                   max="100"
                                   step="0.1"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter interest rate"
                                   value="10">
                        </div>
                        
                        <!-- Term Days -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Term (Days) *
                            </label>
                            <input type="number" 
                                   name="term_days" 
                                   required
                                   min="7"
                                   max="60"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter term in days"
                                    value="30">
                        </div>
                        
                        <!-- Start Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Start Date *
                            </label>
                            <input type="date" 
                                   name="issued_at" 
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    
                    <!-- Voucher Details -->
                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Voucher Details (Optional)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fuel Station
                                </label>
                                <select name="fuel_station_id" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">No voucher</option>
                                    @foreach(App\Models\FuelStation::active()->get() as $station)
                                        <option value="{{ $station->id }}">{{ $station->name }} - {{ $station->city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fuel Type
                                </label>
                                <select name="fuel_type" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select fuel type</option>
                                    <option value="petrol">Petrol</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calculation Preview -->
                    <div class="bg-gray-50 p-4 rounded-xl mt-4">
                        <h4 class="font-semibold text-gray-900 mb-3">Calculation Preview</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Principal:</span>
                                <span id="previewPrincipal" class="font-medium ml-2">ZAR 0.00</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Interest:</span>
                                <span id="previewInterest" class="font-medium text-red-600 ml-2">ZAR 0.00</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Total:</span>
                                <span id="previewTotal" class="font-medium text-green-600 ml-2">ZAR 0.00</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Daily:</span>
                                <span id="previewDaily" class="font-medium text-blue-600 ml-2">ZAR 0.00</span>
                            </div>
                        </div>
                        <p id="createLeaseValidationMessage" class="mt-3 text-xs font-medium text-gray-600">
                            Minimum repayment: R30.00 per day. Term must be between 7 and 60 days.
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                    <button type="button" 
                            onclick="closeCreateLeaseModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            id="createLeaseSubmitBtn"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create Lease
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="paymentForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Record Payment</h3>
                    <button type="button" onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-600">Remaining Balance: <span id="remainingBalance" class="font-semibold text-gray-900"></span></p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Amount (ZAR) *
                        </label>
                        <input type="number" 
                               name="amount" 
                               required
                               min="0.01"
                               step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter payment amount">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payment Method *
                        </label>
                        <select name="payment_method" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select method</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="wallet">Wallet Balance</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reference Number
                        </label>
                        <input type="text" 
                               name="reference"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Optional reference number">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Notes
                        </label>
                        <textarea name="notes" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Additional notes about this payment"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closePaymentModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Record Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Extend Lease Modal -->
<div id="extendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="extendForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Extend Lease Term</h3>
                    <button type="button" onclick="closeExtendModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-600">Current due date: <span id="currentDueDate" class="font-semibold text-gray-900"></span></p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Extra Days *
                        </label>
                        <input type="number" 
                               name="extra_days" 
                               required
                               min="1"
                               max="90"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter number of extra days">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Due Date
                        </label>
                        <input type="date" 
                               id="newDueDate"
                               readonly
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for Extension
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain the reason for extension..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeExtendModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                        Extend Lease
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

    // Toggle advanced filters
    function toggleAdvancedFilters() {
        const advancedFilters = document.getElementById('advancedFilters');
        advancedFilters.classList.toggle('hidden');
    }

    // Select all checkboxes
    document.getElementById('selectAll')?.addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.lease-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    // Create Lease Modal
    function showCreateLeaseModal() {
        document.getElementById('createLeaseModal').classList.remove('hidden');
    }
    
    function closeCreateLeaseModal() {
        document.getElementById('createLeaseModal').classList.add('hidden');
        document.getElementById('createLeaseForm').reset();
        updateCalculationPreview();
    }

    // Payment Modal
    let currentLeaseId = null;
    let currentRemainingBalance = 0;
    
    function showPaymentModal(leaseId, remainingBalance) {
        currentLeaseId = leaseId;
        currentRemainingBalance = remainingBalance;
        
        document.getElementById('remainingBalance').textContent = `ZAR ${remainingBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        const form = document.getElementById('paymentForm');
        form.action = `/admin/leases/${leaseId}/payments`;
        
        // Set max amount
        const amountInput = form.querySelector('input[name="amount"]');
        amountInput.max = remainingBalance;
        amountInput.placeholder = `Max: ZAR ${remainingBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        document.getElementById('paymentModal').classList.remove('hidden');
    }
    
    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
        document.getElementById('paymentForm').reset();
        currentLeaseId = null;
        currentRemainingBalance = 0;
    }

    // Extend Modal
    function showExtendModal(leaseId) {
        currentLeaseId = leaseId;
        
        const form = document.getElementById('extendForm');
        form.action = `/admin/leases/${leaseId}/extend`;
        
        // Get current due date from the table row
        const leaseRow = document.getElementById(`lease-${leaseId}`);
        const dueDateText = leaseRow?.querySelector('td:nth-child(5) .text-sm:nth-child(2) .font-medium')?.textContent || '';
        
        if (dueDateText) {
            document.getElementById('currentDueDate').textContent = dueDateText;
            
            // Parse date and calculate new date
            const extraDaysInput = document.querySelector('input[name="extra_days"]');
            const newDueDateInput = document.getElementById('newDueDate');
            
            extraDaysInput.addEventListener('input', function() {
                const extraDays = parseInt(this.value) || 0;
                const currentDate = new Date(dueDateText);
                currentDate.setDate(currentDate.getDate() + extraDays);
                newDueDateInput.value = currentDate.toISOString().split('T')[0];
            });
        }
        
        document.getElementById('extendModal').classList.remove('hidden');
    }
    
    function closeExtendModal() {
        document.getElementById('extendModal').classList.add('hidden');
        document.getElementById('extendForm').reset();
        currentLeaseId = null;
    }

    // Mark as defaulted
    function markAsDefaulted(leaseId) {
        if (confirm('Are you sure you want to mark this lease as defaulted? This will also flag the user.')) {
            fetch(`/admin/leases/${leaseId}/mark-defaulted`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lease marked as defaulted');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    }

    // Show vouchers modal
    function showVouchersModal(leaseId) {
        alert(`Would show vouchers for lease ${leaseId} in a modal`);
    }

    // Bulk Actions
    function applyBulkAction() {
        const action = document.getElementById('bulkActionSelect').value;
        const selectedLeases = Array.from(document.querySelectorAll('.lease-checkbox:checked'))
                                 .map(cb => cb.value);
        
        if (selectedLeases.length === 0) {
            alert('Please select at least one lease');
            return;
        }
        
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        if (confirm(`Apply "${action}" to ${selectedLeases.length} lease(s)?`)) {
            // In a real app, you would make an AJAX request here
            console.log(`Applying ${action} to:`, selectedLeases);
            alert(`Action applied to ${selectedLeases.length} lease(s)`);
            toggleBulkActions();
        }
    }

    // Export Leases
    function exportLeases() {
        const selectedLeases = Array.from(document.querySelectorAll('.lease-checkbox:checked'))
                                 .map(cb => cb.value);
        
        if (selectedLeases.length === 0) {
            if (!confirm('Export all leases?')) return;
        }
        
        const url = selectedLeases.length > 0 
            ? `/admin/leases/export?leases=${selectedLeases.join(',')}`
            : `/admin/leases/export`;
        
        window.open(url, '_blank');
    }

    // Show overdue report
    function showOverdueReport() {
        window.open('/admin/leases/reports/overdue', '_blank');
    }

    // Show performance report
    function showPerformanceReport() {
        window.open('/admin/leases/reports/performance', '_blank');
    }

    // Calculation preview for create lease
    function updateCalculationPreview() {
        const principal = parseFloat(document.querySelector('input[name="principal_amount"]')?.value) || 0;
        const interestRate = parseFloat(document.querySelector('input[name="interest_rate"]')?.value) || 0;
        const termInput = document.querySelector('input[name="term_days"]');
        let termDays = parseInt(termInput?.value) || 0;
        termDays = Math.max(7, Math.min(60, termDays));
        if (termInput && termInput.value !== '' && Number(termInput.value) !== termDays) {
            termInput.value = String(termDays);
        }
        
        const interest = principal * (interestRate / 100);
        const total = principal + interest;
        const daily = termDays > 0 ? total / termDays : 0;
        const isValid = termDays >= 7 && termDays <= 60 && daily >= 30;
        
        document.getElementById('previewPrincipal').textContent = `ZAR ${principal.toFixed(2)}`;
        document.getElementById('previewInterest').textContent = `ZAR ${interest.toFixed(2)}`;
        document.getElementById('previewTotal').textContent = `ZAR ${total.toFixed(2)}`;
        document.getElementById('previewDaily').textContent = `ZAR ${daily.toFixed(2)}`;

        const validationMessage = document.getElementById('createLeaseValidationMessage');
        const submitBtn = document.getElementById('createLeaseSubmitBtn');
        if (validationMessage) {
            validationMessage.textContent = isValid
                ? 'Minimum repayment: R30.00 per day. Term must be between 7 and 60 days.'
                : 'Daily repayment must be at least R30.00 and term must be between 7 and 60 days.';
            validationMessage.className = isValid
                ? 'mt-3 text-xs font-medium text-emerald-700'
                : 'mt-3 text-xs font-medium text-red-600';
        }
        if (submitBtn) {
            submitBtn.disabled = !isValid;
            submitBtn.classList.toggle('opacity-60', !isValid);
            submitBtn.classList.toggle('cursor-not-allowed', !isValid);
        }
    }

    // Attach calculation preview to input events
    document.querySelectorAll('#createLeaseForm input').forEach(input => {
        if (['principal_amount', 'interest_rate', 'term_days'].includes(input.name)) {
            input.addEventListener('input', updateCalculationPreview);
        }
    });

    // Auto-submit filter form on select change
    document.querySelectorAll('#filterForm select').forEach(select => {
        select.addEventListener('change', function() {
            if (!this.name.includes('date') && !this.name.includes('amount')) {
                document.getElementById('filterForm').submit();
            }
        });
    });

    // Handle form submissions with AJAX
    document.querySelectorAll('#paymentForm, #extendForm').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            submitButton.disabled = true;
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Operation completed successfully!');
                    
                    // Close modal and reload
                    if (this.closest('#paymentModal')) {
                        closePaymentModal();
                    } else if (this.closest('#extendModal')) {
                        closeExtendModal();
                    }
                    
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(data.message || 'An error occurred');
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
    });
</script>
@endsection
