@extends('Layouts.app')

@section('title', 'PayShap Repayment - Bwiser')

@section('content')
<section class="max-w-3xl mx-auto px-6 pt-14 pb-20">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-blue-600">PayShap</p>
            <h1 class="brand-font text-2xl md:text-3xl font-semibold text-slate-900 mt-2">Pay repayment via PayShap</h1>
            <p class="text-sm text-slate-600 mt-2">Approve the request in your bank app to settle this repayment instantly.</p>
        </div>
        <a href="{{ route('driver.repayments.index') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back</a>
    </div>

    @if(!$peachEnabled)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            PayShap is not enabled on this environment yet. Set `PEACH_ENABLED=true` and the Peach credentials in `.env`.
        </div>
    @endif

    @if(session('error'))
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 glass rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500">Repayment due</p>
                <p class="text-xl font-semibold text-slate-900 mt-1">{{ \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500">Amount</p>
                <p class="text-2xl font-semibold text-slate-900 mt-1">R {{ number_format((float) $repayment->amount, 2) }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('driver.repayments.payshap.init', $repayment) }}" class="mt-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Bank</label>
                    <select name="bank" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @foreach($banks as $value => $label)
                            <option value="{{ $value }}" @selected(old('bank', $defaultBank) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('bank')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-slate-500 mt-1">Choose the bank where you will approve the PayShap request.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Mobile number</label>
                    <input name="phone" type="tel" value="{{ old('phone', $defaultPhone) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="+27XXXXXXXXX">
                    @error('phone')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-slate-500 mt-1">We will send the request to this PayShap-linked number.</p>
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-blue-600 text-white py-2.5 font-semibold hover:bg-blue-700 disabled:opacity-50" @disabled(!$peachEnabled)>
                Send PayShap Request
            </button>
            <p class="text-[11px] text-slate-500">
                After you approve the request in your bank app, you will be redirected back to Bwiser to confirm payment status.
            </p>
        </form>
    </div>
</section>
@endsection

