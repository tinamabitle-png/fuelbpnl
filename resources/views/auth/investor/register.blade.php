@extends('Layouts.guest')

@section('title', 'Investor Registration - Bwiser')
@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-10">
            <h2 class="text-3xl font-extrabold text-gray-900">Investor Registration</h2>
            <p class="mt-3 text-gray-600">Join our platform as an investor and start funding fuel purchases</p>
            <div class="mt-4">
                <a href="{{ route('investor.auth.login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    Already have an account? Sign in
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Benefits -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-blue-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Why Invest With Us?</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <div class="flex-shrink-0 h-6 w-6 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-chart-line text-green-600 text-xs"></i>
                            </div>
                            <span class="ml-3 text-sm text-gray-700">Attractive returns on investment</span>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-shield-alt text-blue-600 text-xs"></i>
                            </div>
                            <span class="ml-3 text-sm text-gray-700">Secure and insured investments</span>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 h-6 w-6 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-hand-holding-usd text-purple-600 text-xs"></i>
                            </div>
                            <span class="ml-3 text-sm text-gray-700">Regular monthly payouts</span>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 h-6 w-6 rounded-full bg-yellow-100 flex items-center justify-center">
                                <i class="fas fa-cogs text-yellow-600 text-xs"></i>
                            </div>
                            <span class="ml-3 text-sm text-gray-700">Flexible investment options</span>
                        </li>
                    </ul>
                    
                    <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Your account will be activated after KYC verification (1-2 business days)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Column - Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white">
                        <h3 class="text-lg font-semibold text-gray-900">Account Information</h3>
                        <p class="text-sm text-gray-600">All fields marked with * are required</p>
                    </div>
                    
                    <form action="{{ route('investor.auth.register') }}" method="POST" class="p-6" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Contact Information -->
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-900 mb-4">Contact Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                                    <input type="text" name="company_name" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Enter company name" value="{{ old('company_name') }}">
                                    @error('company_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person *</label>
                                    <input type="text" name="contact_person" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Full name" value="{{ old('contact_person') }}">
                                    @error('contact_person')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                    <input type="email" name="email" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="contact@company.com" value="{{ old('email') }}">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                    <input type="text" name="phone" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="+254 700 000 000" value="{{ old('phone') }}">
                                    @error('phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Login Credentials -->
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-900 mb-4">Login Credentials</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                                    <input type="password" name="password" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Minimum 8 characters">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                                    <input type="password" name="password_confirmation" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Re-enter your password">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Business Information -->
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-900 mb-4">Business Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tax ID / PIN</label>
                                    <input type="text" name="tax_id"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Tax identification number" value="{{ old('tax_id') }}">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Business Type</label>
                                    <select name="business_type"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select business type</option>
                                        <option value="individual" {{ old('business_type') == 'individual' ? 'selected' : '' }}>Individual</option>
                                        <option value="llc" {{ old('business_type') == 'llc' ? 'selected' : '' }}>LLC</option>
                                        <option value="corporation" {{ old('business_type') == 'corporation' ? 'selected' : '' }}>Corporation</option>
                                        <option value="partnership" {{ old('business_type') == 'partnership' ? 'selected' : '' }}>Partnership</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Registration Number</label>
                                    <input type="text" name="registration_number"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Business registration number" value="{{ old('registration_number') }}">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Year Established</label>
                                    <input type="number" name="year_established" min="1900" max="{{ date('Y') }}"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="YYYY" value="{{ old('year_established') }}">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="address" rows="2"
                                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="Physical business address">{{ old('address') }}</textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                    <input type="text" name="city"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="City" value="{{ old('city') }}">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                    <input type="text" name="country" value="Kenya" readonly
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Investment Preferences -->
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-900 mb-4">Investment Preferences</h4>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Risk Profile</label>
                                    <div class="flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="risk_profile" value="conservative" {{ old('risk_profile') == 'conservative' ? 'checked' : '' }}
                                                   class="form-radio h-4 w-4 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700">Conservative</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="risk_profile" value="moderate" {{ old('risk_profile') == 'moderate' ? 'checked' : '' }}
                                                   class="form-radio h-4 w-4 text-blue-600" checked>
                                            <span class="ml-2 text-sm text-gray-700">Moderate</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="risk_profile" value="aggressive" {{ old('risk_profile') == 'aggressive' ? 'checked' : '' }}
                                                   class="form-radio h-4 w-4 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700">Aggressive</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Investment Amount (KES)</label>
                                        <input type="number" name="minimum_investment_amount" min="1000" step="1000"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="1000" value="{{ old('minimum_investment_amount', 1000) }}">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Investment Amount (KES)</label>
                                        <input type="number" name="maximum_investment_amount" min="1000" step="1000"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="100000" value="{{ old('maximum_investment_amount', 100000) }}">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Investment Preferences / Notes</label>
                                    <textarea name="investment_preferences" rows="3"
                                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                              placeholder="Any specific investment preferences or requirements">{{ old('investment_preferences') }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- KYC Documents -->
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-900 mb-4">KYC Documents</h4>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Please upload the following documents for verification:
                                </p>
                                <ul class="text-sm text-yellow-700 mt-2 ml-6 list-disc">
                                    <li>Business registration certificate</li>
                                    <li>Tax compliance certificate</li>
                                    <li>ID/Passport of authorized signatory</li>
                                </ul>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Documents</label>
                                <input type="file" name="kyc_documents[]" multiple
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG files only. Max 5MB per file.</p>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="mb-8">
                            <div class="flex items-start">
                                <input type="checkbox" name="terms" id="terms" required
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1">
                                <label for="terms" class="ml-2 block text-sm text-gray-900">
                                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">Terms and Conditions</a> and 
                                    <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>. I confirm that all information provided is accurate.
                                </label>
                            </div>
                            @error('terms')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button type="submit"
                                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300">
                                <i class="fas fa-user-plus mr-2"></i> Register as Investor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection