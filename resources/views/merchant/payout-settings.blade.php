@extends('layouts.app')

@section('title', 'Direct Bank Deposit Settings')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        @include('merchant.partials.nav')
        <div class="flex items-center justify-between">
            <div>
                <h1 class="brand-font text-2xl text-slate-900">Direct Bank Deposit Settings</h1>
                <p class="text-slate-600 mt-2">Update where your station receives direct bank deposit payments.</p>
            </div>
            <a href="{{ route('merchant.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700">
                Back to dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="mt-6 glass rounded-xl p-4 text-emerald-700 border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-6 glass rounded-xl p-4 text-red-700 border border-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('merchant.payout.update') }}" class="mt-8 space-y-6">
            @csrf

            <div>
                <label class="block text-sm text-slate-700 mb-2">Direct Bank Deposit Method</label>
                <select name="payout_method"
                        class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="bank_transfer" {{ old('payout_method', $station->payout_method) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Bank Name</label>
                    <input type="text" name="payout_bank_name" list="bankList" value="{{ old('payout_bank_name', $station->payout_bank_name) }}"
                           class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="bankList"></datalist>
                </div>
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Bank Code (Paystack)</label>
                    <input type="text" name="payout_bank_code" value="{{ old('payout_bank_code', $station->payout_bank_code) }}"
                           class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g. 058">
                    <p class="text-xs text-slate-500 mt-1">Required for Paystack transfers. If blank, we’ll try to match by bank name.</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Account Name</label>
                    <input type="text" name="payout_account_name" value="{{ old('payout_account_name', $station->payout_account_name) }}"
                           class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Account Number</label>
                    <input type="text" name="payout_account_number" value="{{ old('payout_account_number', $station->payout_account_number) }}"
                           class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Branch Code</label>
                    <input type="text" name="payout_branch_code" value="{{ old('payout_branch_code', $station->payout_branch_code) }}"
                           class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm text-slate-700 mb-2">Payment Reference</label>
                <input type="text" name="payout_reference" value="{{ old('payout_reference', $station->payout_reference) }}"
                       class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm text-slate-700 mb-2">PayFast Email (if PayFast selected)</label>
                <input type="email" name="payout_email" value="{{ old('payout_email', $station->payout_email) }}"
                       class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary px-6 py-3 rounded-xl text-sm font-semibold">
                    Save Direct Bank Deposit Details
                </button>
            </div>
        </form>
    </div>
</section>

<script>
(() => {
    const bankNameInput = document.querySelector('input[name="payout_bank_name"]');
    const bankCodeInput = document.querySelector('input[name="payout_bank_code"]');
    const accountNumberInput = document.querySelector('input[name="payout_account_number"]');
    const accountNameInput = document.querySelector('input[name="payout_account_name"]');
    const bankList = document.getElementById('bankList');
    if (!bankNameInput || !bankCodeInput || !bankList) return;

    let banks = [];

    const normalize = (value) => (value || '').toString().toLowerCase().replace(/[^a-z0-9]/g, '');

    const loadBanks = async () => {
        try {
            const res = await fetch("{{ route('merchant.payout.banks') }}", {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await res.json();
            if (!payload.success) return;
            banks = payload.data || [];
            bankList.innerHTML = banks
                .map((bank) => `<option value="${bank.name}"></option>`)
                .join('');
        } catch (_) {}
    };

    const setBankByName = (name) => {
        const target = normalize(name);
        const bank = banks.find((item) => normalize(item.name) === target);
        if (bank) {
            bankCodeInput.value = bank.code || '';
        }
    };

    const setBankByCode = (code) => {
        const bank = banks.find((item) => String(item.code) === String(code));
        if (bank) {
            bankNameInput.value = bank.name || bankNameInput.value;
        }
    };

    const resolveAccount = async () => {
        const bankCode = bankCodeInput.value.trim();
        const accountNumber = accountNumberInput?.value.trim();
        if (!bankCode || !accountNumber || accountNumber.length < 8) return;
        try {
            const url = new URL("{{ route('merchant.payout.resolve') }}", window.location.origin);
            url.searchParams.set('account_number', accountNumber);
            url.searchParams.set('bank_code', bankCode);
            const res = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await res.json();
            if (!payload.success) return;
            const name = payload.data?.account_name;
            if (name && accountNameInput && (accountNameInput.value.trim() === '' || accountNameInput.dataset.auto === '1')) {
                accountNameInput.value = name;
                accountNameInput.dataset.auto = '1';
            }
        } catch (_) {}
    };

    bankNameInput.addEventListener('input', (event) => {
        setBankByName(event.target.value);
    });
    bankCodeInput.addEventListener('input', (event) => {
        setBankByCode(event.target.value);
    });
    accountNumberInput?.addEventListener('blur', resolveAccount);
    bankCodeInput.addEventListener('blur', resolveAccount);
    accountNameInput?.addEventListener('input', () => {
        if (accountNameInput) accountNameInput.dataset.auto = '0';
    });

    loadBanks();
})();
</script>
@endsection
