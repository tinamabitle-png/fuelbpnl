{{-- [file name]: layouts/investor.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Bwiser Investor Portal</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border-right: 4px solid #3b82f6;
            color: #3b82f6;
        }
        .sidebar-link:hover {
            background-color: #f9fafb;
        }
        .notification-dot {
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            position: absolute;
            top: 12px;
            right: 12px;
        }
    </style>
    
    @yield('styles')
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('investor.dashboard') }}" class="text-xl font-bold text-gray-900">
                            <i class="fas fa-gas-pump text-blue-600 mr-2"></i>
                            Bwiser <span class="text-blue-600">Investor</span>
                        </a>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('investor.dashboard') }}" 
                           class="{{ request()->routeIs('investor.dashboard') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                        <a href="{{ route('investor.investments') }}" 
                           class="{{ request()->routeIs('investor.investments') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-folder-open mr-2"></i> Investments
                        </a>
                        <a href="{{ route('investor.opportunities') }}" 
                           class="{{ request()->routeIs('investor.opportunities') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-search-dollar mr-2"></i> Opportunities
                        </a>
                        <a href="{{ route('investor.statements') }}" 
                           class="{{ request()->routeIs('investor.statements') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-file-invoice-dollar mr-2"></i> Statements
                        </a>
                        <a href="{{ route('investor.profile') }}" 
                           class="{{ request()->routeIs('investor.profile') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-user-circle mr-2"></i> Profile
                        </a>
                    </div>
                </div>
                
                <!-- Right side -->
                <div class="flex items-center">
                    <!-- Notifications -->
                    <div class="relative ml-3">
                        <button onclick="toggleNotifications()" 
                                class="p-2 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100 focus:outline-none relative">
                            <i class="fas fa-bell"></i>
                            <span class="notification-dot"></span>
                        </button>
                        
                        <!-- Notifications Dropdown -->
                        <div id="notificationsDropdown" 
                             class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <h3 class="font-medium text-gray-900">Notifications</h3>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-money-bill-wave text-green-600 text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Interest Payment Received</p>
                                            <p class="text-xs text-gray-500">KES 12,450 from Lease #1234</p>
                                            <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-chart-line text-blue-600 text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">New Investment Opportunity</p>
                                            <p class="text-xs text-gray-500">Lease #5678 with 18% return</p>
                                            <p class="text-xs text-gray-400 mt-1">1 day ago</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-star text-yellow-600 text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Investor Score Updated</p>
                                            <p class="text-xs text-gray-500">Your score increased to 85/100</p>
                                            <p class="text-xs text-gray-400 mt-1">3 days ago</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="px-4 py-2 border-t border-gray-100">
                                <a href="#" class="text-sm text-blue-600 hover:text-blue-800">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="relative ml-3">
                        <button onclick="toggleUserMenu()" 
                                class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 focus:outline-none">
                            @if(auth()->user()->profile_photo)
                                <img class="h-8 w-8 rounded-full" src="{{ auth()->user()->profile_photo }}" alt="{{ auth()->user()->name }}">
                            @else
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                                    <span class="text-blue-600 font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                </div>
                            @endif
                            <span class="text-sm font-medium text-gray-700">{{ auth()->user()->investor->company_name }}</span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </button>
                        
                        <!-- User Dropdown -->
                        <div id="userDropdown" 
                             class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50">
                            <a href="{{ route('investor.profile') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-circle mr-2"></i> My Profile
                            </a>
                            <a href="{{ route('investor.statements') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-file-invoice-dollar mr-2"></i> Statements
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}" class="block">
                                @csrf
                                <button type="submit" 
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="sm:hidden" id="mobile-menu">
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('investor.dashboard') }}" 
                   class="{{ request()->routeIs('investor.dashboard') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <a href="{{ route('investor.investments') }}" 
                   class="{{ request()->routeIs('investor.investments') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-folder-open mr-2"></i> Investments
                </a>
                <a href="{{ route('investor.opportunities') }}" 
                   class="{{ request()->routeIs('investor.opportunities') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-search-dollar mr-2"></i> Opportunities
                </a>
                <a href="{{ route('investor.statements') }}" 
                   class="{{ request()->routeIs('investor.statements') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Statements
                </a>
                <a href="{{ route('investor.profile') }}" 
                   class="{{ request()->routeIs('investor.profile') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-user-circle mr-2"></i> Profile
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif
        
        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        @endif
        
        @yield('stats')
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-gray-600 text-sm">
                    © {{ date('Y') }} Bwiser Investor Portal. All rights reserved.
                </div>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-500 hover:text-gray-700 text-sm">
                        <i class="fas fa-question-circle mr-1"></i> Help Center
                    </a>
                    <a href="#" class="text-gray-500 hover:text-gray-700 text-sm">
                        <i class="fas fa-shield-alt mr-1"></i> Security
                    </a>
                    <a href="#" class="text-gray-500 hover:text-gray-700 text-sm">
                        <i class="fas fa-file-contract mr-1"></i> Terms
                    </a>
                    <a href="#" class="text-gray-500 hover:text-gray-700 text-sm">
                        <i class="fas fa-lock mr-1"></i> Privacy
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Toggle mobile menu
        function toggleMobileMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }
        
        // Toggle notifications dropdown
        function toggleNotifications() {
            document.getElementById('notificationsDropdown').classList.toggle('hidden');
        }
        
        // Toggle user menu dropdown
        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('hidden');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const notificationsBtn = document.querySelector('button[onclick="toggleNotifications()"]');
            const notificationsDropdown = document.getElementById('notificationsDropdown');
            const userBtn = document.querySelector('button[onclick="toggleUserMenu()"]');
            const userDropdown = document.getElementById('userDropdown');
            
            if (notificationsBtn && !notificationsBtn.contains(event.target) && 
                notificationsDropdown && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.add('hidden');
            }
            
            if (userBtn && !userBtn.contains(event.target) && 
                userDropdown && !userDropdown.contains(event.target)) {
                userDropdown.classList.add('hidden');
            }
        });
        
        // Auto-hide success/error messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.bg-green-50, .bg-red-50');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
    
    @yield('scripts')
</body>
</html>