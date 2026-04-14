@extends('mobile.layouts.app')

@section('title', 'Merchant Registration')
@section('meta_description', 'Register your fuel station or merchant account on Bwiser for voucher redemption and settlements.')

@section('content')
<main class="px-4 pb-10 pt-6">
    <div class="mx-auto max-w-md space-y-4">
        <section class="mobile-card p-5">
            <p class="text-xs uppercase tracking-[0.22em] text-blue-700">Merchant Onboarding</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Create Merchant Account</h1>
            <p class="mt-1 text-xs text-slate-600">Register your station profile for voucher redemption and settlement processing.</p>

            @if($errors->any())
                <div class="mt-4 rounded-xl border border-rose-400/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.merchant.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
	                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
	                    We use your first and last name for contact details.
	                </div>
	                <div class="grid grid-cols-2 gap-2">
	                    <div>
	                        <label class="block text-xs font-medium text-slate-700">First Name</label>
	                        <input name="first_name" type="text" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
	                    </div>
	                    <div>
	                        <label class="block text-xs font-medium text-slate-700">Last Name</label>
	                        <input name="last_name" type="text" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
	                    </div>
	                </div>
	                <div class="grid grid-cols-2 gap-2">
	                    <div>
	                        <label class="block text-xs font-medium text-slate-700">Gender</label>
	                        <select name="gender" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
	                            <option value="">Select</option>
	                            <option value="male" @selected(old('gender', 'male') === 'male')>Male</option>
	                            <option value="female" @selected(old('gender') === 'female')>Female</option>
	                            <option value="other" @selected(old('gender') === 'other')>Other</option>
	                        </select>
	                    </div>
	                    <div>
	                        <label class="block text-xs font-medium text-slate-700">Date of Birth</label>
	                        <input name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
	                    </div>
	                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Phone (South Africa)</label>
                    <input name="phone" type="tel" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" pattern="^(\+27|27|0)[6-8][0-9]{8}$" placeholder="+27XXXXXXXXX or 0XXXXXXXXX" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Business Address</label>
                    <input name="business_address" type="text" value="{{ old('business_address') }}" required placeholder="Street address, suburb" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-700">City</label>
                        <input name="city" type="text" value="{{ old('city') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Country</label>
                        <input name="country" type="text" value="{{ old('country', 'South Africa') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                    </div>
                </div>
                <input type="hidden" name="latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" value="{{ old('longitude') }}">
                <div>
                    <label class="block text-xs font-medium text-slate-700">Franchise</label>
                    <select name="franchise_id" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                        <option value="">Select franchise</option>
                        @foreach(($franchises ?? collect()) as $franchise)
                            <option value="{{ $franchise['id'] }}" @selected((string) old('franchise_id') === (string) $franchise['id'])>
                                {{ $franchise['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Password</label>
                    <input name="password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Confirm Password</label>
                    <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Optional Documents</p>
                    <div>
                        <label class="block text-xs text-slate-700">CK Document (PDF/JPG/PNG)</label>
                        <input name="ck_document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-700">B-BBEE Document (PDF/JPG/PNG)</label>
                        <input name="bbbee_document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Create Merchant Account</button>
            </form>
        </section>

        <section class="mobile-card p-4">
            <p class="text-xs text-slate-600">Already registered?</p>
            <div class="mt-2 grid grid-cols-1 gap-2">
                <a href="{{ route('login') }}" class="rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-blue-700">Sign In</a>
                <a href="{{ route('register.driver') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700">Register Driver Instead</a>
            </div>
        </section>
    </div>
</main>
@endsection
