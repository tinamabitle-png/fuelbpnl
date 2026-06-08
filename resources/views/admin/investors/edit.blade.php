@extends('Layouts.admin')

@section('title', 'Edit Investor')
@section('page-title', 'Edit Investor')
@section('page-description', 'Update investor settings and limits')
@section('breadcrumb', 'Investors / Edit')

@section('content')
<div class="p-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 max-w-5xl">
        <form method="POST" action="{{ route('admin.investors.update', $investor) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')
            @include('admin.investors.partials.form-fields', ['investor' => $investor])

            <div class="md:col-span-2 flex gap-3">
                <button type="submit" class="px-5 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">Save Changes</button>
                <a href="{{ route('admin.investors.show', $investor) }}" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
