@extends('Layouts.admin')

@section('title', 'Direct Bank Deposit #' . $settlement->id)
@section('page-title', 'Direct Bank Deposit Details')
@section('page-description', 'Review and complete direct bank deposit payment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settlements.index') }}">Direct Bank Deposits</a></li>
<li class="breadcrumb-item active">Direct Bank Deposit #{{ $settlement->id }}</li>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Direct Bank Deposit #{{ $settlement->id }}</h2>
                <p class="text-gray-600 mt-1">Reference: {{ $settlement->reference }}</p>
            </div>
            <a href="{{ route('admin.settlements.index') }}" class="text-blue-600 hover:text-blue-800 no-print">
                <i class="fas fa-arrow-left mr-2"></i>Back to direct bank deposits
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6 print-full">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Direct Bank Deposit Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                        <div>
                            <p class="text-gray-500">Fuel Station</p>
                            <p class="font-semibold">{{ $settlement->fuelStation->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Amount</p>
                            <p class="font-semibold">ZAR {{ number_format($settlement->amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Status</p>
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold
                                {{ $settlement->status === 'completed' ? 'bg-green-100 text-green-700' : ($settlement->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($settlement->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-500">Direct Bank Deposit Date</p>
                            <p class="font-semibold">{{ $settlement->settlement_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Transaction Ref</p>
                            <p class="font-semibold">{{ $settlement->transaction_reference ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                    <p class="text-sm text-gray-700">{{ $settlement->notes ?? '—' }}</p>
                </div>
            </div>

            <div class="space-y-6 no-print">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>

                    @if($settlement->transaction_reference)
                        <form method="POST" action="{{ route('admin.settlements.verify-paystack', $settlement) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 bg-blue-100 text-blue-700 rounded-xl font-semibold hover:bg-blue-200">
                                Verify on Paystack
                            </button>
                        </form>
                    @endif

                    @if(session('paystack_otp_required'))
                        <button
                            type="button"
                            onclick="document.getElementById('otpModal').classList.remove('hidden')"
                            class="w-full mb-3 px-4 py-2.5 bg-amber-100 text-amber-700 rounded-xl font-semibold hover:bg-amber-200">
                            Enter OTP to Finalize Transfer
                        </button>
                    @endif

                    @if($settlement->status === 'pending')
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 mb-3">
                            <p class="text-sm font-semibold text-emerald-700">Payment Method: Paystack Direct Transfer</p>
                            <p class="text-xs text-emerald-700/80 mt-1">Only Paystack direct-to-account is enabled for settlements.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.settlements.process', $settlement) }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700">
                                Process via Paystack
                            </button>
                        </form>
                    @else
                        <div class="text-sm text-gray-500">
                            This direct bank deposit is {{ $settlement->status }}.
                        </div>
                    @endif

                    @if($settlement->status === 'pending')
                        <form method="POST" action="{{ route('admin.settlements.mark-as-failed', $settlement) }}" class="mt-6">
                            @csrf
                            <label class="block text-sm text-gray-600 mb-2">Failure Reason</label>
                            <textarea name="reason" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-3" required></textarea>
                            <button type="submit" class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg font-semibold hover:bg-red-200">
                                Mark as Failed
                            </button>
                        </form>
                    @endif
                </div>

                @if(session('paystack_verify'))
                    @php($pv = session('paystack_verify'))
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Paystack Verification</h3>
                        <div class="space-y-2 text-sm text-slate-700">
                            <p><span class="text-slate-500">Reference:</span> <span class="font-semibold">{{ $pv['reference'] ?? '—' }}</span></p>
                            <p>
                                <span class="text-slate-500">Status:</span>
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold
                                    {{ in_array(strtolower((string) ($pv['status'] ?? '')), ['success', 'successful'], true) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ strtoupper((string) ($pv['status'] ?? 'unknown')) }}
                                </span>
                            </p>
                            <p><span class="text-slate-500">Amount:</span> <span class="font-semibold">{{ isset($pv['amount']) ? number_format((float) $pv['amount'], 2) : '—' }} {{ $pv['currency'] ?? 'ZAR' }}</span></p>
                            @if(!empty($pv['recipient']))
                                <p><span class="text-slate-500">Recipient:</span> {{ $pv['recipient'] }}</p>
                            @endif
                            @if(!empty($pv['reason']))
                                <p><span class="text-slate-500">Reason:</span> {{ $pv['reason'] }}</p>
                            @endif
                            @if(!empty($pv['transferred_at']))
                                <p><span class="text-slate-500">Transferred At:</span> {{ $pv['transferred_at'] }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@if(session('paystack_otp_required'))
    <div id="otpModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Paystack OTP Required</h3>
                    <p class="text-sm text-slate-600 mt-1">Reference: {{ data_get(session('paystack_otp_required'), 'reference', $settlement->transaction_reference) }}</p>
                </div>
                <button type="button" onclick="document.getElementById('otpModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.settlements.finalize-paystack-otp', $settlement) }}" class="mt-4 space-y-3">
                @csrf
                <label class="block text-sm font-medium text-slate-700">OTP</label>
                <input type="text" name="otp" required maxlength="12" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter OTP from Paystack">
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" onclick="document.getElementById('otpModal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">
                        Finalize OTP
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@if(request()->boolean('print'))
    @push('styles')
        <style>
            @media print {
                #sidebar,
                header,
                footer,
                .no-print {
                    display: none !important;
                }
                .content-area {
                    margin-left: 0 !important;
                }
                .print-full {
                    grid-column: 1 / -1;
                }
                body {
                    background: #ffffff !important;
                }
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endpush
@endif
