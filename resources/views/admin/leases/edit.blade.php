@extends('Layouts.admin')

@section('title', 'Edit Lease: #' . $lease->id)
@section('page-title', 'Edit Lease')
@section('page-description', 'Update lease agreement details')
@section('breadcrumb')
    <a href="{{ route('admin.leases.index') }}" class="text-blue-600 hover:text-blue-800">Leases</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <a href="{{ route('admin.leases.show', $lease) }}" class="text-blue-600 hover:text-blue-800">Lease #{{ $lease->id }}</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>Edit</span>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-yellow-600 to-yellow-700 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">Edit Lease #{{ $lease->id }}</h2>
                        <p class="text-yellow-100 text-sm mt-1">Update lease agreement details</p>
                    </div>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-file-contract text-white text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-6">
                <form action="{{ route('admin.leases.update', $lease) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Lease Info -->
                    <div class="flex items-center space-x-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                            <i class="fas fa-file-contract text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Lease #{{ $lease->id }}</p>
                            <p class="text-sm text-gray-500">User: {{ $lease->user->name }} • Created {{ $lease->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Basic Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- User -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    User *
                                </label>
                                <select name="user_id" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('user_id') border-red-500 @enderror">
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" 
                                                {{ old('user_id', $lease->user_id) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->phone }}) - Credit: ZAR {{ number_format($user->available_credit) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Status *
                                </label>
                                <select name="status" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                                    <option value="">Select Status</option>
                                    <option value="active" {{ old('status', $lease->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="completed" {{ old('status', $lease->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="defaulted" {{ old('status', $lease->status) == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                                    <option value="cancelled" {{ old('status', $lease->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Fuel Station -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Fuel Station (Optional)
                            </label>
                            <select name="fuel_station_id" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">No Station</option>
                                @foreach($stations as $station)
                                    <option value="{{ $station->id }}" 
                                            {{ old('fuel_station_id', $lease->fuel_station_id) == $station->id ? 'selected' : '' }}>
                                        {{ $station->name }} - {{ $station->city }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Financial Details
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Principal Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Principal Amount (ZAR) *
                                </label>
                                <input type="number" 
                                       name="principal_amount" 
                                       required
                                       min="100"
                                       max="50000"
                                       step="0.01"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('principal_amount') border-red-500 @enderror"
                                       value="{{ old('principal_amount', $lease->principal_amount) }}">
                                @error('principal_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Late Fee Rate -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Late Fee Rate (%) *
                                </label>
                                <input type="number" 
                                       name="interest_rate" 
                                       required
                                       min="0"
                                       max="100"
                                       step="0.1"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('interest_rate') border-red-500 @enderror"
                                       value="{{ old('interest_rate', $lease->interest_rate) }}">
                                @error('interest_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Term Days -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Term (Days) *
                                </label>
                                <input type="number" 
                                       name="term_days" 
                                       required
                                       min="7"
                                       max="60"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('term_days') border-red-500 @enderror"
                                       value="{{ old('term_days', $lease->term_days) }}">
                                @error('term_days')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Fuel Details (Optional) -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-3">Fuel Details (Optional)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Fuel Type
                                    </label>
                                    <select name="fuel_type" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select Type</option>
                                        <option value="petrol" {{ old('fuel_type', $lease->fuel_type) == 'petrol' ? 'selected' : '' }}>Petrol</option>
                                        <option value="diesel" {{ old('fuel_type', $lease->fuel_type) == 'diesel' ? 'selected' : '' }}>Diesel</option>
                                        <option value="premium" {{ old('fuel_type', $lease->fuel_type) == 'premium' ? 'selected' : '' }}>Premium</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Liters
                                    </label>
                                    <input type="number" 
                                           name="liters"
                                           min="1"
                                           max="1000"
                                           step="0.001"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           value="{{ old('liters', $lease->liters) }}">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Price per Liter (ZAR)
                                    </label>
                                    <input type="number" 
                                           name="fuel_price_per_liter"
                                           min="1"
                                           max="100"
                                           step="0.01"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           value="{{ old('fuel_price_per_liter', $lease->fuel_price_per_liter) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calculation Preview -->
                    <div class="bg-blue-50 border border-blue-200 p-5 rounded-xl">
                        <h4 class="font-semibold text-blue-900 mb-3">Calculation Preview</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                            <div class="text-center">
                                <div class="text-gray-600">Principal</div>
                                <div id="previewPrincipal" class="font-bold text-lg text-gray-900">ZAR {{ number_format($lease->principal_amount, 2) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-600">Late Fees</div>
                                <div id="previewInterest" class="font-bold text-lg text-red-600">ZAR {{ number_format($lease->interest_amount, 2) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-600">Total</div>
                                <div id="previewTotal" class="font-bold text-lg text-green-600">ZAR {{ number_format($lease->total_amount, 2) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-600">Daily</div>
                                <div id="previewDaily" class="font-bold text-lg text-blue-600">ZAR {{ number_format($lease->daily_repayment, 2) }}</div>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            Changing financial details will recalculate the repayment schedule
                        </div>
                        <p id="editLeaseValidationMessage" class="mt-2 text-xs font-medium text-blue-700">
                            Minimum repayment: R30.00 per day. Term must be between 7 and 60 days.
                        </p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 pt-6 border-t border-gray-200">
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.leases.show', $lease) }}" 
                               class="flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-eye mr-2"></i> View
                            </a>
                            <a href="{{ route('admin.leases.index') }}" 
                               class="flex items-center text-gray-600 hover:text-gray-900">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Leases
                            </a>
                        </div>
                        <div class="flex space-x-3">
                            <button type="reset" 
                                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                                Reset Changes
                            </button>
                            <button type="submit" 
                                    id="updateLeaseSubmitBtn"
                                    class="px-6 py-2.5 bg-gradient-to-r from-yellow-600 to-yellow-700 text-white rounded-lg font-semibold hover:from-yellow-700 hover:to-yellow-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                                <i class="fas fa-save mr-2"></i> Update Lease
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-5 mt-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-red-900">Danger Zone</h4>
                    <p class="text-sm text-red-700">Irreversible actions</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Delete Form -->
                <form action="{{ route('admin.leases.destroy', $lease) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            onclick="return confirm('WARNING: This will permanently delete Lease #{{ $lease->id }} and all associated data. This action cannot be undone. Are you sure?')"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center">
                        <i class="fas fa-trash mr-2"></i> Delete Lease Permanently
                    </button>
                </form>

                <!-- Status Toggle -->
                @if($lease->status == 'active')
                <form action="{{ route('admin.leases.toggle-status', $lease) }}" method="POST" class="inline ml-3">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Mark Lease #{{ $lease->id }} as defaulted? This will also flag the user.')"
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Mark as Defaulted
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    // Calculate preview on input changes
    function updateCalculationPreview() {
        const principal = parseFloat(document.querySelector('input[name="principal_amount"]').value) || 0;
        const interestRate = parseFloat(document.querySelector('input[name="interest_rate"]').value) || 0;
        const termInput = document.querySelector('input[name="term_days"]');
        let termDays = parseInt(termInput.value) || 0;
        termDays = Math.max(7, Math.min(60, termDays));
        if (termInput.value !== '' && Number(termInput.value) !== termDays) {
            termInput.value = String(termDays);
        }
        
        // Calculate from fuel if provided
        const liters = parseFloat(document.querySelector('input[name="liters"]').value);
        const pricePerLiter = parseFloat(document.querySelector('input[name="fuel_price_per_liter"]').value);
        
        let calculatedPrincipal = principal;
        if (liters && pricePerLiter) {
            calculatedPrincipal = liters * pricePerLiter;
            // Update principal field
            document.querySelector('input[name="principal_amount"]').value = calculatedPrincipal.toFixed(2);
        }
        
        const interestAmount = calculatedPrincipal * (interestRate / 100);
        const totalAmount = calculatedPrincipal + interestAmount;
        const dailyRepayment = termDays > 0 ? totalAmount / termDays : 0;
        const isValid = termDays >= 7 && termDays <= 60 && dailyRepayment >= 30;

        document.getElementById('previewPrincipal').textContent = 'ZAR ' + calculatedPrincipal.toFixed(2);
        document.getElementById('previewInterest').textContent = 'ZAR ' + interestAmount.toFixed(2);
        document.getElementById('previewTotal').textContent = 'ZAR ' + totalAmount.toFixed(2);
        document.getElementById('previewDaily').textContent = 'ZAR ' + dailyRepayment.toFixed(2);

        const validationMessage = document.getElementById('editLeaseValidationMessage');
        const submitBtn = document.getElementById('updateLeaseSubmitBtn');
        if (validationMessage) {
            validationMessage.textContent = isValid
                ? 'Minimum repayment: R30.00 per day. Term must be between 7 and 60 days.'
                : 'Daily repayment must be at least R30.00 and term must be between 7 and 60 days.';
            validationMessage.className = isValid
                ? 'mt-2 text-xs font-medium text-emerald-700'
                : 'mt-2 text-xs font-medium text-red-600';
        }
        if (submitBtn) {
            submitBtn.disabled = !isValid;
            submitBtn.classList.toggle('opacity-60', !isValid);
            submitBtn.classList.toggle('cursor-not-allowed', !isValid);
        }
    }

    // Attach calculation preview to input events
    document.querySelectorAll('input[name="principal_amount"], input[name="interest_rate"], input[name="term_days"], input[name="liters"], input[name="fuel_price_per_liter"]').forEach(input => {
        input.addEventListener('input', updateCalculationPreview);
    });

    // Auto-format fuel price
    document.querySelector('input[name="fuel_price_per_liter"]')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9.]/g, '');
        if (value.includes('.')) {
            const parts = value.split('.');
            if (parts[1].length > 2) {
                parts[1] = parts[1].substring(0, 2);
                value = parts.join('.');
            }
        }
        e.target.value = value;
    });

    // Auto-calculate principal from fuel
    document.querySelectorAll('input[name="liters"], input[name="fuel_price_per_liter"]').forEach(input => {
        input.addEventListener('input', function() {
            const liters = parseFloat(document.querySelector('input[name="liters"]').value);
            const price = parseFloat(document.querySelector('input[name="fuel_price_per_liter"]').value);
            
            if (liters && price) {
                const principal = liters * price;
                document.querySelector('input[name="principal_amount"]').value = principal.toFixed(2);
                updateCalculationPreview();
            }
        });
    });

    // Initialize calculation on page load
    document.addEventListener('DOMContentLoaded', updateCalculationPreview);
</script>
@endsection
