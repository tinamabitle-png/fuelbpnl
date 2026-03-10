@extends('Layouts.app')

@section('title', 'Delete Account')

@section('content')
<section class="max-w-3xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <div class="space-y-2">
            <h1 class="brand-font text-3xl text-slate-900">Delete Your Account</h1>
            <p class="text-sm text-slate-600">
                This will disable your access immediately using a soft delete. You cannot undo this action from the app.
            </p>
        </div>

        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">Important</p>
            <p class="mt-1">
                You cannot delete your account if you have active leases or an outstanding balance.
            </p>
        </div>

        <form method="POST" action="{{ route('account.delete') }}" class="space-y-4">
            @csrf
            @method('DELETE')

            <div>
                <label class="block text-sm font-medium text-slate-700">Reason (optional)</label>
                <textarea name="reason" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('reason') }}</textarea>
                @error('reason')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Password (required for password accounts)</label>
                <input name="password" type="password" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" autocomplete="current-password">
                @error('password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-start gap-3">
                <input id="confirmDelete" name="confirm" type="checkbox" value="1" class="mt-1 h-4 w-4 rounded border-slate-300">
                <label for="confirmDelete" class="text-sm text-slate-700">
                    I understand this will delete my account access and revoke my sessions.
                </label>
            </div>
            @error('confirm')<p class="text-xs text-rose-600 -mt-2">{{ $message }}</p>@enderror

            <div class="flex flex-wrap gap-3">
                <a href="{{ url()->previous() }}" class="btn-ghost px-5 py-3 rounded-xl text-sm font-semibold">Cancel</a>
                <button type="submit" class="btn-danger px-5 py-3 rounded-xl text-sm font-semibold">
                    Delete My Account
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

