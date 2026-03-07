@extends('Layouts.admin')

@section('title', 'Fuel Stations Management')
@section('page-title', 'Fuel Stations Dashboard')
@section('page-description', 'Manage all fuel stations in the BNPL network')
@section('breadcrumb', 'Fuel Stations')

@php
    // Calculate stats
    $totalStations = App\Models\FuelStation::count();
    $activeStations = App\Models\FuelStation::active()->count();
    $inactiveStations = App\Models\FuelStation::where('status', 'inactive')->count();
    $suspendedStations = App\Models\FuelStation::where('status', 'suspended')->count();
    
    $totalWalletBalance = App\Models\FuelStation::sum('wallet_balance');
    $totalSettlements = App\Models\FuelStation::sum('total_settlements');
    $pendingSettlements = App\Models\FuelStation::get()->sum->getPendingSettlementAmount();
    
    // Get top 5 stations by wallet balance
    $topStationsByWallet = App\Models\FuelStation::orderBy('wallet_balance', 'desc')->take(5)->get();
    
    // Get stations by city distribution
    $stationsByCity = App\Models\FuelStation::select('city', DB::raw('count(*) as count'))
        ->whereNotNull('city')
        ->groupBy('city')
        ->orderBy('count', 'desc')
        ->get();
    
    // Recent stations (last 7 days)
    $recentStations = App\Models\FuelStation::where('created_at', '>=', now()->subDays(7))->count();
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Stations -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Stations</p>
                <p class="text-xm font-bold text-gray-600 mt-2">{{ number_format($totalStations) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-gas-pump text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            <span class="text-green-600">{{ number_format($recentStations) }}</span> added this week
        </div>
    </div>

    <!-- Active Stations -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Active Stations</p>
                <p class="text-xm font-bold text-gray-600 mt-2">{{ number_format($activeStations) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full bg-green-500" 
                     style="width: {{ $totalStations > 0 ? ($activeStations / $totalStations) * 100 : 0 }}%"></div>
            </div>
            <div class="text-xs text-gray-600 mt-1">{{ number_format(($activeStations / max($totalStations, 1)) * 100, 1) }}% active</div>
        </div>
    </div>

    <!-- Wallet Balance -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Total Wallet Balance</p>
                <p class="text-xm font-bold text-gray-600 mt-2">ZAR {{ number_format($totalWalletBalance, 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-wallet text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Across all {{ $totalStations }} stations
        </div>
    </div>

    <!-- Pending Settlements -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Pending Settlements</p>
                <p class="text-xm font-bold text-gray-600 mt-2">ZAR {{ number_format($pendingSettlements, 2) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            @if($pendingSettlements > 0)
                <a href="{{ route('admin.settlements.index') }}" 
                   class="text-xs bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-medium hover:bg-yellow-200">
                    Process Now
                </a>
            @else
                <span class="text-xs text-gray-500">All settled</span>
            @endif
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-xm font-bold text-gray-600">All Fuel Stations</h2>
            <p class="text-gray-600 mt-1">Manage {{ $totalStations }} stations across {{ $stationsByCity->count() }} cities</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('admin.stations.create') }}" 
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i> Add New Station
            </a>
           
            <button onclick="toggleFilters()" 
                    class="px-4 py-3 bg-white border border-gray-300 text-gray-500 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300">
                <i class="fas fa-filter"></i> Filters
            </button>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('admin.stations.index', ['status' => 'active']) }}" 
           class="bg-green-50 border border-green-200 rounded-xl p-4 hover:bg-green-100 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-700 font-medium">Active</p>
                    <p class="text-xm font-bold text-green-900">{{ $activeStations }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            </div>
        </a>
        
        <a href="{{ route('admin.stations.index', ['status' => 'inactive']) }}" 
           class="bg-gray-50 border border-gray-200 rounded-xl p-4 hover:bg-gray-100 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Inactive</p>
                    <p class="text-xm font-bold text-gray-600">{{ $inactiveStations }}</p>
                </div>
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-pause-circle text-gray-600"></i>
                </div>
            </div>
        </a>
        
        <a href="{{ route('admin.stations.index', ['status' => 'suspended']) }}" 
           class="bg-red-50 border border-red-200 rounded-xl p-4 hover:bg-red-100 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-700 font-medium">Suspended</p>
                    <p class="text-xm font-bold text-red-900">{{ $suspendedStations }}</p>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-ban text-red-600"></i>
                </div>
            </div>
        </a>
        
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-700 font-medium">With Merchant</p>
                    <p class="text-xm font-bold text-blue-900">{{ App\Models\FuelStation::whereNotNull('owner_id')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-blue-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div id="filterSection" class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('admin.stations.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2 relative">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search stations by name, company, license, location..." 
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                </div>
                
                <!-- City Filter -->
                <select name="city" 
                        class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <option value="">All Cities</option>
                    @foreach($cities as $city)
                        @if($city)
                            <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>
                                {{ $city }} ({{ App\Models\FuelStation::where('city', $city)->count() }})
                            </option>
                        @endif
                    @endforeach
                </select>
                
                <!-- Status Filter -->
                <select name="status" 
                        class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            
            <!-- Sort Options -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">Sort by:</span>
                    <select name="sort" 
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Newest</option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                        <option value="wallet_balance" {{ request('sort') == 'wallet_balance' ? 'selected' : '' }}>Wallet Balance</option>
                        <option value="total_settlements" {{ request('sort') == 'total_settlements' ? 'selected' : '' }}>Total Settlements</option>
                        <option value="city" {{ request('sort') == 'city' ? 'selected' : '' }}>City</option>
                    </select>
                    <select name="direction" 
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Descending</option>
                        <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>Ascending</option>
                    </select>
                </div>
                
                <div class="flex space-x-2">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.stations.index') }}" 
                       class="px-4 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-medium hover:bg-gray-300">
                        Clear All
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Stations Grid/Table View Toggle -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">View:</span>
            <button id="tableViewBtn" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm">
                <i class="fas fa-table mr-1"></i> Table
            </button>
            <button id="gridViewBtn" class="px-3 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm hover:bg-gray-300">
                <i class="fas fa-th-large mr-1"></i> Grid
            </button>
        </div>
        <div class="text-sm text-gray-500">
            Showing {{ $stations->firstItem() ?? 0 }} - {{ $stations->lastItem() ?? 0 }} of {{ $stations->total() }} stations
        </div>
    </div>

    <!-- Table View (Default) -->
    <div id="tableView" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Station Details
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Location & Contact
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Financial Summary
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Status & Activity
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($stations as $station)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <!-- Station Details -->
                        <td class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                        <i class="fas fa-gas-pump text-blue-600 text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-600">
                                        <a href="{{ route('admin.stations.show', $station) }}" class="hover:text-blue-600">
                                            {{ $station->name }}
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $station->company }}</div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-id-card mr-1"></i> {{ $station->license_number }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-calendar mr-1"></i> {{ $station->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Location & Contact -->
                        <td class="px-6 py-5">
                            <div class="space-y-2">
                                <div>
                                    <div class="text-sm text-gray-600 font-medium">{{ $station->city }}, {{ $station->country }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ $station->address }}</div>
                                </div>
                                <div class="text-xs text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-user mr-2"></i> {{ $station->contact_person }}
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-phone mr-2"></i> {{ $station->contact_phone }}
                                    </div>
                                    @if($station->contact_email)
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-envelope mr-2"></i> {{ $station->contact_email }}
                                    </div>
                                    @endif
                                </div>
                                @if($station->owner)
                                <div class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-user-tie mr-1"></i> Owner: {{ $station->owner->name }}
                                </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Financial Summary -->
                        <td class="px-6 py-5">
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Wallet Balance:</span>
                                        <span class="text-sm font-bold text-gray-600">ZAR {{ number_format($station->wallet_balance, 2) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                        @php
                                            $maxBalance = $topStationsByWallet->first()->wallet_balance ?? 10000;
                                            $percentage = $maxBalance > 0 ? min(($station->wallet_balance / $maxBalance) * 100, 100) : 0;
                                        @endphp
                                        <div class="h-1.5 rounded-full bg-green-500" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Total Settlements:</span>
                                        <span class="text-sm font-bold text-green-600">ZAR {{ number_format($station->total_settlements, 2) }}</span>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <div class="flex justify-between">
                                        <span>Vouchers: {{ $station->vouchers_count }}</span>
                                        <span>Settlements: {{ $station->settlements_count }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Status & Activity -->
                        <td class="px-3 py-5">
                            <div class="space-y-3">
                                @php
                                    $statusColors = [
                                        'active' => ['bg-green-100', 'text-green-800', 'Active', 'fa-check-circle'],
                                        'inactive' => ['bg-gray-100', 'text-gray-800', 'Inactive', 'fa-pause-circle'],
                                        'pending' => ['bg-yellow-100', 'text-yellow-800', 'Pending', 'fa-clock'],
                                        'suspended' => ['bg-red-100', 'text-red-800', 'Suspended', 'fa-ban'],
                                    ];
                                    $status = $statusColors[$station->status] ?? $statusColors['active'];
                                @endphp
                                <div class="flex items-center">
                                    <span class="px-3 py-1.5 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                        <i class="fas {{ $status[3] }} mr-1.5"></i>
                                        {{ $status[2] }}
                                    </span>
                                
                                </div>
                                
                                <!-- Recent Activity -->
                                <div class="text-xs text-gray-500">
                                    @if($station->vouchers_count > 0)
                                        <div class="mt-1">
                                            <i class="fas fa-history mr-1"></i>
                                            Last voucher: {{ $station->vouchers()->latest()->first()->created_at->diffForHumans() ?? 'N/A' }}
                                        </div>
                                    @endif
                                    @if($station->settlements_count > 0)
                                        <div class="mt-1">
                                            <i class="fas fa-money-check-alt mr-1"></i>
                                            Last settlement: {{ $station->settlements()->latest()->first()->created_at->diffForHumans() ?? 'N/A' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-5">
                            <div class="flex flex-col space-y-2">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.stations.show', $station) }}" 
                                       class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.stations.edit', $station) }}" 
                                       class="text-yellow-600 hover:text-yellow-900 p-2 hover:bg-yellow-50 rounded-lg transition-colors"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="showWalletModal({{ $station->id }}, '{{ addslashes($station->name) }}', {{ $station->wallet_balance }})" 
                                            class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Adjust Wallet">
                                        <i class="fas fa-wallet"></i>
                                    </button>
                                    <form action="{{ route('admin.stations.destroy', $station) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                                onclick="return confirm('Delete {{ $station->name }}?')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Quick Links -->
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <a href="tel:{{ $station->contact_phone }}" 
                                       class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded hover:bg-gray-200">
                                        <i class="fas fa-phone text-xs mr-1"></i> Call
                                    </a>
                                    @if($station->contact_email)
                                    <a href="mailto:{{ $station->contact_email }}" 
                                       class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded hover:bg-gray-200">
                                        <i class="fas fa-envelope text-xs mr-1"></i> Email
                                    </a>
                                    @endif
                                    <a href="https://www.google.com/maps?q={{ $station->latitude }},{{ $station->longitude }}" 
                                       target="_blank"
                                       class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded hover:bg-gray-200">
                                        <i class="fas fa-map-marker-alt text-xs mr-1"></i> Map
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-gas-pump text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-500">No fuel stations found</p>
                                <p class="text-gray-500 mt-1">Get started by adding your first station</p>
                                <a href="{{ route('admin.stations.create') }}" 
                                   class="inline-block mt-4 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i> Add New Station
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($stations->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing <span class="font-semibold">{{ $stations->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $stations->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $stations->total() }}</span> stations
                </div>
                <div class="flex space-x-2">
                    @if($stations->onFirstPage())
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $stations->previousPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    @foreach(range(1, min(5, $stations->lastPage())) as $page)
                        <a href="{{ $stations->url($page) }}" 
                           class="px-3 py-2 border rounded-lg {{ $stations->currentPage() == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-500 hover:bg-gray-50' }}">
                            {{ $page }}
                        </a>
                    @endforeach

                    @if($stations->hasMorePages())
                        <a href="{{ $stations->nextPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-500 hover:bg-gray-50">
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

    <!-- Grid View (Hidden by Default) -->
    <div id="gridView" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($stations as $station)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Station Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white truncate">{{ $station->name }}</h3>
                            <p class="text-blue-100 text-sm truncate">{{ $station->company }}</p>
                        </div>
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-gas-pump text-white"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Station Details -->
                <div class="p-5">
                    <!-- Location -->
                    <div class="mb-4">
                        <div class="flex items-center text-gray-600 mb-1">
                            <i class="fas fa-map-marker-alt mr-2 text-sm"></i>
                            <span class="text-sm">{{ $station->city }}, {{ $station->country }}</span>
                        </div>
                        <p class="text-xs text-gray-500 truncate">{{ $station->address }}</p>
                    </div>
                    
                    <!-- Contact -->
                    <div class="mb-4">
                        <div class="flex items-center text-gray-600 mb-1">
                            <i class="fas fa-user mr-2 text-sm"></i>
                            <span class="text-sm">{{ $station->contact_person }}</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-phone mr-2 text-sm"></i>
                            <span class="text-sm">{{ $station->contact_phone }}</span>
                        </div>
                    </div>
                    
                    <!-- Financial Info -->
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Wallet:</span>
                            <span class="text-sm font-bold text-gray-600">ZAR {{ number_format($station->wallet_balance, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Settlements:</span>
                            <span class="text-sm font-bold text-green-600">ZAR {{ number_format($station->total_settlements, 2) }}</span>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="flex items-center justify-between">
                        @php
                            $status = $statusColors[$station->status] ?? $statusColors['active'];
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-small mr-2{{ $status[0] }} {{ $status[1] }}">
                            {{ $status[2] }}
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ $station->vouchers_count }} vouchers
                        </span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="px-5 pb-5 pt-0">
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.stations.show', $station) }}" 
                               class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.stations.edit', $station) }}" 
                               class="text-yellow-600 hover:text-yellow-900 p-2 hover:bg-yellow-50 rounded-lg">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="showWalletModal({{ $station->id }}, '{{ addslashes($station->name) }}', {{ $station->wallet_balance }})" 
                                    class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg">
                                <i class="fas fa-wallet"></i>
                            </button>
                        </div>
                        <form action="{{ route('admin.stations.destroy', $station) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg"
                                    onclick="return confirm('Delete {{ $station->name }}?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Pagination for Grid View -->
        @if($stations->hasPages())
        <div class="mt-6 px-6 py-4 border-t border-gray-200 bg-white rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ $stations->firstItem() }} - {{ $stations->lastItem() }} of {{ $stations->total() }} stations
                </div>
                <div class="flex space-x-2">
                    <!-- Same pagination as table view -->
                    @if($stations->onFirstPage())
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $stations->previousPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    @foreach(range(1, min(5, $stations->lastPage())) as $page)
                        <a href="{{ $stations->url($page) }}" 
                           class="px-3 py-2 border rounded-lg {{ $stations->currentPage() == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-00 hover:bg-gray-50' }}">
                            {{ $page }}
                        </a>
                    @endforeach

                    @if($stations->hasMorePages())
                        <a href="{{ $stations->nextPageUrl() }}" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-500 hover:bg-gray-50">
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
        <!-- Top Stations by Wallet -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-600 mb-4">Top Stations by Wallet Balance</h3>
            <div class="space-y-4">
                @foreach($topStationsByWallet as $topStation)
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-gas-pump text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">{{ $topStation->name }}</p>
                            <p class="text-xs text-gray-500">{{ $topStation->city }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-600">ZAR {{ number_format($topStation->wallet_balance, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $topStation->status }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Stations by City -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-600 mb-4">Stations by City</h3>
            <div class="space-y-3">
                @foreach($stationsByCity as $city)
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">{{ $city->city ?: 'Unknown' }}</span>
                    <div class="flex items-center">
                        <span class="font-bold text-gray-600 mr-3">{{ $city->count }}</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full bg-blue-500" 
                                 style="width: {{ ($city->count / max($totalStations, 1)) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Wallet Adjustment Modal -->
<div id="walletModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="walletForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-600">Adjust Wallet Balance</h3>
                    <button type="button" onclick="closeWalletModal()" class="text-gray-500 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">Station: <span id="walletStationName" class="font-semibold text-gray-600"></span></p>
                    <p class="text-sm text-gray-500 mt-1">Current Balance: <span id="currentWalletBalance" class="font-medium"></span></p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">
                            Type
                        </label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="credit" 
                                       checked
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-500">Credit (Add)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="debit" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-500">Debit (Subtract)</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">
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
                        <label class="block text-sm font-medium text-gray-500 mb-2">
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
                            class="px-4 py-2 border border-gray-300 text-gray-500 rounded-lg hover:bg-gray-50">
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
    // Toggle filters
    function toggleFilters() {
        const filterSection = document.getElementById('filterSection');
        filterSection.classList.toggle('hidden');
    }

    // View toggle
    document.getElementById('tableViewBtn').addEventListener('click', function() {
        document.getElementById('tableView').classList.remove('hidden');
        document.getElementById('gridView').classList.add('hidden');
        this.classList.add('bg-blue-600', 'text-white');
        this.classList.remove('bg-gray-200', 'text-gray-500');
        document.getElementById('gridViewBtn').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('gridViewBtn').classList.add('bg-gray-200', 'text-gray-500');
    });

    document.getElementById('gridViewBtn').addEventListener('click', function() {
        document.getElementById('tableView').classList.add('hidden');
        document.getElementById('gridView').classList.remove('hidden');
        this.classList.add('bg-blue-600', 'text-white');
        this.classList.remove('bg-gray-200', 'text-gray-500');
        document.getElementById('tableViewBtn').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('tableViewBtn').classList.add('bg-gray-200', 'text-gray-500');
    });

    // Wallet Modal
    let currentStationId = null;
    
    function showWalletModal(stationId, stationName, currentBalance) {
        currentStationId = stationId;
        document.getElementById('walletStationName').textContent = stationName;
        document.getElementById('currentWalletBalance').textContent = `ZAR ${currentBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        const form = document.getElementById('walletForm');
        form.action = `/admin/stations/${stationId}/wallet`;
        
        document.getElementById('walletModal').classList.remove('hidden');
    }
    
    function closeWalletModal() {
        document.getElementById('walletModal').classList.add('hidden');
        document.getElementById('walletForm').reset();
        currentStationId = null;
    }

    // Auto-submit sort/filter changes
    document.querySelectorAll('select[name="sort"], select[name="direction"]').forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });

    // Handle wallet form submission
    document.getElementById('walletForm').addEventListener('submit', function(e) {
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
                alert(data.message || 'Wallet updated successfully!');
                closeWalletModal();
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
</script>
@endsection
