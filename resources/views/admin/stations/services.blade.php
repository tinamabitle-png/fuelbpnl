@extends('Layouts.admin')

@section('title', 'Station Services - ' . $station->name)
@section('page-title', 'Station Services')
@section('page-description', $station->name)
@section('breadcrumb')
<a href="{{ route('admin.stations.index') }}">Fuel Stations</a>
<i class="fas fa-chevron-right mx-2 text-xs"></i>
<a href="{{ route('admin.stations.show', $station) }}">{{ $station->name }}</a>
<i class="fas fa-chevron-right mx-2 text-xs"></i>
<span class="text-blue-600">Services</span>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Add or Update Service</h3>
        <form method="POST" action="{{ route('admin.stations.services.store', $station) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-600">Service Key</label>
                <input type="text" name="service_key" class="mt-1 w-full border-gray-300 rounded-lg" placeholder="air_tyre" required>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Service Label</label>
                <input type="text" name="service_label" class="mt-1 w-full border-gray-300 rounded-lg" placeholder="Air & Tyre" required>
            </div>
            <div class="flex items-end">
                <label class="bw-morph-switch">
                    <input type="checkbox" name="is_available" value="1" checked>
                    <svg viewBox="0 0 36 18" aria-hidden="true">
                        <path d="M18 9C18 13.9706 13.9706 18 9 18C4.02944 18 0 13.9706 0 9C0 4.02944 4.02944 0 9 0C13.9706 0 18 4.02944 18 9Z" />
                    </svg>
                    <span class="bw-morph-switch-label">Available</span>
                </label>
            </div>
            <div class="md:col-span-3">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Service</button>
                <a href="{{ route('admin.stations.show', $station) }}" class="ml-2 px-4 py-2 border border-gray-300 rounded-lg">Back</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Current Services</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Key</th>
                        <th class="py-2">Label</th>
                        <th class="py-2">Available</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr class="border-b">
                            <td class="py-2 font-medium text-gray-700">{{ $service->service_key }}</td>
                            <td class="py-2">{{ $service->service_label }}</td>
                            <td class="py-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $service->is_available ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $service->is_available ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="py-2">
                                <form method="POST" action="{{ route('admin.stations.services.update', [$station, $service]) }}" class="inline-flex items-center space-x-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="service_label" value="{{ $service->service_label }}" class="border-gray-300 rounded-lg">
                                    <label class="bw-morph-switch">
                                        <input type="checkbox" name="is_available" value="1" {{ $service->is_available ? 'checked' : '' }}>
                                        <svg viewBox="0 0 36 18" aria-hidden="true">
                                            <path d="M18 9C18 13.9706 13.9706 18 9 18C4.02944 18 0 13.9706 0 9C0 4.02944 4.02944 0 9 0C13.9706 0 18 4.02944 18 9Z" />
                                        </svg>
                                        <span class="bw-morph-switch-label text-xs">Available</span>
                                    </label>
                                    <button class="px-3 py-1 bg-gray-800 text-white rounded-lg">Update</button>
                                </form>
                                <form method="POST" action="{{ route('admin.stations.services.destroy', [$station, $service]) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-2 px-3 py-1 bg-red-600 text-white rounded-lg">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-gray-500 text-center">No services recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
