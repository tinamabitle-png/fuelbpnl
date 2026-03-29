@extends('Layouts.app')

@section('title', 'Create BNPL Order - Bwiser')

@section('content')
<section class="max-w-4xl mx-auto px-6 pt-16 pb-20">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Create Shopper BNPL Order</h1>
            <p class="text-sm text-slate-600 mt-2">Generate a checkout link for a shopper. Payments will be added next.</p>
        </div>
        <a href="{{ route('driver.bnpl.orders.index', [], false) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-800 hover:bg-slate-50">
            Back
        </a>
    </div>

    <div class="mt-6 glass rounded-2xl border border-slate-200 bg-white p-6">
        <form method="POST" action="{{ route('driver.bnpl.orders.store', [], false) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-slate-700">Title (optional)</label>
                <input name="title" value="{{ old('title') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="e.g. Grocery run" />
                @error('title')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700">Description (optional)</label>
                <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="What is the shopper buying?">{{ old('description') }}</textarea>
                @error('description')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Total Amount (R)</label>
                    <input name="amount_total" type="number" step="0.01" min="10" value="{{ old('amount_total') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="e.g. 650.00" required />
                    @error('amount_total')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Deposit (R)</label>
                    <input name="deposit_amount" type="number" step="0.01" min="0" value="{{ old('deposit_amount') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="e.g. 150.00" />
                    @error('deposit_amount')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Installments</label>
                    <input name="installments_count" type="number" min="2" max="6" value="{{ old('installments_count', 4) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" />
                    @error('installments_count')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl py-3 font-semibold text-white" style="background:#020DFF;">
                Create Order
            </button>
        </form>
    </div>
</section>
@endsection

