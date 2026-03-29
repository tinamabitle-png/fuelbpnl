@extends('Layouts.app')

@section('title', 'Create Order - Bwiser')

@section('content')
<section class="max-w-4xl mx-auto px-6 pt-16 pb-20">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Bwiser</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Create Order</h1>
            <p class="text-sm text-slate-600 mt-2">Driver: <span class="font-semibold text-slate-900">{{ $driver->name }}</span></p>
        </div>
        <a href="{{ route('bnpl.marketplace.index', [], false) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
            Back
        </a>
    </div>

    @if(session('error'))
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 glass rounded-2xl border border-slate-200 bg-white p-6">
        <form method="POST" action="{{ route('bnpl.marketplace.order.store', $driver, false) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-slate-700">What are you buying?</label>
                <input name="title" value="{{ old('title') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="e.g. Groceries and delivery" />
                @error('title')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700">Notes (optional)</label>
                <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="Add store name, address, list, etc.">{{ old('description') }}</textarea>
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
                    <input name="deposit_amount" type="number" step="0.01" min="0" value="{{ old('deposit_amount', 0) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="e.g. 150.00" />
                    @error('deposit_amount')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Installments</label>
                    <input name="installments_count" type="number" min="2" max="6" value="{{ old('installments_count', 4) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3" />
                    @error('installments_count')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Payments are not enabled yet. This creates a request that the driver can confirm later.
            </div>

            <button type="submit" class="w-full rounded-xl py-3 font-semibold text-white" style="background:#020DFF;">
                Send Order Request
            </button>
        </form>
    </div>
</section>
@endsection

