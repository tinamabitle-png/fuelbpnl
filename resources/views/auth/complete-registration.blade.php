@extends('Layouts.app')

@section('title', 'Complete Registration - Bwiser')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white">
                <h2 class="text-2xl font-semibold text-gray-900">Complete Your {{ ucfirst($role) }} Profile</h2>
                <p class="mt-2 text-sm text-gray-600">Google sign-in worked. Upload required verification documents to continue.</p>
            </div>

            <form action="{{ route('registration.documents.store') }}" method="POST" enctype="multipart/form-data" class="p-6" data-live-validate>
                @csrf
                <input type="hidden" name="role" value="{{ $role }}">

                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700 mb-2">South African ID Number *</label>
                    <input type="text" name="id_number" required inputmode="numeric" maxlength="13" pattern="[0-9]{13}" title="Enter exactly 13 digits"
                           value="{{ old('id_number', auth()->user()->id_number) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="13-digit ID number">
                    <p class="mt-1 text-xs text-gray-500">Use exactly 13 digits, no spaces or symbols.</p>
                    @error('id_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @include('auth.partials.document-upload-flow')

                <button type="submit"
                        class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300">
                    <i class="fas fa-cloud-upload-alt mr-2"></i> Submit Documents
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
