@extends('layouts.admin')

@section('title', 'Create New User')
@section('page-title', 'Create New User')
@section('page-description', 'Add a new user to the system')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800">Users</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>Create New</span>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-3xl mx-auto">
        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">Create New User</h2>
                        <p class="text-blue-100 text-sm mt-1">Fill in the user's details below</p>
                    </div>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-user-plus text-white text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-6">
                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
                    @csrf

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
                                       placeholder="John Doe"
                                       value="{{ old('name') }}">
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
                                       placeholder="+27 000 6656"
                                       value="{{ old('phone') }}">
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
                                   placeholder="john@example.com"
                                   value="{{ old('email') }}">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Account Security -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Account Security
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Password *
                                </label>
                                <div class="relative">
                                    <input type="password" 
                                           name="password" 
                                           required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror"
                                           placeholder="••••••••">
                                </div>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm Password *
                                </label>
                                <input type="password" 
                                       name="password_confirmation" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="••••••••">
                            </div>
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
                                        <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
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
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="flagged" {{ old('status') == 'flagged' ? 'selected' : '' }}>Flagged</option>
                                    <option value="blocked" {{ old('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
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
                                   placeholder="500"
                                   value="{{ old('credit_score', 500) }}">
                            <p class="mt-1 text-xs text-gray-500">
                                Leave blank or enter 500 for default. Auto-calculates credit limit.
                            </p>
                            @error('credit_score')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 pt-6 border-t border-gray-200">
                        <div>
                            <a href="{{ route('admin.users.index') }}" 
                               class="flex items-center text-gray-600 hover:text-gray-900">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Users
                            </a>
                        </div>
                        <div class="flex space-x-3">
                            <button type="reset" 
                                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                                Clear Form
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                                <i class="fas fa-user-plus mr-2"></i> Create User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mt-6">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                <div>
                    <h4 class="font-medium text-blue-900">What happens when you create a user?</h4>
                    <ul class="text-sm text-blue-800 mt-2 space-y-1">
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-green-500 mt-0.5"></i>
                            <span>A wallet with ZAR 0 balance will be created</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-green-500 mt-0.5"></i>
                            <span>Credit limit will be auto-calculated based on credit score</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-green-500 mt-0.5"></i>
                            <span>User can immediately start using BNPL services</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-green-500 mt-0.5"></i>
                            <span>Default currency is ZAR (South African Rands)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple form validation feedback
    document.querySelectorAll('input, select').forEach(element => {
        element.addEventListener('blur', function() {
            if (this.value.trim() === '' && this.hasAttribute('required')) {
                this.classList.add('border-red-300');
            } else {
                this.classList.remove('border-red-300');
            }
        });
    });

    // Show password requirements on focus
    document.querySelector('input[name="password"]').addEventListener('focus', function() {
        const helpText = document.createElement('p');
        helpText.className = 'text-xs text-gray-500 mt-1';
        helpText.innerHTML = 'Password must be at least 8 characters long';
        
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('text-xs')) {
            this.parentNode.insertBefore(helpText, this.nextElementSibling);
        }
    });

    // Auto-format phone number
    document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.startsWith('0')) {
            value = '+27' + value.substring(1);
        } else if (!value.startsWith('+')) {
            value = '+27' + value;
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
        
        e.target.value = value.substring(0, 16); // Limit length
    });

    // Credit score feedback
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
            feedbackEl.textContent = 'Excellent - Credit limit: ZAR 50,000';
            feedbackEl.className = 'text-xs font-medium text-green-600 mt-1';
        } else if (value >= 600) {
            feedbackEl.textContent = 'Good - Credit limit: ZAR 15,000';
            feedbackEl.className = 'text-xs font-medium text-green-500 mt-1';
        } else if (value >= 500) {
            feedbackEl.textContent = 'Fair - Credit limit: ZAR 8,000';
            feedbackEl.className = 'text-xs font-medium text-yellow-600 mt-1';
        } else if (value >= 400) {
            feedbackEl.textContent = 'Poor - Credit limit: ZAR 3,000';
            feedbackEl.className = 'text-xs font-medium text-orange-600 mt-1';
        } else if (value > 0) {
            feedbackEl.textContent = 'Very Poor - Credit limit: ZAR 1,000';
            feedbackEl.className = 'text-xs font-medium text-red-600 mt-1';
        } else {
            feedbackEl.textContent = '';
        }
    });
</script>
@endsection