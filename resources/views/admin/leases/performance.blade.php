@extends('layouts.admin')

@section('title', 'Performance Report')
@section('page-title', 'Lease Performance Report')
@section('page-description', 'Comprehensive analysis of lease portfolio performance')
@section('breadcrumb')
    <a href="{{ route('admin.leases.index') }}" class="text-blue-600 hover:text-blue-800">Leases</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>Performance Report</span>
@endsection

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Lease Performance Report</h2>
            <p class="text-gray-600 mt-1">Comprehensive analysis of lease portfolio performance and metrics</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <button onclick="exportReport()" 
                    class="px-5 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-semibold hover:from-green-700 hover:to-green-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-file-export mr-2"></i> Export Report
            </button>
            <a href="{{ route('admin.leases.index') }}" 
               class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm hover:shadow transition-all duration-300 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Leases
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-gradient-to-r from-gray-50 to-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('admin.leases.reports.performance') }}" method="GET" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Filter Report</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium">
                        Generate Report
                    </button>
                </div>
            </div>
            
            @if(request()->has('date_from') || request()->has('date_to'))
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <span class="text-sm text-gray-600">
                    @if(request('date_from') && request('date_to'))
                        Showing data from {{ request('date_from') }} to {{ request('date_to') }}
                    @elseif(request('date_from'))
                        Showing data from {{ request('date_from') }} onwards
                    @elseif(request('date_to'))
                        Showing data up to {{ request('date_to') }}
                    @endif
                </span>
                <a href="{{ route('admin.leases.reports.performance') }}" 
                   class="text-sm text-blue-600 hover:text-blue-800">
                    Clear Filters
                </a>
            </div>
            @endif
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Leases -->
        <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-600 text-sm font-semibold">Total Leases</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['total_leases']) }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                    <i class="fas fa-file-contract text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-600">
                Total lease agreements
            </div>
        </div>

        <!-- Total Portfolio -->
        <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-600 text-sm font-semibold">Total Portfolio</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">ZAR {{ number_format($metrics['total_revenue']) }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                    <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-600">
                Principal: ZAR {{ number_format($metrics['total_principal'], 2) }}
            </div>
        </div>

        <!-- Collection Rate -->
        <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-600 text-sm font-semibold">Collection Rate</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['collection_rate'], 1) }}%</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                    <i class="fas fa-percentage text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full bg-gradient-to-r from-purple-500 to-purple-600" 
                         style="width: {{ $metrics['collection_rate'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- Default Rate -->
        <div class="bg-gradient-to-br from-red-50 to-white p-5 rounded-2xl shadow-sm border border-red-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-600 text-sm font-semibold">Default Rate</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['default_rate'], 1) }}%</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-xl">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-600">
                Risk indicator
            </div>
        </div>
    </div>

    <!-- Detailed Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Financial Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Financial Summary</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Principal</span>
                    <span class="font-bold text-gray-900">ZAR {{ number_format($metrics['total_principal'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Interest</span>
                    <span class="font-bold text-red-600">ZAR {{ number_format($metrics['total_interest'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Revenue</span>
                    <span class="font-bold text-green-600">ZAR {{ number_format($metrics['total_revenue'], 2) }}</span>
                </div>
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Average Interest Rate</span>
                        <span class="font-bold text-purple-600">{{ number_format($metrics['avg_interest_rate'], 1) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Performance Metrics</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Average Term Days</span>
                        <span class="font-medium">{{ number_format($metrics['avg_term_days'], 0) }} days</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gradient-to-r from-blue-500 to-blue-600" 
                             style="width: {{ min(($metrics['avg_term_days'] / 365) * 100, 100) }}%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Collection Rate</span>
                        <span class="font-medium">{{ number_format($metrics['collection_rate'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-600" 
                             style="width: {{ $metrics['collection_rate'] }}%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Default Rate</span>
                        <span class="font-medium">{{ number_format($metrics['default_rate'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gradient-to-r from-red-500 to-red-600" 
                             style="width: {{ min($metrics['default_rate'] * 10, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Monthly Breakdown</h3>
            <button onclick="toggleChart()" 
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-chart-bar mr-2"></i> Toggle Chart
            </button>
        </div>
        
        <!-- Chart Container -->
        <div id="chartContainer" class="mb-6">
            <canvas id="monthlyChart" height="100"></canvas>
        </div>
        
        <!-- Monthly Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Month
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Number of Leases
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Principal
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Revenue
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Avg. Lease Size
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($monthlyData->sortByDesc(function($value, $key) { return $key; }) as $month => $data)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($data['count']) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">ZAR {{ number_format($data['principal'], 2) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-bold text-green-600">ZAR {{ number_format($data['revenue'], 2) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                ZAR {{ number_format($data['count'] > 0 ? $data['principal'] / $data['count'] : 0, 2) }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No monthly data available
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Risk Analysis -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Risk Analysis</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Risk Indicators -->
            <div>
                <h4 class="font-medium text-gray-900 mb-4">Risk Indicators</h4>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Default Rate</span>
                            <span class="font-medium">{{ number_format($metrics['default_rate'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                $riskColor = $metrics['default_rate'] > 10 ? 'bg-red-500' : 
                                            ($metrics['default_rate'] > 5 ? 'bg-yellow-500' : 'bg-green-500');
                                $riskWidth = min($metrics['default_rate'] * 10, 100);
                            @endphp
                            <div class="h-2 rounded-full {{ $riskColor }}" 
                                 style="width: {{ $riskWidth }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Collection Rate</span>
                            <span class="font-medium">{{ number_format($metrics['collection_rate'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                $collectionColor = $metrics['collection_rate'] < 70 ? 'bg-red-500' : 
                                                 ($metrics['collection_rate'] < 85 ? 'bg-yellow-500' : 'bg-green-500');
                            @endphp
                            <div class="h-2 rounded-full {{ $collectionColor }}" 
                                 style="width: {{ $metrics['collection_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recommendations -->
            <div>
                <h4 class="font-medium text-gray-900 mb-4">Recommendations</h4>
                <div class="space-y-3">
                    @if($metrics['default_rate'] > 10)
                    <div class="flex items-start">
                        <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mr-3 mt-0.5">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-red-900">High Default Rate</p>
                            <p class="text-xs text-gray-600">Consider stricter credit checks or lower limits</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($metrics['collection_rate'] < 70)
                    <div class="flex items-start">
                        <div class="w-6 h-6 rounded-full bg-yellow-100 flex items-center justify-center mr-3 mt-0.5">
                            <i class="fas fa-clock text-yellow-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-yellow-900">Low Collection Rate</p>
                            <p class="text-xs text-gray-600">Improve collection processes and follow-up</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($metrics['avg_interest_rate'] < 5)
                    <div class="flex items-start">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-0.5">
                            <i class="fas fa-percentage text-blue-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Low Interest Rates</p>
                            <p class="text-xs text-gray-600">Consider adjusting rates for better profitability</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="mt-8 bg-gradient-to-r from-gray-50 to-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Options</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="exportReport()" 
                    class="p-4 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors flex items-center">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                    <i class="fas fa-file-csv text-green-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">CSV Report</p>
                    <p class="text-xs text-gray-500">Detailed data in CSV format</p>
                </div>
            </button>
            
            <button onclick="exportChart()" 
                    class="p-4 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors flex items-center">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                    <i class="fas fa-chart-bar text-blue-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Chart Export</p>
                    <p class="text-xs text-gray-500">Download chart as image</p>
                </div>
            </button>
            
            <button onclick="printReport()" 
                    class="p-4 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors flex items-center">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                    <i class="fas fa-print text-purple-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Print Report</p>
                    <p class="text-xs text-gray-500">Printer-friendly version</p>
                </div>
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let monthlyChart = null;
    let chartVisible = true;
    
    // Initialize chart
    document.addEventListener('DOMContentLoaded', function() {
        initializeChart();
    });
    
    function initializeChart() {
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = @json($monthlyData);
        
        // Prepare data
        const months = Object.keys(monthlyData).sort().map(month => {
            return new Date(month + '-01').toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
        });
        
        const leaseCounts = Object.entries(monthlyData).sort(([a], [b]) => a.localeCompare(b)).map(([_, data]) => data.count);
        const revenues = Object.entries(monthlyData).sort(([a], [b]) => a.localeCompare(b)).map(([_, data]) => data.revenue);
        
        monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Number of Leases',
                        data: leaseCounts,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (ZAR)',
                        data: revenues,
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1,
                        yAxisID: 'y1',
                        type: 'line'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Leases'
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (ZAR)'
                        },
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'ZAR ' + value.toLocaleString();
                            }
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 0) {
                                    label += context.parsed.y + ' leases';
                                } else {
                                    label += 'ZAR ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Toggle chart visibility
    function toggleChart() {
        const chartContainer = document.getElementById('chartContainer');
        chartVisible = !chartVisible;
        
        if (chartVisible) {
            chartContainer.style.display = 'block';
            initializeChart();
        } else {
            chartContainer.style.display = 'none';
            if (monthlyChart) {
                monthlyChart.destroy();
            }
        }
    }
    
    // Export report
    function exportReport() {
        const dateFrom = "{{ request('date_from') }}";
        const dateTo = "{{ request('date_to') }}";
        
        let url = '/admin/leases/export?report=performance';
        if (dateFrom) url += `&date_from=${dateFrom}`;
        if (dateTo) url += `&date_to=${dateTo}`;
        
        window.open(url, '_blank');
    }
    
    // Export chart as image
    function exportChart() {
        if (!monthlyChart) {
            alert('Chart not available');
            return;
        }
        
        const link = document.createElement('a');
        link.download = 'lease-performance-chart-' + new Date().toISOString().split('T')[0] + '.png';
        link.href = monthlyChart.toBase64Image();
        link.click();
    }
    
    // Print report
    function printReport() {
        window.print();
    }
    
    // Print styles
    const style = document.createElement('style');
    style.textContent = `
        @media print {
            header, footer, .no-print, button, a, nav {
                display: none !important;
            }
            body {
                padding: 20px !important;
                font-size: 12px !important;
            }
            .container {
                max-width: 100% !important;
                padding: 0 !important;
            }
            table {
                font-size: 10px !important;
            }
            canvas {
                max-width: 100% !important;
                height: auto !important;
            }
        }
    `;
    document.head.appendChild(style);
</script>

<style>
    @media print {
        .bg-gradient-to-r, .shadow-lg, .shadow-sm, .hover\\:shadow-lg, .hover\\:shadow {
            box-shadow: none !important;
        }
        .border {
            border: 1px solid #e5e7eb !important;
        }
    }
</style>
@endsection