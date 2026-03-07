{{-- [file name]: investor/dashboard.blade.php --}}
@extends('Layouts.investor')

@section('title', 'Investor Dashboard')
@section('page-title', 'Investment Dashboard')
@section('page-description', 'Manage your investments and track performance')

@php
    $investor = auth()->user()->investor;
    $portfolio = $investor->getInvestmentPortfolio();
    $recentInvestments = $investor->leaseInvestments()->latest()->take(5)->get();
    $activeInvestments = $investor->leaseInvestments()->where('status', 'active')->get();
    $opportunities = App\Models\Lease::where('status', 'active')
        ->where('principal_amount', '>=', $investor->minimum_investment_amount)
        ->whereDoesntHave('leaseInvestments', function($q) use ($investor) {
            $q->where('investor_id', $investor->id);
        })
        ->take(3)->get();
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Portfolio Value -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Portfolio Value</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($investor->invested_capital + $investor->interest_earned) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-chart-line text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium text-green-600 flex items-center">
            <i class="fas fa-arrow-up mr-1 text-xs"></i> 
            {{ $investor->interest_earned > 0 ? number_format(($investor->interest_earned / max($investor->invested_capital, 1)) * 100, 1) : '0' }}% return
        </div>
    </div>

    <!-- Available Capital -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Available Capital</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($investor->available_capital) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-wallet text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm font-medium">
            <span class="text-gray-600">{{ number_format(($investor->available_capital / max($investor->total_investment_capital, 1)) * 100, 1) }}% of total</span>
        </div>
    </div>

    <!-- Active Investments -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Active Investments</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $portfolio['active_investments'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-folder-open text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-xs bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-medium">
                {{ $portfolio['completed_investments'] }} completed
            </span>
        </div>
    </div>

    <!-- Investor Score -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Investor Score</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $investor->investor_score }}/100</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-star text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Risk: <span class="font-semibold capitalize">{{ $investor->risk_profile }}</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Header with Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Welcome back, {{ $investor->contact_person }}</h2>
            <p class="text-gray-600 mt-1">Manage your Bwiser investments and explore new opportunities</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('investor.opportunities') }}" 
               class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-semibold hover:from-green-700 hover:to-green-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center group">
                <i class="fas fa-search-dollar mr-2 group-hover:rotate-12 transition-transform"></i> Explore Opportunities
            </a>
            <button onclick="showAddCapitalModal()" 
                    class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-plus-circle mr-2"></i> Add Capital
            </button>
        </div>
    </div>

    <!-- Portfolio Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Portfolio Chart -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Portfolio Performance</h3>
                <select class="border border-gray-300 rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option>Last 30 days</option>
                    <option>Last 90 days</option>
                    <option>Last 12 months</option>
                </select>
            </div>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-xl">
                <!-- Chart would go here -->
                <div class="text-center">
                    <i class="fas fa-chart-bar text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">Performance chart would appear here</p>
                    <p class="text-sm text-gray-400 mt-1">Average Return: {{ number_format($portfolio['average_return'], 1) }}%</p>
                </div>
            </div>
        </div>

        <!-- Risk Profile -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Risk Profile</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Your Profile</span>
                        <span class="text-sm font-semibold capitalize text-{{ $investor->risk_profile == 'conservative' ? 'green' : ($investor->risk_profile == 'moderate' ? 'yellow' : 'red') }}-600">
                            {{ $investor->risk_profile }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        @php
                            $width = $investor->risk_profile == 'conservative' ? '33%' : ($investor->risk_profile == 'moderate' ? '66%' : '100%');
                            $color = $investor->risk_profile == 'conservative' ? 'green' : ($investor->risk_profile == 'moderate' ? 'yellow' : 'red');
                        @endphp
                        <div class="h-2 rounded-full bg-gradient-to-r from-{{ $color }}-400 to-{{ $color }}-600" 
                             style="width: {{ $width }}"></div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Investment Horizon:</span>
                        <span class="font-medium">{{ str_replace('_', ' ', $investor->investment_horizon) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Min Investment:</span>
                        <span class="font-medium">KES {{ number_format($investor->minimum_investment_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Max Investment:</span>
                        <span class="font-medium">KES {{ number_format($investor->maximum_investment_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Preferred Interest:</span>
                        <span class="font-medium">{{ $investor->preferred_interest_rate_min }}% - {{ $investor->preferred_interest_rate_max }}%</span>
                    </div>
                </div>
                
                <button onclick="showPreferencesModal()" 
                        class="w-full mt-4 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    <i class="fas fa-edit mr-2"></i> Update Preferences
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Investments & Opportunities -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Investments -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Investments</h3>
                    <a href="{{ route('investor.investments') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                @forelse($recentInvestments as $investment)
                <div class="p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Lease #{{ $investment->lease_id }}</p>
                            <p class="text-sm text-gray-500">KES {{ number_format($investment->amount_invested) }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 text-xs rounded-full 
                                {{ $investment->status == 'active' ? 'bg-green-100 text-green-800' : 
                                   ($investment->status == 'completed' ? 'bg-blue-100 text-blue-800' : 
                                   'bg-red-100 text-red-800') }}">
                                {{ ucfirst($investment->status) }}
                            </span>
                            <p class="text-sm text-gray-600 mt-1">{{ $investment->investment_date->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-sm text-gray-600">Interest Rate: {{ $investment->interest_rate }}%</span>
                        <span class="text-sm font-medium {{ $investment->interest_earned > 0 ? 'text-green-600' : 'text-gray-600' }}">
                            Earned: KES {{ number_format($investment->interest_earned) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center">
                    <i class="fas fa-folder-open text-3xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No investments yet</p>
                    <a href="{{ route('investor.opportunities') }}" class="inline-block mt-2 text-blue-600 hover:text-blue-800">
                        Start investing now →
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Investment Opportunities -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Investment Opportunities</h3>
                    <a href="{{ route('investor.opportunities') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                @forelse($opportunities as $lease)
                <div class="p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Lease #{{ $lease->id }}</p>
                            <p class="text-sm text-gray-500">{{ $lease->user->name }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                {{ $lease->credit_score ?? 'N/A' }} Score
                            </span>
                            <p class="text-sm text-gray-600 mt-1">Due: {{ $lease->due_date->format('M d') }}</p>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Amount:</span>
                            <p class="font-medium">KES {{ number_format($lease->principal_amount) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600">Interest:</span>
                            <p class="font-medium text-green-600">{{ $lease->interest_rate }}%</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-600">Progress</span>
                            <span class="font-medium">{{ number_format($lease->progress_percentage, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-600" 
                                 style="width: {{ $lease->progress_percentage }}%"></div>
                        </div>
                    </div>
                    <button onclick="showInvestModal({{ $lease->id }})" 
                            class="w-full mt-3 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 text-sm font-medium">
                        Invest Now
                    </button>
                </div>
                @empty
                <div class="p-8 text-center">
                    <i class="fas fa-search-dollar text-3xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No investment opportunities available</p>
                    <p class="text-sm text-gray-400 mt-1">Check back later for new opportunities</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Auto-Invest Settings -->
    @if($investor->auto_invest_enabled)
    <div class="mt-8 bg-gradient-to-r from-green-50 to-white p-6 rounded-2xl shadow-sm border border-green-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-robot text-green-600 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">Auto-Invest is Active</h4>
                    <p class="text-sm text-gray-600">Automatically investing in eligible opportunities</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="toggleAutoInvest()" 
                        class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium">
                    Disable Auto-Invest
                </button>
                <a href="#" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                    Settings
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Invest Modal -->
<div id="investModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="investForm" method="POST" action="{{ route('investor.invest') }}">
            @csrf
            <input type="hidden" id="investLeaseId" name="lease_id">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Invest in Lease</h3>
                    <button type="button" onclick="closeInvestModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm text-gray-600">Lease Details</p>
                        <p id="leaseDetails" class="font-medium text-gray-900 mt-1"></p>
                        <p id="leaseTerms" class="text-sm text-gray-600 mt-1"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Investment Amount (KES) *
                        </label>
                        <input type="number" 
                               name="amount" 
                               id="investmentAmount"
                               required
                               min="{{ $investor->minimum_investment_amount }}"
                               max="{{ $investor->maximum_investment_amount }}"
                               step="1000"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter amount to invest">
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Min: KES {{ number_format($investor->minimum_investment_amount) }}</span>
                            <span>Available: KES {{ number_format($investor->available_capital) }}</span>
                            <span>Max: KES {{ number_format($investor->maximum_investment_amount) }}</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Investment Strategy
                        </label>
                        <select name="strategy" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="full_amount">Full Amount</option>
                            <option value="partial">Partial Investment</option>
                            <option value="auto_reinvest">Auto-Reinvest Returns</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="auto_reinvest" 
                               id="autoReinvest"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="autoReinvest" class="ml-2 block text-sm text-gray-700">
                            Automatically reinvest returns from this investment
                        </label>
                    </div>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-xl mt-6">
                    <h4 class="font-medium text-blue-900 mb-2">Investment Preview</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-blue-700">Expected Interest Rate:</span>
                            <span id="previewRate" class="font-medium">0%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-blue-700">Estimated Annual Return:</span>
                            <span id="previewReturn" class="font-medium">KES 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-blue-700">New Portfolio Balance:</span>
                            <span id="previewBalance" class="font-medium">KES {{ number_format($investor->invested_capital) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeInvestModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800">
                        Confirm Investment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Capital Modal -->
<div id="addCapitalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="addCapitalForm" method="POST" action="#">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Add Investment Capital</h3>
                    <button type="button" onclick="closeAddCapitalModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm text-gray-600">Current Total Capital</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">KES {{ number_format($investor->total_investment_capital) }}</p>
                        <p class="text-sm text-gray-600 mt-1">Available: KES {{ number_format($investor->available_capital) }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Amount to Add (KES) *
                        </label>
                        <input type="number" 
                               name="amount" 
                               required
                               min="1000"
                               step="1000"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter amount">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payment Method *
                        </label>
                        <select name="payment_method" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select method</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="equitel">Equitel</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reference/Transaction ID
                        </label>
                        <input type="text" 
                               name="reference"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Optional reference number">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeAddCapitalModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800">
                        Add Capital
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let currentLeaseForInvestment = null;
    
    function showInvestModal(leaseId) {
        currentLeaseForInvestment = leaseId;
        
        // Fetch lease details
        fetch(`/api/leases/${leaseId}`)
            .then(response => response.json())
            .then(lease => {
                document.getElementById('investLeaseId').value = leaseId;
                document.getElementById('leaseDetails').textContent = 
                    `Lease #${lease.id} - KES ${lease.principal_amount.toLocaleString()}`;
                document.getElementById('leaseTerms').textContent = 
                    `${lease.interest_rate}% interest • ${lease.term_days} days`;
                document.getElementById('previewRate').textContent = `${lease.interest_rate}%`;
                
                // Set max investment amount
                const amountInput = document.getElementById('investmentAmount');
                amountInput.max = Math.min(lease.principal_amount * 0.8, {{ $investor->available_capital }});
                
                // Update preview on amount change
                amountInput.addEventListener('input', function() {
                    updateInvestmentPreview(lease);
                });
                
                updateInvestmentPreview(lease);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load lease details');
            });
        
        document.getElementById('investModal').classList.remove('hidden');
    }
    
    function closeInvestModal() {
        document.getElementById('investModal').classList.add('hidden');
        document.getElementById('investForm').reset();
        currentLeaseForInvestment = null;
    }
    
    function updateInvestmentPreview(lease) {
        const amount = parseFloat(document.getElementById('investmentAmount').value) || 0;
        const interestRate = lease.interest_rate;
        
        // Calculate expected return (simple interest for one term)
        const expectedReturn = amount * (interestRate / 100) * (lease.term_days / 365);
        const newBalance = {{ $investor->invested_capital }} + amount;
        
        document.getElementById('previewReturn').textContent = 
            `KES ${expectedReturn.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('previewBalance').textContent = 
            `KES ${newBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
    
    function showAddCapitalModal() {
        document.getElementById('addCapitalModal').classList.remove('hidden');
    }
    
    function closeAddCapitalModal() {
        document.getElementById('addCapitalModal').classList.add('hidden');
        document.getElementById('addCapitalForm').reset();
    }
    
    function showPreferencesModal() {
        // This would open a modal to update investment preferences
        alert('Preferences modal would open here');
    }
    
    function toggleAutoInvest() {
        if (confirm('Are you sure you want to disable auto-invest?')) {
            fetch('{{ route("investor.preferences.update") }}', {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    auto_invest_enabled: false
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Auto-invest disabled successfully');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update preferences');
            });
        }
    }
    
    // Handle form submissions
    document.getElementById('investForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        submitButton.disabled = true;
        
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Investment successful!');
                closeInvestModal();
                location.reload();
            } else {
                alert(data.message || 'Investment failed');
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