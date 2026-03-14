@extends('Layouts.admin')

@section('title', 'System Settings')
@section('page-title', 'System Configuration')
@section('page-description', 'Manage application settings and configurations')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Settings</li>
@endsection

@php
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $locales = ['en' => 'English', 'sw' => 'Swahili', 'fr' => 'French'];
    $currencies = [
        'ZAR' => 'Kenyan Shilling (ZAR)',
        'USD' => 'US Dollar (USD)',
        'EUR' => 'Euro (EUR)',
        'GBP' => 'British Pound (GBP)',
        'ZAR' => 'South African Rand (ZAR)'
    ];
    $mailDrivers = ['smtp', 'mailgun', 'ses', 'sendmail'];
    $paymentGateways = ['stripe', 'paypal', 'flutterwave'];
    $paymentMethods = ['mpesa', 'bank_transfer', 'card'];
    $cacheDrivers = ['file', 'redis', 'database'];
    $sessionDrivers = ['file', 'cookie', 'database', 'redis'];
@endphp

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- System Status -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">System Status</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    @if(app()->isDownForMaintenance())
                        <span class="text-red-600">Maintenance</span>
                    @else
                        <span class="text-green-600">Active</span>
                    @endif
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-server text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            PHP {{ phpversion() }} • Laravel {{ app()->version() }}
        </div>
    </div>

    <!-- Cache Status -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Cache Driver</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ config('cache.default') }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-bolt text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <form action="{{ route('admin.settings.clear-cache') }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        class="text-sm bg-purple-600 text-white px-3 py-1.5 rounded-lg hover:bg-purple-700">
                    <i class="fas fa-sync-alt mr-1"></i> Clear Cache
                </button>
            </form>
        </div>
    </div>

    <!-- Database -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Database</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ config('database.default') }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-database text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <form action="{{ route('admin.settings.backup') }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        class="text-sm bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700">
                    <i class="fas fa-download mr-1"></i> Backup DB
                </button>
            </form>
        </div>
    </div>

    <!-- Mail Status -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Mail Driver</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ config('mail.default') }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-envelope text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            @if(config('mail.default') === 'smtp')
                <span class="text-green-600">Configured</span>
            @else
                <span class="text-yellow-600">Setup required</span>
            @endif
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Settings Tabs -->
    <div class="mb-8">
        <div class="flex flex-wrap gap-2 border-b border-gray-200">
            <button onclick="showTab('general')" 
                    id="generalTab"
                    class="px-5 py-3 font-medium rounded-t-lg border-b-2 border-blue-500 text-blue-600 bg-blue-50">
                <i class="fas fa-cog mr-2"></i> General
            </button>
            <button onclick="showTab('mail')" 
                    id="mailTab"
                    class="px-5 py-3 font-medium rounded-t-lg border-b-2 border-transparent text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                <i class="fas fa-envelope mr-2"></i> Mail
            </button>
            <button onclick="showTab('payment')" 
                    id="paymentTab"
                    class="px-5 py-3 font-medium rounded-t-lg border-b-2 border-transparent text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                <i class="fas fa-credit-card mr-2"></i> Payment
            </button>
            <button onclick="showTab('vouchers')" 
                    id="vouchersTab"
                    class="px-5 py-3 font-medium rounded-t-lg border-b-2 border-transparent text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                <i class="fas fa-ticket-alt mr-2"></i> Vouchers
            </button>
            <button onclick="showTab('merchantBranding')" 
                    id="merchantBrandingTab"
                    class="px-5 py-3 font-medium rounded-t-lg border-b-2 border-transparent text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                <i class="fas fa-store mr-2"></i> Merchant Branding
            </button>
            <button onclick="showTab('system')" 
                    id="systemTab"
                    class="px-5 py-3 font-medium rounded-t-lg border-b-2 border-transparent text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                <i class="fas fa-server mr-2"></i> System
            </button>
            <div class="ml-auto flex items-center space-x-2">
                <a href="{{ route('admin.settings.system-info') }}" 
                   class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-info-circle mr-1"></i> System Info
                </a>
                <a href="{{ route('admin.settings.logs') }}" 
                   class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-file-alt mr-1"></i> View Logs
                </a>
            </div>
        </div>
    </div>

    <!-- General Settings -->
    <div id="generalTabContent" class="tab-content">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">General Settings</h3>
                    <p class="text-gray-600 text-sm mt-1">Configure basic application settings</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-globe mr-1"></i> {{ config('app.timezone') }}
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.settings.update-general') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Application Name *
                        </label>
                        <input type="text" 
                               name="app_name" 
                               value="{{ old('app_name', $settings['general']['app_name']) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Application URL *
                        </label>
                        <input type="url" 
                               name="app_url" 
                               value="{{ old('app_url', $settings['general']['app_url']) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Timezone *
                        </label>
                        <select name="timezone" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}" {{ $settings['general']['timezone'] == $tz ? 'selected' : '' }}>
                                    {{ $tz }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Default Language *
                        </label>
                        <select name="locale" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($locales as $code => $name)
                                <option value="{{ $code }}" {{ $settings['general']['locale'] == $code ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Currency *
                        </label>
                        <select name="currency" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($currencies as $code => $name)
                                <option value="{{ $code }}" {{ $settings['payment']['currency'] == $code ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Currency Symbol *
                        </label>
                        <input type="text" 
                               name="currency_symbol" 
                               value="{{ old('currency_symbol', $settings['payment']['currency_symbol']) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i> Save General Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mail Settings -->
    <div id="mailTabContent" class="tab-content hidden">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Mail Settings</h3>
                    <p class="text-gray-600 text-sm mt-1">Configure email server settings</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                        <i class="fas fa-paper-plane mr-1"></i> {{ strtoupper($settings['mail']['mail_driver']) }}
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.settings.update-mail') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mail Driver *
                        </label>
                        <select name="mail_driver" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($mailDrivers as $driver)
                                <option value="{{ $driver }}" {{ $settings['mail']['mail_driver'] == $driver ? 'selected' : '' }}>
                                    {{ strtoupper($driver) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SMTP Host *
                        </label>
                        <input type="text" 
                               name="mail_host" 
                               value="{{ old('mail_host', $settings['mail']['mail_host']) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="smtp.gmail.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SMTP Port *
                        </label>
                        <input type="number" 
                               name="mail_port" 
                               value="{{ old('mail_port', $settings['mail']['mail_port']) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="587">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SMTP Username
                        </label>
                        <input type="text" 
                               name="mail_username" 
                               value="{{ old('mail_username', $settings['mail']['mail_username']) }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="your-email@gmail.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SMTP Password
                        </label>
                        <input type="password" 
                               name="mail_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Leave blank to keep current">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Encryption
                        </label>
                        <select name="mail_encryption" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">None</option>
                            <option value="tls" {{ $settings['mail']['mail_encryption'] == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ $settings['mail']['mail_encryption'] == 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            From Address *
                        </label>
                        <input type="email" 
                               name="mail_from_address" 
                               value="{{ old('mail_from_address', $settings['mail']['mail_from_address']) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="noreply@example.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            From Name *
                        </label>
                        <input type="text" 
                               name="mail_from_name" 
                               value="{{ old('mail_from_name', $settings['mail']['mail_from_name']) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Bwiser">
                    </div>
                </div>

                <!-- Test Email -->
                <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-blue-800">Test Email Configuration</h4>
                            <p class="text-sm text-blue-600 mt-1">Send a test email to verify your settings</p>
                        </div>
                        <button type="button" 
                                onclick="testEmail()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-paper-plane mr-1"></i> Send Test Email
                        </button>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i> Save Mail Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Settings -->
    <div id="paymentTabContent" class="tab-content hidden">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Payment Settings</h3>
                    <p class="text-gray-600 text-sm mt-1">Configure payment gateways and methods</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                        <i class="fas fa-money-bill-wave mr-1"></i> {{ strtoupper($settings['payment']['currency']) }}
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.settings.update-payment') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payment Gateway *
                        </label>
                        <select name="payment_gateway" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($paymentGateways as $gateway)
                                <option value="{{ $gateway }}" {{ $settings['payment']['payment_gateway'] == $gateway ? 'selected' : '' }}>
                                    {{ ucfirst($gateway) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Default Payment Method *
                        </label>
                        <select name="default_payment_method" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" {{ $settings['payment']['default_payment_method'] == $method ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $method)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-6">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_mpesa" 
                                       value="1"
                                       {{ $settings['payment']['enable_mpesa'] ? 'checked' : '' }}
                                       class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-gray-700 font-medium">Enable M-Pesa Payments</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_bank_transfer" 
                                       value="1"
                                       {{ $settings['payment']['enable_bank_transfer'] ? 'checked' : '' }}
                                       class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-gray-700 font-medium">Enable Bank Transfers</span>
                            </label>
                        </div>
                    </div>

                    <!-- Stripe Configuration -->
                    <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-200">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Stripe Configuration</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Stripe Publishable Key
                                </label>
                                <input type="text" 
                                       name="stripe_key" 
                                       value="{{ old('stripe_key') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="pk_live_...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Stripe Secret Key
                                </label>
                                <input type="password" 
                                       name="stripe_secret" 
                                       value="{{ old('stripe_secret') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="sk_live_...">
                            </div>
                        </div>
                    </div>

                    <!-- M-Pesa Configuration -->
                    <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-200">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">M-Pesa Configuration</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    M-Pesa Consumer Key
                                </label>
                                <input type="text" 
                                       name="mpesa_consumer_key" 
                                       value="{{ old('mpesa_consumer_key') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    M-Pesa Consumer Secret
                                </label>
                                <input type="password" 
                                       name="mpesa_consumer_secret" 
                                       value="{{ old('mpesa_consumer_secret') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i> Save Payment Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Voucher Settings -->
    <div id="vouchersTabContent" class="tab-content hidden">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Voucher Settings</h3>
                    <p class="text-gray-600 text-sm mt-1">Configure voucher behavior and limits</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
                        <i class="fas fa-ticket-alt mr-1"></i> Voucher Rules
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.settings.update-vouchers') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Voucher Expiry (Days) *
                        </label>
                        <input type="number" 
                               name="voucher_expiry_days" 
                               value="{{ old('voucher_expiry_days', $settings['vouchers']['voucher_expiry_days']) }}"
                               required
                               min="1"
                               max="30"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Number of days before voucher expires</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Voucher Amount *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">{{ $settings['payment']['currency_symbol'] }}</span>
                            </div>
                            <input type="number" 
                                   name="max_voucher_amount" 
                                   value="{{ old('max_voucher_amount', $settings['vouchers']['max_voucher_amount']) }}"
                                   required
                                   min="100"
                                   step="100"
                                   class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Minimum Voucher Amount *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">{{ $settings['payment']['currency_symbol'] }}</span>
                            </div>
                            <input type="number" 
                                   name="min_voucher_amount" 
                                   value="{{ old('min_voucher_amount', $settings['vouchers']['min_voucher_amount']) }}"
                                   required
                                   min="10"
                                   step="10"
                                   class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Minimum Daily Repayment *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">{{ $settings['payment']['currency_symbol'] }}</span>
                            </div>
                            <input type="number" 
                                   name="min_repayment_amount" 
                                   value="{{ old('min_repayment_amount', $settings['vouchers']['min_repayment_amount']) }}"
                                   required
                                   min="1"
                                   step="1"
                                   class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Minimum allowed daily repayment for new vouchers</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Require Approval Threshold
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">{{ $settings['payment']['currency_symbol'] }}</span>
                            </div>
                            <input type="number" 
                                   name="require_approval_threshold" 
                                   value="{{ old('require_approval_threshold') }}"
                                   min="0"
                                   step="1000"
                                   class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Amount above which manual approval is required</p>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="auto_approve_vouchers" 
                                   value="1"
                                   {{ $settings['vouchers']['auto_approve_vouchers'] ? 'checked' : '' }}
                                   class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                            <span class="ml-2 text-gray-700 font-medium">Auto-approve vouchers</span>
                            <span class="ml-2 text-sm text-gray-500">(Vouchers will be approved automatically without manual review)</span>
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i> Save Voucher Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Merchant Branding Settings -->
    <div id="merchantBrandingTabContent" class="tab-content hidden">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Merchant Dashboard Branding</h3>
                    <p class="text-gray-600 text-sm mt-1">Set header branding for the merchant dashboard using brand selection or custom logo upload.</p>
                </div>
                <div class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                    <i class="fas fa-palette mr-1"></i> Branding
                </div>
            </div>

            <form action="{{ route('admin.settings.update-merchant-dashboard') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm font-semibold text-gray-800 mb-3">Branding Source</p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="branding_mode" value="brand" {{ ($settings['merchant_dashboard']['branding_mode'] ?? 'brand') === 'brand' ? 'checked' : '' }}>
                                Use a fuel brand from the system
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="branding_mode" value="upload" {{ ($settings['merchant_dashboard']['branding_mode'] ?? 'brand') === 'upload' ? 'checked' : '' }}>
                                Upload custom logo
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm font-semibold text-gray-800 mb-3">Current</p>
                        @php $currentLogo = (string) ($settings['merchant_dashboard']['logo_path'] ?? ''); @endphp
                        @if($currentLogo !== '')
                            <img src="{{ asset('storage/' . ltrim($currentLogo, '/')) }}" alt="Current merchant dashboard logo" class="h-20 w-20 object-contain rounded-lg border border-gray-200 p-2 bg-gray-50">
                        @else
                            <p class="text-sm text-gray-500">No custom logo uploaded.</p>
                        @endif
                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_logo" value="1">
                            Remove existing custom logo
                        </label>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Choose Popular Fuel Brand</label>
                    <select name="selected_brand" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select a brand</option>
                        @foreach(($popularBrands ?? collect()) as $brand)
                            <option value="{{ $brand['slug'] }}" {{ ($settings['merchant_dashboard']['selected_brand'] ?? '') === $brand['slug'] ? 'selected' : '' }}>
                                {{ $brand['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Popular Brands</p>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach(($popularBrands ?? collect()) as $brand)
                            <label class="cursor-pointer">
                                <input type="radio" name="selected_brand" value="{{ $brand['slug'] }}" class="sr-only" {{ ($settings['merchant_dashboard']['selected_brand'] ?? '') === $brand['slug'] ? 'checked' : '' }}>
                                <span class="merchant-brand-choice block rounded-xl border border-gray-200 bg-white p-3 text-center hover:border-indigo-400">
                                    @if(!empty($brand['logo_url']))
                                        <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }} logo" class="h-8 w-full object-contain mx-auto">
                                    @else
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200 text-slate-700 text-xs font-bold">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($brand['name'], 0, 2)) }}
                                        </span>
                                    @endif
                                    <span class="mt-2 block text-xs font-medium text-slate-700">{{ $brand['name'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Custom Logo</label>
                    <div id="merchantLogoDropZone" class="rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/30 p-6 text-center transition-colors">
                        <input id="merchantLogoFile" type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg" class="hidden">
                        <p class="text-sm text-gray-700 font-medium">Drag and drop logo here, or</p>
                        <button type="button" id="merchantLogoPickBtn" class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Choose File</button>
                        <p class="text-xs text-gray-500 mt-3">JPG, PNG, WEBP, SVG up to 5MB</p>
                        <p id="merchantLogoFileName" class="text-xs text-indigo-700 mt-2"></p>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-xl font-medium hover:from-indigo-700 hover:to-indigo-800 shadow-md hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i> Save Merchant Branding
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- System Settings -->
    <div id="systemTabContent" class="tab-content hidden">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">System Settings</h3>
                    <p class="text-gray-600 text-sm mt-1">Configure system performance and maintenance</p>
                </div>
                <div class="flex items-center space-x-2">
                    @if($settings['system']['maintenance_mode'])
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">
                            <i class="fas fa-tools mr-1"></i> Maintenance Mode
                        </span>
                    @else
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                            <i class="fas fa-check-circle mr-1"></i> System Active
                        </span>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.settings.update-system') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Cache Driver *
                        </label>
                        <select name="cache_driver" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($cacheDrivers as $driver)
                                <option value="{{ $driver }}" {{ $settings['system']['cache_driver'] == $driver ? 'selected' : '' }}>
                                    {{ ucfirst($driver) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Redis recommended for production</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Session Driver *
                        </label>
                        <select name="session_driver" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach($sessionDrivers as $driver)
                                <option value="{{ $driver }}" {{ $settings['system']['session_driver'] == $driver ? 'selected' : '' }}>
                                    {{ ucfirst($driver) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-6">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="debug_mode" 
                                       value="1"
                                       {{ $settings['system']['debug_mode'] ? 'checked' : '' }}
                                       class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-gray-700 font-medium">Enable Debug Mode</span>
                                <span class="ml-2 text-sm text-gray-500">(For development only)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="maintenance_mode" 
                                       value="1"
                                       {{ $settings['system']['maintenance_mode'] ? 'checked' : '' }}
                                       class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-gray-700 font-medium">Maintenance Mode</span>
                                <span class="ml-2 text-sm text-gray-500">(Temporarily disable application)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="md:col-span-2 mt-8 pt-8 border-t border-red-200">
                        <h4 class="text-md font-semibold text-red-700 mb-4">Danger Zone</h4>
                        <div class="bg-red-50 p-4 rounded-xl border border-red-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="font-medium text-red-800">Clear Application Cache</h5>
                                    <p class="text-sm text-red-600 mt-1">Clear all cached data including views, routes, and config</p>
                                </div>
                                <form action="{{ route('admin.settings.clear-cache') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to clear all cache?')"
                                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                                        <i class="fas fa-trash-alt mr-1"></i> Clear Cache
                                    </button>
                                </form>
                            </div>
                            
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-red-200">
                                <div>
                                    <h5 class="font-medium text-red-800">Clear System Logs</h5>
                                    <p class="text-sm text-red-600 mt-1">Permanently delete all system log files</p>
                                </div>
                                <form action="{{ route('admin.settings.clear-logs') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to clear all logs? This cannot be undone.')"
                                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                                        <i class="fas fa-trash-alt mr-1"></i> Clear Logs
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i> Save System Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div id="testEmailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form id="testEmailForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Send Test Email</h3>
                    <button type="button" onclick="closeTestEmailModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">Enter an email address to test your mail configuration.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <input type="email" 
                               name="test_email" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="test@example.com">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeTestEmailModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Send Test Email
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab switching
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('[id$="Tab"]').forEach(tab => {
            tab.classList.remove('border-blue-500', 'text-blue-600', 'bg-blue-50');
            tab.classList.add('border-transparent', 'text-gray-600');
        });
        
        // Show selected tab
        document.getElementById(tabName + 'TabContent').classList.remove('hidden');
        
        // Activate selected tab button
        document.getElementById(tabName + 'Tab').classList.add('border-blue-500', 'text-blue-600', 'bg-blue-50');
        document.getElementById(tabName + 'Tab').classList.remove('border-transparent', 'text-gray-600');
    }
    
    // Initialize with general tab active
    document.addEventListener('DOMContentLoaded', function() {
        const requestedTab = new URLSearchParams(window.location.search).get('tab');
        if (requestedTab && document.getElementById(requestedTab + 'Tab')) {
            showTab(requestedTab);
        } else {
            showTab('general');
        }

        const dropZone = document.getElementById('merchantLogoDropZone');
        const fileInput = document.getElementById('merchantLogoFile');
        const pickBtn = document.getElementById('merchantLogoPickBtn');
        const fileName = document.getElementById('merchantLogoFileName');

        if (dropZone && fileInput && pickBtn && fileName) {
            const setFileName = () => {
                fileName.textContent = fileInput.files && fileInput.files.length
                    ? `Selected: ${fileInput.files[0].name}`
                    : '';
            };

            pickBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', setFileName);

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropZone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropZone.classList.add('border-indigo-500', 'bg-indigo-100');
                });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropZone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropZone.classList.remove('border-indigo-500', 'bg-indigo-100');
                });
            });

            dropZone.addEventListener('drop', (event) => {
                const files = event.dataTransfer?.files;
                if (!files || files.length === 0) {
                    return;
                }
                fileInput.files = files;
                setFileName();
            });
        }

        const brandCards = document.querySelectorAll('.merchant-brand-choice');
        document.querySelectorAll('input[name="selected_brand"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                brandCards.forEach((card) => {
                    card.classList.remove('ring-2', 'ring-indigo-500', 'border-indigo-500', 'bg-indigo-50');
                });
                if (radio.checked) {
                    const card = radio.closest('label')?.querySelector('.merchant-brand-choice');
                    if (card) {
                        card.classList.add('ring-2', 'ring-indigo-500', 'border-indigo-500', 'bg-indigo-50');
                    }
                }
            });
            if (radio.checked) {
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
    
    // Test email modal
    function testEmail() {
        document.getElementById('testEmailModal').classList.remove('hidden');
    }
    
    function closeTestEmailModal() {
        document.getElementById('testEmailModal').classList.add('hidden');
        document.getElementById('testEmailForm').reset();
    }
    
    // Handle test email form submission
    document.getElementById('testEmailForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        const email = this.querySelector('input[name="test_email"]').value;
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
        submitButton.disabled = true;
        
        fetch("{{ route('admin.settings.test-email') }}", {
            method: 'POST',
            body: JSON.stringify({ email: email }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test email sent successfully!');
                closeTestEmailModal();
            } else {
                alert('Failed to send test email: ' + data.message);
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending test email');
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('border-red-500');
                    
                    // Add error message
                    if (!field.nextElementSibling?.classList.contains('text-red-600')) {
                        const error = document.createElement('p');
                        error.className = 'text-red-600 text-xs mt-1';
                        error.textContent = 'This field is required';
                        field.parentNode.appendChild(error);
                    }
                } else {
                    field.classList.remove('border-red-500');
                    const error = field.nextElementSibling;
                    if (error?.classList.contains('text-red-600')) {
                        error.remove();
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill all required fields.');
            }
        });
    });
    
    // Live validation
    document.querySelectorAll('[required]').forEach(field => {
        field.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('border-red-500');
                const error = this.nextElementSibling;
                if (error?.classList.contains('text-red-600')) {
                    error.remove();
                }
            }
        });
    });
</script>
@endsection
