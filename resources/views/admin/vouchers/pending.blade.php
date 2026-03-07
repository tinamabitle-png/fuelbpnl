@extends('Layouts.admin')

@section('title', 'Pending Vouchers')
@section('page-title', 'Pending Vouchers')
@section('page-description', 'Review and approve vouchers awaiting admin action')
@section('breadcrumb', 'Vouchers / Pending')

@section('content')
<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Pending Vouchers</h2>
            <p class="text-gray-600 mt-1">Approve or reject vouchers that are waiting for review</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('admin.vouchers.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create Voucher
            </a>
            <a href="{{ route('admin.vouchers.index') }}"
               class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                <i class="fas fa-list mr-2"></i>All Vouchers
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold">Awaiting Approval</h3>
                <span class="text-sm text-gray-500">{{ $vouchers->total() }} pending</span>
            </div>
            <form action="{{ route('admin.vouchers.pending') }}" method="GET" class="w-full md:w-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search code, name, email..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <select name="station_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Stations</option>
                        @foreach($stations as $station)
                            <option value="{{ $station->id }}" {{ request('station_id') == $station->id ? 'selected' : '' }}>
                                {{ $station->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.vouchers.pending') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-semibold">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="p-6">
            @if($vouchers->count() > 0)
            <form id="bulkActionForm" action="{{ route('admin.vouchers.bulk-action') }}" method="POST" class="mb-4">
                @csrf
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="selectAll" class="h-4 w-4">
                        <span class="text-sm text-gray-700">Select all</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <select name="action" id="bulkAction" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Bulk action...</option>
                            <option value="approve">Approve</option>
                            <option value="reject">Reject</option>
                            <option value="expire">Expire</option>
                        </select>
                        <button type="submit"
                                class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-semibold"
                                onclick="return confirm('Apply bulk action to selected vouchers?')">
                            Apply
                        </button>
                    </div>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-3 pr-4">
                                <span class="sr-only">Select</span>
                            </th>
                            <th class="py-3 pr-4">Voucher</th>
                            <th class="py-3 pr-4">User</th>
                            <th class="py-3 pr-4">Station</th>
                            <th class="py-3 pr-4">Amount</th>
                            <th class="py-3 pr-4">Issued</th>
                            <th class="py-3 pr-4">Expires</th>
                            <th class="py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($vouchers as $voucher)
                        <tr>
                            <td class="py-3 pr-4">
                                <input type="checkbox" name="vouchers[]" form="bulkActionForm" value="{{ $voucher->id }}" class="voucher-checkbox h-4 w-4">
                            </td>
                            <td class="py-3 pr-4">
                                <div class="font-medium text-gray-900">{{ $voucher->code }}</div>
                                <div class="text-xs text-gray-500">#{{ $voucher->id }}</div>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="font-medium text-gray-900">{{ $voucher->user->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $voucher->user->email ?? 'N/A' }}</div>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="font-medium text-gray-900">{{ $voucher->fuelStation->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $voucher->fuelStation->city ?? '' }}</div>
                            </td>
                            <td class="py-3 pr-4 font-semibold text-gray-900">
                                ZAR {{ number_format($voucher->amount, 2) }}
                            </td>
                            <td class="py-3 pr-4 text-gray-600">
                                {{ $voucher->issued_at ? $voucher->issued_at->format('M d, Y H:i') : $voucher->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="py-3 pr-4 text-gray-600">
                                {{ $voucher->expires_at ? $voucher->expires_at->format('M d, Y H:i') : 'N/A' }}
                            </td>
                            <td class="py-3">
                                <div class="flex items-center space-x-2">
                                    <button type="button"
                                            class="px-3 py-1.5 bg-green-600 text-white rounded-md hover:bg-green-700 text-xs font-semibold open-risk-modal"
                                            data-approve-url="{{ route('admin.vouchers.approve', $voucher) }}"
                                            data-reject-url="{{ route('admin.vouchers.reject', $voucher) }}"
                                            data-voucher-code="{{ $voucher->code }}"
                                            data-voucher-amount="{{ $voucher->amount }}"
                                            data-user-name="{{ $voucher->user->name ?? 'Unknown' }}"
                                            data-user-email="{{ $voucher->user->email ?? 'N/A' }}"
                                            data-user-status="{{ $voucher->user->status ?? 'unknown' }}"
                                            data-credit-score="{{ $voucher->user->credit_score ?? '' }}"
                                            data-outstanding="{{ optional($voucher->user->wallet)->outstanding_balance ?? 0 }}"
                                            data-wallet-balance="{{ optional($voucher->user->wallet)->balance ?? 0 }}"
                                            data-available-credit="{{ $voucher->user->available_credit ?? 0 }}"
                                            data-total-leases="{{ $voucher->user->leases_count ?? 0 }}"
                                            data-defaulted-leases="{{ $voucher->user->defaulted_leases_count ?? 0 }}"
                                            data-total-vouchers="{{ $voucher->user->vouchers_count ?? 0 }}"
                                            data-redeemed-vouchers="{{ $voucher->user->redeemed_vouchers_count ?? 0 }}"
                                            data-wallet-txn-count="{{ $voucher->user->wallet_transactions_count ?? 0 }}"
                                            data-last-wallet-txn-at="{{ $voucher->user->last_wallet_txn_at ? \Carbon\Carbon::parse($voucher->user->last_wallet_txn_at)->format('M d, Y H:i') : 'N/A' }}"
                                            data-last-wallet-txn-amount="{{ $voucher->user->last_wallet_txn_amount ?? 0 }}"
                                            data-last-login="{{ $voucher->user->last_login_at ? $voucher->user->last_login_at->format('M d, Y H:i') : 'Never' }}"
                                            data-credit-decision-id="{{ $voucher->user->latestCreditDecision?->id ?? '' }}"
                                            data-credit-decision="{{ $voucher->user->latestCreditDecision?->decision ?? '' }}"
                                            data-credit-decision-score="{{ $voucher->user->latestCreditDecision?->score ?? '' }}"
                                            data-credit-decision-date="{{ $voucher->user->latestCreditDecision?->decided_at?->format('M d, Y H:i') ?? '' }}"
                                            data-credit-agent-decision="{{ data_get($voucher->user->latestCreditDecision?->explanation_json, 'agent_recommendation.recommendation.decision', '') }}"
                                            data-credit-agent-confidence="{{ data_get($voucher->user->latestCreditDecision?->explanation_json, 'agent_recommendation.recommendation.confidence', '') }}"
                                            data-credit-menu-url="{{ route('admin.credit-decisions.all', ['search' => $voucher->user->email ?? $voucher->user->id]) }}">
                                        Approve
                                    </button>
                                    <form action="{{ route('admin.vouchers.reject', $voucher) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="reason" value="Rejected by admin">
                                        <button type="submit"
                                                onclick="return confirm('Reject this voucher?')"
                                                class="px-3 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 text-xs font-semibold">
                                            Reject
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.vouchers.show', $voucher) }}"
                                       class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-xs font-semibold">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $vouchers->links() }}
            </div>
            @else
            <div class="text-center py-10">
                <i class="fas fa-check-circle text-green-300 text-4xl mb-4"></i>
                <p class="text-gray-600">No pending vouchers right now.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.voucher-checkbox');
    const bulkAction = document.getElementById('bulkAction');

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }

    if (bulkAction) {
        document.getElementById('bulkActionForm').addEventListener('submit', (e) => {
            const selected = Array.from(checkboxes).some(cb => cb.checked);
            if (!selected || !bulkAction.value) {
                e.preventDefault();
                window.showAdminAlert('Please select at least one voucher and a bulk action.');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('riskModal');
        const modalApproveForm = document.getElementById('riskApproveForm');
        const modalRejectForm = document.getElementById('riskRejectForm');
        const modalCloseButtons = document.querySelectorAll('[data-close-risk]');
        const openButtons = document.querySelectorAll('.open-risk-modal');
        const rejectReasonInput = document.getElementById('riskRejectReason');
        const riskLevelBadge = document.getElementById('riskLevel');

        function formatCurrency(amount) {
            const value = parseFloat(amount || 0);
            return `ZAR ${value.toFixed(2)}`;
        }

        function getRiskLevel(score, defaultRate) {
            if ((score !== null && score < 600) || defaultRate > 10) return 'High';
            if ((score !== null && score < 700) || defaultRate > 5) return 'Medium';
            return 'Low';
        }

        function applyRiskColor(level) {
            if (!riskLevelBadge) return;
            riskLevelBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold';
            if (level === 'High') {
                riskLevelBadge.classList.add('bg-red-100', 'text-red-700');
            } else if (level === 'Medium') {
                riskLevelBadge.classList.add('bg-yellow-100', 'text-yellow-700');
            } else {
                riskLevelBadge.classList.add('bg-green-100', 'text-green-700');
            }
        }

        openButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const score = btn.dataset.creditScore ? parseInt(btn.dataset.creditScore, 10) : null;
                const totalLeases = parseInt(btn.dataset.totalLeases || '0', 10);
                const defaultedLeases = parseInt(btn.dataset.defaultedLeases || '0', 10);
                const defaultRate = totalLeases > 0 ? (defaultedLeases / totalLeases) * 100 : 0;
                const riskLevel = getRiskLevel(score, defaultRate);
                const totalVouchers = parseInt(btn.dataset.totalVouchers || '0', 10);
                const redeemedVouchers = parseInt(btn.dataset.redeemedVouchers || '0', 10);
                const voucherUsageRate = totalVouchers > 0 ? (redeemedVouchers / totalVouchers) * 100 : 0;

                document.getElementById('riskVoucherCode').textContent = btn.dataset.voucherCode || 'N/A';
                document.getElementById('riskVoucherAmount').textContent = formatCurrency(btn.dataset.voucherAmount);
                document.getElementById('riskUserName').textContent = btn.dataset.userName || 'Unknown';
                document.getElementById('riskUserEmail').textContent = btn.dataset.userEmail || 'N/A';
                document.getElementById('riskUserStatus').textContent = btn.dataset.userStatus || 'unknown';
                document.getElementById('riskCreditScore').textContent = score !== null ? score : 'N/A';
                document.getElementById('riskOutstanding').textContent = formatCurrency(btn.dataset.outstanding);
                document.getElementById('riskAvailable').textContent = formatCurrency(btn.dataset.availableCredit);
                document.getElementById('riskDefaultRate').textContent = `${defaultRate.toFixed(1)}%`;
                document.getElementById('riskLastLogin').textContent = btn.dataset.lastLogin || 'Never';
                document.getElementById('riskWalletBalance').textContent = formatCurrency(btn.dataset.walletBalance);
                document.getElementById('riskWalletTxnCount').textContent = btn.dataset.walletTxnCount || '0';
                document.getElementById('riskLastWalletTxn').textContent = `${btn.dataset.lastWalletTxnAt || 'N/A'} • ${formatCurrency(btn.dataset.lastWalletTxnAmount)}`;
                document.getElementById('riskVoucherUsage').textContent = `${voucherUsageRate.toFixed(1)}% (${redeemedVouchers}/${totalVouchers})`;
                document.getElementById('riskLevel').textContent = riskLevel;
                document.getElementById('riskDecisionValue').textContent = (btn.dataset.creditDecision || 'N/A').toUpperCase();
                document.getElementById('riskDecisionScore').textContent = `(score: ${btn.dataset.creditDecisionScore || 'N/A'})`;
                document.getElementById('riskDecisionDate').textContent = btn.dataset.creditDecisionDate || 'N/A';
                document.getElementById('riskAgentDecision').textContent = (btn.dataset.creditAgentDecision || 'N/A').toUpperCase();
                document.getElementById('riskAgentConfidence').textContent = btn.dataset.creditAgentConfidence ? `(${btn.dataset.creditAgentConfidence}%)` : '';
                const creditMenuLink = document.getElementById('riskCreditMenuLink');
                if (creditMenuLink) {
                    creditMenuLink.href = btn.dataset.creditMenuUrl || '#';
                }
                applyRiskColor(riskLevel);

                if (modalApproveForm) modalApproveForm.action = btn.dataset.approveUrl;
                if (modalRejectForm) modalRejectForm.action = btn.dataset.rejectUrl;
                if (rejectReasonInput) rejectReasonInput.value = '';
                if (modal) modal.classList.remove('hidden');
            });
        });

        modalCloseButtons.forEach(btn => {
            btn.addEventListener('click', () => modal && modal.classList.add('hidden'));
        });

        if (modalRejectForm) {
            modalRejectForm.addEventListener('submit', (e) => {
                if (!rejectReasonInput || !rejectReasonInput.value.trim()) {
                    e.preventDefault();
                    window.showAdminAlert('Please provide a reason for rejection.');
                }
            });
        }
    });
</script>
<div id="riskModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold">Voucher Risk Review</h3>
            <button class="text-gray-500 hover:text-gray-700" data-close-risk>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Voucher</p>
                <p class="font-semibold text-gray-900" id="riskVoucherCode">N/A</p>
                <p class="text-sm text-gray-600 mt-1" id="riskVoucherAmount">ZAR 0.00</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Risk Level</p>
                <span id="riskLevel" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-700">Low</span>
                <p class="text-sm text-gray-600 mt-2">Default rate: <span id="riskDefaultRate">0%</span></p>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">User</p>
                <p class="font-semibold text-gray-900" id="riskUserName">Unknown</p>
                <p class="text-sm text-gray-600" id="riskUserEmail">N/A</p>
                <p class="text-xs text-gray-500 mt-2">Status: <span id="riskUserStatus">unknown</span></p>
                <p class="text-xs text-gray-500">Last login: <span id="riskLastLogin">Never</span></p>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Credit</p>
                <p class="text-sm text-gray-700">Credit score: <span class="font-semibold" id="riskCreditScore">N/A</span></p>
                <p class="text-sm text-gray-700 mt-1">Outstanding: <span class="font-semibold" id="riskOutstanding">ZAR 0.00</span></p>
                <p class="text-sm text-gray-700">Available credit: <span class="font-semibold" id="riskAvailable">ZAR 0.00</span></p>
                <p class="text-sm text-gray-700 mt-2">Wallet balance: <span class="font-semibold" id="riskWalletBalance">ZAR 0.00</span></p>
                <p class="text-sm text-gray-700">Wallet history: <span class="font-semibold" id="riskWalletTxnCount">0</span> txns</p>
                <p class="text-xs text-gray-500 mt-1" id="riskLastWalletTxn">N/A</p>
                <p class="text-sm text-gray-700 mt-2">Voucher usage: <span class="font-semibold" id="riskVoucherUsage">0% (0/0)</span></p>
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500">Latest Credit Decision</p>
                    <p class="text-sm text-gray-700">
                        <span class="font-semibold uppercase" id="riskDecisionValue">N/A</span>
                        <span class="text-xs text-gray-500" id="riskDecisionScore">(score: N/A)</span>
                    </p>
                    <p class="text-xs text-gray-500">At: <span id="riskDecisionDate">N/A</span></p>
                    <p class="text-xs text-gray-500 mt-1">Agent recommendation:
                        <span class="font-semibold uppercase" id="riskAgentDecision">N/A</span>
                        <span id="riskAgentConfidence"></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t flex flex-col md:flex-row md:items-center md:justify-end gap-3">
            <a id="riskCreditMenuLink" href="#"
               class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 text-sm font-semibold">
                Credit Menu
            </a>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200" data-close-risk>Cancel</button>
            <form id="riskRejectForm" action="#" method="POST" class="flex-1">
                @csrf
                <label class="block text-xs text-gray-500 mb-1" for="riskRejectReason">Reject reason</label>
                <div class="flex flex-col md:flex-row md:items-end gap-2">
                    <textarea name="reason"
                              id="riskRejectReason"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                              rows="2"
                              placeholder="Explain why this voucher is being rejected..."></textarea>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold whitespace-nowrap">
                        Reject
                    </button>
                </div>
            </form>
            <form id="riskApproveForm" action="#" method="POST">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    Approve Voucher
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
