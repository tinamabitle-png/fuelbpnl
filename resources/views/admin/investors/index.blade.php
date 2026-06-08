@extends('Layouts.admin')

@section('title', 'Investor Management')
@section('page-title', 'Investor Management')
@section('page-description', 'Admin-created investors, capital, wallet balances, and funded subprime voucher leases')
@section('breadcrumb', 'Investors')

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <p class="text-blue-600 text-sm font-semibold">Investors</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_investors'] ?? 0) }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-100">
        <p class="text-emerald-600 text-sm font-semibold">Available Capital</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">R {{ number_format((float) ($stats['total_available_capital'] ?? 0), 2) }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <p class="text-slate-600 text-sm font-semibold">Invested Capital</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">R {{ number_format((float) ($stats['total_invested_capital'] ?? 0), 2) }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-amber-100">
        <p class="text-amber-600 text-sm font-semibold">Investor Wallets</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">R {{ number_format((float) ($stats['total_wallet_balance'] ?? 0), 2) }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Investor Directory</h2>
            <p class="text-gray-600 mt-1">Investors are created by admin and use the same login as drivers and merchants.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th>Investor</th>
                        <th>Wallet</th>
                        <th>Capital</th>
                        <th>Approved Leases</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($investors as $investor)
                        <tr>
                            <td>
                                <p class="font-semibold text-gray-900">{{ $investor->company_name }}</p>
                                <p class="text-xs text-gray-500">{{ $investor->user?->email ?? $investor->contact_email }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-gray-900">R {{ number_format((float) $investor->wallet_balance, 2) }}</p>
                                <p class="text-xs text-gray-500">Available R {{ number_format((float) $investor->wallet_available_balance, 2) }}</p>
                            </td>
                            <td>
                                <p class="text-sm text-gray-700">Available R {{ number_format((float) $investor->available_capital, 2) }}</p>
                                <p class="text-xs text-gray-500">Invested R {{ number_format((float) $investor->invested_capital, 2) }}</p>
                            </td>
                            <td>
                                <span class="inline-flex px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-semibold">{{ $investor->lease_investments_count }} funded</span>
                            </td>
                            <td>
                                <span class="inline-flex px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">{{ ucfirst(str_replace('_', ' ', $investor->status)) }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.investors.show', $investor) }}" class="text-sm font-semibold text-blue-600">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500 py-10">No investors found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $investors->links() }}</div>
</div>
@endsection
