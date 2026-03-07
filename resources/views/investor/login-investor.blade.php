{{-- [file name]: auth/login-investor.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Login - Bwiser</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Login Card -->
        <div class="login-card rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-8 text-center">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-gas-pump text-blue-600 text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">Investor Portal</h1>
                <p class="text-blue-100 mt-2">Bwiser Investment Platform</p>
            </div>
            
            <!-- Login Form -->
            <div class="p-8">
                @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
                @endif
                
                @if(session('status'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                </div>
                @endif
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <!-- Email/Phone -->
                    <div class="mb-6">
                        <label for="login" class="block text-sm font-medium text-gray-700 mb-2">
                            Email or Phone Number
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   id="login" 
                                   name="login"
                                   value="{{ old('login') }}"
                                   required
                                   autofocus
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter email or phone">
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" 
                                   id="password" 
                                   name="password"
                                   required
                                   class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter password">
                            <button type="button" 
                                    onclick="togglePassword()"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i id="passwordIcon" class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="remember"
                                   name="remember"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800">
                            Forgot password?
                        </a>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-4 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign In to Investor Portal
                    </button>
                    
                    <!-- Hidden field to identify investor login -->
                    <input type="hidden" name="user_type" value="investor">
                </form>
                
                <!-- Divider -->
                <div class="my-8 flex items-center">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="flex-shrink mx-4 text-gray-500 text-sm">OR</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>
                
                <!-- OTP Login -->
                <button onclick="showOtpLogin()"
                        class="w-full border-2 border-blue-600 text-blue-600 py-3 px-4 rounded-xl font-semibold hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300 mb-4">
                    <i class="fas fa-mobile-alt mr-2"></i> Login with OTP
                </button>
                
                <!-- Register Link -->
                <div class="text-center mt-6">
                    <p class="text-gray-600">
                        Not an investor yet? 
                        <a href="{{ route('investor.register') }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                            Apply for investor account
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                <div class="text-center">
                    <p class="text-xs text-gray-500">
                        By signing in, you agree to our 
                        <a href="#" class="text-blue-600 hover:text-blue-800">Terms of Service</a> 
                        and 
                        <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Back to main site -->
        <div class="text-center mt-6">
            <a href="{{ url('/') }}" class="text-white hover:text-blue-200 text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to main website
            </a>
        </div>
    </div>
    
    <!-- OTP Login Modal -->
    <div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <form id="otpForm" method="POST" action="{{ route('login.otp') }}">
                @csrf
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">OTP Login</h3>
                        <button type="button" onclick="closeOtpModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input type="tel" 
                                       name="phone"
                                       required
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="+2547XXXXXXXX">
                            </div>
                        </div>
                        
                        <div id="otpSection" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Enter OTP
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="otp"
                                       maxlength="6"
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter 6-digit OTP">
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                OTP sent to your phone. Valid for 10 minutes.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" 
                                onclick="closeOtpModal()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                id="otpSubmit"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800">
                            Send OTP
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // OTP Login Modal
        function showOtpLogin() {
            document.getElementById('otpModal').classList.remove('hidden');
        }
        
        function closeOtpModal() {
            document.getElementById('otpModal').classList.add('hidden');
            document.getElementById('otpForm').reset();
            document.getElementById('otpSection').classList.add('hidden');
            document.getElementById('otpSubmit').textContent = 'Send OTP';
        }
        
        // Handle OTP form submission
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('otpSubmit');
            const otpSection = document.getElementById('otpSection');
            const phoneInput = document.querySelector('input[name="phone"]');
            
            if (otpSection.classList.contains('hidden')) {
                // First step: Request OTP
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
                submitButton.disabled = true;
                
                // Simulate API call
                setTimeout(() => {
                    otpSection.classList.remove('hidden');
                    submitButton.textContent = 'Verify OTP';
                    submitButton.disabled = false;
                    
                    // In real app, you would send OTP via SMS
                    console.log('OTP would be sent to:', phoneInput.value);
                }, 1500);
            } else {
                // Second step: Verify OTP
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Verifying...';
                submitButton.disabled = true;
                
                // In real app, you would verify OTP with backend
                setTimeout(() => {
                    // Simulate successful login
                    alert('OTP verified successfully! Redirecting...');
                    window.location.href = "{{ route('investor.dashboard') }}";
                }, 1500);
            }
        });
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOtpModal();
            }
        });
    </script>
</body>
</html>