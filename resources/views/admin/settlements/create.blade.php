@extends('Layouts.admin')

@section('title', 'Create New Direct Bank Deposit')
@section('page-title', 'New Direct Bank Deposit')
@section('page-description', 'Create a new direct bank deposit for a fuel station')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settlements.index') }}">Direct Bank Deposits</a></li>
<li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center mb-4">
                <a href="{{ route('admin.settlements.index') }}" 
                   class="text-blue-600 hover:text-blue-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="text-2xl font-bold text-gray-900">Create New Direct Bank Deposit</h2>
            </div>
            <p class="text-gray-600">Select a fuel station and vouchers to create a direct bank deposit</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-700">Please fix the following:</p>
                <ul class="mt-2 text-sm text-red-700 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Station Selection Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sticky top-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Station Selection</h3>
                    
                    <!-- Station Search -->
                    <div class="relative mb-6">
                        <input type="text" 
                               id="stationSearch"
                               placeholder="Search stations..."
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    </div>
                    
                    <!-- Station List -->
                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                        @forelse($fuelStations as $station)
                            @php
                                $pendingAmount = $station->getPendingSettlementAmount();
                            @endphp
                            <a href="{{ route('admin.settlements.create', ['station_id' => $station->id]) }}"
                               class="block p-4 border border-gray-200 rounded-xl hover:border-blue-500 hover:shadow-sm transition-all duration-200 {{ $selectedStation && $selectedStation->id == $station->id ? 'border-blue-500 bg-blue-50' : '' }}">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                        <i class="fas fa-gas-pump text-blue-600"></i>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h4 class="text-sm font-semibold text-gray-900">{{ $station->name }}</h4>
                                        <p class="text-xs text-gray-500">{{ $station->company }}</p>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs text-gray-500">
                                                <i class="fas fa-map-marker-alt mr-1"></i> {{ $station->city }}
                                            </span>
                                            @if($pendingAmount > 0)
                                                <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2 py-1 rounded-full">
                                                    ZAR {{ number_format($pendingAmount, 2) }} pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-gas-pump text-3xl mb-3 opacity-20"></i>
                                <p>No stations available</p>
                            </div>
                        @endforelse
                    </div>
                    
                    <!-- Station Info -->
                    @if($selectedStation)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Selected Station</h4>
                        <div class="bg-gradient-to-r from-blue-50 to-white p-4 rounded-xl">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                        <i class="fas fa-gas-pump text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h5 class="text-sm font-bold text-gray-900">{{ $selectedStation->name }}</h5>
                                    <p class="text-xs text-gray-600">{{ $selectedStation->company }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <div>
                                    <p class="text-xs text-gray-500">Contact</p>
                                    <p class="text-sm font-medium">{{ $selectedStation->contact_phone }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm font-medium">{{ $selectedStation->contact_email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Main Form -->
            <div class="lg:col-span-2">
                @if($selectedStation)
                <form action="{{ route('admin.settlements.store') }}" method="POST" id="settlementForm">
                    @csrf
                    <input type="hidden" name="fuel_station_id" value="{{ $selectedStation->id }}">
                    
                    <!-- Vouchers Selection -->
                    <div id="voucherSelectionCard" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Select Vouchers</h3>
                            <div class="text-sm text-gray-600">
                                {{ $pendingVouchers->count() }} vouchers available
                            </div>
                        </div>
                        
                        @if($pendingVouchers->count() > 0)
                        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                            @foreach($pendingVouchers as $voucher)
                            <div class="flex items-center p-4 border border-gray-200 rounded-xl hover:border-blue-300 transition-colors">
                                <input type="checkbox" 
                                       name="voucher_ids[]" 
                                       value="{{ $voucher->id }}" 
                                       id="voucher_{{ $voucher->id }}"
                                       class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300 voucher-checkbox"
                                       data-amount="{{ $voucher->amount }}">
                                <label for="voucher_{{ $voucher->id }}" class="ml-4 flex-1 cursor-pointer">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Voucher #{{ $voucher->reference }}</h4>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-user mr-1"></i> {{ $voucher->user->name ?? 'Unknown' }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-calendar mr-1"></i> {{ $voucher->created_at->format('M d, Y H:i') }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-gray-900">ZAR {{ number_format($voucher->amount, 2) }}</div>
                                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                                Redeemed
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- Select All -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <label class="inline-flex items-center">
                                <input type="checkbox" 
                                       id="selectAll" 
                                       class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm font-medium text-gray-900">Select all vouchers</span>
                                <span id="selectedCount" class="ml-auto text-sm text-blue-600 font-semibold">
                                    0 selected
                                </span>
                            </label>
                        </div>
                        @else
                        <div class="text-center py-8">
                            <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-600">No pending vouchers for this station</p>
                            <p class="text-sm text-gray-500 mt-1">All vouchers have been settled</p>
                        </div>
                        @endif
                    </div>

                    <!-- Direct Bank Deposit Details -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Direct Bank Deposit Details</h3>

                        <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">Top-up Source</p>
                            <p class="text-xs text-slate-600 mt-1">Choose whether this top-up is based on selected vouchers or a manual amount.</p>
                            @php
                                $defaultTopupSource = old('topup_source', $pendingVouchers->count() > 0 ? 'vouchers' : 'manual');
                            @endphp
                            <div class="mt-3 flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="topup_source" value="vouchers" class="topup-source rounded border-slate-300" {{ $defaultTopupSource === 'vouchers' ? 'checked' : '' }}>
                                    From selected vouchers
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="topup_source" value="manual" class="topup-source rounded border-slate-300" {{ $defaultTopupSource === 'manual' ? 'checked' : '' }}>
                                    Manual top-up amount
                                </label>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Direct Bank Deposit Amount *
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500">ZAR</span>
                                    </div>
                                    <input type="number" 
                                           name="amount" 
                                           id="amount"
                                           step="0.01"
                                           min="0.01"
                                           required
                                           class="w-full pl-16 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 font-bold text-xl"
                                           placeholder="0.00">
                                </div>
                                <p class="text-xs text-gray-500 mt-2" id="voucherCount">
                                    Amount will be calculated based on selected vouchers
                                </p>
                            </div>

                            <!-- Direct Bank Deposit Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Direct Bank Deposit Date *
                                </label>
                                <input type="date" 
                                       name="settlement_date" 
                                       value="{{ old('settlement_date', date('Y-m-d')) }}"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Method *
                                </label>
                                <input type="hidden" name="payment_method" value="paystack_transfer">
                                <div class="w-full px-4 py-3 border border-emerald-200 bg-emerald-50 rounded-xl">
                                    <p class="text-sm font-semibold text-emerald-700">Paystack Direct Transfer</p>
                                    <p class="text-xs text-emerald-700/80 mt-1">Settlements use Paystack only.</p>
                                </div>
                            </div>

                            <!-- Transaction Reference -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Transaction Reference
                                </label>
                                <input type="text" 
                                       name="transaction_reference" 
                                       value="{{ old('transaction_reference') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Optional reference number">
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes / Remarks
                                </label>
                                <textarea name="notes" 
                                          rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="Additional notes about this direct bank deposit...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-xl">
                                    <p class="text-sm text-gray-600">Selected Vouchers</p>
                                    <p id="selectedVouchersCount" class="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-xl">
                                    <p class="text-sm text-blue-600">Total Amount</p>
                                    <p id="totalAmountDisplay" class="text-2xl font-bold text-blue-700">ZAR 0.00</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-8 flex justify-between">
                            <a href="{{ route('admin.settlements.index') }}" 
                               class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" 
                                    id="submitBtn"
                                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                <i class="fas fa-check-circle mr-2"></i> Create Direct Bank Deposit
                            </button>
                        </div>
                    </div>
                </form>
                @else
                <!-- No Station Selected -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                    <i class="fas fa-gas-pump text-5xl text-gray-300 mb-6"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-3">Select a Fuel Station</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        Choose a fuel station from the list to view pending vouchers and create a direct bank deposit
                    </p>
                    <div class="inline-block bg-blue-50 text-blue-700 px-6 py-3 rounded-xl">
                        <i class="fas fa-hand-point-left mr-2"></i> Select station from the left panel
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Tips -->
        @if($selectedStation)
        <div class="mt-8 bg-gradient-to-r from-yellow-50 to-white border border-yellow-200 rounded-2xl p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                        <i class="fas fa-lightbulb text-yellow-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-semibold text-yellow-800">Quick Tips</h4>
                    <ul class="mt-2 space-y-2 text-sm text-yellow-700">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-yellow-600 mt-0.5 mr-2 text-xs"></i>
                            <span>Review vouchers carefully before creating a direct bank deposit</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-yellow-600 mt-0.5 mr-2 text-xs"></i>
                            <span>Voucher mode auto-calculates amount from selected vouchers; Manual mode allows custom amount.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-yellow-600 mt-0.5 mr-2 text-xs"></i>
                            <span>Once created, the direct bank deposit will be in "Pending" status</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-8 text-center">
        <style>
            .themed-loader {
                width: 80px;
                height: 50px;
                position: relative;
                margin: 0 auto 1rem;
            }

            .themed-loader-text {
                position: absolute;
                top: 0;
                padding: 0;
                margin: 0;
                color: #C8B6FF;
                animation: themed_text_713 3.5s ease both infinite;
                font-size: .8rem;
                letter-spacing: 1px;
                text-transform: lowercase;
            }

            .themed-load {
                background-color: #9A79FF;
                border-radius: 50px;
                display: block;
                height: 16px;
                width: 16px;
                bottom: 0;
                position: absolute;
                transform: translateX(64px);
                animation: themed_loading_713 3.5s ease both infinite;
            }

            .themed-load::before {
                position: absolute;
                content: "";
                width: 100%;
                height: 100%;
                background-color: #D1C2FF;
                border-radius: inherit;
                animation: themed_loading2_713 3.5s ease both infinite;
            }

            @keyframes themed_text_713 {
                0% { letter-spacing: 1px; transform: translateX(0px); }
                40% { letter-spacing: 2px; transform: translateX(26px); }
                80% { letter-spacing: 1px; transform: translateX(32px); }
                90% { letter-spacing: 2px; transform: translateX(0px); }
                100% { letter-spacing: 1px; transform: translateX(0px); }
            }

            @keyframes themed_loading_713 {
                0% { width: 16px; transform: translateX(0px); }
                40% { width: 100%; transform: translateX(0px); }
                80% { width: 16px; transform: translateX(64px); }
                90% { width: 100%; transform: translateX(0px); }
                100% { width: 16px; transform: translateX(0px); }
            }

            @keyframes themed_loading2_713 {
                0% { transform: translateX(0px); width: 16px; }
                40% { transform: translateX(0%); width: 80%; }
                80% { width: 100%; transform: translateX(0px); }
                90% { width: 80%; transform: translateX(15px); }
                100% { transform: translateX(0px); width: 16px; }
            }
        </style>
        <div class="themed-loader" aria-hidden="true">
            <span class="themed-loader-text">loading</span>
            <span class="themed-load"></span>
        </div>
        <p class="text-lg font-semibold text-gray-900">Creating Direct Bank Deposit</p>
        <p class="text-gray-600 mt-2">Please wait while we process your request...</p>
    </div>
</div>

<script>
    // Handle voucher selection
    let totalAmount = 0;
    let selectedCount = 0;
    const voucherCheckboxes = document.querySelectorAll('.voucher-checkbox');
    const topupSourceRadios = document.querySelectorAll('.topup-source');
    const voucherSelectionCard = document.getElementById('voucherSelectionCard');
    const amountInput = document.getElementById('amount');
    const selectedCountSpan = document.getElementById('selectedCount');
    const selectedVouchersCount = document.getElementById('selectedVouchersCount');
    const totalAmountDisplay = document.getElementById('totalAmountDisplay');
    const submitBtn = document.getElementById('submitBtn');
    const voucherCountText = document.getElementById('voucherCount');
    
    // Initialize Select All
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            voucherCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                checkbox.dispatchEvent(new Event('change'));
            });
        });
    }
    
    // Handle individual checkbox changes
    voucherCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const amount = parseFloat(this.dataset.amount) || 0;
            
            if (this.checked) {
                totalAmount += amount;
                selectedCount++;
            } else {
                totalAmount -= amount;
                selectedCount--;
            }
            
            updateUI();
        });
    });
    
    function updateUI() {
        const topupSource = getTopupSource();

        // Update selected count
        selectedCountSpan.textContent = `${selectedCount} selected`;
        selectedVouchersCount.textContent = selectedCount;

        if (topupSource === 'vouchers') {
            amountInput.readOnly = true;
            amountInput.classList.add('bg-gray-50');
            amountInput.classList.remove('bg-white');
            amountInput.value = totalAmount.toFixed(2);
            totalAmountDisplay.textContent = `ZAR ${totalAmount.toFixed(2)}`;
            voucherCountText.textContent = `${selectedCount} voucher${selectedCount !== 1 ? 's' : ''} selected`;
            if (voucherSelectionCard) voucherSelectionCard.classList.remove('hidden');
            submitBtn.disabled = selectedCount === 0 || totalAmount <= 0;
        } else {
            amountInput.readOnly = false;
            amountInput.classList.remove('bg-gray-50');
            amountInput.classList.add('bg-white');
            if (voucherSelectionCard) voucherSelectionCard.classList.add('hidden');
            const manualAmount = parseFloat(amountInput.value) || 0;
            totalAmountDisplay.textContent = `ZAR ${manualAmount.toFixed(2)}`;
            voucherCountText.textContent = 'Manual top-up mode';
            submitBtn.disabled = manualAmount <= 0;
        }
        
        // Update select all checkbox
        if (selectAllCheckbox) {
            const allChecked = selectedCount === voucherCheckboxes.length;
            const someChecked = selectedCount > 0 && selectedCount < voucherCheckboxes.length;
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        }
    }

    function getTopupSource() {
        const selected = document.querySelector('.topup-source:checked');
        return selected ? selected.value : 'vouchers';
    }

    topupSourceRadios.forEach(radio => {
        radio.addEventListener('change', updateUI);
    });

    amountInput?.addEventListener('input', () => {
        if (getTopupSource() === 'manual') {
            updateUI();
        }
    });
    
    // Handle form submission
    document.getElementById('settlementForm')?.addEventListener('submit', function(e) {
        const topupSource = getTopupSource();
        const manualAmount = parseFloat(amountInput.value) || 0;

        if (topupSource === 'vouchers' && selectedCount === 0) {
            e.preventDefault();
            alert('Please select at least one voucher or switch to manual top-up mode.');
            return;
        }

        if (manualAmount <= 0) {
            e.preventDefault();
            alert('Direct bank deposit amount must be greater than zero.');
            return;
        }
        
        // Show loading modal
        document.getElementById('loadingModal').classList.remove('hidden');
    });
    
    // Station search
    document.getElementById('stationSearch')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const stationItems = document.querySelectorAll('[href*="station_id="]');
        
        stationItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Update UI on page load
    voucherCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const amount = parseFloat(checkbox.dataset.amount) || 0;
            totalAmount += amount;
            selectedCount++;
        }
    });

    if (amountInput && !amountInput.value) {
        amountInput.value = "{{ old('amount', '0.00') }}";
    }
    updateUI();
</script>
@endsection
