@extends('Layouts.admin')

@section('title', 'Create Voucher')
@section('page-title', 'Create Voucher')
@section('page-description', 'Issue a new fuel voucher for a user')
@section('breadcrumb', 'Vouchers / Create')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.vouchers.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i> Back to Vouchers
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
            <h3 class="text-lg font-semibold text-gray-900">Voucher Details</h3>
            <p class="text-sm text-gray-600 mt-1">Select a user and station, then set the voucher details.</p>
        </div>

        <form id="voucherForm" action="{{ route('admin.vouchers.store') }}" method="POST" class="p-6 space-y-8">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <input type="hidden" name="user_id" id="user_id" value="{{ old('user_id') }}" />
                    <input
                        type="text"
                        id="user_search"
                        list="users-list"
                        placeholder="Start typing a name, email, or phone..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        autocomplete="off"
                        value="{{ old('user_search') }}"
                    />
                    <datalist id="users-list">
                        @foreach($users as $user)
                            <option
                                value="{{ $user->name }} — {{ $user->email }} ({{ $user->phone ?? 'no phone' }})"
                                data-id="{{ $user->id }}"
                            ></option>
                        @endforeach
                    </datalist>
                    @error('user_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Pick an option from the list to set the user.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fuel Station</label>
                    <input type="hidden" name="fuel_station_id" id="fuel_station_id" value="{{ old('fuel_station_id') }}" />
                    <input
                        type="text"
                        id="station_search"
                        list="stations-list"
                        placeholder="Start typing a station name..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        autocomplete="off"
                        value="{{ old('station_search') }}"
                    />
                    <datalist id="stations-list">
                        @foreach($stations as $station)
                            <option value="{{ $station->name }}" data-id="{{ $station->id }}"></option>
                        @endforeach
                    </datalist>
                    @error('fuel_station_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Pick an option from the list to set the station.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount (ZAR)</label>
                    <input
                        type="number"
                        name="amount"
                        min="10"
                        step="0.01"
                        value="{{ old('amount', 500) }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                    />
                    @error('amount')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fuel Type</label>
                    <select
                        name="fuel_type"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                    >
                        <option value="petrol" @selected(old('fuel_type') === 'petrol')>Petrol</option>
                        <option value="diesel" @selected(old('fuel_type') === 'diesel')>Diesel</option>
                        <option value="super" @selected(old('fuel_type') === 'super')>Super</option>
                    </select>
                    @error('fuel_type')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Liters (optional)</label>
                    <input
                        type="number"
                        name="liters"
                        min="0.1"
                        step="0.01"
                        value="{{ old('liters') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('liters')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expires At</label>
                    <input
                        type="datetime-local"
                        name="expires_at"
                        value="{{ old('expires_at') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('expires_at')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-base font-semibold text-gray-900">BNPL Lease Options</h4>
                    <label class="bw-morph-switch">
                        <input type="checkbox" name="create_bnpl_lease" value="1" @checked(old('create_bnpl_lease'))>
                        <svg viewBox="0 0 36 18" aria-hidden="true">
                            <path d="M18 9C18 13.9706 13.9706 18 9 18C4.02944 18 0 13.9706 0 9C0 4.02944 4.02944 0 9 0C13.9706 0 18 4.02944 18 9Z" />
                        </svg>
                        <span class="bw-morph-switch-label">Create lease for voucher</span>
                    </label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lease Rate (%)</label>
                        <input
                            type="number"
                            name="lease_rate"
                            min="0"
                            max="100"
                            step="0.01"
                            value="{{ old('lease_rate', $leaseDefaults['rate']) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        @error('lease_rate')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lease Term (days)</label>
                        <input
                            type="number"
                            name="lease_term_days"
                            min="7"
                            max="60"
                            step="1"
                            value="{{ old('lease_term_days', $leaseDefaults['term_days']) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        @error('lease_term_days')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                        <p id="voucherLeaseValidationMessage" class="text-xs text-gray-500 mt-2">
                            Minimum repayment: R30.00/day. Lease term must be between 7 and 60 days.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <label class="block text-sm font-medium text-gray-700">Initial Status</label>
                    <select
                        name="status"
                        class="px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="issued" @selected(old('status') === 'issued')>Issued</option>
                        <option value="approved" @selected(old('status') === 'approved')>Approved</option>
                    </select>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="submit" name="create_and_approve" value="1" id="createAndApproveBtn" class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-semibold hover:from-green-700 hover:to-green-800 shadow-md">
                        Create & Approve
                    </button>
                    <button type="submit" id="createVoucherBtn" class="px-5 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 shadow-md">
                        Create Voucher
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const bindAutocomplete = (inputId, listId, hiddenId) => {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            const hidden = document.getElementById(hiddenId);

            const syncHidden = () => {
                const value = input.value.trim();
                const option = list.querySelector(`option[value="${CSS.escape(value)}"]`);
                hidden.value = option ? option.dataset.id : '';
            };

            input.addEventListener('change', syncHidden);
            input.addEventListener('blur', syncHidden);
        };

        bindAutocomplete('user_search', 'users-list', 'user_id');
        bindAutocomplete('station_search', 'stations-list', 'fuel_station_id');

        const validateLeaseInputs = () => {
            const shouldCreateLease = document.querySelector('input[name="create_bnpl_lease"]')?.checked;
            const amount = Number(document.querySelector('input[name="amount"]')?.value || 0);
            const rate = Number(document.querySelector('input[name="lease_rate"]')?.value || 0);
            const termInput = document.querySelector('input[name="lease_term_days"]');
            let termDays = Number(termInput?.value || 0);
            termDays = Math.max(7, Math.min(60, termDays));
            if (termInput && termInput.value !== '' && Number(termInput.value) !== termDays) {
                termInput.value = String(termDays);
            }

            const total = amount + (amount * (rate / 100));
            const daily = termDays > 0 ? (total / termDays) : 0;
            const isValid = !shouldCreateLease || (termDays >= 7 && termDays <= 60 && daily >= 30);

            const message = document.getElementById('voucherLeaseValidationMessage');
            const btn1 = document.getElementById('createAndApproveBtn');
            const btn2 = document.getElementById('createVoucherBtn');
            if (message) {
                message.textContent = isValid
                    ? 'Minimum repayment: R30.00/day. Lease term must be between 7 and 60 days.'
                    : 'When lease creation is enabled, daily repayment must be at least R30.00 and term 7-60 days.';
                message.className = isValid ? 'text-xs text-emerald-700 mt-2' : 'text-xs text-red-600 mt-2';
            }
            [btn1, btn2].forEach((btn) => {
                if (!btn) return;
                btn.disabled = !isValid;
                btn.classList.toggle('opacity-60', !isValid);
                btn.classList.toggle('cursor-not-allowed', !isValid);
            });
        };

        ['amount', 'lease_rate', 'lease_term_days', 'create_bnpl_lease'].forEach((name) => {
            const el = document.querySelector(`[name="${name}"]`);
            if (!el) return;
            el.addEventListener('input', validateLeaseInputs);
            el.addEventListener('change', validateLeaseInputs);
        });

        document.getElementById('voucherForm').addEventListener('submit', (event) => {
            const userId = document.getElementById('user_id').value;
            const stationId = document.getElementById('fuel_station_id').value;
            if (!userId || !stationId) {
                event.preventDefault();
                window.showAdminAlert('Please select a valid user and fuel station from the suggestions.');
            }
        });

        validateLeaseInputs();
    })();
</script>
@endsection
