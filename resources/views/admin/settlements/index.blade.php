@extends('layouts.admin')

@section('title', 'Settlements Management')
@section('page-title', 'Settlement Processing')
@section('page-description', 'Manage fuel station settlements and payments')
@section('breadcrumb', 'Settlements')

@php
    // Additional stats calculations
    $todaySettlements = App\Models\Settlement::whereDate('settlement_date', today())->sum('amount');
    $thisMonthSettlements = App\Models\Settlement::whereMonth('settlement_date', now()->month)->sum('amount');
    $avgSettlementAmount = $stats['total_settlements'] > 0 ? $stats['total_amount'] / $stats['total_settlements'] : 0;
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Settlements -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Settlements</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_settlements']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-money-check-alt text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            ZAR {{ number_format($stats['total_amount'], 2) }} total amount
        </div>
    </div>

    <!-- Pending Settlements -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Pending Settlements</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['pending_count']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium text-yellow-700">
            ZAR {{ number_format($stats['pending_amount'], 2) }} pending
        </div>
    </div>

    <!-- Completed Settlements -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Completed</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['completed_count']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            ZAR {{ number_format($stats['completed_amount'], 2) }} processed
        </div>
    </div>

    <!-- Average Settlement -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Avg. Settlement</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">ZAR {{ number_format($avgSettlementAmount, 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            {{ $stats['total_settlements'] > 0 ? number_format(($stats['completed_count'] / $stats['total_settlements']) * 100, 1) : 0 }}% completion rate
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Settlement Management</h2>
            <p class="text-gray-600 mt-1">Process payments to fuel stations for redeemed vouchers</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('admin.settlements.create') }}" 
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i> New Settlement
            </a>
            <a href="{{ route('admin.settlements.export') }}" 
               class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-download mr-2"></i> Export
            </a>
            <button onclick="toggleFilters()" 
                    class="px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300">
                <i class="fas fa-filter"></i>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">Today's Settlements</p>
            <p class="text-xl font-bold text-gray-900">ZAR {{ number_format($todaySettlements, 2) }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">This Month</p>
            <p class="text-xl font-bold text-gray-900">ZAR {{ number_format($thisMonthSettlements, 2) }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">Failed Settlements</p>
            <p class="text-xl font-bold text-red-600">{{ number_format($stats['failed_count']) }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600">Avg. Vouchers/Settlement</p>
            <p class="text-xl font-bold text-gray-900">
                {{ $stats['total_settlements'] > 0 ? number_format(DB::table('fuel_vouchers')->whereNotNull('settlement_id')->count() / $stats['total_settlements'], 1) : 0 }}
            </p>
        </div>
    </div>

    <!-- Search and Filters -->
    <div id="filterSection" class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('admin.settlements.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2 relative">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search by reference, transaction ID, or station..." 
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                </div>
                
                <!-- Status Filter -->
                <select name="status" 
                        class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
                
                <!-- Station Filter -->
                <select name="fuel_station_id" 
                        class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <option value="">All Stations</option>
                    @foreach($fuelStations as $station)
                        <option value="{{ $station->id }}" {{ request('fuel_station_id') == $station->id ? 'selected' : '' }}>
                            {{ $station->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    <select name="sort" 
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Sort by: Newest</option>
                        <option value="amount" {{ request('sort') == 'amount' ? 'selected' : '' }}>Sort by: Amount</option>
                        <option value="settlement_date" {{ request('sort') == 'settlement_date' ? 'selected' : '' }}>Sort by: Date</option>
                    </select>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-2">
                <div class="text-sm text-gray-600">
                    Found {{ $settlements->total() }} settlements
                </div>
                <div class="flex space-x-2">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.settlements.index') }}" 
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                        Clear All
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Settlements Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-3">Reference</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Station Details
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Settlement Info
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($settlements as $settlement)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <!-- Reference -->
                        <td class="px-6 py-5">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="selected_settlements[]" 
                                       value="{{ $settlement->id }}" 
                                       class="settlement-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                                <div class="space-y-1">
                                    <div class="text-sm font-semibold text-gray-900">{{ $settlement->reference }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $settlement->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        ID: {{ $settlement->id }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Station Details -->
                        <td class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                        <i class="fas fa-gas-pump text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('admin.stations.show', $settlement->fuelStation) }}" class="hover:text-blue-600">
                                            {{ $settlement->fuelStation->name }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $settlement->fuelStation->company }}</div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i> {{ $settlement->fuelStation->city }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Settlement Info -->
                        <td class="px-6 py-5">
                            <div class="space-y-2">
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        ZAR {{ number_format($settlement->amount, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $settlement->voucher_count }} voucher{{ $settlement->voucher_count != 1 ? 's' : '' }}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                                        {{ $settlement->settlement_date->format('M d, Y') }}
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-money-bill-wave mr-2 text-gray-400"></i>
                                        {{ ucfirst(str_replace('_', ' ', $settlement->payment_method)) }}
                                    </div>
                                    @if($settlement->transaction_reference)
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-receipt mr-2 text-gray-400"></i>
                                        {{ $settlement->transaction_reference }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Status -->
                        <td class="px-6 py-5">
                            @php
                                $statusColors = [
                                    'pending' => ['bg-yellow-100', 'text-yellow-800', 'Pending', 'fa-clock'],
                                    'completed' => ['bg-green-100', 'text-green-800', 'Completed', 'fa-check-circle'],
                                    'failed' => ['bg-red-100', 'text-red-800', 'Failed', 'fa-exclamation-circle'],
                                ];
                                $status = $statusColors[$settlement->status] ?? $statusColors['pending'];
                            @endphp
                            <div class="space-y-2">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                    <i class="fas {{ $status[3] }} mr-1.5"></i>
                                    {{ $status[2] }}
                                </span>
                                
                                @if($settlement->processed_at)
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-check mr-1"></i> 
                                    Processed: {{ $settlement->processed_at->format('M d, Y H:i') }}
                                </div>
                                @endif
                                
                                @if($settlement->notes)
                                <div class="text-xs text-gray-500 truncate max-w-xs" title="{{ $settlement->notes }}">
                                    <i class="fas fa-sticky-note mr-1"></i> {{ Str::limit($settlement->notes, 50) }}
                                </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-5">
                            <div class="flex flex-col space-y-2">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.settlements.show', $settlement) }}" 
                                       class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($settlement->status === 'pending')
                                        <a href="{{ route('admin.settlements.edit', $settlement) }}" 
                                           class="text-yellow-600 hover:text-yellow-900 p-2 hover:bg-yellow-50 rounded-lg transition-colors"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form action="{{ route('admin.settlements.process', $settlement) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors"
                                                    onclick="return confirm('Process settlement {{ $settlement->reference }}?')"
                                                    title="Process Settlement">
                                                <i class="fas fa-play-circle"></i>
                                            </button>
                                        </form>
                                        
                                        <button onclick="showFailModal({{ $settlement->id }}, '{{ addslashes($settlement->reference) }}')" 
                                                class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Mark as Failed">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    @endif
                                    
                                    @if($settlement->status === 'pending')
                                    <form action="{{ route('admin.settlements.destroy', $settlement) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                                onclick="return confirm('Delete settlement {{ $settlement->reference }}?')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                
                                <!-- Quick Links -->
                                <div class="flex flex-wrap gap-1">
                                    <a href="{{ route('admin.stations.show', $settlement->fuelStation) }}" 
                                       class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200">
                                        <i class="fas fa-gas-pump mr-1"></i> Station
                                    </a>
                                    <a href="#" onclick="showVouchers({{ $settlement->id }})" 
                                       class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200">
                                        <i class="fas fa-ticket-alt mr-1"></i> Vouchers
                                    </a>
                                    @if($settlement->transaction_reference)
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                        <i class="fas fa-receipt mr-1"></i> {{ Str::limit($settlement->transaction_reference, 10) }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-money-check-alt text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-700">No settlements found</p>
                                <p class="text-gray-500 mt-1">Create your first settlement to get started</p>
                                <a href="{{ route('admin.settlements.create') }}" 
                                   class="inline-block mt-4 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i> Create Settlement
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($settlements->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-semibold">{{ $settlements->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $settlements->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $settlements->total() }}</span> settlements
                </div>
                <div class="flex space-x-2">
                    @if($settlements->onFirstPage())
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $settlements->previousPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    @foreach(range(1, min(5, $settlements->lastPage())) as $page)
                        <a href="{{ $settlements->url($page) }}" 
                           class="px-3 py-2 border rounded-lg {{ $settlements->currentPage() == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            {{ $page }}
                        </a>
                    @endforeach

                    @if($settlements->hasMorePages())
                        <a href="{{ $settlements->nextPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
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
        <!-- Quick Actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('admin.settlements.create') }}" 
                   class="flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-blue-600"></i>
                    </div>
                    <span class="font-medium">Create New Settlement</span>
                </a>
                
                <button onclick="showBulkProcess()" 
                        class="w-full flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors text-left">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-play-circle text-green-600"></i>
                    </div>
                    <span class="font-medium">Bulk Process Settlements</span>
                </button>
                
                <a href="{{ route('admin.settlements.export') }}" 
                   class="flex items-center p-3 bg-purple-50 text-purple-700 rounded-xl hover:bg-purple-100 transition-colors">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-download text-purple-600"></i>
                    </div>
                    <span class="font-medium">Export All Settlements</span>
                </a>
                
                <a href="{{ route('admin.stations.index') }}" 
                   class="flex items-center p-3 bg-yellow-50 text-yellow-700 rounded-xl hover:bg-yellow-100 transition-colors">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-gas-pump text-yellow-600"></i>
                    </div>
                    <span class="font-medium">View All Stations</span>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div class="space-y-3">
                @php
                    $recentSettlements = App\Models\Settlement::latest()->take(5)->get();
                @endphp
                @foreach($recentSettlements as $recent)
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg {{ $recent->status === 'completed' ? 'bg-green-100' : ($recent->status === 'failed' ? 'bg-red-100' : 'bg-yellow-100') }} flex items-center justify-center mr-3">
                            <i class="fas {{ $recent->status === 'completed' ? 'fa-check text-green-600' : ($recent->status === 'failed' ? 'fa-times text-red-600' : 'fa-clock text-yellow-600') }} text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $recent->reference }}</p>
                            <p class="text-xs text-gray-500">{{ $recent->fuelStation->name }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-900">ZAR {{ number_format($recent->amount, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $recent->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Dropdown -->
<div id="bulkActions" class="hidden bg-white p-4 rounded-xl shadow-md border border-gray-200 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="text-gray-700 font-medium">Bulk Actions:</span>
            <select id="bulkActionSelect" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Choose action...</option>
                <option value="process">Process Settlements</option>
                <option value="export">Export Selected</option>
                <option value="delete">Delete Settlements</option>
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

<!-- Fail Modal -->
<div id="failModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="failForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Mark as Failed</h3>
                    <button type="button" onclick="closeFailModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">Settlement: <span id="failSettlementRef" class="font-semibold text-gray-900"></span></p>
                    <p class="text-sm text-gray-500 mt-1">Provide a reason for marking this settlement as failed.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for Failure
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain why this settlement failed..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeFailModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Mark as Failed
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Vouchers Modal -->
<div id="vouchersModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Settlement Vouchers</h3>
                <button type="button" onclick="closeVouchersModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="vouchersContent" class="max-h-96 overflow-y-auto">
                <!-- Vouchers will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Bulk Process Modal -->
<div id="bulkProcessModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
        <form action="{{ route('admin.settlements.bulk-process') }}" method="POST" id="bulkProcessForm">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Bulk Process Settlements</h3>
                    <button type="button" onclick="closeBulkProcessModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-6">
                    <p class="text-gray-600">Select multiple pending settlements to process them all at once.</p>
                    <p class="text-sm text-gray-500 mt-1">This will credit station wallets for all selected settlements.</p>
                </div>
                
                <!-- Settlements Selection -->
                <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                    @foreach(App\Models\Settlement::pending()->with('fuelStation')->get() as $settlement)
                    <div class="flex items-center p-4 border border-gray-200 rounded-xl hover:border-blue-300 transition-colors">
                        <input type="checkbox" 
                               name="settlements[]" 
                               value="{{ $settlement->id }}" 
                               id="bulk_{{ $settlement->id }}"
                               class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300 bulk-checkbox">
                        <label for="bulk_{{ $settlement->id }}" class="ml-4 flex-1 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">{{ $settlement->reference }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ $settlement->fuelStation->name }}</p>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-calendar-alt text-gray-400 text-xs mr-1"></i>
                                        <span class="text-xs text-gray-500">{{ $settlement->settlement_date->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-gray-900">ZAR {{ number_format($settlement->amount, 2) }}</div>
                                    <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                        {{ $settlement->voucher_count }} vouchers
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                    
                    @if(App\Models\Settlement::pending()->count() === 0)
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-3xl mb-3 opacity-20"></i>
                        <p>No pending settlements available</p>
                        <p class="text-sm mt-1">All settlements are already processed</p>
                    </div>
                    @endif
                </div>
                
                <!-- Summary -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600">Selected</p>
                            <p id="bulkSelectedCount" class="text-xl font-bold text-gray-900">0 settlements</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Total Amount</p>
                            <p id="bulkTotalAmount" class="text-xl font-bold text-blue-700">ZAR 0.00</p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeBulkProcessModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            id="bulkProcessBtn"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        <i class="fas fa-play-circle mr-2"></i>
                        Process Selected
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle filters
    function toggleFilters() {
        const filterSection = document.getElementById('filterSection');
        filterSection.classList.toggle('hidden');
    }

    // Toggle bulk actions
    function toggleBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        bulkActions.classList.toggle('hidden');
    }

    // Select all checkboxes
    document.getElementById('selectAll')?.addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.settlement-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    // Fail Modal
    function showFailModal(settlementId, settlementRef) {
        document.getElementById('failSettlementRef').textContent = settlementRef;
        const form = document.getElementById('failForm');
        form.action = `/admin/settlements/${settlementId}/mark-as-failed`;
        document.getElementById('failModal').classList.remove('hidden');
    }
    
    function closeFailModal() {
        document.getElementById('failModal').classList.add('hidden');
        document.getElementById('failForm').reset();
    }

    // Vouchers Modal
    async function showVouchers(settlementId) {
        try {
            const response = await fetch(`/admin/settlements/${settlementId}/vouchers`);
            const data = await response.json();
            
            let vouchersHtml = `
                <div class="space-y-3">
                    <div class="text-sm text-gray-600 mb-4">
                        Total vouchers: ${data.vouchers.length}
                    </div>
            `;
            
            data.vouchers.forEach(voucher => {
                vouchersHtml += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Voucher #${voucher.id}</p>
                            <p class="text-xs text-gray-500">User: ${voucher.user?.name || 'Unknown'}</p>
                            <p class="text-xs text-gray-500">Amount: ZAR ${parseFloat(voucher.amount).toFixed(2)}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs px-2 py-1 rounded ${voucher.status === 'redeemed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                ${voucher.status}
                            </span>
                            <p class="text-xs text-gray-500 mt-1">${new Date(voucher.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                `;
            });
            
            vouchersHtml += '</div>';
            document.getElementById('vouchersContent').innerHTML = vouchersHtml;
            document.getElementById('vouchersModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error loading vouchers:', error);
            document.getElementById('vouchersContent').innerHTML = '<p class="text-red-600">Failed to load vouchers</p>';
            document.getElementById('vouchersModal').classList.remove('hidden');
        }
    }
    
    function closeVouchersModal() {
        document.getElementById('vouchersModal').classList.add('hidden');
    }

    // Bulk process modal
    function showBulkProcess() {
        updateBulkSummary();
        document.getElementById('bulkProcessModal').classList.remove('hidden');
    }
    
    function closeBulkProcessModal() {
        document.getElementById('bulkProcessModal').classList.add('hidden');
        document.querySelectorAll('.bulk-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateBulkSummary();
    }
    
    // Bulk process summary
    function updateBulkSummary() {
        let totalAmount = 0;
        let selectedCount = 0;
        
        document.querySelectorAll('.bulk-checkbox:checked').forEach(checkbox => {
            selectedCount++;
            const settlementRow = checkbox.closest('.flex.items-center');
            const amountText = settlementRow.querySelector('.text-sm.font-bold')?.textContent || '0';
            const amount = parseFloat(amountText.replace('ZAR ', '').replace(/,/g, '')) || 0;
            totalAmount += amount;
        });
        
        document.getElementById('bulkSelectedCount').textContent = selectedCount + ' settlement' + (selectedCount !== 1 ? 's' : '');
        document.getElementById('bulkTotalAmount').textContent = 'ZAR ' + totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2 });
        document.getElementById('bulkProcessBtn').disabled = selectedCount === 0;
    }
    
    // Add event listeners to bulk checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.bulk-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkSummary);
        });
    });
    
    // Handle bulk form submission
    document.getElementById('bulkProcessForm')?.addEventListener('submit', function(e) {
        const selectedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
        if (selectedCount === 0) {
            e.preventDefault();
            alert('Please select at least one settlement to process');
            return;
        }
        
        const confirmation = confirm(`Process ${selectedCount} settlement(s)? This will credit station wallets.`);
        if (!confirmation) {
            e.preventDefault();
        }
    });

    // Apply bulk action
    function applyBulkAction() {
        const action = document.getElementById('bulkActionSelect').value;
        const selectedSettlements = Array.from(document.querySelectorAll('.settlement-checkbox:checked'))
                                       .map(cb => cb.value);
        
        if (selectedSettlements.length === 0) {
            alert('Please select at least one settlement');
            return;
        }
        
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        if (action === 'process') {
            // Show bulk process modal
            showBulkProcess();
        } else if (action === 'export') {
            // Handle export
            const url = selectedSettlements.length > 0 
                ? `/admin/settlements/export?settlements=${selectedSettlements.join(',')}`
                : `/admin/settlements/export`;
            window.location.href = url;
        } else if (action === 'delete') {
            if (confirm(`Delete ${selectedSettlements.length} settlement(s)? This will release all vouchers back to unsettled state.`)) {
                // In a real app, you would make an AJAX request here
                alert(`Deleting ${selectedSettlements.length} settlement(s)...`);
                toggleBulkActions();
            }
        }
    }

    // Auto-submit date changes
    document.querySelectorAll('input[type="date"], select[name="status"], select[name="fuel_station_id"]').forEach(element => {
        element.addEventListener('change', function() {
            if (this.form) this.form.submit();
        });
    });

    // Handle fail form submission
    document.getElementById('failForm')?.addEventListener('submit', function(e) {
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
                alert('Settlement marked as failed!');
                location.reload();
            } else {
                alert('Failed to mark settlement as failed');
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

    // Handle bulk process form
    document.getElementById('bulkProcessForm')?.addEventListener('submit', function(e) {
        const submitButton = this.querySelector('#bulkProcessBtn');
        const originalText = submitButton.innerHTML;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        submitButton.disabled = true;
    });
</script>
@endsection