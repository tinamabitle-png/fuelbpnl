<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Fuel BNPL Admin')</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --sidebar: #1f2937;
            --sidebar-hover: #374151;
            --sidebar-active: #111827;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Smooth transitions */
        .sidebar-link, button, a {
            transition: all 0.2s ease-in-out;
        }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(241, 241, 241, 0.3);
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.7);
        }
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Glass effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Smooth animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Hover effects */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Fix for main content height */
        .main-content-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .scrollable-content {
            flex: 1;
            overflow-y: auto;
            height: 0; /* This allows flexbox to control height */
        }

        #sidebar {
            width: 16rem;
            transition: width 0.25s ease, transform 0.3s ease;
        }

        #mainContent {
            transition: margin-left 0.25s ease;
        }

        @media (min-width: 1024px) {
            #mainContent {
                margin-left: 16rem;
            }

            body.sidebar-collapsed #sidebar {
                width: 5.5rem;
            }

            body.sidebar-collapsed #mainContent {
                margin-left: 5.5rem;
            }

            body.sidebar-collapsed.sidebar-hover-open #sidebar {
                width: 16rem;
            }

            body.sidebar-collapsed.sidebar-hover-open #mainContent {
                margin-left: 16rem;
            }

            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-logo-row {
                justify-content: center;
            }

            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-logo-text,
            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-collapsible,
            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-user-panel,
            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-quick-stats,
            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-footer-meta {
                display: none;
            }

            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-link {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-link-inner {
                justify-content: center;
            }

            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-link .sidebar-label,
            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-link .sidebar-badge,
            body.sidebar-collapsed:not(.sidebar-hover-open) .sidebar-logout .sidebar-label {
                display: none;
            }
        }

    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Mobile Sidebar Toggle -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobileSidebarToggle" class="p-3 bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-xl shadow-lg hover:from-blue-700 hover:to-blue-800 hover:shadow-xl transition-all duration-200">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <!-- Mobile Sidebar Overlay -->
    <div id="mobileOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>
    
    <div class="main-content-wrapper">
        <!-- Sidebar -->
<aside id="sidebar" class="fixed lg:fixed bg-gradient-to-b from-gray-900 to-gray-800 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 lg:z-0 h-screen shadow-2xl flex flex-col">
            <!-- Logo Area -->
            <div class="p-6 border-b border-gray-700/50">
                <div class="flex items-center justify-between sidebar-logo-row">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                            <i class="fas fa-gas-pump text-white text-lg"></i>
                        </div>
                        <div class="sidebar-logo-text">
                            <h2 class="text-xl font-bold tracking-tight">Fuel BNPL</h2>
                            <p class="text-xs text-gray-400 font-medium">Admin Console</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex items-center space-x-2 text-sm text-gray-400 bg-gray-800/50 px-3 py-2 rounded-lg sidebar-collapsible">
                    <i class="fas fa-circle text-green-500 text-xs animate-pulse"></i>
                    <span>System: <span class="text-green-400 font-medium">Active</span></span>
                </div>
            </div>
            
            <!-- User Profile -->
            <div class="p-4 border-b border-gray-700/50 bg-gradient-to-r from-gray-800/50 to-transparent mx-4 rounded-xl mt-2 sidebar-user-panel">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-gray-600 to-gray-700 rounded-full flex items-center justify-center shadow-md">
                            <i class="fas fa-user text-white text-lg"></i>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-gray-800"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium truncate text-white">{{ Auth::user()->name ?? 'Admin User' }}</p>
                        <p class="text-xs text-gray-400 truncate">Administrator</p>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ date('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto custom-scrollbar">
                @php
                    $currentRoute = request()->route()->getName();
                    $pendingSettlements = App\Models\Settlement::pending()->count();
                    $pendingVouchers = App\Models\FuelVoucher::where('status', 'issued')->count();
                    $feedbackCount = \Illuminate\Support\Facades\Schema::hasTable('admin_feedback')
                        ? App\Models\AdminFeedback::count()
                        : 0;
                @endphp
                
                @foreach([
                    ['route' => 'admin.dashboard', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'badge' => null],
                    ['route' => 'admin.users.index', 'icon' => 'fas fa-users', 'label' => 'Users', 'badge' => 'new'],
                    ['route' => 'admin.stations.index', 'icon' => 'fas fa-gas-pump', 'label' => 'Stations', 'badge' => null],
                    ['route' => 'admin.vouchers.index', 'icon' => 'fas fa-ticket-alt', 'label' => 'Vouchers', 'badge' => $pendingVouchers],
                    ['route' => 'admin.leases.index', 'icon' => 'fas fa-file-contract', 'label' => 'Leases', 'badge' => '3'],
                    ['route' => 'admin.settlements.index', 'icon' => 'fas fa-money-check-alt', 'label' => 'Settlements', 'badge' => $pendingSettlements],
                    ['route' => 'admin.repayments.ops', 'icon' => 'fas fa-repeat', 'label' => 'Repayment Ops', 'badge' => null],
                    ['route' => 'admin.feedback.index', 'icon' => 'fas fa-comments', 'label' => 'Feedback', 'badge' => $feedbackCount],
                    ['route' => 'admin.reports.index', 'icon' => 'fas fa-chart-bar', 'label' => 'Reports', 'badge' => null],
                    ['route' => 'admin.settings.index', 'icon' => 'fas fa-cog', 'label' => 'Settings', 'badge' => null],
                ] as $item)
                    @php
                        $isActive = str_starts_with($currentRoute, str_replace('.index', '', $item['route']));
                        $badgeColor = match($item['badge']) {
                            'new' => 'bg-gradient-to-r from-green-500 to-green-600',
                            'pending' => 'bg-gradient-to-r from-yellow-500 to-yellow-600',
                            default => 'bg-gradient-to-r from-blue-500 to-blue-600',
                        };
                    @endphp
                    
                    <a href="{{ route($item['route']) }}" 
                       title="{{ $item['label'] }}"
                       class="sidebar-link group flex items-center justify-between px-4 py-3 rounded-xl hover-lift
                              {{ $isActive ? 'bg-gradient-to-r from-blue-900/40 to-blue-800/20 border-l-4 border-blue-400 shadow-lg' : 'hover:bg-gray-700/30' }}">
                        <div class="flex items-center space-x-3 sidebar-link-inner">
                            <div class="relative">
                                <i class="{{ $item['icon'] }} {{ $isActive ? 'text-blue-400' : 'text-gray-400 group-hover:text-white' }} w-5"></i>
                                @if($isActive)
                                <div class="absolute -top-1 -right-1 w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                                @endif
                            </div>
                            <span class="sidebar-label font-medium {{ $isActive ? 'text-white' : 'text-gray-300 group-hover:text-white' }}">
                                {{ $item['label'] }}
                            </span>
                        </div>
                        @if($item['badge'])
                            <span class="sidebar-badge px-2 py-1 text-xs rounded-full font-bold min-w-[24px] text-center shadow-sm {{ $badgeColor }} text-white">
                                {{ $item['badge'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
                
                <!-- Quick Stats -->
                <div class="mt-8 p-4 bg-gradient-to-br from-gray-800/50 to-gray-900/30 rounded-xl border border-gray-700/50 sidebar-quick-stats">
                    <p class="text-xs text-gray-400 font-medium mb-2">Quick Stats</p>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400">Today's Revenue</span>
                            <span class="text-xs font-bold text-green-400">R12,450</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400">Active Users</span>
                            <span class="text-xs font-bold text-blue-400">847</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400">Avg. Response</span>
                            <span class="text-xs font-bold text-yellow-400">2.4s</span>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Bottom Actions -->
            <div class="p-4 border-t border-gray-700/50 bg-gradient-to-t from-gray-900 to-transparent">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="sidebar-logout w-full flex items-center justify-center space-x-3 px-4 py-3 rounded-xl text-gray-300 
                                   bg-gradient-to-r from-gray-800/50 to-gray-700/30 hover:from-red-900/30 hover:to-red-800/20 
                                   hover:text-white transition-all duration-200 hover-lift group">
                        <i class="fas fa-sign-out-alt transform group-hover:translate-x-1 transition-transform"></i>
                        <span class="sidebar-label font-medium">Logout</span>
                    </button>
                </form>
                <div class="mt-3 flex justify-between items-center text-xs text-gray-500 sidebar-footer-meta">
                    <span>v2.1.0</span>
                    <span>{{ date('h:i A') }}</span>
                </div>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <div id="mainContent" class="content-area">
            <!-- Top Navigation -->
            <header class="bg-white border-b border-gray-200 px-6 py-4 shadow-sm sticky top-0 z-30">
                <div class="flex items-center justify-between">
                    <div class="animate-fade-in">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-8 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                                <p class="text-gray-600 text-sm mt-1 flex items-center">
                                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                    @yield('page-description', 'Welcome back, Administrator')
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <!-- Notifications -->
                        <button class="relative p-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift group">
                            <i class="fas fa-bell text-lg"></i>
                            <span class="absolute top-2 right-2 w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-100 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        </button>
                        
                        <!-- Search -->
                        <div class="relative hidden md:block">
                            <input type="text" 
                                   placeholder="Search users, vouchers, stations..." 
                                   class="pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-72 shadow-sm hover:shadow transition-shadow duration-200">
                            <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="relative group">
                            <button class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium 
                                          hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-200 
                                          flex items-center space-x-2 hover-lift">
                                <i class="fas fa-bolt"></i>
                                <span>Quick Actions</span>
                                <i class="fas fa-chevron-down ml-1 text-sm transform group-hover:rotate-180 transition-transform"></i>
                            </button>
                            <div class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl border border-gray-200 
                                      hidden group-hover:block animate-fade-in z-20 overflow-hidden">
                                <div class="p-2 space-y-1">
                                    <a href="{{ route('admin.users.create') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg group">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-blue-50 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-user-plus text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">New User</p>
                                            <p class="text-xs text-gray-500">Create user account</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('admin.vouchers.index', ['status' => 'issued']) }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg group">
                                        <div class="w-8 h-8 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-ticket-alt text-yellow-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">Review Vouchers</p>
                                            <p class="text-xs text-gray-500">{{ $pendingVouchers }} pending</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('admin.settlements.create') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg group">
                                        <div class="w-8 h-8 bg-gradient-to-br from-green-100 to-green-50 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-plus text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">New Settlement</p>
                                            <p class="text-xs text-gray-500">Create payment batch</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Breadcrumb -->
                <nav class="mt-4 flex items-center text-sm text-gray-600 bg-gray-50 px-4 py-2 rounded-lg animate-fade-in">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 flex items-center">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    @hasSection('breadcrumb')
                        <i class="fas fa-chevron-right mx-3 text-xs text-gray-400"></i>
                        <div class="flex items-center">
                            @yield('breadcrumb')
                        </div>
                    @endif
                    <div class="ml-auto flex items-center space-x-2">
                        <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full">
                            <i class="fas fa-database mr-1"></i> Online
                        </span>
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">
                            <i class="fas fa-bolt mr-1"></i> Fast
                        </span>
                    </div>
                </nav>
            </header>
            
            <!-- Scrollable Main Content -->
            <main class="scrollable-content p-6 bg-gradient-to-br from-gray-50 via-white to-blue-50/20">
                <div class="max-w-7xl mx-auto">
                    <!-- Flash Messages -->
                    <div class="mb-6 space-y-3">
                        @if(session('success'))
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-green-100 
                                      border-l-4 border-green-500 rounded-xl shadow-sm animate-fade-in hover-lift">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-green-800 font-medium">{{ session('success') }}</p>
                                        <p class="text-green-600 text-sm mt-1">Successfully completed</p>
                                    </div>
                                </div>
                                <button onclick="this.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.remove(), 300)" 
                                        class="text-green-600 hover:text-green-800 p-2 hover:bg-green-100 rounded-lg">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                        
                        @if(session('error'))
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-red-50 to-red-100 
                                      border-l-4 border-red-500 rounded-xl shadow-sm animate-fade-in hover-lift">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-red-800 font-medium">{{ session('error') }}</p>
                                        <p class="text-red-600 text-sm mt-1">Please review and try again</p>
                                    </div>
                                </div>
                                <button onclick="this.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.remove(), 300)" 
                                        class="text-red-600 hover:text-red-800 p-2 hover:bg-red-100 rounded-lg">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                        
                        @if(session('info'))
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 
                                      border-l-4 border-blue-500 rounded-xl shadow-sm animate-fade-in hover-lift">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-blue-800 font-medium">{{ session('info') }}</p>
                                        <p class="text-blue-600 text-sm mt-1">Information notification</p>
                                    </div>
                                </div>
                                <button onclick="this.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.remove(), 300)" 
                                        class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-100 rounded-lg">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Stats Bar (Optional - can be overridden) -->
                    @hasSection('stats')
                        @yield('stats')
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                            <div class="bg-gradient-to-br from-white to-gray-50 p-6 rounded-2xl shadow-sm border border-gray-200 hover-lift">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Total Users</p>
                                        <p class="text-3xl font-bold text-gray-800 mt-1">1,248</p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                                        <i class="fas fa-users text-blue-500 text-2xl"></i>
                                    </div>
                                </div>
                                <div class="mt-4 text-sm font-medium flex items-center">
                                    <span class="text-green-600 bg-green-50 px-3 py-1 rounded-full">
                                        <i class="fas fa-arrow-up mr-1"></i> 12%
                                    </span>
                                    <span class="text-gray-500 ml-3">from last month</span>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-white to-gray-50 p-6 rounded-2xl shadow-sm border border-gray-200 hover-lift">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Active Leases</p>
                                        <p class="text-3xl font-bold text-gray-800 mt-1">324</p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl">
                                        <i class="fas fa-file-contract text-green-500 text-2xl"></i>
                                    </div>
                                </div>
                                <div class="mt-4 text-sm font-medium flex items-center">
                                    <span class="text-green-600 bg-green-50 px-3 py-1 rounded-full">
                                        <i class="fas fa-arrow-up mr-1"></i> 8%
                                    </span>
                                    <span class="text-gray-500 ml-3">from last month</span>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-white to-gray-50 p-6 rounded-2xl shadow-sm border border-gray-200 hover-lift">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Revenue</p>
                                        <p class="text-3xl font-bold text-gray-800 mt-1">R42.8K</p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl">
                                        <i class="fas fa-dollar-sign text-purple-500 text-2xl"></i>
                                    </div>
                                </div>
                                <div class="mt-4 text-sm font-medium flex items-center">
                                    <span class="text-green-600 bg-green-50 px-3 py-1 rounded-full">
                                        <i class="fas fa-arrow-up mr-1"></i> 23%
                                    </span>
                                    <span class="text-gray-500 ml-3">from last month</span>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-white to-gray-50 p-6 rounded-2xl shadow-sm border border-gray-200 hover-lift">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Pending Actions</p>
                                        <p class="text-3xl font-bold text-gray-800 mt-1">16</p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl">
                                        <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                                    </div>
                                </div>
                                <div class="mt-4 text-sm font-medium flex items-center">
                                    <span class="text-red-600 bg-red-50 px-3 py-1 rounded-full">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Review
                                    </span>
                                    <span class="text-gray-500 ml-3">requires attention</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Page Content -->
                    <div class="animate-fade-in">
                        @yield('content')
                    </div>
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="max-w-7xl mx-auto">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="text-gray-600 text-sm">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-gas-pump text-white text-xs"></i>
                                </div>
                                <p>© {{ date('Y') }} Fuel BNPL Admin. All rights reserved.</p>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-clock mr-1"></i> Last login: {{ Auth::user()->last_login_at?->format('M d, Y h:i A') ?? 'Today' }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-6 mt-3 md:mt-0">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-xs text-gray-600">API Status: <span class="font-bold text-green-600">Online</span></span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <a href="#" class="text-gray-500 hover:text-blue-600 text-sm transition-colors">
                                    <i class="fas fa-question-circle mr-1"></i> Help
                                </a>
                                <a href="{{ route('admin.settings.index') }}" class="text-gray-500 hover:text-blue-600 text-sm transition-colors">
                                    <i class="fas fa-cog mr-1"></i> Settings
                                </a>
                                <a href="#" class="text-gray-500 hover:text-blue-600 text-sm transition-colors">
                                    <i class="fas fa-shield-alt mr-1"></i> Privacy
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all duration-300 scale-95">
            <div class="p-6">
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-gradient-to-br from-red-50 to-red-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Delete</h3>
                    <p class="text-gray-600 mb-4" id="deleteUserName">Are you sure?</p>
                    <p class="text-sm text-gray-500 mb-6 px-4">
                        This action cannot be undone. All associated data will be permanently removed from the system.
                    </p>
                </div>
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" 
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium">
                        Cancel
                    </button>
                    <form id="deleteForm" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl 
                                       hover:from-red-700 hover:to-red-800 shadow-md hover:shadow-lg transition-all duration-200 font-medium">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Sidebar toggles
        const mobileToggle = document.getElementById('mobileSidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

        function initializeDesktopSidebarState() {
            if (!isDesktop()) {
                document.body.classList.remove('sidebar-collapsed');
                document.body.classList.remove('sidebar-hover-open');
                return;
            }
            document.body.classList.add('sidebar-collapsed');
        }

        initializeDesktopSidebarState();

        window.addEventListener('resize', initializeDesktopSidebarState);

        if (sidebar) {
            sidebar.addEventListener('mouseenter', () => {
                if (isDesktop()) {
                    document.body.classList.add('sidebar-hover-open');
                }
            });
            sidebar.addEventListener('mouseleave', () => {
                if (isDesktop()) {
                    document.body.classList.remove('sidebar-hover-open');
                }
            });
        }
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                mobileOverlay.classList.toggle('hidden');
                document.body.style.overflow = sidebar.classList.contains('-translate-x-full') ? 'auto' : 'hidden';
            });
            
            mobileOverlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            });
        }
        
        // Auto-dismiss flash messages after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('[class*="bg-gradient-to-r"]').forEach(el => {
                if (el.closest('.mb-6')) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-10px)';
                    setTimeout(() => el.remove(), 300);
                }
            });
        }, 5000);
        
        // Active link highlighting
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname;
            document.querySelectorAll('.sidebar-link').forEach(link => {
                if (link.href.includes(currentPath) && currentPath !== '/') {
                    link.classList.add('bg-gradient-to-r', 'from-blue-900/40', 'to-blue-800/20', 'border-l-4', 'border-blue-400', 'shadow-lg');
                    link.querySelector('i').classList.add('text-blue-400');
                }
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[type="text"]')?.focus();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                closeDeleteModal();
                if (!isDesktop() && sidebar && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                    mobileOverlay?.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
            }
        });
        
        // Delete modal functions
        function showDeleteModal(userId, userName) {
            document.getElementById('deleteUserName').textContent = `Delete "${userName}"?`;
            document.getElementById('deleteForm').action = `/admin/users/${userId}`;
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.scale-95').classList.remove('scale-95');
            }, 10);
        }
        
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.querySelector('.scale-95').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
        
        // Initialize tooltips
        document.querySelectorAll('[title]').forEach(el => {
            el.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'fixed z-50 px-3 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 transition-opacity duration-200';
                tooltip.textContent = e.target.title;
                document.body.appendChild(tooltip);
                
                const rect = e.target.getBoundingClientRect();
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 10}px`;
                tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
                
                setTimeout(() => tooltip.classList.remove('opacity-0'), 10);
                
                e.target._tooltip = tooltip;
            });
            
            el.addEventListener('mouseleave', (e) => {
                if (e.target._tooltip) {
                    e.target._tooltip.remove();
                    delete e.target._tooltip;
                }
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>
