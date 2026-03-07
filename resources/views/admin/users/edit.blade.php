@extends('Layouts.admin')

@section('title', 'Edit User: ' . $user->name)
@section('page-title', 'Edit User')
@section('page-description', 'Update user information')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800">Users</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800">{{ $user->name }}</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>Edit</span>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-3xl mx-auto">
        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-yellow-600 to-yellow-700 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">Edit User</h2>
                        <p class="text-yellow-100 text-sm mt-1">Update {{ $user->name }}'s information</p>
                    </div>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-user-edit text-white text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-6">
                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- User Info -->
                    <div class="flex items-center space-x-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                            <span class="text-blue-600 font-bold text-lg">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-sm text-gray-500">ID: {{ $user->id }} • Joined {{ $user->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Personal Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name *
                                </label>
                                <input type="text" 
                                       name="name" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                       value="{{ old('name', $user->name) }}">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone Number *
                                </label>
                                <input type="tel" 
                                       name="phone" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <input type="email" 
                                   name="email"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror"
                                   value="{{ old('email', $user->email) }}">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Account Settings
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Role -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Role *
                                </label>
                                <select name="role" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('role') border-red-500 @enderror">
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" 
                                                {{ old('role', $user->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Status *
                                </label>
                                <select name="status" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                                    <option value="">Select Status</option>
                                    <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $user->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="flagged" {{ old('status', $user->status) == 'flagged' ? 'selected' : '' }}>Flagged</option>
                                    <option value="blocked" {{ old('status', $user->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Credit Score -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Credit Score (300-850)
                            </label>
                            <input type="number" 
                                   name="credit_score"
                                   min="300"
                                   max="850"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('credit_score') border-red-500 @enderror"
                                   value="{{ old('credit_score', $user->credit_score ?? 500) }}">
                            @error('credit_score')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Changing this will update the credit limit automatically.
                                Current limit: ZAR {{ number_format($user->creditLimit->limit ?? 0) }}
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 pt-6 border-t border-gray-200">
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.users.show', $user) }}" 
                               class="flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-eye mr-2"></i> View
                            </a>
                            <a href="{{ route('admin.users.index') }}" 
                               class="flex items-center text-gray-600 hover:text-gray-900">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Users
                            </a>
                        </div>
                        <div class="flex space-x-3">
                            <button type="reset" 
                                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                                Reset Changes
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2.5 bg-gradient-to-r from-yellow-600 to-yellow-700 text-white rounded-lg font-semibold hover:from-yellow-700 hover:to-yellow-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                                <i class="fas fa-save mr-2"></i> Update User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        @if($user->id != auth()->id())
        <div class="bg-red-50 border border-red-200 rounded-xl p-5 mt-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-red-900">Danger Zone</h4>
                    <p class="text-sm text-red-700">Irreversible actions</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Delete Form -->
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            onclick="return confirm('WARNING: This will permanently delete {{ $user->name }} and all associated data. Are you sure?')"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center">
                        <i class="fas fa-trash mr-2"></i> Delete User Permanently
                    </button>
                </form>

                <!-- Status Toggle -->
                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline ml-3">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('{{ $user->status === 'active' ? 'Suspend' : 'Activate' }} {{ $user->name }}?')"
                            class="px-4 py-2 {{ $user->status === 'active' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition-colors">
                        {{ $user->status === 'active' ? 'Suspend User' : 'Activate User' }}
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    // Show credit limit feedback when score changes
    document.querySelector('input[name="credit_score"]').addEventListener('input', function(e) {
        const value = parseInt(e.target.value) || 0;
        const feedback = document.getElementById('creditScoreFeedback');
        
        if (!feedback) {
            const feedbackEl = document.createElement('p');
            feedbackEl.id = 'creditScoreFeedback';
            feedbackEl.className = 'text-xs font-medium mt-1';
            this.parentNode.appendChild(feedbackEl);
        }
        
        const feedbackEl = document.getElementById('creditScoreFeedback');
        
        if (value >= 700) {
            feedbackEl.textContent = 'New limit will be: ZAR 50,000';
            feedbackEl.className = 'text-xs font-medium text-green-600 mt-1';
        } else if (value >= 600) {
            feedbackEl.textContent = 'New limit will be: ZAR 15,000';
            feedbackEl.className = 'text-xs font-medium text-green-500 mt-1';
        } else if (value >= 500) {
            feedbackEl.textContent = 'New limit will be: ZAR 8,000';
            feedbackEl.className = 'text-xs font-medium text-yellow-600 mt-1';
        } else if (value >= 400) {
            feedbackEl.textContent = 'New limit will be: ZAR 3,000';
            feedbackEl.className = 'text-xs font-medium text-orange-600 mt-1';
        } else if (value > 0) {
            feedbackEl.textContent = 'New limit will be: ZAR 1,000';
            feedbackEl.className = 'text-xs font-medium text-red-600 mt-1';
        } else {
            feedbackEl.textContent = '';
        }
    });

    // Auto-format phone number
    document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.startsWith('0')) {
            value = '+254' + value.substring(1);
        } else if (!value.startsWith('+')) {
            value = '+254' + value;
        }
        
        // Format: +254 XXX XXX XXX
        if (value.length > 4) {
            value = value.substring(0, 4) + ' ' + value.substring(4);
        }
        if (value.length > 8) {
            value = value.substring(0, 8) + ' ' + value.substring(8);
        }
        if (value.length > 12) {
            value = value.substring(0, 12) + ' ' + value.substring(12);
        }
        
        e.target.value = value.substring(0, 16);
    });
</script>
@endsection