@extends('Layouts.app')

@section('title', 'Crypto Repayment - Bwiser')

@section('content')
<section class="max-w-3xl mx-auto px-6 pt-16 pb-20">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-blue-600">Crypto</p>
            <h1 class="brand-font text-2xl md:text-3xl font-semibold text-slate-900 mt-2">Pay repayment with {{ $asset === 'ETH' ? 'Ethereum' : 'Bitcoin' }}</h1>
            <p class="text-sm text-slate-600 mt-2">Send the exact amount from your wallet, then paste the transaction hash (txid) to confirm.</p>
        </div>
        <a href="{{ route('driver.repayments.index') }}" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold">Back</a>
    </div>

    @if(!$enabled)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Crypto payments are not enabled. Set <code class="font-mono">LUNO_ENABLED=true</code> and Luno API credentials in <code class="font-mono">.env</code>.
        </div>
    @else
        <div class="mt-6 glass rounded-2xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Repayment</p>
                    <p class="text-sm font-semibold text-slate-900 mt-2">Due {{ \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y') }}</p>
                    <p class="text-sm text-slate-600 mt-1">Amount: <span class="font-semibold">-R {{ number_format(abs((float) $repayment->amount), 2) }}</span></p>
                    @if(!empty($rate) && !empty($expectedAssetAmount))
                        <p class="text-xs text-slate-500 mt-2">Rate ({{ $pair }} last trade): R {{ number_format((float) $rate, 2) }}</p>
                        <p class="text-sm text-slate-900 mt-2">
                            Send:
                            <span class="font-semibold">{{ number_format((float) $expectedAssetAmount, 8) }} {{ $asset }}</span>
                        </p>
                    @else
                        <p class="text-xs text-rose-700 mt-2">Could not fetch price quote. Try again later.</p>
                    @endif
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Deposit Address</p>
                    @if(!empty($qr))
                        <img src="{{ $qr }}" alt="Deposit QR code" class="mt-3 h-36 w-36 rounded-xl border border-slate-200 bg-white object-contain">
                    @endif
                    <p class="text-xs text-slate-500 mt-3">Address</p>
                    <p class="mt-1 font-mono text-[12px] break-all text-slate-900">{{ $address }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('driver.repayments.crypto.confirm', $repayment) }}" class="mt-6 space-y-3">
                @csrf
                <input type="hidden" name="asset" value="{{ $asset }}">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Transaction hash (txid)</label>
                    <input name="txid" value="{{ old('txid') }}" class="mt-2 w-full px-3 py-2" placeholder="Paste your txid from your wallet">
                    @error('txid')
                        <p class="text-sm text-rose-700 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold">Confirm Payment</button>
                <p class="text-xs text-slate-500">Confirmation can take a few minutes depending on network and exchange processing time.</p>
            </form>
        </div>
    @endif
</section>
@endsection

