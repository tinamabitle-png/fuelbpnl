<aside class="w-64 bg-gray-800 text-white">
    <div class="p-4">
        <h2 class="text-2xl font-bold">Fuel BNPL</h2>
        <p class="text-gray-400 text-sm">Admin Dashboard</p>
    </div>
    
    <nav class="mt-6">
        <div class="px-4 py-2 text-gray-400 text-sm font-semibold uppercase">
            Main
        </div>
        
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white 
                  {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Dashboard
        </a>
        
        <div class="px-4 py-2 text-gray-400 text-sm font-semibold uppercase mt-4">
            Management
        </div>
        
        <a href="{{ route('admin.users.index') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white 
                  {{ request()->routeIs('admin.users.*') ? 'bg-gray-700' : '' }}">
            <i class="fas fa-users mr-3"></i>
            Users
        </a>
        
        <a href="{{ route('admin.stations.index') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white 
                  {{ request()->routeIs('admin.stations.*') ? 'bg-gray-700' : '' }}">
            <i class="fas fa-gas-pump mr-3"></i>
            Fuel Stations
        </a>
        
        <a href="{{ route('admin.vouchers.index') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white 
                  {{ request()->routeIs('admin.vouchers.*') ? 'bg-gray-700' : '' }}">
            <i class="fas fa-ticket-alt mr-3"></i>
            Vouchers
            @if($pending_count = \App\Models\FuelVoucher::where('status', 'issued')->count())
                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">
                    {{ $pending_count }}
                </span>
            @endif
        </a>
        
        <a href="{{ route('admin.leases.index') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white 
                  {{ request()->routeIs('admin.leases.*') ? 'bg-gray-700' : '' }}">
            <i class="fas fa-file-contract mr-3"></i>
            Leases
        </a>
        
        <a href="{{ route('admin.settlements.index') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white 
                  {{ request()->routeIs('admin.settlements.*') ? 'bg-gray-700' : '' }}">
            <i class="fas fa-money-check-alt mr-3"></i>
            Direct Bank Deposits
            @if($pending_settlements = \App\Models\Settlement::where('status', 'pending')->count())
                <span class="ml-auto bg-yellow-500 text-white text-xs rounded-full px-2 py-1">
                    {{ $pending_settlements }}
                </span>
            @endif
        </a>
        
        <div class="px-4 py-2 text-gray-400 text-sm font-semibold uppercase mt-4">
            Reports
        </div>
        
        <a href="{{ route('admin.reports.financial') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-chart-bar mr-3"></i>
            Financial Reports
        </a>
        
        <a href="{{ route('admin.reports.risk') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            Risk Analysis
        </a>
        
        <div class="px-4 py-2 text-gray-400 text-sm font-semibold uppercase mt-4">
            System
        </div>
        
        <a href="{{ route('admin.settings') }}" 
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-cog mr-3"></i>
            Settings
        </a>
        
        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" 
                    class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
                <i class="fas fa-sign-out-alt mr-3"></i>
                Logout
            </button>
        </form>
    </nav>
</aside>