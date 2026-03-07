@extends('Layouts.admin')

@section('title', 'PayFast Bulk Direct Bank Deposits')
@section('page-title', 'PayFast Bulk Direct Bank Deposits')
@section('page-description', 'Pay selected direct bank deposits via PayFast')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settlements.index') }}">Direct Bank Deposits</a></li>
<li class="breadcrumb-item active">PayFast Bulk</li>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Selected Direct Bank Deposits</h2>
                <p class="text-gray-600 mt-1">Use PayFast to complete each direct bank deposit payment.</p>
            </div>
            <a href="{{ route('admin.settlements.index') }}" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to direct bank deposits
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Reference</th>
                        <th class="px-4 py-3 text-left">Fuel Station</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    @forelse($settlements as $settlement)
                        <tr>
                            <td class="px-4 py-3">{{ $settlement->reference }}</td>
                            <td class="px-4 py-3">{{ $settlement->fuelStation->name }}</td>
                            <td class="px-4 py-3">ZAR {{ number_format($settlement->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('payments.payfast.settlement', $settlement) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                                        Pay with PayFast
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
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
