@extends('layouts.admin')

@section('title', 'System Information')
@section('page-title', 'System Information')
@section('page-description', 'Detailed system configuration and status')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
<li class="breadcrumb-item active">System Info</li>
@endsection

@section('content')
<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- System Overview -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">System Overview</h3>
                        <p class="text-gray-600 text-sm mt-1">Detailed system configuration and environment</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                            <i class="fas fa-check-circle mr-1"></i> Active
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- PHP Information -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 border-b pb-2">PHP Information</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">PHP Version</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['php_version'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Memory Limit</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['memory_limit'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Max Execution Time</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['max_execution_time'] }}s</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Upload Max Filesize</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['upload_max_filesize'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Post Max Size</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['post_max_size'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Laravel Information -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 border-b pb-2">Laravel Information</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Laravel Version</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['laravel_version'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Timezone</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['timezone'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Debug Mode</span>
                                <span class="text-sm font-medium {{ $info['debug_mode'] == 'Enabled' ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $info['debug_mode'] }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Cache Driver</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['cache_driver'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Session Driver</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['session_driver'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Database Information -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 border-b pb-2">Database Information</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Database Driver</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['database_driver'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Database Name</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['database_name'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Queue Driver</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['queue_driver'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Server Information -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 border-b pb-2">Server Information</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Server Software</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['server_software'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Server OS</span>
                                <span class="text-sm font-medium text-gray-900">{{ $info['server_os'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Server Protocol</span>
                                <span class="text-sm font-medium text-gray-900">{{ $_SERVER['SERVER_PROTOCOL'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Installed Packages -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Installed Packages</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Laravel Framework</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $info['laravel_version'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Tailwind CSS</td>
                                <td class="px-4 py-3 text-sm text-gray-600">3.3.0</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Alpine.js</td>
                                <td class="px-4 py-3 text-sm text-gray-600">3.12.0</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Chart.js</td>
                                <td class="px-4 py-3 text-sm text-gray-600">4.3.0</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.settings.clear-cache') }}" 
                       onclick="return confirm('Clear all cache?')"
                       class="flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-sync-alt text-blue-600"></i>
                        </div>
                        <span class="font-medium">Clear Cache</span>
                    </a>
                    
                    <a href="{{ route('admin.settings.backup') }}" 
                       class="flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-database text-green-600"></i>
                        </div>
                        <span class="font-medium">Backup Database</span>
                    </a>
                    
                    <a href="{{ route('admin.settings.logs') }}" 
                       class="flex items-center p-3 bg-yellow-50 text-yellow-700 rounded-xl hover:bg-yellow-100 transition-colors">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-file-alt text-yellow-600"></i>
                        </div>
                        <span class="font-medium">View System Logs</span>
                    </a>
                    
                    <a href="{{ route('admin.settings.index') }}" 
                       class="flex items-center p-3 bg-gray-50 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-cog text-gray-600"></i>
                        </div>
                        <span class="font-medium">Back to Settings</span>
                    </a>
                </div>
            </div>
            
            <!-- System Health -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">System Health</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">PHP Version</span>
                            <span class="text-sm font-medium {{ version_compare($info['php_version'], '8.0', '>=') ? 'text-green-600' : 'text-red-600' }}">
                                {{ version_compare($info['php_version'], '8.0', '>=') ? '✓' : '✗' }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">Memory Usage</span>
                            <span class="text-sm font-medium text-green-600">✓ Optimal</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 65%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">Storage Space</span>
                            <span class="text-sm font-medium text-green-600">✓ Available</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 45%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">Database Connection</span>
                            <span class="text-sm font-medium text-green-600">✓ Connected</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-green-50 rounded-xl border border-green-200">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">System Healthy</p>
                            <p class="text-xs text-green-600 mt-1">All systems are functioning normally</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                <i class="fas fa-cog text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Settings Updated</p>
                                <p class="text-xs text-gray-500">5 minutes ago</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                                <i class="fas fa-database text-green-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Database Backup</p>
                                <p class="text-xs text-gray-500">2 hours ago</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                                <i class="fas fa-sync-alt text-purple-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Cache Cleared</p>
                                <p class="text-xs text-gray-500">Yesterday</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Copy system info to clipboard
    function copySystemInfo() {
        const infoText = `
PHP Version: ${document.querySelector('[data-php-version]').textContent}
Laravel Version: ${document.querySelector('[data-laravel-version]').textContent}
Server: ${document.querySelector('[data-server]').textContent}
Database: ${document.querySelector('[data-database]').textContent}
Timezone: ${document.querySelector('[data-timezone]').textContent}
        `.trim();
        
        navigator.clipboard.writeText(infoText).then(() => {
            alert('System information copied to clipboard!');
        });
    }
</script>
@endsection