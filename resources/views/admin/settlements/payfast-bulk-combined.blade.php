@extends('layouts.admin')

@section('title', 'PayFast Bulk Direct Bank Deposit')
@section('page-title', 'PayFast Bulk Direct Bank Deposit')
@section('page-description', 'Single PayFast payment for selected direct bank deposits')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settlements.index') }}">Direct Bank Deposits</a></li>
<li class="breadcrumb-item active">Bulk PayFast (Single)</li>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Bulk PayFast Payment</h2>
                <p class="text-gray-600 mt-1">One PayFast checkout for all selected direct bank deposits.</p>
            </div>
            <a href="{{ route('admin.settlements.index') }}" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to direct bank deposits
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Direct Bank Deposits</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $settlements->count() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Total Amount</p>
                    <p class="text-2xl font-bold text-teal-700">ZAR {{ number_format($total, 2) }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('payments.payfast.settlement-bulk') }}" class="mt-6">
                @csrf
                @foreach($settlements as $settlement)
                    <input type="hidden" name="settlements[]" value="{{ $settlement->id }}">
                @endforeach
                <button type="submit" class="w-full px-4 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700">
                    Pay All with PayFast
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Reference</th>
                        <th class="px-4 py-3 text-left">Fuel Station</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    @forelse($settlements as $settlement)
                        <tr>
                            <td class="px-4 py-3">{{ $settlement->reference }}</td>
                            <td class="px-4 py-3">{{ $settlement->fuelStation->name }}</td>
                            <td class="px-4 py-3">ZAR {{ number_format($settlement->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                No pending direct bank deposits selected.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
