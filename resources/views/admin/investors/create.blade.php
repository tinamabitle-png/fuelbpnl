@extends('Layouts.admin')

@section('title', 'Create Investor')
@section('page-title', 'Create Investor')
@section('page-description', 'Create an investor profile for an existing user. They will use the normal Bwiser login.')
@section('breadcrumb', 'Investors / Create')

@section('content')
<div class="p-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 max-w-5xl">
        <form method="POST" action="{{ route('admin.investors.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div class="md:col-span-2">
                <label class="text-sm font-semibold text-gray-700">Login User</label>
                <select name="user_id" required class="mt-2 w-full px-3 py-2">
                    <option value="">Select existing user</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                    @endforeach
                </select>
                @error('user_id')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            @include('admin.investors.partials.form-fields', ['investor' => null])

            <div class="md:col-span-2 flex gap-3">
                <button type="submit" class="px-5 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">Create Investor</button>
                <a href="{{ route('admin.investors.index') }}" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
