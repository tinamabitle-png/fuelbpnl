@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Bwiser System Overview')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stats Cards -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Users</p>
                <p class="text-2xl font-bold">{{ $stats['total_users'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-gas-pump text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Active Stations</p>
                <p class="text-2xl font-bold">{{ $stats['active_stations'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-ticket-alt text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Pending Vouchers</p>
                <p class="text-2xl font-bold">{{ $stats['pending_vouchers'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Settlements</p>
                <p class="text-2xl font-bold">ZAR {{ number_format($stats['total_settlement_amount'], 2) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Charts -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Vouchers Issued (Last 30 Days)</h3>
        <canvas id="vouchersChart"></canvas>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Leases by Status</h3>
        <canvas id="leasesChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Vouchers -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Recent Vouchers</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recent_vouchers as $voucher)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.vouchers.show', $voucher->id) }}" class="text-blue-600 hover:underline">
                                    {{ $voucher->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $voucher->user->name }}</td>
                            <td class="px-4 py-3">ZAR {{ number_format($voucher->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($voucher->status == 'issued') bg-yellow-100 text-yellow-800
                                    @elseif($voucher->status == 'redeemed') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($voucher->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.vouchers.index') }}" class="text-blue-600 hover:underline">View All Vouchers</a>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Recent Users</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Score</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recent_users as $user)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 hover:underline">
                                    {{ $user->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $user->phone }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full" 
                                             style="width: {{ ($user->credit_score / 850) * 100 }}%"></div>
                                    </div>
                                    <span class="ml-2 text-sm">{{ $user->credit_score }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($user->status == 'active') bg-green-100 text-green-800
                                    @elseif($user->status == 'suspended') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">View All Users</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Vouchers Chart
    const vouchersCtx = document.getElementById('vouchersChart').getContext('2d');
    const vouchersChart = new Chart(vouchersCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($chart_data['vouchers_data']->toArray())) !!},
            datasets: [{
                label: 'Vouchers Issued',
                data: {!! json_encode(array_values($chart_data['vouchers_data']->toArray())) !!},
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Leases Chart
    const leasesCtx = document.getElementById('leasesChart').getContext('2d');
    const leasesChart = new Chart(leasesCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($chart_data['leases_by_status']->toArray())) !!},
            datasets: [{
                data: {!! json_encode(array_values($chart_data['leases_by_status']->toArray())) !!},
                backgroundColor: [
                    'rgb(34, 197, 94)', // green for active
                    'rgb(59, 130, 246)', // blue for completed
                    'rgb(239, 68, 68)', // red for defaulted
                    'rgb(156, 163, 175)', // gray for cancelled
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
</script>
@endpush
@endsection