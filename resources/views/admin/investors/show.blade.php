@extends('Layouts.admin')

@section('title', $investor->company_name . ' - Investor')
@section('page-title', 'Investor Detail')
@section('page-description', 'Wallet, capital, approved subprime leases, and portfolio returns')
@section('breadcrumb', 'Investors / ' . $investor->company_name)

@section('content')
<div class="p-6 space-y-6">
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Admin-created investor</p>
                <h2 class="text-3xl font-bold text-gray-900 mt-2">{{ $investor->company_name }}</h2>
                <p class="text-gray-500 mt-1">{{ $investor->user?->email ?? $investor->contact_email }} • {{ $investor->contact_phone }}</p>
            </div>
            <span class="inline-flex px-3 py-1.5 rounded-full bg-blue-100 text-blue-700 text-sm font-semibold">{{ ucfirst(str_replace('_', ' ', $investor->status)) }}</span>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
                <p class="text-sm text-blue-700">Wallet Balance</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">R {{ number_format((float) $investor->wallet_balance, 2) }}</p>
            </div>
            <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                <p class="text-sm text-emerald-700">Available Capital</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">R {{ number_format((float) $investor->available_capital, 2) }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                <p class="text-sm text-slate-600">Invested Capital</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">R {{ number_format((float) $investor->invested_capital, 2) }}</p>
            </div>
            <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
                <p class="text-sm text-amber-700">Average Return</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format((float) ($portfolio['average_return'] ?? 0), 2) }}%</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-emerald-600">Fund Account</p>
                    <h3 class="text-xl font-bold text-gray-900 mt-1">Finance Company Funding</h3>
                    <p class="text-sm text-gray-500 mt-1">Top up capital for lease funding, the linked wallet, or both.</p>
                </div>
                <span class="inline-flex rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">Admin action</span>
            </div>

            <form method="POST" action="{{ route('admin.investors.capital.update', $investor) }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="type" value="add">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Amount</label>
                    <input type="number" name="amount" min="100" step="0.01" required class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. 25000">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Destination</label>
                    <select name="destination" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="capital">Capital account only</option>
                        <option value="wallet">Wallet only</option>
                        <option value="both">Capital and wallet</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Reason</label>
                    <input type="text" name="reason" required value="Admin finance company funding" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    Fund {{ $investor->company_name }}
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Send Lease</p>
                    <h3 class="text-xl font-bold text-gray-900 mt-1">Allocate Approved Lease</h3>
                    <p class="text-sm text-gray-500 mt-1">Only approved subprime voucher leases with remaining funding capacity are listed.</p>
                </div>
                <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">{{ ($assignableLeases ?? collect())->count() }} available</span>
            </div>

            <form method="POST" action="{{ route('admin.investors.invest', $investor) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-semibold text-slate-700">Lease</label>
                    <select name="lease_id" required class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Choose eligible lease</option>
                        @foreach(($assignableLeases ?? collect()) as $lease)
                            @php
                                $voucher = $lease->vouchers?->firstWhere('status', 'approved') ?: $lease->vouchers?->firstWhere('status', 'redeemed');
                            @endphp
                            <option value="{{ $lease->id }}">
                                #{{ $lease->id }} • {{ $lease->user?->name ?? 'Driver' }} • Score {{ $lease->user?->credit_score ?? 'N/A' }} • {{ $voucher?->code ?? 'Voucher' }} • Remaining R {{ number_format((float) $lease->investor_funding_remaining, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Investment Amount</label>
                    <input type="number" name="amount" min="1000" step="0.01" required class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. 5000">
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    Send Lease To {{ $investor->company_name }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-xl font-bold text-gray-900">Approved Subprime Funded Leases</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th>Lease</th>
                        <th>Driver</th>
                        <th>Voucher</th>
                        <th>Invested</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($approvedLeases as $investment)
                        @php
                            $voucher = $investment->lease?->vouchers?->firstWhere('status', 'approved') ?: $investment->lease?->vouchers?->firstWhere('status', 'redeemed');
                        @endphp
                        <tr>
                            <td>#{{ $investment->lease_id }}</td>
                            <td>
                                <p class="font-semibold text-gray-900">{{ $investment->lease?->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">Score {{ $investment->lease?->user?->credit_score ?? 'N/A' }}</p>
                            </td>
                            <td>
                                <span class="inline-flex px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-semibold">{{ $voucher?->status ? ucfirst($voucher->status) : 'N/A' }}</span>
                                <p class="text-xs text-gray-500 mt-1">{{ $voucher?->code ?? 'No voucher' }}</p>
                            </td>
                            <td>R {{ number_format((float) $investment->amount_invested, 2) }}</td>
                            <td>{{ ucfirst($investment->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-8">No approved subprime funded leases yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $approvedLeases->links() }}</div>
    </div>
</div>
@endsection
