@extends('layouts.app')

@section('title', 'Merchant Settings - Bwiser')

@section('content')
<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Merchant Station Console</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-2">Settings</h1>
            <p class="text-slate-600 mt-3">Manage station profile, payout account details, and fuel price selectors.</p>
        </div>
        <a href="{{ route('merchant.dashboard', request()->filled('station_id') ? ['station_id' => (int) request('station_id')] : []) }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back to Dashboard</a>
    </div>

    @include('merchant.partials.nav')

    @if(session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Station & Payout Settings</h2>
            <p class="text-sm text-slate-600 mt-1">Profile and direct bank deposit details for settlement topups.</p>

            <form method="POST" action="{{ route('merchant.settings.update') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                @if(request()->filled('station_id'))
                    <input type="hidden" name="station_id" value="{{ (int) request('station_id') }}">
                @endif

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $station->contact_person) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $station->contact_phone) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $station->contact_email) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Payout Email</label>
                    <input type="email" name="payout_email" value="{{ old('payout_email', $station->payout_email) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Address</label>
                    <input type="text" name="address" value="{{ old('address', $station->address) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city', $station->city) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Country</label>
                    <input type="text" name="country" value="{{ old('country', $station->country) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Payout Method</label>
                    <select name="payout_method" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @php $selectedPayoutMethod = old('payout_method', $station->payout_method ?: 'paystack_direct_deposit'); @endphp
                        <option value="paystack_direct_deposit" {{ $selectedPayoutMethod === 'paystack_direct_deposit' ? 'selected' : '' }}>Paystack Direct Deposit</option>
                        <option value="paystack_transfer" {{ $selectedPayoutMethod === 'paystack_transfer' ? 'selected' : '' }}>Paystack Transfer</option>
                        <option value="bank_transfer" {{ $selectedPayoutMethod === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Bank Name</label>
                    <input type="text" name="payout_bank_name" value="{{ old('payout_bank_name', $station->payout_bank_name) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. Standard Bank">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Bank Code</label>
                    <input type="text" name="payout_bank_code" value="{{ old('payout_bank_code', $station->payout_bank_code) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. 051001">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Account Name</label>
                    <input type="text" name="payout_account_name" value="{{ old('payout_account_name', $station->payout_account_name) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Account Number</label>
                    <input type="text" name="payout_account_number" value="{{ old('payout_account_number', $station->payout_account_number) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Branch Code</label>
                    <input type="text" name="payout_branch_code" value="{{ old('payout_branch_code', $station->payout_branch_code) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Payout Reference</label>
                    <input type="text" name="payout_reference" value="{{ old('payout_reference', $station->payout_reference) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Station weekly topup">
                </div>
                <div class="md:col-span-2">
                    <button class="btn-primary w-full rounded-xl py-2.5 text-sm font-semibold">Save Station Settings</button>
                </div>
            </form>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="brand-font text-xl text-slate-900">Fuel Price Selector</h2>
            <p class="text-sm text-slate-600 mt-1">Pick a fuel type from the board, then set the station price.</p>

            <form method="POST" action="{{ route('merchant.settings.fuel-prices.update') }}" class="mt-4 space-y-4">
                @csrf
                @if(request()->filled('station_id'))
                    <input type="hidden" name="station_id" value="{{ (int) request('station_id') }}">
                @endif

                <div class="fuel-board" id="fuelBoard">
                    @foreach(['petrol' => 'Petrol', 'diesel' => 'Diesel', 'super' => 'Super'] as $fuelKey => $fuelLabel)
                        @php
                            $priceRow = (array) ($stationPrices[$fuelKey] ?? []);
                            $priceValue = old("prices.$fuelKey", isset($priceRow['price']) ? number_format((float) $priceRow['price'], 2, '.', '') : '');
                        @endphp
                        <button type="button" class="fuel-selector-chip {{ $loop->first ? 'is-active' : '' }}" data-fuel-chip="{{ $fuelKey }}">
                            <span class="fuel-selector-chip-title">{{ $fuelLabel }}</span>
                            <span class="fuel-selector-chip-sub">{{ $priceRow['source_label'] ?? 'System Default' }}</span>
                        </button>

                        <div class="fuel-price-panel {{ $loop->first ? 'is-active' : '' }}" data-fuel-panel="{{ $fuelKey }}">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ $fuelLabel }} Price (R/L)</label>
                            <div class="fuel-range-wrap">
                                <input
                                    type="range"
                                    min="15"
                                    max="35"
                                    step="0.01"
                                    value="{{ $priceValue !== '' ? $priceValue : '24.50' }}"
                                    class="fuel-range-input"
                                    data-fuel-range="{{ $fuelKey }}"
                                    list="fuelTickerMarks"
                                >
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-2 text-sm text-slate-600">R/L</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="15"
                                    max="35"
                                    name="prices[{{ $fuelKey }}]"
                                    value="{{ $priceValue }}"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    placeholder="0.00"
                                    data-fuel-number="{{ $fuelKey }}"
                                >
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Current source: {{ $priceRow['source_label'] ?? 'System Default' }}</p>
                        </div>
                    @endforeach
                </div>

                <button class="btn-primary w-full rounded-xl py-2.5 text-sm font-semibold">Save Fuel Prices</button>
            </form>
        </div>
    </div>
</section>

<datalist id="fuelTickerMarks">
    <option value="16"></option>
    <option value="18"></option>
    <option value="20"></option>
    <option value="22"></option>
    <option value="24"></option>
    <option value="26"></option>
    <option value="28"></option>
    <option value="30"></option>
    <option value="32"></option>
    <option value="34"></option>
</datalist>

<style>
    .fuel-board {
        display: grid;
        gap: 0.75rem;
    }

    .fuel-selector-chip {
        border: 1px solid #cbd5e1;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 0.9rem;
        padding: 0.8rem 0.9rem;
        text-align: left;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .fuel-selector-chip::before {
        content: "";
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(
            90deg,
            rgba(15, 23, 42, 0.08) 0 8px,
            rgba(255, 255, 255, 0.06) 8px 16px
        );
        transform: translateX(-35%);
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .fuel-selector-chip:hover::before,
    .fuel-selector-chip.is-active::before {
        opacity: 1;
        animation: fuelBoardScroll 4s linear infinite;
    }

    .fuel-selector-chip:hover,
    .fuel-selector-chip.is-active {
        border-color: #1d4ed8;
        box-shadow: 0 10px 25px rgba(29, 78, 216, 0.12);
    }

    .fuel-selector-chip-title,
    .fuel-selector-chip-sub {
        position: relative;
        z-index: 1;
        display: block;
    }

    .fuel-selector-chip-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }

    .fuel-selector-chip-sub {
        margin-top: 0.15rem;
        font-size: 0.75rem;
        color: #475569;
    }

    .fuel-price-panel {
        display: none;
        border: 1px solid #e2e8f0;
        border-radius: 0.9rem;
        background: #ffffff;
        padding: 0.9rem;
    }

    .fuel-price-panel.is-active {
        display: block;
    }

    .fuel-range-wrap {
        width: 100%;
        padding: 0.25rem 0;
    }

    .fuel-range-input {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 12px;
        border-radius: 999px;
        background: linear-gradient(90deg, #16a34a 0%, #84cc16 45%, #f59e0b 78%, #dc2626 100%);
        outline: none;
        border: 1px solid #cbd5e1;
    }

    .fuel-range-input::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        border: 2px solid #ffffff;
        background: #0f172a;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.25);
        cursor: pointer;
    }

    .fuel-range-wrap::after {
        content: "R15   R20   R25   R30   R35";
        display: block;
        margin-top: 0.35rem;
        font-size: 0.68rem;
        letter-spacing: 0.08em;
        color: #64748b;
        text-align: justify;
        text-justify: inter-character;
    }

    .fuel-range-input::-moz-range-thumb {
        width: 22px;
        height: 22px;
        border-radius: 999px;
        border: 2px solid #ffffff;
        background: #0f172a;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.25);
        cursor: pointer;
    }

    @keyframes fuelBoardScroll {
        from { transform: translateX(-35%); }
        to { transform: translateX(35%); }
    }
</style>

<script>
(() => {
    const chips = Array.from(document.querySelectorAll('[data-fuel-chip]'));
    const panels = Array.from(document.querySelectorAll('[data-fuel-panel]'));
    if (!chips.length || !panels.length) return;

    const activate = (fuel) => {
        chips.forEach((chip) => chip.classList.toggle('is-active', chip.dataset.fuelChip === fuel));
        panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.fuelPanel === fuel));
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => activate(chip.dataset.fuelChip));
    });

    const ranges = Array.from(document.querySelectorAll('[data-fuel-range]'));
    const numbers = Array.from(document.querySelectorAll('[data-fuel-number]'));

    const getNumberByFuel = (fuel) => numbers.find((node) => node.dataset.fuelNumber === fuel);
    const getRangeByFuel = (fuel) => ranges.find((node) => node.dataset.fuelRange === fuel);

    ranges.forEach((range) => {
        range.addEventListener('input', () => {
            const fuel = range.dataset.fuelRange;
            const number = getNumberByFuel(fuel);
            if (!number) return;
            number.value = Number.parseFloat(range.value || '0').toFixed(2);
        });
    });

    numbers.forEach((number) => {
        number.addEventListener('input', () => {
            const fuel = number.dataset.fuelNumber;
            const range = getRangeByFuel(fuel);
            if (!range) return;
            const value = Math.min(35, Math.max(15, Number(number.value || 0)));
            range.value = Number.isFinite(value) ? value.toString() : '15';
        });
    });
})();
</script>
@endsection
