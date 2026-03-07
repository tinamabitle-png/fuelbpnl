{{-- [file name]: investor/investments.blade.php --}}
@extends('Layouts.investor')

@section('title', 'My Investments')
@section('page-title', 'Investment Portfolio')
@section('page-description', 'Track and manage all your investments')

@php
    $investor = auth()->user()->investor;
    $investments = $investor->leaseInvestments()->with('lease')->latest()->paginate(10);
    
    // Calculate portfolio metrics
    $portfolio = $investor->getInvestmentPortfolio();
    $activeInvestments = $investor->leaseInvestments()->where('status', 'active')->count();
    $completedInvestments = $investor->leaseInvestments()->where('status', 'completed')->count();
    $defaultedInvestments = $investor->leaseInvestments()->where('status', 'defaulted')->count();
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Invested -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Total Invested</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($investor->invested_capital) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium text-green-600 flex items-center">
            <i class="fas fa-arrow-up mr-1 text-xs"></i> {{ $portfolio['total_earned'] > 0 ? number_format(($portfolio['total_earned'] / max($investor->invested_capital, 1)) * 100, 1) : '0' }}% return
        </div>
    </div>

    <!-- Interest Earned -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Interest Earned</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($investor->interest_earned) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-chart-line text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium">
            <span class="text-gray-600">Avg. Return: {{ number_format($portfolio['average_return'], 1) }}%</span>
        </div>
    </div>

    <!-- Active Investments -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Active Investments</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $activeInvestments }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-folder-open text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-xs bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-medium">
                {{ $completedInvestments }} completed
            </span>
        </div>
    </div>

    <!-- Default Rate -->
    <div class="bg-gradient-to-br from-red-50 to-white p-5 rounded-2xl shadow-sm border border-red-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-600 text-sm font-semibold">Default Rate</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $defaultedInvestments > 0 ? number_format(($defaultedInvestments / ($activeInvestments + $completedInvestments + $defaultedInvestments)) * 100, 1) : '0' }}%</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-xl">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            <span class="font-semibold">{{ $defaultedInvestments }}</span> defaulted
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Investment Portfolio</h2>
            <p class="text-gray-600 mt-1">Track performance and manage your investments</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('investor.opportunities') }}" 
               class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-semibold hover:from-green-700 hover:to-green-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-search-dollar mr-2 group-hover:rotate-12 transition-transform"></i> Explore New Opportunities
            </a>
            <button onclick="exportInvestments()" 
                    class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-file-export mr-2"></i> Export Portfolio
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('investor.investments') }}" 
                   class="px-4 py-2 {{ !request('status') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-blue-200">
                    All ({{ $investments->total() }})
                </a>
                <a href="{{ route('investor.investments', ['status' => 'active']) }}" 
                   class="px-4 py-2 {{ request('status') == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-green-200">
                    Active ({{ $activeInvestments }})
                </a>
                <a href="{{ route('investor.investments', ['status' => 'completed']) }}" 
                   class="px-4 py-2 {{ request('status') == 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-blue-200">
                    Completed ({{ $completedInvestments }})
                </a>
                <a href="{{ route('investor.investments', ['status' => 'defaulted']) }}" 
                   class="px-4 py-2 {{ request('status') == 'defaulted' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }} rounded-lg text-sm font-medium hover:bg-red-200">
                    Defaulted ({{ $defaultedInvestments }})
                </a>
            </div>
            
            <div class="relative">
                <input type="text" 
                       placeholder="Search investments..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
            </div>
        </div>
    </div>

    <!-- Investments Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Investment Details
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Lease & Borrower
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Amount & Terms
                        </th>
                        <th class="px6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Performance
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Timeline
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($investments as $investment)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg 
                                        {{ $investment->status == 'active' ? 'bg-green-100' : 
                                           ($investment->status == 'completed' ? 'bg-blue-100' : 
                                           'bg-red-100') }} flex items-center justify-center">
                                        <i class="fas 
                                            {{ $investment->status == 'active' ? 'fa-chart-line text-green-600' : 
                                               ($investment->status == 'completed' ? 'fa-check-circle text-blue-600' : 
                                               'fa-exclamation-triangle text-red-600') }}"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        Investment #{{ $investment->id }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $investment->percentage_ownership }}% ownership
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Lease & Borrower -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('investor.investments.show', $investment) }}" class="hover:text-blue-600">
                                        Lease #{{ $investment->lease_id }}
                                    </a>
                                </div>
                                <div class="text-xs text-gray-500">
                                    @if($investment->lease && $investment->lease->user)
                                        {{ $investment->lease->user->name }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Amount & Terms -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="text-sm">
                                    <span class="text-gray-600">Invested:</span>
                                    <span class="font-bold text-gray-900 ml-1">KES {{ number_format($investment->amount_invested, 2) }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">Interest:</span>
                                    <span class="font-bold text-green-600 ml-1">{{ $investment->interest_rate }}%</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $investment->payment_schedule }} payments
                                </div>
                            </div>
                        </td>
                        
                        <!-- Performance -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-2">
                                <!-- Status Badge -->
                                @php
                                    $statusColors = [
                                        'active' => ['bg-green-100', 'text-green-800', 'Active'],
                                        'completed' => ['bg-blue-100', 'text-blue-800', 'Completed'],
                                        'defaulted' => ['bg-red-100', 'text-red-800', 'Defaulted'],
                                        'cancelled' => ['bg-gray-100', 'text-gray-800', 'Cancelled'],
                                    ];
                                    $status = $statusColors[$investment->status] ?? $statusColors['active'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                                    {{ $status[2] }}
                                </span>
                                
                                <!-- Earnings -->
                                <div class="text-sm">
                                    <span class="text-gray-600">Earned:</span>
                                    <span class="font-medium text-green-600 ml-1">KES {{ number_format($investment->interest_earned, 2) }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Expected: KES {{ number_format($investment->expected_interest, 2) }}
                                </div>
                            </div>
                        </td>
                        
                        <!-- Timeline -->
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="text-sm">
                                    <span class="text-gray-600">Invested:</span>
                                    <span class="font-medium">{{ $investment->investment_date->format('M d, Y') }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">Matures:</span>
                                    <span class="font-medium {{ $investment->maturity_date < now() && $investment->status == 'active' ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $investment->maturity_date->format('M d, Y') }}
                                    </span>
                                </div>
                                @if($investment->actual_maturity_date)
                                    <div class="text-sm">
                                        <span class="text-gray-600">Completed:</span>
                                        <span class="font-medium">{{ $investment->actual_maturity_date->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-5 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('investor.investments.show', $investment) }}" 
                                   class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if($investment->status == 'active')
                                    <button onclick="showReinvestModal({{ $investment->id }})" 
                                            class="text-green-600 hover:text-green-900 p-2 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Reinvest">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                @endif
                                
                                @if($investment->returns()->count() > 0)
                                    <button onclick="showReturnsModal({{ $investment->id }})" 
                                            class="text-purple-600 hover:text-purple-900 p-2 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="View Returns">
                                        <i class="fas fa-history"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-folder-open text-4xl mb-4 opacity-20"></i>
                                <p class="text-lg font-medium text-gray-700">No investments found</p>
                                <p class="text-gray-500 mt-1">Start building your investment portfolio</p>
                                <a href="{{ route('investor.opportunities') }}" 
                                   class="inline-block mt-4 px-5 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800">
                                    Explore Opportunities
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($investments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-semibold">{{ $investments->firstItem() }}</span> 
                    to <span class="font-semibold">{{ $investments->lastItem() }}</span> 
                    of <span class="font-semibold">{{ $investments->total() }}</span> investments
                </div>
                <div class="flex space-x-2">
                    {{ $investments->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Portfolio Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
        <!-- Performance Chart -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Portfolio Performance</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-xl">
                <!-- Chart would go here -->
                <div class="text-center">
                    <i class="fas fa-chart-bar text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">Performance chart would appear here</p>
                    <p class="text-sm text-gray-400 mt-1">Track your investment growth over time</p>
                </div>
            </div>
        </div>

        <!-- Portfolio Allocation -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Portfolio Allocation</h3>
            <div class="space-y-4">
                @php
                    $statuses = ['active', 'completed', 'defaulted'];
                    $colors = ['bg-green-500', 'bg-blue-500', 'bg-red-500'];
                @endphp
                
                @foreach($statuses as $index => $status)
                    @php
                        $count = $investor->leaseInvestments()->where('status', $status)->count();
                        $total = $investments->total();
                        $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium capitalize">{{ $status }} Investments</span>
                            <span>{{ $count }} ({{ number_format($percentage, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $colors[$index] }}" 
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
                
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total Investments:</span>
                        <span class="font-bold">{{ $investments->total() }}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-2">
                        <span class="text-gray-600">Average Return:</span>
                        <span class="font-bold text-green-600">{{ number_format($portfolio['average_return'], 1) }}%</span>
                    </div>
                    <div class="flex justify-between text-sm mt-2">
                        <span class="text-gray-600">Portfolio Value:</span>
                        <span class="font-bold">KES {{ number_format($investor->invested_capital + $investor->interest_earned) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reinvest Modal -->
<div id="reinvestModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="reinvestForm" method="POST" action="#">
            @csrf
            <input type="hidden" id="reinvestInvestmentId" name="investment_id">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Reinvest Earnings</h3>
                    <button type="button" onclick="closeReinvestModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm text-gray-600">Available Earnings</p>
                        <p id="availableEarnings" class="text-2xl font-bold text-gray-900 mt-1">KES 0</p>
                        <p class="text-sm text-gray-600 mt-1">From Investment #<span id="reinvestInvestmentNumber"></span></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Amount to Reinvest (KES) *
                        </label>
                        <input type="number" 
                               name="amount" 
                               id="reinvestAmount"
                               required
                               min="100"
                               step="100"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter amount">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reinvestment Strategy
                        </label>
                        <select name="strategy" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="same_lease">Same Lease (if active)</option>
                            <option value="new_opportunity">New Opportunity</option>
                            <option value="portfolio">Add to Available Capital</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="recurring" 
                               id="recurringReinvest"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="recurringReinvest" class="ml-2 block text-sm text-gray-700">
                            Set up recurring reinvestment for future earnings
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeReinvestModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800">
                        Confirm Reinvestment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Returns Modal -->
<div id="returnsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Investment Returns</h3>
                <button type="button" onclick="closeReturnsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="returnsContent">
                <!-- Returns will be loaded here -->
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-3xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">Loading returns...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Export investments
    function exportInvestments() {
        const url = "{{ route('investor.investments.export') }}";
        window.open(url, '_blank');
    }
    
    // Reinvest Modal
    function showReinvestModal(investmentId) {
        // Fetch investment details
        fetch(`/api/investments/${investmentId}`)
            .then(response => response.json())
            .then(investment => {
                document.getElementById('reinvestInvestmentId').value = investmentId;
                document.getElementById('reinvestInvestmentNumber').textContent = investmentId;
                
                const availableEarnings = investment.interest_earned || 0;
                document.getElementById('availableEarnings').textContent = 
                    `KES ${availableEarnings.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                
                const amountInput = document.getElementById('reinvestAmount');
                amountInput.max = availableEarnings;
                amountInput.placeholder = `Max: KES ${availableEarnings.toLocaleString()}`;
                
                document.getElementById('reinvestModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load investment details');
            });
    }
    
    function closeReinvestModal() {
        document.getElementById('reinvestModal').classList.add('hidden');
        document.getElementById('reinvestForm').reset();
    }
    
    // Returns Modal
    function showReturnsModal(investmentId) {
        // Fetch investment returns
        fetch(`/api/investments/${investmentId}/returns`)
            .then(response => response.json())
            .then(returns => {
                let html = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                `;
                
                if (returns.length > 0) {
                    returns.forEach(returnItem => {
                        html += `
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">${new Date(returnItem.payment_date).toLocaleDateString()}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">${returnItem.type}</td>
                                <td class="px-4 py-3 text-sm text-green-600 font-medium">KES ${returnItem.amount.toLocaleString()}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${returnItem.reference}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full ${returnItem.status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                        ${returnItem.status}
                                    </span>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html += `
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-history text-3xl mb-3 opacity-20"></i>
                                <p>No returns recorded yet</p>
                            </td>
                        </tr>
                    `;
                }
                
                html += `
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Total Returns</p>
                                <p class="text-xl font-bold text-gray-900">KES ${returns.reduce((sum, r) => sum + r.amount, 0).toLocaleString()}</p>
                            </div>
                            <button onclick="exportReturns(${investmentId})" 
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                                <i class="fas fa-download mr-2"></i> Export
                            </button>
                        </div>
                    </div>
                `;
                
                document.getElementById('returnsContent').innerHTML = html;
                document.getElementById('returnsModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('returnsContent').innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-3xl text-red-300 mb-3"></i>
                        <p class="text-red-600">Failed to load returns</p>
                        <button onclick="showReturnsModal(${investmentId})" class="mt-2 text-blue-600 hover:text-blue-800">
                            Try again
                        </button>
                    </div>
                `;
            });
    }
    
    function closeReturnsModal() {
        document.getElementById('returnsModal').classList.add('hidden');
    }
    
    function exportReturns(investmentId) {
        const url = `/api/investments/${investmentId}/returns/export`;
        window.open(url, '_blank');
    }
    
    // Handle form submissions
    document.getElementById('reinvestForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        submitButton.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            alert('Reinvestment successful!');
            closeReinvestModal();
            location.reload();
        }, 1500);
    });
</script>
@endsection