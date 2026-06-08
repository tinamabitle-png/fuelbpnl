@php
    $value = fn (string $key, $default = '') => old($key, $investor?->{$key} ?? $default);
@endphp

<div>
    <label class="text-sm font-semibold text-gray-700">Company Name</label>
    <input name="company_name" value="{{ $value('company_name') }}" required class="mt-2 w-full px-3 py-2">
    @error('company_name')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Registration Number</label>
    <input name="registration_number" value="{{ $value('registration_number') }}" required class="mt-2 w-full px-3 py-2">
    @error('registration_number')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Tax ID</label>
    <input name="tax_id" value="{{ $value('tax_id') }}" class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Contact Person</label>
    <input name="contact_person" value="{{ $value('contact_person') }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Contact Email</label>
    <input type="email" name="contact_email" value="{{ $value('contact_email') }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Contact Phone</label>
    <input name="contact_phone" value="{{ $value('contact_phone') }}" required class="mt-2 w-full px-3 py-2">
</div>
<div class="md:col-span-2">
    <label class="text-sm font-semibold text-gray-700">Company Address</label>
    <textarea name="company_address" required class="mt-2 w-full px-3 py-2">{{ $value('company_address') }}</textarea>
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">City</label>
    <input name="city" value="{{ $value('city', 'Johannesburg') }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Country</label>
    <input name="country" value="{{ $value('country', 'South Africa') }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Total Capital</label>
    <input type="number" step="0.01" min="0" name="total_investment_capital" value="{{ $value('total_investment_capital', 0) }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Risk Profile</label>
    <select name="risk_profile" required class="mt-2 w-full px-3 py-2">
        @foreach(['conservative','moderate','aggressive'] as $option)
            <option value="{{ $option }}" @selected($value('risk_profile', 'moderate') === $option)>{{ ucfirst($option) }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Min Investment</label>
    <input type="number" step="0.01" min="1000" name="minimum_investment_amount" value="{{ $value('minimum_investment_amount', 1000) }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Max Investment</label>
    <input type="number" step="0.01" min="1000" name="maximum_investment_amount" value="{{ $value('maximum_investment_amount', 100000) }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Min Interest %</label>
    <input type="number" step="0.01" min="1" max="100" name="preferred_interest_rate_min" value="{{ $value('preferred_interest_rate_min', 5) }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Max Interest %</label>
    <input type="number" step="0.01" min="1" max="100" name="preferred_interest_rate_max" value="{{ $value('preferred_interest_rate_max', 25) }}" required class="mt-2 w-full px-3 py-2">
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Investment Horizon</label>
    <select name="investment_horizon" required class="mt-2 w-full px-3 py-2">
        @foreach(['short_term','medium_term','long_term'] as $option)
            <option value="{{ $option }}" @selected($value('investment_horizon', 'medium_term') === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="text-sm font-semibold text-gray-700">Status</label>
    <select name="status" required class="mt-2 w-full px-3 py-2">
        @foreach(['active','pending_approval','suspended'] as $option)
            <option value="{{ $option }}" @selected($value('status', 'active') === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
        @endforeach
    </select>
</div>
