@extends('Layouts.admin')

@section('title', 'Overdue Leases Report')
@section('page-title', 'Overdue Leases Report')
@section('page-description', 'Review and manage overdue lease agreements')
@section('breadcrumb')
    <a href="{{ route('admin.leases.index') }}" class="text-blue-600 hover:text-blue-800">Leases</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>Overdue Report</span>
@endsection

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Overdue -->
    <div class="bg-gradient-to-br from-red-50 to-white p-5 rounded-2xl shadow-sm border border-red-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-600 text-sm font-semibold">Total Overdue</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_overdue']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-xl">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Active leases past due date
        </div>
    </div>

    <!-- Total Amount Overdue -->
    <div class="bg-gradient-to-br from-orange-50 to-white p-5 rounded-2xl shadow-sm border border-orange-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-600 text-sm font-semibold">Total Amount Overdue</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">ZAR {{ number_format($stats['total_amount'], 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-orange-100 to-orange-50 rounded-xl">
                <i class="fas fa-money-bill-wave text-orange-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Total outstanding balance
        </div>
    </div>

    <!-- Average Days Overdue -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Average Days Overdue</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['avg_days_overdue'], 1) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-calendar-times text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Average days past due date
        </div>
    </div>

    <!-- Collection Rate -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Action Required</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overdueLeases->where('days_overdue', '>=', 30)->count()) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-clock text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            30+ days overdue
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Overdue Leases Report</h2>
            <p class="text-gray-600 mt-1">Review and manage overdue lease agreements requiring attention</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <button onclick="exportReport()" 
                    class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-semibold hover:from-green-700 hover:to-green-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-file-export mr-2"></i> Export Report
            </button>
            <a href="{{ route('admin.leases.index') }}" 
               class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Leases
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Days Overdue Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Days Overdue</label>
                <select id="daysFilter" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Overdue</option>
                    <option value="1-7">1-7 Days</option>
                    <option value="8-30">8-30 Days</option>
                    <option value="31-90">31-90 Days</option>
                    <option value="90+">90+ Days</option>
                </select>
            </div>
            
            <!-- Amount Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount Range</label>
                <select id="amountFilter" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Any Amount</option>
                    <option value="0-1000">ZAR 0 - 1,000</option>
                    <option value="1000-5000">ZAR 1,000 - 5,000</option>
                    <option value="5000+">ZAR 5,000+</option>
                </select>
            </div>
            
            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select id="userFilter" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Users</option>
                    @foreach($overdueLeases->unique('user_id')->take(10) as $lease)
                        <option value="{{ $lease->user_id }}">{{ $lease->user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="flex space-x-3 mt-4">
            <button onclick="applyFilters()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                Apply Filters
            </button>
            <button onclick="clearFilters()" 
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                Clear Filters
            </button>
        </div>
    </div>

    <!-- Overdue Leases Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-red-50 to-red-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Lease Details
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Amount Details
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Overdue Status
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
                    @forelse($overdueLeases as $lease)
                    <tr class="hover:bg-red-50/30 transition-colors duration-150 overdue-row" 
                        data-days="{{ $lease->days_overdue }}" 
                        data-amount="{{ $lease->remaining_balance }}"
                        data-user="{{ $lease->user_id }}">
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-100 to-red-50 flex items-center justify-center">
                                        <i class="fas fa-exclamation-triangle text-red-600"></i>
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
                        
                        <!-- Amount Details -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="text-sm">
                                    <span class="text-gray-600">Total:</span>
                                    <span class="font-bold text-gray-900 ml-1">ZAR {{ number_format($lease->total_amount, 2) }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">Remaining:</span>
                                    <span class="font-bold text-red-600 ml-1">ZAR {{ number_format($lease->remaining_balance, 2) }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $lease->term_days }} days • ZAR {{ number_format($lease->daily_repayment, 2) }}/day
                                </div>
                            </div>
                        </td>
                        
                        <!-- Overdue Status -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-2">
                                @php
                                    $overdueColor = $lease->days_overdue >= 90 ? 'bg-red-100 text-red-800' : 
                                                    ($lease->days_overdue >= 30 ? 'bg-orange-100 text-orange-800' : 
                                                    'bg-yellow-100 text-yellow-800');
                                    $overdueText = $lease->days_overdue >= 90 ? 'Critical' : 
                                                    ($lease->days_overdue >= 30 ? 'High' : 'Medium');
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $overdueColor }}">
                                    <i class="fas fa-clock mr-1.5"></i>
                                    {{ $overdueText }} Priority
                                </span>
                                
                                <div class="text-sm">
                                    <span class="text-gray-600">Days Overdue:</span>
                                    <span class="font-bold {{ $lease->days_overdue >= 30 ? 'text-red-600' : 'text-yellow-600' }}">
                                        {{ $lease->days_overdue }} days
                                    </span>
                                </div>
                                
                                <div class="text-xs text-gray-500">
                                    Due {{ $lease->due_date->diffForHumans() }}
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
                                    <span class="font-medium text-red-600">{{ $lease->due_date->format('M d, Y') }}</span>
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
                                   class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <!-- Record Payment -->
                                <button onclick="showPaymentModal({{ $lease->id }}, {{ $lease->remaining_balance }})" 
                                        class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors"
                                        title="Record Payment">
                                    <i class="fas fa-money-bill-wave"></i>
                                </button>
                                
                                <!-- Extend Lease -->
                                <button onclick="showExtendModal({{ $lease->id }})" 
                                        class="text-yellow-600 hover:text-yellow-900 p-2 hover:bg-yellow-50 rounded-lg transition-colors"
                                        title="Extend Lease">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                                
                                <!-- Mark Defaulted -->
                                <button onclick="markAsDefaulted({{ $lease->id }})" 
                                        class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Mark as Defaulted">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </button>
                                
                                <!-- Quick Actions Dropdown -->
                                <div class="relative group">
                                    <button class="text-gray-600 hover:text-gray-900 p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-10">
                                        <a href="tel:{{ $lease->user->phone }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-phone mr-2 text-blue-500"></i> Call User
                                        </a>
                                        <a href="sms:{{ $lease->user->phone }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-sms mr-2 text-green-500"></i> Send SMS
                                        </a>
                                        <a href="mailto:{{ $lease->user->email }}" 
                                           class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-envelope mr-2 text-purple-500"></i> Send Email
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
                                <i class="fas fa-check-circle text-4xl mb-4 opacity-20 text-green-500"></i>
                                <p class="text-lg font-medium text-gray-700">No overdue leases found</p>
                                <p class="text-gray-500 mt-1">Great job! All leases are up to date</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Section -->
    @if($overdueLeases->count() > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
        <!-- Overdue Distribution -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Overdue Distribution</h3>
            <div class="space-y-4">
                @php
                    $distributions = [
                        '1-7 days' => $overdueLeases->whereBetween('days_overdue', [1, 7])->count(),
                        '8-30 days' => $overdueLeases->whereBetween('days_overdue', [8, 30])->count(),
                        '31-90 days' => $overdueLeases->whereBetween('days_overdue', [31, 90])->count(),
                        '90+ days' => $overdueLeases->where('days_overdue', '>=', 90)->count(),
                    ];
                @endphp
                
                @foreach($distributions as $range => $count)
                    @php
                        $percentage = $overdueLeases->count() > 0 ? ($count / $overdueLeases->count()) * 100 : 0;
                        $color = match($range) {
                            '1-7 days' => 'bg-yellow-500',
                            '8-30 days' => 'bg-orange-500',
                            '31-90 days' => 'bg-red-500',
                            default => 'bg-red-700',
                        };
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ $range }}</span>
                            <span class="font-medium">{{ $count }} leases ({{ number_format($percentage, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $color }}" 
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top Users by Overdue -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Users by Overdue Amount</h3>
            <div class="space-y-4">
                @foreach($overdueLeases->groupBy('user_id')->sortByDesc(function($leases) {
                    return $leases->sum('remaining_balance');
                })->take(5) as $userId => $userLeases)
                    @php
                        $user = $userLeases->first()->user;
                        $totalOverdue = $userLeases->sum('remaining_balance');
                        $leaseCount = $userLeases->count();
                    @endphp
                    <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl">
                        <div class="flex items-center">
                            @if($user->profile_photo)
                                <img class="w-10 h-10 rounded-full mr-3" src="{{ $user->profile_photo }}" alt="{{ $user->name }}">
                            @else
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mr-3">
                                    <span class="text-gray-600 font-bold">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $leaseCount }} overdue lease(s)</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-red-600">ZAR {{ number_format($totalOverdue, 2) }}</p>
                            <p class="text-xs text-gray-500">Total overdue</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Action Plan -->
    <div class="mt-8 bg-gradient-to-r from-red-50 to-white rounded-2xl shadow-sm border border-red-200 p-6">
        <h3 class="text-lg font-semibold text-red-900 mb-4">Recommended Action Plan</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-4 rounded-xl border border-gray-200">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                        <i class="fas fa-phone text-blue-600"></i>
                    </div>
                    <h4 class="font-medium text-gray-900">Contact Users</h4>
                </div>
                <p class="text-sm text-gray-600">
                    Contact users with overdue leases via phone, SMS, or email. Prioritize those with 30+ days overdue.
                </p>
            </div>
            
            <div class="bg-white p-4 rounded-xl border border-gray-200">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center mr-3">
                        <i class="fas fa-calendar-plus text-yellow-600"></i>
                    </div>
                    <h4 class="font-medium text-gray-900">Extend Terms</h4>
                </div>
                <p class="text-sm text-gray-600">
                    Consider extending lease terms for users facing temporary difficulties but with good payment history.
                </p>
            </div>
            
            <div class="bg-white p-4 rounded-xl border border-gray-200">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <h4 class="font-medium text-gray-900">Mark Defaulted</h4>
                </div>
                <p class="text-sm text-gray-600">
                    Mark leases as defaulted for users unresponsive for 90+ days. This will flag the user accounts.
                </p>
            </div>
        </div>
    </div>
    @endif
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

<!-- Extend Modal -->
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
                    <p class="text-gray-600">This will give the user more time to repay the overdue amount.</p>
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
    let currentLeaseId = null;
    let currentRemainingBalance = 0;
    
    // Payment Modal
    function showPaymentModal(leaseId, remainingBalance) {
        currentLeaseId = leaseId;
        currentRemainingBalance = remainingBalance;
        
        document.getElementById('remainingBalance').textContent = `ZAR ${remainingBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        const form = document.getElementById('paymentForm');
        form.action = `/admin/leases/${leaseId}/payments`;
        
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

    // Export report
    function exportReport() {
        const daysFilter = document.getElementById('daysFilter').value;
        const amountFilter = document.getElementById('amountFilter').value;
        const userFilter = document.getElementById('userFilter').value;
        
        let url = '/admin/leases/export?overdue=true';
        
        if (daysFilter) url += `&days=${daysFilter}`;
        if (amountFilter) url += `&amount=${amountFilter}`;
        if (userFilter) url += `&user=${userFilter}`;
        
        window.open(url, '_blank');
    }

    // Apply filters
    function applyFilters() {
        const daysFilter = document.getElementById('daysFilter').value;
        const amountFilter = document.getElementById('amountFilter').value;
        const userFilter = document.getElementById('userFilter').value;
        
        const rows = document.querySelectorAll('.overdue-row');
        
        rows.forEach(row => {
            const days = parseInt(row.getAttribute('data-days'));
            const amount = parseFloat(row.getAttribute('data-amount'));
            const user = row.getAttribute('data-user');
            
            let show = true;
            
            // Days filter
            if (daysFilter) {
                switch (daysFilter) {
                    case '1-7':
                        show = show && (days >= 1 && days <= 7);
                        break;
                    case '8-30':
                        show = show && (days >= 8 && days <= 30);
                        break;
                    case '31-90':
                        show = show && (days >= 31 && days <= 90);
                        break;
                    case '90+':
                        show = show && (days >= 90);
                        break;
                }
            }
            
            // Amount filter
            if (amountFilter && amount) {
                switch (amountFilter) {
                    case '0-1000':
                        show = show && (amount <= 1000);
                        break;
                    case '1000-5000':
                        show = show && (amount >= 1000 && amount <= 5000);
                        break;
                    case '5000+':
                        show = show && (amount >= 5000);
                        break;
                }
            }
            
            // User filter
            if (userFilter && user) {
                show = show && (user === userFilter);
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    // Clear filters
    function clearFilters() {
        document.getElementById('daysFilter').value = '';
        document.getElementById('amountFilter').value = '';
        document.getElementById('userFilter').value = '';
        
        const rows = document.querySelectorAll('.overdue-row');
        rows.forEach(row => {
            row.style.display = '';
        });
    }

    // Handle form submissions with AJAX
    document.querySelectorAll('#paymentForm, #extendForm').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            submitButton.disabled = true;
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Operation completed successfully!');
                    
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
                alert('An error occurred');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
    });

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePaymentModal();
            closeExtendModal();
        }
    });
</script>
@endsection