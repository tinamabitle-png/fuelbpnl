@extends('Layouts.guest')

@section('title', 'Complete Google Registration')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="min-h-screen bg-slate-100 py-10 px-4">
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Complete your registration</h1>
        <p class="text-sm text-slate-600 mt-1">You signed in with Google. Finish the same onboarding fields used in standard registration.</p>

        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
            <p><span class="font-medium text-slate-700">Google account:</span> {{ $pending['email'] }}</p>
        </div>

        <form method="POST" action="{{ route('auth.google.complete.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf

            @if(!$lockedRole)
                <div>
                    <label class="block text-sm font-medium text-slate-700">Account Type</label>
                    <select id="role" name="role" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option value="">Select role</option>
                        <option value="driver" @selected(old('role') === 'driver')>Driver</option>
                        <option value="merchant" @selected(old('role') === 'merchant')>Merchant</option>
                    </select>
                    @error('role')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            @else
                <input type="hidden" name="role" value="{{ $lockedRole }}">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    Account type: <span class="font-semibold">{{ ucfirst($lockedRole) }}</span>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-slate-700">Full Name</label>
                <input name="name" type="text" value="{{ old('name', $existingUser->name ?? $pending['name'] ?? '') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Phone (South Africa)</label>
                <input name="phone" type="tel" value="{{ old('phone', $existingUser->phone ?? '') }}" required inputmode="tel" pattern="^(\+27|27|0)[6-8][0-9]{8}$" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="+27XXXXXXXXX or 0XXXXXXXXX">
                @error('phone')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div id="driverFields" class="space-y-4 {{ (old('role', $lockedRole) === 'driver') ? '' : 'hidden' }}">
                <div>
                    <label class="block text-sm font-medium text-slate-700">South African ID Number</label>
                    <input name="id_number" type="text" value="{{ old('id_number', $existingUser->id_number ?? '') }}" maxlength="13" pattern="[0-9]{13}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="13 digits">
                    @error('id_number')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Home Address</label>
                    <input name="home_address" type="text" value="{{ old('home_address', $existingUser->home_address ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @error('home_address')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">City</label>
                        <input name="city" type="text" value="{{ old('city', $existingUser->city ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @error('city')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Country</label>
                        <input name="country" type="text" value="{{ old('country', $existingUser->country ?? 'South Africa') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @error('country')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Latitude</label>
                        <input name="latitude" type="text" value="{{ old('latitude', $existingUser->latitude ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Longitude</label>
                        <input name="longitude" type="text" value="{{ old('longitude', $existingUser->longitude ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Delivery Platform</label>
                    <select id="driver_platform" name="driver_platform" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option value="">Select platform</option>
                        <option value="checkers_sixty60" @selected(old('driver_platform', $existingUser->driver_platform ?? '') === 'checkers_sixty60')>Checkers Sixty60</option>
                        <option value="mr_d" @selected(old('driver_platform', $existingUser->driver_platform ?? '') === 'mr_d')>Mr D</option>
                        <option value="takealot" @selected(old('driver_platform', $existingUser->driver_platform ?? '') === 'takealot')>Takealot</option>
                        <option value="indrive" @selected(old('driver_platform', $existingUser->driver_platform ?? '') === 'indrive')>inDrive</option>
                        <option value="uber" @selected(old('driver_platform', $existingUser->driver_platform ?? '') === 'uber')>Uber</option>
                        <option value="bolt" @selected(old('driver_platform', $existingUser->driver_platform ?? '') === 'bolt')>Bolt</option>
                        <option value="other" @selected(old('driver_platform', $existingUser->driver_platform ?? '') === 'other')>Other</option>
                    </select>
                    @error('driver_platform')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div id="driver_platform_other_wrap" class="{{ old('driver_platform', $existingUser->driver_platform ?? '') === 'other' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-slate-700">Other Platform Name</label>
                    <input name="driver_platform_other" type="text" value="{{ old('driver_platform_other', $existingUser->driver_platform_other ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @error('driver_platform_other')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <p class="text-xs uppercase tracking-wide text-slate-600 font-semibold">Required documents</p>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">ID Document (PDF/JPG/PNG)</label>
                        <input type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                        @error('id_document')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Driver License (PDF/JPG/PNG)</label>
                        <input type="file" name="driver_license_document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                        @error('driver_license_document')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Vehicle License (PDF/JPG/PNG)</label>
                        <input type="file" name="vehicle_license_document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                        @error('vehicle_license_document')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Bank Statement (PDF, optional)</label>
                        <input type="file" name="bank_statement_document" accept=".pdf" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                        @error('bank_statement_document')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div id="merchantFields" class="space-y-4 {{ (old('role', $lockedRole) === 'merchant') ? '' : 'hidden' }}">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Business Address</label>
                    <input name="business_address" type="text" value="{{ old('business_address') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @error('business_address')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">City</label>
                        <input name="city" type="text" value="{{ old('city', $existingUser->city ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @error('city')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Country</label>
                        <input name="country" type="text" value="{{ old('country', $existingUser->country ?? 'South Africa') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @error('country')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Latitude</label>
                        <input name="latitude" type="text" value="{{ old('latitude') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Longitude</label>
                        <input name="longitude" type="text" value="{{ old('longitude') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>

                @if(($franchises ?? collect())->count() > 0)
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Franchise</label>
                        <select name="franchise_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                            <option value="">Select franchise</option>
                            @foreach($franchises as $franchise)
                                <option value="{{ $franchise->id }}" @selected((string) old('franchise_id', $existingUser->merchant_franchise_id ?? '') === (string) $franchise->id)>{{ $franchise->name }}</option>
                            @endforeach
                        </select>
                        @error('franchise_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <p class="text-xs uppercase tracking-wide text-slate-600 font-semibold">Required business documents</p>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">CK Document (PDF/JPG/PNG)</label>
                        <input type="file" name="ck_document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                        @error('ck_document')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">B-BBEE Document (PDF/JPG/PNG)</label>
                        <input type="file" name="bbbee_document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 bg-white">
                        @error('bbbee_document')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-blue-600 text-white py-2.5 font-semibold hover:bg-blue-700">
                Complete Registration
            </button>
        </form>
    </div>
</section>

<script>
    (function () {
        const roleInput = document.getElementById('role');
        const driverFields = document.getElementById('driverFields');
        const merchantFields = document.getElementById('merchantFields');
        const platformSelect = document.getElementById('driver_platform');
        const platformOtherWrap = document.getElementById('driver_platform_other_wrap');

        function toggleRole() {
            const role = roleInput ? roleInput.value : @json($lockedRole);
            if (driverFields) driverFields.classList.toggle('hidden', role !== 'driver');
            if (merchantFields) merchantFields.classList.toggle('hidden', role !== 'merchant');
        }

        function togglePlatformOther() {
            if (!platformSelect || !platformOtherWrap) return;
            platformOtherWrap.classList.toggle('hidden', platformSelect.value !== 'other');
        }

        if (roleInput) roleInput.addEventListener('change', toggleRole);
        if (platformSelect) platformSelect.addEventListener('change', togglePlatformOther);
        toggleRole();
        togglePlatformOther();
    })();
</script>
@endsection

