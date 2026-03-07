@extends('Layouts.app')

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
        <div class="glass rounded-2xl p-6 merchant-card merchant-card--profile">
            <h2 class="brand-font text-xl text-slate-900">Station & Payout Settings</h2>
            <p class="text-sm text-slate-600 mt-1">Profile, franchise branding, and direct bank deposit details for settlement topups.</p>

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
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Franchise Brand</label>
                    <select name="company" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select franchise</option>
                        @foreach(($franchiseBrands ?? collect()) as $brand)
                            <option value="{{ $brand['name'] }}" {{ old('company', $station->company) === $brand['name'] ? 'selected' : '' }}>
                                {{ $brand['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-1">This selection controls the logo shown on your merchant dashboard.</p>
                </div>
                <div class="md:col-span-2">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach(($franchiseBrands ?? collect()) as $brand)
                            @php $active = old('company', $station->company) === $brand['name']; @endphp
                            <label class="franchise-choice {{ $active ? 'is-active' : '' }}">
                                <input type="radio" name="company" value="{{ $brand['name'] }}" class="sr-only" {{ $active ? 'checked' : '' }}>
                                @if(!empty($brand['logo_url']))
                                    <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }} logo" class="h-7 w-full object-contain">
                                @else
                                    <span class="text-xs font-bold text-slate-700">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($brand['name'], 0, 2)) }}</span>
                                @endif
                                <span class="mt-1 block text-[11px] font-medium text-slate-700">{{ $brand['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
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

        @php
            $initialFuel = old('fuel_type', 'petrol');
            $initialPrice = (float) (($stationPrices[$initialFuel]['price'] ?? 24.50));
            $initialRand = (int) floor($initialPrice);
            $initialCents = (int) round(($initialPrice - $initialRand) * 100);
        @endphp
        <div class="glass rounded-2xl p-6 merchant-card merchant-card--fuel">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="brand-font text-xl text-slate-900">Fuel Price Controls</h2>
                    <p class="text-sm text-slate-600 mt-1">Set fuel price per liter using wheel controls and publish instantly.</p>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold">{{ $station->name }}</span>
            </div>

            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 benchmark-shell">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <p class="text-sm font-semibold text-slate-800">Live Benchmark Feed</p>
                    <p class="text-xs text-slate-500">Source: {{ $stationPrices['petrol']['source_label'] ?? 'Fallback' }} · {{ $stationPrices['petrol']['effective_at'] ?? now()->format('Y-m-d H:i:s') }}</p>
                </div>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach(['petrol' => 'Petrol', 'super' => 'Super', 'diesel' => 'Diesel'] as $fuelKey => $fuelLabel)
                        @php $price = (float) ($stationPrices[$fuelKey]['price'] ?? 0); @endphp
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 benchmark-card">
                            <p class="text-sm text-slate-500">{{ $fuelLabel }}</p>
                            <p class="text-2xl font-semibold text-slate-900 mt-1">ZAR {{ number_format($price, 2) }}/L</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('merchant.settings.fuel-prices.update') }}" class="mt-4 space-y-4">
                @csrf
                @if(request()->filled('station_id'))
                    <input type="hidden" name="station_id" value="{{ (int) request('station_id') }}">
                @endif
                <input type="hidden" name="fuel_type" id="fuelTypeInput" value="{{ $initialFuel }}">
                <input type="hidden" name="rand" id="fuelRandInput" value="{{ $initialRand }}">
                <input type="hidden" name="cents" id="fuelCentsInput" value="{{ $initialCents }}">

                <div class="fuel-wheel-shell">
                    <div class="fuel-wheel-grid">
                        <div class="fuel-wheel-col">
                            <p class="fuel-wheel-kicker">RAND</p>
                            <div class="fuel-wheel-list" id="randWheel"></div>
                        </div>
                        <div class="fuel-wheel-col">
                            <p class="fuel-wheel-kicker">CENTS</p>
                            <div class="fuel-wheel-list" id="centsWheel"></div>
                        </div>
                        <div class="fuel-wheel-col">
                            <p class="fuel-wheel-kicker">FUEL</p>
                            <div class="fuel-wheel-list" id="fuelWheel"></div>
                        </div>
                    </div>
                    <div class="fuel-wheel-highlight" aria-hidden="true"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end price-preview-wrap">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Preview</p>
                        <p id="fuelPreviewValue" class="text-5xl font-black text-slate-900 mt-1">ZAR 0.00/L</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1">Effective At (optional)</label>
                        <input type="datetime-local" name="effective_at" value="{{ old('effective_at') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <button class="btn-primary rounded-xl px-5 py-2.5 text-sm font-semibold">Save Fuel Price</button>
                    <p class="text-sm text-slate-500">Scroll each wheel to pick values.</p>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    .merchant-card {
        position: relative;
        border: 1px solid #dbe4ef;
        background: #ffffff;
        box-shadow:
            0 18px 40px rgba(15, 23, 42, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }

    .merchant-card:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow:
            0 24px 50px rgba(30, 64, 175, 0.16),
            inset 0 1px 0 rgba(255, 255, 255, 0.92);
    }

    .benchmark-shell {
        background: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .benchmark-card {
        background: #f8fafc;
        box-shadow:
            0 10px 20px rgba(15, 23, 42, 0.06),
            inset 0 1px 0 rgba(255, 255, 255, 0.85);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .benchmark-card:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow:
            0 15px 30px rgba(37, 99, 235, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
    }

    .price-preview-wrap {
        border: 1px solid #dbe4ef;
        border-radius: 1rem;
        padding: 1rem;
        background: #f8fafc;
    }

    #fuelPreviewValue {
        letter-spacing: -0.03em;
        text-shadow: 0 2px 12px rgba(30, 64, 175, 0.18);
    }

    .franchise-choice {
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 0.8rem;
        padding: 0.55rem 0.6rem;
        display: block;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .franchise-choice:hover {
        border-color: #818cf8;
        box-shadow: 0 14px 28px rgba(79, 70, 229, 0.2);
        transform: translateY(-1px);
    }

    .franchise-choice.is-active {
        border-color: #4f46e5;
        box-shadow: 0 0 0 2px #c7d2fe, 0 14px 30px rgba(79, 70, 229, 0.24);
        background: #eef2ff;
    }

    .fuel-wheel-shell {
        position: relative;
        border-radius: 1.2rem;
        border: 1px solid #1e3a5f;
        background: #0f172a;
        padding: 1rem;
        overflow: hidden;
    }

    .fuel-wheel-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .fuel-wheel-col {
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.52);
        padding: 0.7rem;
    }

    .fuel-wheel-kicker {
        text-align: center;
        font-size: 0.78rem;
        letter-spacing: 0.14em;
        color: #cbd5e1;
        font-weight: 700;
        margin-bottom: 0.4rem;
    }

    .fuel-wheel-list {
        height: 180px;
        overflow-y: auto;
        scroll-snap-type: y mandatory;
        border-radius: 0.8rem;
        padding: 68px 0;
        position: relative;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .fuel-wheel-list::-webkit-scrollbar {
        display: none;
    }

    .fuel-wheel-item {
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(226, 232, 240, 0.48);
        font-weight: 800;
        font-size: 1.2rem;
        scroll-snap-align: center;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    .fuel-wheel-item.active {
        color: #ffffff;
        transform: scale(1.06);
    }

    .fuel-wheel-highlight {
        position: absolute;
        left: 1rem;
        right: 1rem;
        top: calc(50% - 22px);
        height: 44px;
        border-radius: 0.7rem;
        border: 1px solid rgba(96, 165, 250, 0.45);
        background: rgba(37, 99, 235, 0.16);
        pointer-events: none;
        z-index: 3;
    }

    @media (max-width: 760px) {
        .fuel-wheel-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
(() => {
    const franchiseInputs = Array.from(document.querySelectorAll('input[name="company"][type="radio"]'));
    const refreshFranchiseCards = () => {
        document.querySelectorAll('.franchise-choice').forEach((card) => card.classList.remove('is-active'));
        franchiseInputs.forEach((input) => {
            if (input.checked) {
                const card = input.closest('.franchise-choice');
                if (card) {
                    card.classList.add('is-active');
                }
            }
        });
    };
    franchiseInputs.forEach((input) => input.addEventListener('change', refreshFranchiseCards));
    refreshFranchiseCards();

    const randWheel = document.getElementById('randWheel');
    const centsWheel = document.getElementById('centsWheel');
    const fuelWheel = document.getElementById('fuelWheel');
    const randInput = document.getElementById('fuelRandInput');
    const centsInput = document.getElementById('fuelCentsInput');
    const fuelTypeInput = document.getElementById('fuelTypeInput');
    const preview = document.getElementById('fuelPreviewValue');

    if (!randWheel || !centsWheel || !fuelWheel || !randInput || !centsInput || !fuelTypeInput || !preview) {
        return;
    }

    const fuels = ['petrol', 'super', 'diesel'];
    const prettyFuel = {
        petrol: 'PETROL',
        super: 'SUPER',
        diesel: 'DIESEL',
    };

    const buildWheel = (container, values) => {
        container.innerHTML = '';
        values.forEach((value) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'fuel-wheel-item';
            item.dataset.value = String(value);
            item.textContent = typeof value === 'number'
                ? String(value).padStart(2, '0')
                : String(value).toUpperCase();
            container.appendChild(item);
        });
    };

    buildWheel(randWheel, Array.from({ length: 90 }, (_, i) => i + 10));
    buildWheel(centsWheel, Array.from({ length: 100 }, (_, i) => i));
    buildWheel(fuelWheel, fuels);

    const getClosestItem = (container) => {
        const items = Array.from(container.querySelectorAll('.fuel-wheel-item'));
        const middle = container.getBoundingClientRect().top + (container.clientHeight / 2);
        let selected = items[0] || null;
        let min = Number.POSITIVE_INFINITY;
        items.forEach((item) => {
            const rect = item.getBoundingClientRect();
            const center = rect.top + (rect.height / 2);
            const dist = Math.abs(center - middle);
            if (dist < min) {
                min = dist;
                selected = item;
            }
        });
        return selected;
    };

    const setActive = (container, value) => {
        container.querySelectorAll('.fuel-wheel-item').forEach((item) => {
            item.classList.toggle('active', item.dataset.value === String(value));
        });
    };

    const syncPreview = () => {
        const rand = parseInt(randInput.value || '0', 10);
        const cents = parseInt(centsInput.value || '0', 10);
        const fuel = fuelTypeInput.value || 'petrol';
        const price = rand + (cents / 100);
        preview.textContent = `ZAR ${price.toFixed(2)}/L`;
        preview.dataset.fuel = prettyFuel[fuel] || fuel.toUpperCase();
    };

    const snapToValue = (container, value) => {
        const item = container.querySelector(`.fuel-wheel-item[data-value="${String(value)}"]`);
        if (!item) {
            return;
        }
        const top = item.offsetTop - ((container.clientHeight - item.clientHeight) / 2);
        container.scrollTo({ top, behavior: 'auto' });
        setActive(container, value);
    };

    const bindWheel = (container, onChange) => {
        let timer = null;
        const commit = () => {
            const closest = getClosestItem(container);
            if (!closest) {
                return;
            }
            const value = closest.dataset.value;
            snapToValue(container, value);
            onChange(value);
            syncPreview();
        };

        container.addEventListener('scroll', () => {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(commit, 80);
        });

        container.querySelectorAll('.fuel-wheel-item').forEach((item) => {
            item.addEventListener('click', () => {
                snapToValue(container, item.dataset.value);
                onChange(item.dataset.value);
                syncPreview();
            });
        });
    };

    bindWheel(randWheel, (value) => {
        randInput.value = String(parseInt(value || '0', 10));
    });
    bindWheel(centsWheel, (value) => {
        centsInput.value = String(parseInt(value || '0', 10));
    });
    bindWheel(fuelWheel, (value) => {
        fuelTypeInput.value = String(value || 'petrol');
    });

    snapToValue(randWheel, randInput.value || '24');
    snapToValue(centsWheel, centsInput.value || '50');
    snapToValue(fuelWheel, fuelTypeInput.value || 'petrol');
    syncPreview();
})();
</script>
@endsection
