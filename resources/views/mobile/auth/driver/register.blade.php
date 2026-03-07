@extends('mobile.layouts.app')

@section('title', 'Driver Registration - Bwiser Mobile')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<main class="px-4 pb-10 pt-6">
    <div class="mx-auto max-w-md space-y-4">
        <section class="mobile-card p-5">
            <p class="text-xs uppercase tracking-[0.22em] text-blue-700">Driver Onboarding</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Create Driver Account</h1>
            <p class="mt-1 text-xs text-slate-600">Submit your details for voucher access and credit approval workflows.</p>

            @if($errors->any())
                <div class="mt-4 rounded-xl border border-rose-400/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.driver.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700">Full Name</label>
                    <input name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
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
                    <label class="block text-xs font-medium text-slate-700">ID Number (13 digits)</label>
                    <input name="id_number" type="text" value="{{ old('id_number') }}" required maxlength="13" pattern="[0-9]{13}" placeholder="13 digits" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Home Address</label>
                    <input name="home_address" type="text" value="{{ old('home_address') }}" required placeholder="Street address, suburb" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
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
                    <label class="block text-xs font-medium text-slate-700">Delivery Platform</label>
                    <select id="driver_platform" name="driver_platform" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
                        <option value="">Select platform</option>
                        <option value="checkers_sixty60" @selected(old('driver_platform') === 'checkers_sixty60')>Checkers Sixty60</option>
                        <option value="mr_d" @selected(old('driver_platform') === 'mr_d')>Mr D</option>
                        <option value="takealot" @selected(old('driver_platform') === 'takealot')>Takealot</option>
                        <option value="indrive" @selected(old('driver_platform') === 'indrive')>inDrive</option>
                        <option value="uber" @selected(old('driver_platform') === 'uber')>Uber</option>
                        <option value="bolt" @selected(old('driver_platform') === 'bolt')>Bolt</option>
                        <option value="other" @selected(old('driver_platform') === 'other')>Other</option>
                    </select>
                </div>
                <div id="driver_platform_other_wrap" class="{{ old('driver_platform') === 'other' ? '' : 'hidden' }}">
                    <label class="block text-xs font-medium text-slate-700">Other Platform Name</label>
                    <input name="driver_platform_other" type="text" value="{{ old('driver_platform_other') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500">
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
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Required Documents</p>
                    <div>
                        <label class="block text-xs text-slate-700">ID Document (PDF/JPG/PNG)</label>
                        <input name="id_document" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="mt-1 block w-full text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-700">Driver License (PDF/JPG/PNG)</label>
                        <input name="driver_license_document" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="mt-1 block w-full text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-700">Vehicle License (PDF/JPG/PNG)</label>
                        <input name="vehicle_license_document" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="mt-1 block w-full text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-700">Bank Statement (Optional PDF)</label>
                        <input name="bank_statement_document" type="file" accept=".pdf" class="mt-1 block w-full text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white">
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Create Driver Account</button>
            </form>
        </section>

        <section class="mobile-card p-4">
            <p class="text-xs text-slate-600">Already registered?</p>
            <div class="mt-2 grid grid-cols-1 gap-2">
                <a href="{{ route('login') }}" class="rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-blue-700">Sign In</a>
                <a href="{{ route('register.merchant') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700">Register Merchant Instead</a>
            </div>
        </section>
    </div>
</main>

<script>
const platformSelect = document.getElementById('driver_platform');
const otherWrap = document.getElementById('driver_platform_other_wrap');
const toggleOther = () => {
    if (!platformSelect || !otherWrap) return;
    otherWrap.classList.toggle('hidden', platformSelect.value !== 'other');
};
if (platformSelect) {
    platformSelect.addEventListener('change', toggleOther);
    toggleOther();
}
</script>
@endsection
