@extends('layouts.admin')

@section('title', 'System Logs')
@section('page-title', 'System Logs')
@section('page-description', 'View application logs and error messages')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
<li class="breadcrumb-item active">System Logs</li>
@endsection

@section('content')
<div class="p-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Log Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Application Logs</h3>
                    <p class="text-gray-600 text-sm mt-1">Real-time system logs and error tracking</p>
                </div>
                <div class="flex items-center space-x-3">
                    <form action="{{ route('admin.settings.clear-logs') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('Are you sure you want to clear all logs? This cannot be undone.')"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">
                            <i class="fas fa-trash-alt mr-1"></i> Clear Logs
                        </button>
                    </form>
                    <button onclick="refreshLogs()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        <i class="fas fa-sync-alt mr-1"></i> Refresh
                    </button>
                    <button onclick="downloadLogs()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i> Download
                    </button>
                </div>
            </div>
            
            <!-- Log Filters -->
            <div class="mt-6 flex flex-wrap gap-3">
                <button onclick="filterLogs('all')" 
                        class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium">
                    All Logs
                </button>
                <button onclick="filterLogs('error')" 
                        class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium">
                    Errors Only
                </button>
                <button onclick="filterLogs('warning')" 
                        class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg text-sm font-medium">
                    Warnings
                </button>
                <button onclick="filterLogs('info')" 
                        class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium">
                    Info
                </button>
                <div class="ml-auto">
                    <input type="text" 
                           id="logSearch" 
                           placeholder="Search logs..." 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           onkeyup="searchLogs()">
                </div>
            </div>
        </div>
        
        <!-- Log Content -->
        <div class="p-6">
            <div class="bg-gray-900 text-gray-100 rounded-xl p-4 font-mono text-sm overflow-x-auto">
                <div id="logContent">
                    @if(empty($logs))
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-file-alt text-3xl mb-3 opacity-20"></i>
                            <p class="text-lg font-medium text-gray-300">No logs found</p>
                            <p class="text-gray-400 mt-1">Application logs will appear here</p>
                        </div>
                    @else
                        <pre class="whitespace-pre-wrap">{{ $logs }}</pre>
                    @endif
                </div>
            </div>
            
            <!-- Log Stats -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Logs</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalLogs">0</p>
                        </div>
                        <div class="p-3 bg-gray-100 rounded-lg">
                            <i class="fas fa-file-alt text-gray-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-red-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-red-600">Errors</p>
                            <p class="text-2xl font-bold text-red-700" id="errorLogs">0</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="fas fa-exclamation-circle text-red-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-yellow-600">Warnings</p>
                            <p class="text-2xl font-bold text-yellow-700" id="warningLogs">0</p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600">Last Updated</p>
                            <p class="text-2xl font-bold text-green-700">{{ now()->format('H:i') }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-clock text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize log stats
    function updateLogStats() {
        const logContent = document.getElementById('logContent').textContent;
        const totalLogs = (logContent.match(/\[.*?\]/g) || []).length;
        const errorLogs = (logContent.match(/ERROR|FATAL|CRITICAL/gi) || []).length;
        const warningLogs = (logContent.match(/WARNING/gi) || []).length;
        
        document.getElementById('totalLogs').textContent = totalLogs;
        document.getElementById('errorLogs').textContent = errorLogs;
        document.getElementById('warningLogs').textContent = warningLogs;
    }
    
    // Filter logs by type
    function filterLogs(type) {
        const logContent = document.getElementById('logContent');
        const lines = logContent.textContent.split('\n');
        let filteredLines = [];
        
        switch(type) {
            case 'error':
                filteredLines = lines.filter(line => 
                    line.includes('ERROR') || line.includes('FATAL') || line.includes('CRITICAL')
                );
                break;
            case 'warning':
                filteredLines = lines.filter(line => line.includes('WARNING'));
                break;
            case 'info':
                filteredLines = lines.filter(line => line.includes('INFO'));
                break;
            default:
                filteredLines = lines;
        }
        
        logContent.innerHTML = filteredLines.join('\n');
        updateLogStats();
    }
    
    // Search logs
    function searchLogs() {
        const searchTerm = document.getElementById('logSearch').value.toLowerCase();
        const logContent = document.getElementById('logContent');
        const lines = logContent.textContent.split('\n');
        const filteredLines = lines.filter(line => line.toLowerCase().includes(searchTerm));
        
        logContent.innerHTML = filteredLines.join('\n');
        updateLogStats();
    }
    
    // Refresh logs
    function refreshLogs() {
        fetch('{{ route("admin.settings.logs") }}')
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('logContent').innerHTML;
                document.getElementById('logContent').innerHTML = newContent;
                updateLogStats();
            });
    }
    
    // Download logs
    function downloadLogs() {
        const logContent = document.getElementById('logContent').textContent;
        const blob = new Blob([logContent], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `laravel-logs-${new Date().toISOString().split('T')[0]}.log`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    // Auto-refresh logs every 30 seconds
    setInterval(refreshLogs, 30000);
    
    // Initialize on load
    document.addEventListener('DOMContentLoaded', updateLogStats);
</script>
@endsection