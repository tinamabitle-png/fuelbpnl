@extends('Layouts.admin')

@section('title', 'Fuel Station Details - ' . $station->name)
@section('page-title', 'Station Details')
@section('page-description', $station->name)
@section('breadcrumb')
<a href="{{ route('admin.stations.index') }}">Fuel Stations</a>
<i class="fas fa-chevron-right mx-2 text-xs"></i>
<span class="text-blue-600">{{ $station->name }}</span>
@endsection

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Wallet Balance -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100 hover-lift">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Wallet Balance</p>
                <p class="text-2xl font-bold text-gray-600 mt-2">ZAR {{ number_format($station->wallet_balance, 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-wallet text-purple-500 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <a href="javascript:void(0)" 
               onclick="showWalletModal()"
               class="text-sm bg-purple-100 text-purple-600 px-3 py-1 rounded-full hover:bg-purple-200">
                <i class="fas fa-edit mr-1"></i> Adjust
            </a>
        </div>
    </div>

    <!-- Total Vouchers -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100 hover-lift">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Vouchers</p>
                <p class="text-2xl font-bold text-gray-600 mt-2">{{ number_format($stats['total_vouchers']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-ticket-alt text-blue-500 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm">
            <span class="text-green-600">{{ number_format($stats['redeemed_vouchers']) }} redeemed</span>
            <span class="text-gray-500 mx-2">•</span>
            <span class="text-blue-600">{{ number_format($stats['active_vouchers']) }} active</span>
        </div>
    </div>

    <!-- Total Settlements -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100 hover-lift">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Settlements</p>
                <p class="text-2xl font-bold text-gray-600 mt-2">ZAR {{ number_format($stats['total_settlement_amount'], 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-money-check-alt text-green-500 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="text-sm text-gray-600">
                {{ $station->settlements->count() }} transactions
            </div>
        </div>
    </div>

    <!-- Pending Settlement -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100 hover-lift">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Pending Settlement</p>
                <p class="text-2xl font-bold text-gray-600 mt-2">ZAR {{ number_format($stats['pending_settlement'], 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-clock text-yellow-500 text-xl"></i>
            </div>
        </div>
        @if($stats['pending_settlement'] > 0)
        <div class="mt-4">
            <a href="{{ route('admin.settlements.create', ['station_id' => $station->id]) }}" 
               class="text-sm bg-yellow-100 text-yellow-600 px-3 py-1 rounded-full hover:bg-yellow-200">
                <i class="fas fa-plus mr-1"></i> Create Settlement
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Station Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 mb-6 shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
            <div>
                <div class="flex items-center">
                    <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center mr-4">
                        <i class="fas fa-gas-pump text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $station->name }}</h1>
                        <p class="text-blue-100">{{ $station->company }}</p>
                    </div>
                </div>
                
                <!-- Status Badge -->
                @php
                    $statusColors = [
                        'active' => ['bg-green-100', 'text-green-800', 'Active', 'fa-check-circle'],
                        'inactive' => ['bg-gray-100', 'text-gray-800', 'Inactive', 'fa-pause-circle'],
                        'pending' => ['bg-yellow-100', 'text-yellow-800', 'Pending', 'fa-clock'],
                        'suspended' => ['bg-red-100', 'text-red-800', 'Suspended', 'fa-ban'],
                    ];
                    $status = $statusColors[$station->status] ?? $statusColors['active'];
                @endphp
                <div class="flex items-center mt-4">
                    <span class="px-4 py-1.5 rounded-full text-sm font-medium {{ $status[0] }} {{ $status[1] }}">
                        <i class="fas {{ $status[3] }} mr-1.5"></i>
                        {{ $status[2] }}
                    </span>
                    
                    @if($station->owner)
                    <span class="ml-4 px-3 py-1.5 bg-white/20 text-white rounded-full text-sm">
                        <i class="fas fa-user-tie mr-1.5"></i>
                        Owner: {{ $station->owner->name }}
                    </span>
                    @endif
                    
                    <span class="ml-4 px-3 py-1.5 bg-white/20 text-white rounded-full text-sm">
                        <i class="fas fa-calendar mr-1.5"></i>
                        Joined: {{ $station->created_at->format('M d, Y') }}
                    </span>
                </div>
            </div>
            
            <div class="mt-4 md:mt-0 flex space-x-3">
                <a href="{{ route('admin.stations.edit', $station) }}" 
                   class="px-4 py-2.5 bg-white text-blue-600 rounded-xl font-medium hover:bg-blue-50 shadow-lg">
                    <i class="fas fa-edit mr-2"></i> Edit
                </a>
              
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('overview')" 
                        id="overviewTab"
                        class="py-2 px-1 border-b-2 border-blue-500 text-sm font-medium text-blue-600">
                    <i class="fas fa-info-circle mr-2"></i> Overview
                </button>
                <button onclick="showTab('vouchers')" 
                        id="vouchersTab"
                        class="py-2 px-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-ticket-alt mr-2"></i> Vouchers ({{ $stats['total_vouchers'] }})
                </button>
                <button onclick="showTab('settlements')" 
                        id="settlementsTab"
                        class="py-2 px-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-money-check-alt mr-2"></i> Settlements ({{ $station->settlements->count() }})
                </button>
                <button onclick="showTab('activity')" 
                        id="activityTab"
                        class="py-2 px-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-history mr-2"></i> Activity Log
                </button>
            </nav>
        </div>
    </div>

    <!-- Overview Tab Content -->
    <div id="overviewContent" class="tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Station Details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-600 mb-4">Station Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contact Information -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Contact Information</h4>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Contact Person</p>
                                        <p class="text-sm text-gray-500">{{ $station->contact_person }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-phone text-green-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Contact Phone</p>
                                        <a href="tel:{{ $station->contact_phone }}" 
                                           class="text-sm text-green-600 hover:text-green-700">
                                            {{ $station->contact_phone }}
                                        </a>
                                    </div>
                                </div>
                                @if($station->contact_email)
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-envelope text-purple-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Contact Email</p>
                                        <a href="mailto:{{ $station->contact_email }}" 
                                           class="text-sm text-purple-600 hover:text-purple-700">
                                            {{ $station->contact_email }}
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-3">Location Information</h4>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-map-marker-alt text-red-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Address</p>
                                        <p class="text-sm text-gray-500">{{ $station->address }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-city text-yellow-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">City & Country</p>
                                        <p class="text-sm text-gray-500">{{ $station->city }}, {{ $station->country }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-globe text-indigo-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Coordinates</p>
                                        <p class="text-sm text-gray-500">{{ $station->latitude }}, {{ $station->longitude }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Information -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-500 mb-3">Business Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500">License Number</p>
                                <p class="text-sm font-medium text-gray-600">{{ $station->license_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Registration Date</p>
                                <p class="text-sm font-medium text-gray-600">{{ $station->created_at->format('F d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-500 mb-3">Quick Actions</h4>
                        <div class="flex flex-wrap gap-3">
                            <a href="tel:{{ $station->contact_phone }}" 
                               class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">
                                <i class="fas fa-phone mr-2"></i> Call Station
                            </a>
                            @if($station->contact_email)
                            <a href="mailto:{{ $station->contact_email }}" 
                               class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">
                                <i class="fas fa-envelope mr-2"></i> Send Email
                            </a>
                            @endif
                            <a href="https://www.google.com/maps?q={{ $station->latitude }},{{ $station->longitude }}" 
                               target="_blank"
                               class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">
                                <i class="fas fa-map-marker-alt mr-2"></i> View on Map
                            </a>
                            <a href="{{ route('admin.vouchers.index', ['station_id' => $station->id]) }}" 
                               class="px-4 py-2 bg-blue-100 text-blue-600 rounded-lg text-sm hover:bg-blue-200">
                                <i class="fas fa-ticket-alt mr-2"></i> View Vouchers
                            </a>
                            <a href="{{ route('admin.settlements.index', ['fuel_station_id' => $station->id]) }}" 
                               class="px-4 py-2 bg-green-100 text-green-600 rounded-lg text-sm hover:bg-green-200">
                                <i class="fas fa-money-check-alt mr-2"></i> View Settlements
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-600 mb-4">Recent Activity</h3>
                    
                    <div class="space-y-4">
                        @if($recentVouchers->count() > 0)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Recent Vouchers</h4>
                            <div class="space-y-2">
                                @foreach($recentVouchers as $voucher)
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-600">{{ $voucher->code }}</p>
                                            <p class="text-xs text-gray-500">{{ $voucher->user->name ?? 'Unknown' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-600">ZAR {{ number_format($voucher->amount, 2) }}</p>
                                            <span class="px-2 py-1 rounded text-xs {{ $voucher->status === 'redeemed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst($voucher->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i> {{ $voucher->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                @endforeach
                            </div>
                            <a href="{{ route('admin.vouchers.index', ['station_id' => $station->id]) }}" 
                               class="mt-3 text-sm text-blue-600 hover:text-blue-700 block text-center">
                                View all vouchers →
                            </a>
                        </div>
                        @endif

                        @if($recentSettlements->count() > 0)
                        <div class="pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Recent Settlements</h4>
                            <div class="space-y-2">
                                @foreach($recentSettlements as $settlement)
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-600">{{ $settlement->reference }}</p>
                                            <p class="text-xs text-gray-500">{{ $settlement->voucher_count }} vouchers</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-600">ZAR {{ number_format($settlement->amount, 2) }}</p>
                                            <span class="px-2 py-1 rounded text-xs {{ $settlement->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($settlement->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i> {{ $settlement->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                @endforeach
                            </div>
                            <a href="{{ route('admin.settlements.index', ['fuel_station_id' => $station->id]) }}" 
                               class="mt-3 text-sm text-blue-600 hover:text-blue-700 block text-center">
                                View all settlements →
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vouchers Tab Content -->
    <div id="vouchersContent" class="tab-content hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-600">Station Vouchers</h3>
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.vouchers.index', ['station_id' => $station->id]) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-external-link-alt mr-2"></i> View All in Vouchers
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Voucher Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    User
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Issued
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentVouchers as $voucher)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $voucher->code }}</div>
                                    <div class="text-sm text-gray-500">{{ $voucher->fuel_type }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $voucher->user->name ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500">{{ $voucher->user->phone ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">ZAR {{ number_format($voucher->amount, 2) }}</div>
                                    <div class="text-sm text-gray-500">{{ $voucher->liters }} L</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'issued' => 'bg-blue-100 text-blue-800',
                                            'redeemed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            'expired' => 'bg-gray-100 text-gray-800',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full font-medium {{ $statusColors[$voucher->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($voucher->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $voucher->issued_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.vouchers.show', $voucher) }}" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-ticket-alt text-3xl mb-3 opacity-20"></i>
                                    <p>No vouchers found for this station</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($station->vouchers->count() > 5)
                <div class="mt-4 text-center">
                    <a href="{{ route('admin.vouchers.index', ['station_id' => $station->id]) }}" 
                       class="text-blue-600 hover:text-blue-700 font-medium">
                        View all {{ $station->vouchers->count() }} vouchers →
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Settlements Tab Content -->
    <div id="settlementsContent" class="tab-content hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-600">Station Settlements</h3>
                    <div class="flex space-x-2">
                        @if($stats['pending_settlement'] > 0)
                        <a href="{{ route('admin.settlements.create', ['station_id' => $station->id]) }}" 
                           class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                            <i class="fas fa-plus mr-2"></i> Create Settlement
                        </a>
                        @endif
                        <a href="{{ route('admin.settlements.index', ['fuel_station_id' => $station->id]) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-external-link-alt mr-2"></i> View All in Settlements
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reference
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Vouchers
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Settlement Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentSettlements as $settlement)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $settlement->reference }}</div>
                                    <div class="text-sm text-gray-500">{{ $settlement->payment_method }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">ZAR {{ number_format($settlement->amount, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $settlement->voucher_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full font-medium {{ $statusColors[$settlement->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($settlement->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $settlement->settlement_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.settlements.show', $settlement) }}" 
                                       class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                    @if($settlement->status === 'pending')
                                    <form action="{{ route('admin.settlements.process', $settlement) }}" 
                                          method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-play mr-1"></i> Process
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-money-check-alt text-3xl mb-3 opacity-20"></i>
                                    <p>No settlements found for this station</p>
                                    @if($stats['pending_settlement'] > 0)
                                    <a href="{{ route('admin.settlements.create', ['station_id' => $station->id]) }}" 
                                       class="mt-3 inline-block px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                        Create First Settlement
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($station->settlements->count() > 5)
                <div class="mt-4 text-center">
                    <a href="{{ route('admin.settlements.index', ['fuel_station_id' => $station->id]) }}" 
                       class="text-blue-600 hover:text-blue-700 font-medium">
                        View all {{ $station->settlements->count() }} settlements →
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Activity Tab Content -->
    <div id="activityContent" class="tab-content hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-600 mb-4">Activity Log</h3>
            
            <div class="space-y-4">
                <!-- You can integrate an activity log system here -->
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-history text-4xl mb-3 opacity-20"></i>
                    <p>Activity log integration coming soon</p>
                    <p class="text-sm text-gray-400 mt-1">Track all station activities and changes</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Wallet Adjustment Modal -->
<div id="walletModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form action="#" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Adjust Wallet Balance</h3>
                    <button type="button" onclick="closeWalletModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-600">Station: <span class="font-semibold">{{ $station->name }}</span></p>
                    <p class="text-sm text-gray-500 mt-1">
                        Current Balance: <span class="font-medium">ZAR {{ number_format($station->wallet_balance, 2) }}</span>
                    </p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="credit" 
                                       checked
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-600">Credit (Add)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="debit" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-600">Debit (Subtract)</span>
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
                               min="0"
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
    // Tab functionality
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active styles from all tabs
        document.querySelectorAll('nav button').forEach(tab => {
            tab.classList.remove('border-blue-500', 'text-blue-600');
            tab.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab content
        document.getElementById(tabName + 'Content').classList.remove('hidden');
        
        // Add active styles to selected tab
        document.getElementById(tabName + 'Tab').classList.remove('border-transparent', 'text-gray-500');
        document.getElementById(tabName + 'Tab').classList.add('border-blue-500', 'text-blue-600');
    }
    
    // Wallet modal functions
    function showWalletModal() {
        document.getElementById('walletModal').classList.remove('hidden');
    }
    
    function closeWalletModal() {
        document.getElementById('walletModal').classList.add('hidden');
        document.querySelector('#walletModal form').reset();
    }
    
    // Initialize first tab
    document.addEventListener('DOMContentLoaded', function() {
        showTab('overview');
    });
</script>
@endsection