<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Bwiser</title>
    <meta name="description" content="Secure sign-in portal for Bwiser platform users.">
    <meta name="robots" content="noindex,nofollow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Login | Bwiser">
    <meta property="og:description" content="Secure sign-in portal for Bwiser platform users.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/brand-logo.png') }}">
    <meta name="twitter:card" content="summary">
    @php
        $hasViteAssets = is_file(public_path('hot')) || is_file(public_path('build/manifest.json'));
    @endphp
    @if($hasViteAssets)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}">
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .driver-popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.58);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 60;
            backdrop-filter: blur(6px);
        }

        .driver-popup-card {
            width: min(100%, 420px);
            border-radius: 1.5rem;
            background: #ffffff;
            box-shadow: 0 28px 60px -30px rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 1.5rem;
        }

        .driver-popup-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 0.4rem 0.7rem;
        }

        .driver-popup-close {
            border: 0;
            background: #1d4ed8;
            color: #fff;
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        .playstore-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #000;
            border-radius: 9999px;
            background-color: #000;
            padding: 0.625rem 1.5rem;
            text-align: center;
            color: #fff;
            outline: 0;
            transition: all .2s ease;
            text-decoration: none;
        }

        .playstore-button:hover {
            background-color: transparent;
            color: #000;
        }

        .playstore-button .playstore-icon {
            height: 1.5rem;
            width: 1.5rem;
        }

        .playstore-button .texts {
            margin-left: 1rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1;
        }

        .playstore-button .text-1 {
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            line-height: 1rem;
        }

        .playstore-button .text-2 {
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100">
    @if(session('driver_registered_popup'))
        <div id="driverRegisteredPopup" class="driver-popup-overlay">
            <div class="driver-popup-card">
                <div class="driver-popup-badge">
                    <i class="fas fa-car-side"></i>
                    Driver Registered
                </div>
                <h3 class="mt-4 text-xl font-semibold text-slate-900">{{ data_get(session('driver_registered_popup'), 'name', 'New driver') }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ data_get(session('driver_registered_popup'), 'message') }}</p>
                <p class="mt-2 text-sm text-slate-500">You’ve also been added to the latest drivers stack on the welcome page.</p>
                <button type="button" class="driver-popup-close mt-5" onclick="document.getElementById('driverRegisteredPopup')?.remove();">
                    Continue to Sign In
                </button>
            </div>
        </div>
    @endif
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="{{ asset('images/brand-logo.png') }}?v={{ filemtime(public_path('images/brand-logo.png')) }}"
                         alt="Bwiser logo"
                         class="h-20 w-20 rounded-2xl shadow-sm object-cover">
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Bwiser</h2>
                <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
            </div>

            <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
                <form class="space-y-6" action="{{ route('login') }}" method="POST">
                    @csrf

                    @if($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">{{ $errors->first() }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('status'))
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">{{ session('status') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                   value="{{ old('email') }}"
                                   class="pl-10 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="you@example.com">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                   class="pl-10 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="hidden" name="remember" value="0">
                            <input id="remember" name="remember" type="checkbox"
                                   value="1"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-900">Remember me</label>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign in
                        </button>
                    </div>
                </form>

                @if(config('services.google.enabled') && config('services.google.client_id'))
                    <div class="mt-4">
                        @include('partials.google-button', [
                            'label' => 'Continue with Google',
                            'class' => 'w-full'
                        ])
                        <p class="text-xs text-slate-500 mt-1 text-center">If this is a new account, you will complete the same role-specific registration fields next.</p>
                    </div>
                @endif

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a href="{{ route('register.driver') }}"
                       class="inline-flex justify-center items-center py-2 px-4 border border-slate-300 rounded-md text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50">
                        Register Driver
                    </a>
                    @if(config('services.registration.public_merchant_enabled'))
                        <a href="{{ route('register.merchant') }}"
                           class="inline-flex justify-center items-center py-2 px-4 border border-slate-300 rounded-md text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50">
                            Register Merchant
                        </a>
                    @endif
                </div>

	                {{-- Play Store button hidden until the mobile app is published. --}}
	            </div>

	            <div class="mt-8 text-center">
	                <p class="text-sm text-gray-600">
	                    © {{ date('Y') }} Bwiser. All rights reserved.
                </p>
                <div class="mt-2">
                    @include('partials.legal-links', ['class' => 'flex flex-wrap justify-center items-center gap-2 text-xs text-gray-500'])
                </div>
            </div>
        </div>
    </div>
</body>
</html>
