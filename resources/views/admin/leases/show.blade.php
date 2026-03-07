@extends('Layouts.admin')

@section('title', 'Lease #' . $lease->id . ' - Details')
@section('page-title', 'Lease Details')
@section('page-description', 'View and manage lease agreement')
@section('breadcrumb')
    <a href="{{ route('admin.leases.index') }}" class="text-blue-600 hover:text-blue-800">Leases</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>Lease #{{ $lease->id }}</span>
@endsection

@php
    // Calculate stats
    $paidAmount = $lease->repayments()->where('status', 'paid')->sum('amount');
    $pendingRepayments = $lease->repayments()->where('status', 'pending')->count();
    $overdueRepayments = $lease->repayments()->where('status', 'overdue')->count();
    $daysRemaining = max(0, now()->diffInDays($lease->due_date, false));
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Amount -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Amount</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    ZAR {{ number_format($lease->total_amount, 2) }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Principal: <span class="font-semibold">ZAR {{ number_format($lease->principal_amount, 2) }}</span>
        </div>
    </div>

    <!-- Amount Paid -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Amount Paid</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    ZAR {{ number_format($paidAmount, 2) }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-600" 
                     style="width: {{ $lease->total_amount > 0 ? ($paidAmount / $lease->total_amount) * 100 : 0 }}%"></div>
            </div>
            <div class="text-xs text-gray-600 mt-1">
                {{ $lease->total_amount > 0 ? number_format(($paidAmount / $lease->total_amount) * 100, 1) : 0 }}% paid
            </div>
        </div>
    </div>

    <!-- Remaining Balance -->
    <div class="bg-gradient-to-br from-red-50 to-white p-5 rounded-2xl shadow-sm border border-red-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-600 text-sm font-semibold">Remaining Balance</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    ZAR {{ number_format($lease->remaining_balance, 2) }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-xl">
                <i class="fas fa-clock text-red-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Daily: <span class="font-semibold">ZAR {{ number_format($lease->daily_repayment, 2) }}</span>
        </div>
    </div>

    <!-- Status -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Status</p>
                <p class="text-3xl font-bold text-gray-900 mt-2 capitalize">
                    {{ $lease->status }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            @php
                $statusColors = [
                    'active' => ['bg-green-100', 'text-green-800', 'Active'],
                    'completed' => ['bg-blue-100', 'text-blue-800', 'Completed'],
                    'defaulted' => ['bg-red-100', 'text-red-800', 'Defaulted'],
                    'cancelled' => ['bg-gray-100', 'text-gray-800', 'Cancelled'],
                ];
                $status = $statusColors[$lease->status] ?? $statusColors['active'];
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                {{ $status[2] }}
                @if($lease->days_overdue > 0)
                    <span class="ml-1">(+{{ $lease->days_overdue }} days)</span>
                @endif
            </span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Lease Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-lg overflow-hidden mb-6">
        <div class="p-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-white/20 to-white/10 flex items-center justify-center border-4 border-white/30 backdrop-blur-sm">
                            <i class="fas fa-file-contract text-white text-4xl"></i>
                        </div>
                        @if($lease->status == 'active')
                            <div class="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-2 border-white rounded-full"></div>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Lease #{{ $lease->id }}</h1>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                <i class="fas fa-user mr-2"></i> {{ $lease->user->name }}
                            </span>
                            <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                <i class="fas fa-calendar-alt mr-2"></i> {{ $lease->term_days }} days
                            </span>
                            <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                <i class="fas fa-percentage mr-2"></i> {{ $lease->interest_rate }}% interest
                            </span>
                            <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                <i class="fas fa-calendar-day mr-2"></i> Due {{ $lease->due_date->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3 mt-6 md:mt-0">
                    <button onclick="showPaymentModal()" 
                           class="px-5 py-2.5 bg-white text-green-600 rounded-xl font-semibold hover:bg-green-50 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-money-bill-wave mr-2"></i> Record Payment
                    </button>
                    @if($lease->status == 'active')
                        <button onclick="showExtendModal()" 
                               class="px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl font-semibold hover:bg-white/30 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-calendar-plus mr-2"></i> Extend Lease
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Lease Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-info-circle text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Lease Details</h3>
                            <p class="text-gray-600 text-sm">Complete lease agreement information</p>
                        </div>
                    </div>
                    <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                        Created {{ $lease->created_at->format('M d, Y') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                User Information
                            </label>
                            <div class="flex items-center">
                                @if($lease->user->profile_photo)
                                    <img class="w-10 h-10 rounded-full mr-3" src="{{ $lease->user->profile_photo }}" alt="{{ $lease->user->name }}">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mr-3">
                                        <span class="text-gray-600 font-bold">{{ substr($lease->user->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-gray-900 font-medium">
                                        <a href="{{ route('admin.users.show', $lease->user) }}" class="hover:text-blue-600">
                                            {{ $lease->user->name }}
                                        </a>
                                    </p>
                                    <p class="text-sm text-gray-500">{{ $lease->user->phone }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Lease Status
                            </label>
                            <div class="flex items-center space-x-2">
                                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $status[0] }} {{ $status[1] }}">
                                    {{ $status[2] }}
                                </span>
                                @if($lease->status == 'active')
                                    <button onclick="markAsDefaulted()" 
                                            class="text-sm text-red-600 hover:text-red-800 font-medium">
                                        Mark Defaulted
                                    </button>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Dates
                            </label>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Issued:</span>
                                    <span class="font-medium">{{ $lease->issued_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Due:</span>
                                    <span class="font-medium {{ $lease->due_date < now() ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $lease->due_date->format('M d, Y') }}
                                    </span>
                                </div>
                                @if($lease->completed_at)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Completed:</span>
                                        <span class="font-medium text-green-600">{{ $lease->completed_at->format('M d, Y') }}</span>
                                    </div>
                                @endif
                                @if($lease->defaulted_at)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Defaulted:</span>
                                        <span class="font-medium text-red-600">{{ $lease->defaulted_at->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Financial Details
                            </label>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Principal Amount:</span>
                                    <span class="font-bold text-gray-900">ZAR {{ number_format($lease->principal_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Interest ({{ $lease->interest_rate }}%):</span>
                                    <span class="font-bold text-red-600">ZAR {{ number_format($lease->interest_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Amount:</span>
                                    <span class="font-bold text-green-600">ZAR {{ number_format($lease->total_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Daily Repayment:</span>
                                    <span class="font-bold text-blue-600">ZAR {{ number_format($lease->daily_repayment, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Terms
                            </label>
                            <div class="space-y-1">
                                <p class="text-sm text-gray-900">{{ $lease->term_days }} days term</p>
                                <p class="text-xs text-gray-500">{{ $daysRemaining }} days remaining</p>
                                @if($lease->days_overdue > 0)
                                    <p class="text-xs text-red-600">{{ $lease->days_overdue }} days overdue</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Repayment Schedule -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-calendar-check text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Repayment Schedule</h3>
                            <p class="text-gray-600 text-sm">Payment history and schedule</p>
                        </div>
                    </div>
                    <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-medium">
                        {{ $pendingRepayments }} pending • {{ $overdueRepayments }} overdue
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Due Date
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Paid Date
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Method
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($lease->repayments()->orderBy('due_date')->get() as $repayment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $repayment->due_date->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $repayment->due_date->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">ZAR {{ number_format($repayment->amount, 2) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $repaymentStatusColors = [
                                            'paid' => ['bg-green-100', 'text-green-800', 'Paid'],
                                            'pending' => ['bg-yellow-100', 'text-yellow-800', 'Pending'],
                                            'overdue' => ['bg-red-100', 'text-red-800', 'Overdue'],
                                        ];
                                        $repaymentStatus = $repaymentStatusColors[$repayment->status] ?? $repaymentStatusColors['pending'];
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full font-medium {{ $repaymentStatus[0] }} {{ $repaymentStatus[1] }}">
                                        {{ $repaymentStatus[2] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $repayment->paid_at ? $repayment->paid_at->format('M d, Y') : '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $repayment->payment_method ? ucfirst($repayment->payment_method) : '—' }}</div>
                                    @if($repayment->transaction_reference)
                                        <div class="text-xs text-gray-500">{{ $repayment->transaction_reference }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($repayment->status != 'paid')
                                        <button onclick="markRepaymentAsPaid({{ $repayment->id }})" 
                                                class="text-sm text-green-600 hover:text-green-800 font-medium">
                                            Mark Paid
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    No repayment records found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Quick Add Repayment -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Add Manual Repayment</h4>
                    <form id="quickRepaymentForm" action="{{ route('admin.leases.quick-payment', $lease) }}" method="POST" class="flex items-center space-x-3">
                        @csrf
                        <input type="number" 
                               name="amount" 
                               required
                               min="0.01"
                               step="0.01"
                               placeholder="Amount"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <select name="payment_method" 
                                required
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Method</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add Payment
                        </button>
                    </form>
                </div>
            </div>

            <!-- Associated Vouchers -->
            @if($lease->vouchers->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-ticket-alt text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Associated Vouchers</h3>
                            <p class="text-gray-600 text-sm">Fuel vouchers issued for this lease</p>
                        </div>
                    </div>
                    <span class="text-xs bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-medium">
                        {{ $lease->vouchers->count() }} vouchers
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($lease->vouchers as $voucher)
                    <div class="border border-gray-200 rounded-xl p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center mr-3">
                                    <i class="fas fa-gas-pump text-green-600 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $voucher->code }}</div>
                                    <div class="text-xs text-gray-500">{{ $voucher->fuel_type ?? 'Fuel' }}</div>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full 
                                {{ $voucher->status == 'issued' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($voucher->status == 'redeemed' ? 'bg-green-100 text-green-800' : 
                                   'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($voucher->status) }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Amount:</span>
                                <span class="font-bold text-gray-900">ZAR {{ number_format($voucher->amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Station:</span>
                                <span class="font-medium">{{ $voucher->fuelStation->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Expires:</span>
                                <span class="font-medium {{ $voucher->expires_at < now() ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $voucher->expires_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-bolt text-yellow-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        <p class="text-gray-600 text-sm">Manage this lease quickly</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <button onclick="showPaymentModal()" 
                           class="w-full flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors">
                        <i class="fas fa-money-bill-wave mr-3"></i>
                        <span class="font-medium">Record Payment</span>
                    </button>
                    
                    @if($lease->status == 'active')
                        <button onclick="showExtendModal()" 
                               class="w-full flex items-center p-3 bg-yellow-50 text-yellow-700 rounded-xl hover:bg-yellow-100 transition-colors">
                            <i class="fas fa-calendar-plus mr-3"></i>
                            <span class="font-medium">Extend Lease</span>
                        </button>
                        
                        <button onclick="markAsDefaulted()" 
                               class="w-full flex items-center p-3 bg-red-50 text-red-700 rounded-xl hover:bg-red-100 transition-colors">
                            <i class="fas fa-exclamation-triangle mr-3"></i>
                            <span class="font-medium">Mark as Defaulted</span>
                        </button>
                    @endif
                    
                    <a href="{{ route('admin.leases.index') }}" 
                       class="w-full flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                        <i class="fas fa-arrow-left mr-3"></i>
                        <span class="font-medium">Back to Leases</span>
                    </a>
                </div>
            </div>

            <!-- Progress Summary -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-chart-pie text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Progress Summary</h3>
                            <p class="text-gray-600 text-sm">Payment progress overview</p>
                        </div>
                    </div>
                    <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                        {{ number_format($lease->progress_percentage, 1) }}%
                    </span>
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Progress</span>
                            <span class="font-medium">{{ number_format($lease->progress_percentage, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-blue-600" 
                                 style="width: {{ $lease->progress_percentage }}%"></div>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total Amount:</span>
                            <span class="font-bold">ZAR {{ number_format($lease->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Amount Paid:</span>
                            <span class="font-bold text-green-600">ZAR {{ number_format($paidAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Remaining:</span>
                            <span class="font-bold text-red-600">ZAR {{ number_format($lease->remaining_balance, 2) }}</span>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-600">Days Information:</div>
                        <div class="flex justify-between mt-2">
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-900">{{ $lease->term_days }}</div>
                                <div class="text-xs text-gray-500">Total Days</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold {{ $daysRemaining > 0 ? 'text-green-600' : 'text-red-600' }}">{{ abs($daysRemaining) }}</div>
                                <div class="text-xs text-gray-500">{{ $daysRemaining > 0 ? 'Days Left' : 'Days Overdue' }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-yellow-600">{{ $pendingRepayments }}</div>
                                <div class="text-xs text-gray-500">Pending</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Credit Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-user-circle text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">User Information</h3>
                        <p class="text-gray-600 text-sm">Borrower details</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center">
                        @if($lease->user->profile_photo)
                            <img class="w-12 h-12 rounded-full mr-3" src="{{ $lease->user->profile_photo }}" alt="{{ $lease->user->name }}">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mr-3">
                                <span class="text-gray-600 font-bold text-lg">{{ substr($lease->user->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div>
                            <p class="font-medium text-gray-900">{{ $lease->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $lease->user->phone }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Credit Score
                            </label>
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-gray-900">{{ $lease->user->credit_score ?? 'N/A' }}</span>
                                @if($lease->user->credit_score)
                                    @php
                                        $scoreColor = $lease->user->credit_score >= 700 ? 'text-green-600' : 
                                                     ($lease->user->credit_score >= 600 ? 'text-yellow-600' : 'text-red-600');
                                        $scoreText = $lease->user->credit_score >= 700 ? 'Excellent' : 
                                                     ($lease->user->credit_score >= 600 ? 'Good' : 'Poor');
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 {{ $scoreColor }} font-medium">
                                        {{ $scoreText }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        @if($lease->user->creditLimit)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Credit Limit
                            </label>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Limit:</span>
                                    <span class="font-bold">ZAR {{ number_format($lease->user->creditLimit->limit) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Available:</span>
                                    <span class="font-bold text-green-600">
                                        ZAR {{ number_format($lease->user->creditLimit->limit - $lease->user->creditLimit->used) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200">
                        <a href="{{ route('admin.users.show', $lease->user) }}" 
                           class="w-full flex items-center justify-center p-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            View User Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-info-circle text-gray-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
                        <p class="text-gray-600 text-sm">Technical details</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Lease ID
                        </label>
                        <p class="text-sm font-mono text-gray-900">{{ $lease->id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Created At
                        </label>
                        <p class="text-sm text-gray-900">{{ $lease->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Last Updated
                        </label>
                        <p class="text-sm text-gray-900">{{ $lease->updated_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Database Records
                        </label>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>Repayments: {{ $lease->repayments->count() }}</div>
                            <div>Vouchers: {{ $lease->vouchers->count() }}</div>
                            <div>User Status: {{ ucfirst($lease->user->status) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="paymentForm" method="POST" action="{{ route('admin.leases.payments.store', $lease) }}">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Record Payment</h3>
                    <button type="button" onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-600">Remaining Balance: 
                        <span class="font-semibold text-gray-900">ZAR {{ number_format($lease->remaining_balance, 2) }}</span>
                    </p>
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
                               max="{{ $lease->remaining_balance }}"
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
                               name="transaction_reference"
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

<!-- Extend Modal -->
<div id="extendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="extendForm" method="POST" action="{{ route('admin.leases.extend', $lease) }}">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Extend Lease Term</h3>
                    <button type="button" onclick="closeExtendModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-600">Current due date: 
                        <span class="font-semibold text-gray-900">{{ $lease->due_date->format('M d, Y') }}</span>
                    </p>
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
                               placeholder="Enter number of extra days"
                               value="7">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Due Date
                        </label>
                        <input type="date" 
                               id="newDueDate"
                               readonly
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50"
                               value="{{ $lease->due_date->addDays(7)->format('Y-m-d') }}">
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
    // Payment Modal
    function showPaymentModal() {
        const amountInput = document.querySelector('#paymentForm input[name="amount"]');
        amountInput.max = {{ $lease->remaining_balance }};
        amountInput.placeholder = `Max: ZAR {{ number_format($lease->remaining_balance, 2) }}`;
        document.getElementById('paymentModal').classList.remove('hidden');
    }
    
    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
        document.getElementById('paymentForm').reset();
    }

    // Extend Modal
    function showExtendModal() {
        const extraDaysInput = document.querySelector('#extendForm input[name="extra_days"]');
        const newDueDateInput = document.getElementById('newDueDate');
        
        extraDaysInput.addEventListener('input', function() {
            const extraDays = parseInt(this.value) || 0;
            const currentDate = new Date('{{ $lease->due_date->format("Y-m-d") }}');
            currentDate.setDate(currentDate.getDate() + extraDays);
            newDueDateInput.value = currentDate.toISOString().split('T')[0];
        });
        
        document.getElementById('extendModal').classList.remove('hidden');
    }
    
    function closeExtendModal() {
        document.getElementById('extendModal').classList.add('hidden');
        document.getElementById('extendForm').reset();
    }

    // Mark as defaulted
    function markAsDefaulted() {
        if (confirm('Are you sure you want to mark this lease as defaulted? This will also flag the user.')) {
            fetch('{{ route("admin.leases.mark-defaulted", $lease) }}', {
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

    // Mark repayment as paid
    function markRepaymentAsPaid(repaymentId) {
        if (confirm('Mark this repayment as paid?')) {
            fetch(`/admin/repayments/${repaymentId}/mark-paid`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Repayment marked as paid');
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

    // Quick repayment form
    document.getElementById('quickRepaymentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
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
                alert('Payment added successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
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