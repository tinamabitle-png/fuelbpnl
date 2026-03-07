@extends('Layouts.admin')

@section('title', 'Repayment History - Lease #' . $lease->id)
@section('page-title', 'Repayment History')
@section('page-description', 'Complete payment history for lease #' . $lease->id)
@section('breadcrumb')
    <a href="{{ route('admin.leases.index') }}" class="text-blue-600 hover:text-blue-800">Leases</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <a href="{{ route('admin.leases.show', $lease) }}" class="text-blue-600 hover:text-blue-800">Lease #{{ $lease->id }}</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>Repayment History</span>
@endsection

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
            Principal: ZAR {{ number_format($lease->principal_amount, 2) }}
        </div>
    </div>

    <!-- Amount Paid -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Amount Paid</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    ZAR {{ number_format($lease->total_amount - $lease->remaining_balance, 2) }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-600" 
                     style="width: {{ $lease->progress_percentage }}%"></div>
            </div>
            <div class="text-xs text-gray-600 mt-1">
                {{ number_format($lease->progress_percentage, 1) }}% paid
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
            Daily: ZAR {{ number_format($lease->daily_repayment, 2) }}
        </div>
    </div>

    <!-- Total Repayments -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Total Repayments</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    {{ $repayments->total() }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-history text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            {{ $repayments->where('status', 'paid')->count() }} paid • {{ $repayments->where('status', 'pending')->count() }} pending
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Repayment History</h2>
            <p class="text-gray-600 mt-1">Complete payment history for Lease #{{ $lease->id }} - {{ $lease->user->name }}</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <button onclick="showPaymentModal()" 
                   class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-semibold hover:from-green-700 hover:to-green-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-plus-circle mr-2"></i> Add Payment
            </button>
            <button onclick="exportHistory()" 
                    class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-file-export mr-2"></i> Export History
            </button>
            <a href="{{ route('admin.leases.show', $lease) }}" 
               class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Lease
            </a>
        </div>
    </div>

    <!-- Lease Summary -->
    <div class="bg-gradient-to-r from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-600">Lease #{{ $lease->id }}</p>
                <p class="font-bold text-gray-900">{{ $lease->user->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Status</p>
                <span class="px-3 py-1 rounded-full text-xs font-medium 
                    {{ $lease->status == 'active' ? 'bg-green-100 text-green-800' : 
                       ($lease->status == 'completed' ? 'bg-blue-100 text-blue-800' : 
                       'bg-red-100 text-red-800') }}">
                    {{ ucfirst($lease->status) }}
                    @if($lease->days_overdue > 0)
                        <span class="ml-1">(+{{ $lease->days_overdue }} days)</span>
                    @endif
                </span>
            </div>
            <div>
                <p class="text-sm text-gray-600">Due Date</p>
                <p class="font-bold {{ $lease->due_date < now() && $lease->status == 'active' ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $lease->due_date->format('M d, Y') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Repayment History Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Amount
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Method
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Reference
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($repayments as $repayment)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $repayment->paid_at ? $repayment->paid_at->format('M d, Y') : $repayment->due_date->format('M d, Y') }}
                            </div>
                            <div class="text-xs text-gray-500">
                                @if($repayment->paid_at)
                                    Paid: {{ $repayment->paid_at->format('h:i A') }}
                                @else
                                    Due: {{ $repayment->due_date->diffForHumans() }}
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">ZAR {{ number_format($repayment->amount, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'paid' => ['bg-green-100', 'text-green-800', 'Paid'],
                                    'pending' => ['bg-yellow-100', 'text-yellow-800', 'Pending'],
                                    'overdue' => ['bg-red-100', 'text-red-800', 'Overdue'],
                                ];
                                $status = $statusColors[$repayment->status] ?? $statusColors['pending'];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                {{ $status[2] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $repayment->payment_method ? ucfirst($repayment->payment_method) : '—' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $repayment->transaction_reference ?? '—' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                @if($repayment->status != 'paid')
                                    <button onclick="markAsPaid({{ $repayment->id }})" 
                                            class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Mark as Paid">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                                
                                @if($repayment->paid_at)
                                    <button onclick="showPaymentDetails({{ $repayment->id }})" 
                                            class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-history text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-700">No repayment history found</p>
                                <p class="text-gray-500 mt-1">No payments have been recorded for this lease</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($repayments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-semibold">{{ $repayments->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $repayments->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $repayments->total() }}</span> repayments
                </div>
                <div class="flex space-x-2">
                    {{ $repayments->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Payment Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Payment Statistics -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Payment Statistics</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Total Paid</span>
                        <span class="font-bold text-green-600">
                            ZAR {{ number_format($lease->total_amount - $lease->remaining_balance, 2) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-600" 
                             style="width: {{ $lease->progress_percentage }}%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Remaining Balance</span>
                        <span class="font-bold text-red-600">
                            ZAR {{ number_format($lease->remaining_balance, 2) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gradient-to-r from-red-500 to-red-600" 
                             style="width: {{ 100 - $lease->progress_percentage }}%"></div>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Total Payments:</span>
                            <span class="font-bold block">{{ $repayments->where('status', 'paid')->count() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Pending:</span>
                            <span class="font-bold text-yellow-600 block">{{ $repayments->where('status', 'pending')->count() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Overdue:</span>
                            <span class="font-bold text-red-600 block">{{ $repayments->where('status', 'overdue')->count() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Avg. Payment:</span>
                            <span class="font-bold block">
                                @php
                                    $paidCount = $repayments->where('status', 'paid')->count();
                                    $totalPaid = $lease->total_amount - $lease->remaining_balance;
                                    $avgPayment = $paidCount > 0 ? $totalPaid / $paidCount : 0;
                                @endphp
                                ZAR {{ number_format($avgPayment, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Payment Methods</h3>
            <div class="space-y-4">
                @php
                    $methods = $repayments->where('status', 'paid')->groupBy('payment_method')->map(function($group) {
                        return [
                            'count' => $group->count(),
                            'amount' => $group->sum('amount')
                        ];
                    })->sortByDesc('amount');
                @endphp
                
                @forelse($methods as $method => $data)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">{{ ucfirst($method) }}</span>
                        <span class="font-medium">{{ $data['count'] }} payments</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>ZAR {{ number_format($data['amount'], 2) }}</span>
                        <span>{{ number_format(($data['amount'] / ($lease->total_amount - $lease->remaining_balance)) * 100, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gradient-to-r from-blue-500 to-blue-600" 
                             style="width: {{ ($data['amount'] / ($lease->total_amount - $lease->remaining_balance)) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="fas fa-credit-card text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">No payment method data</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h3>
            <div class="space-y-3">
                <button onclick="showPaymentModal()" 
                       class="w-full flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors">
                    <i class="fas fa-money-bill-wave mr-3"></i>
                    <span class="font-medium">Record Payment</span>
                </button>
                
                <button onclick="showBulkPaymentModal()" 
                       class="w-full flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                    <i class="fas fa-calculator mr-3"></i>
                    <span class="font-medium">Calculate Repayment</span>
                </button>
                
                <button onclick="exportHistory()" 
                       class="w-full flex items-center p-3 bg-purple-50 text-purple-700 rounded-xl hover:bg-purple-100 transition-colors">
                    <i class="fas fa-file-export mr-3"></i>
                    <span class="font-medium">Export History</span>
                </button>
                
                <a href="{{ route('admin.leases.show', $lease) }}" 
                   class="w-full flex items-center p-3 bg-gray-50 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                    <i class="fas fa-arrow-left mr-3"></i>
                    <span class="font-medium">Back to Lease</span>
                </a>
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

<!-- Payment Details Modal -->
<div id="paymentDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Payment Details</h3>
                <button type="button" onclick="closePaymentDetailsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="paymentDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            
            <div class="flex justify-end mt-6">
                <button type="button" 
                        onclick="closePaymentDetailsModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>
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

    // Payment Details Modal
    function showPaymentDetails(repaymentId) {
        fetch(`/admin/repayments/${repaymentId}/details`)
            .then(response => response.json())
            .then(data => {
                const content = document.getElementById('paymentDetailsContent');
                content.innerHTML = `
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Amount
                            </label>
                            <p class="text-lg font-bold text-gray-900">ZAR ${data.amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Date & Time
                            </label>
                            <p class="text-sm text-gray-900">${new Date(data.paid_at).toLocaleString()}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Payment Method
                            </label>
                            <p class="text-sm text-gray-900">${data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1)}</p>
                        </div>
                        
                        ${data.transaction_reference ? `
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Reference Number
                            </label>
                            <p class="text-sm font-mono text-gray-900">${data.transaction_reference}</p>
                        </div>
                        ` : ''}
                        
                        ${data.notes ? `
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Notes
                            </label>
                            <p class="text-sm text-gray-900">${data.notes}</p>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                document.getElementById('paymentDetailsModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load payment details');
            });
    }
    
    function closePaymentDetailsModal() {
        document.getElementById('paymentDetailsModal').classList.add('hidden');
        document.getElementById('paymentDetailsContent').innerHTML = '';
    }

    // Mark repayment as paid
    function markAsPaid(repaymentId) {
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

    // Export history
    function exportHistory() {
        window.open(`/admin/leases/{{ $lease->id }}/export-history`, '_blank');
    }

    // Handle form submission with AJAX
    document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
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
                alert('Payment recorded successfully!');
                closePaymentModal();
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

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePaymentModal();
            closePaymentDetailsModal();
        }
    });

    // Auto-calculate payment amount
    document.querySelector('#paymentForm input[name="amount"]')?.addEventListener('input', function(e) {
        const maxAmount = parseFloat(this.max);
        const currentAmount = parseFloat(this.value) || 0;
        
        if (currentAmount > maxAmount) {
            this.value = maxAmount;
        }
    });
</script>
@endsection