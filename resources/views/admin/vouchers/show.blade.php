@extends('layouts.admin')

@section('title', 'Voucher Details')
@section('page-title', 'Voucher Details')
@section('page-description', 'View detailed information about fuel voucher')
@section('breadcrumb', 'Vouchers / Details')

@section('content')
<div class="p-6">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('admin.vouchers.index') }}" 
           class="inline-flex items-center text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i> Back to Vouchers
        </a>
    </div>

    <!-- Voucher Details Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <!-- Card Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Voucher #{{ $voucher->id }}</h3>
                    <p class="text-gray-600 text-sm mt-1">{{ $voucher->code ?? 'No code' }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Status Badge -->
                    @php
                        $statusColors = [
                            'issued' => ['bg-blue-100', 'text-blue-800', 'fa-clock'],
                            'approved' => ['bg-yellow-100', 'text-yellow-800', 'fa-check-circle'],
                            'redeemed' => ['bg-green-100', 'text-green-800', 'fa-gas-pump'],
                            'expired' => ['bg-gray-100', 'text-gray-800', 'fa-calendar-times'],
                            'cancelled' => ['bg-red-100', 'text-red-800', 'fa-ban'],
                        ];
                        $status = $statusColors[$voucher->status] ?? $statusColors['issued'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $status[0] }} {{ $status[1] }}">
                        <i class="fas {{ $status[2] }} mr-1.5"></i>
                        {{ ucfirst($voucher->status) }}
                    </span>
                    
                    <!-- Actions -->
                    @if($voucher->status === 'issued')
                        <form action="{{ route('admin.vouchers.approve', $voucher) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm('Approve this voucher?')"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Approve
                            </button>
                        </form>
                        
                        <button onclick="showRejectModal()" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Reject
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="p-6">
            <!-- Main Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left Column: Basic Info -->
                <div class="space-y-6">
                    <!-- Amount -->
                    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-xl border border-blue-100">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Voucher Amount</h4>
                        <div class="text-3xl font-bold text-gray-900">
                            ZAR {{ number_format($voucher->amount, 2) }}
                        </div>
                        @if($voucher->lease)
                            <div class="mt-2 text-sm text-gray-600">
                                <i class="fas fa-credit-card mr-1 text-purple-500"></i>
                                BNPL Lease Payment
                            </div>
                        @else
                            <div class="mt-2 text-sm text-gray-600">
                                <i class="fas fa-wallet mr-1 text-green-500"></i>
                                Wallet Purchase
                            </div>
                        @endif
                    </div>

                    <!-- User Information -->
                    <div class="bg-white p-5 rounded-xl border border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">User Information</h4>
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl flex items-center justify-center mr-3">
                                <i class="fas fa-user text-blue-600 text-lg"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-900">
                                    <a href="{{ route('admin.users.show', $voucher->user) }}" class="hover:text-blue-600">
                                        {{ $voucher->user->name ?? 'Unknown User' }}
                                    </a>
                                </h5>
                                <p class="text-sm text-gray-600">{{ $voucher->user->email ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $voucher->user->phone ?? 'No phone' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Station Information -->
                    @if($voucher->fuelStation)
                    <div class="bg-white p-5 rounded-xl border border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Fuel Station</h4>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-50 rounded-xl flex items-center justify-center mr-3">
                                <i class="fas fa-gas-pump text-green-600 text-lg"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-900">
                                    <a href="{{ route('admin.stations.show', $voucher->fuelStation) }}" class="hover:text-green-600">
                                        {{ $voucher->fuelStation->name }}
                                    </a>
                                </h5>
                                <p class="text-sm text-gray-600">{{ $voucher->fuelStation->city ?? '' }}</p>
                                <p class="text-xs text-gray-500">{{ $voucher->fuelStation->address ?? 'No address' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column: Dates & Additional Info -->
                <div class="space-y-6">
                    <!-- Dates Timeline -->
                    <div class="bg-white p-5 rounded-xl border border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-4">Timeline</h4>
                        <div class="space-y-4">
                            <!-- Created Date -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-plus text-gray-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Created</p>
                                    <p class="text-sm text-gray-600">{{ $voucher->created_at->format('F d, Y H:i') }}</p>
                                    <p class="text-xs text-gray-500">{{ $voucher->created_at->diffForHumans() }}</p>
                                </div>
                            </div>

                            <!-- Issued Date -->
                            @if($voucher->issued_at)
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-paper-plane text-blue-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Issued</p>
                                    <p class="text-sm text-gray-600">{{ $voucher->issued_at->format('F d, Y H:i') }}</p>
                                    <p class="text-xs text-gray-500">{{ $voucher->issued_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @endif

                            <!-- Expiry Date -->
                            @if($voucher->expires_at)
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 {{ $voucher->expires_at->isPast() ? 'bg-red-100' : 'bg-yellow-100' }} rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-clock {{ $voucher->expires_at->isPast() ? 'text-red-600' : 'text-yellow-600' }} text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Expires</p>
                                    <p class="text-sm text-gray-600">{{ $voucher->expires_at->format('F d, Y H:i') }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $voucher->expires_at->isPast() ? 'Expired ' : 'Expires in ' }}
                                        {{ $voucher->expires_at->isPast() ? $voucher->expires_at->diffForHumans() : $voucher->expires_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            @endif

                            <!-- Redeemed Date -->
                            @if($voucher->redeemed_at)
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-gas-pump text-green-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Redeemed</p>
                                    <p class="text-sm text-gray-600">{{ $voucher->redeemed_at->format('F d, Y H:i') }}</p>
                                    <p class="text-xs text-gray-500">{{ $voucher->redeemed_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="bg-white p-5 rounded-xl border border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-4">Additional Information</h4>
                        <div class="space-y-3">
                            @if($voucher->lease)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Lease:</span>
                                <span class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('admin.leases.show', $voucher->lease) }}" class="hover:text-purple-600">
                                        #{{ $voucher->lease->id }}
                                    </a>
                                </span>
                            </div>
                            @endif

                            @if($voucher->settlement)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Settlement:</span>
                                <span class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('admin.settlements.show', $voucher->settlement) }}" class="hover:text-yellow-600">
                                        #{{ $voucher->settlement->id }}
                                    </a>
                                </span>
                            </div>
                            @endif

                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Voucher Type:</span>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ $voucher->lease ? 'BNPL Lease Payment' : 'Wallet Purchase' }}
                                </span>
                            </div>

                            @if($voucher->rejection_reason)
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="text-sm font-medium text-gray-700 mb-1">Rejection Reason:</p>
                                <p class="text-sm text-gray-900 bg-red-50 p-3 rounded-lg">
                                    {{ $voucher->rejection_reason }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 pt-8 border-t border-gray-200 flex justify-between">
                <div>
                    @if($voucher->status === 'issued')
                        <form action="{{ route('admin.vouchers.approve', $voucher) }}" method="POST" class="inline mr-3">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm('Approve this voucher?')"
                                    class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 font-medium">
                                <i class="fas fa-check-circle mr-2"></i> Approve Voucher
                            </button>
                        </form>
                        
                        <button onclick="showRejectModal()" 
                                class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-medium">
                            <i class="fas fa-times-circle mr-2"></i> Reject Voucher
                        </button>
                    @endif
                </div>
                
                <div>
                    <a href="{{ route('admin.vouchers.index') }}" 
                       class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Information -->
    @if($voucher->lease || $voucher->settlement)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        @if($voucher->lease)
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Lease Information</h4>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Lease ID:</span>
                    <span class="text-sm font-medium text-gray-900">
                        <a href="{{ route('admin.leases.show', $voucher->lease) }}" class="hover:text-purple-600">
                            #{{ $voucher->lease->id }}
                        </a>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Amount:</span>
                    <span class="text-sm font-medium text-gray-900">
                        ZAR {{ number_format($voucher->lease->total_amount, 2) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Status:</span>
                    <span class="text-sm font-medium">
                        <span class="px-2 py-1 rounded-full text-xs {{ $voucher->lease->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($voucher->lease->status) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
        @endif

        @if($voucher->settlement)
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Settlement Information</h4>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Settlement ID:</span>
                    <span class="text-sm font-medium text-gray-900">
                        <a href="{{ route('admin.settlements.show', $voucher->settlement) }}" class="hover:text-yellow-600">
                            #{{ $voucher->settlement->id }}
                        </a>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Amount:</span>
                    <span class="text-sm font-medium text-gray-900">
                        ZAR {{ number_format($voucher->settlement->amount, 2) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Status:</span>
                    <span class="text-sm font-medium">
                        <span class="px-2 py-1 rounded-full text-xs {{ $voucher->settlement->status === 'paid' ? 'bg-green-100 text-green-800' : ($voucher->settlement->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst($voucher->settlement->status) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif
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
                    <p class="text-gray-600">Voucher: <span class="font-semibold text-gray-900">#{{ $voucher->id }}</span></p>
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

<script>
    function showRejectModal() {
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.querySelector('#rejectModal textarea[name="reason"]').value = '';
    }

    // Close modal when clicking outside
    document.getElementById('rejectModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });
</script>
@endsection