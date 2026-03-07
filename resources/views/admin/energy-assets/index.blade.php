@extends('Layouts.admin')

@section('title', 'Energy Assets')
@section('page-title', 'Energy Assets')
@section('page-description', 'Track renewable energy assets installed at stations')
@section('breadcrumb', 'Energy Assets')

@section('content')
<div class="p-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Energy Assets</h2>
            <p class="text-gray-600">Manage solar, battery, EV charging, and efficiency assets.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.energy-assets.create') }}"
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-plus mr-2"></i> Add Energy Asset
            </a>
            <a href="{{ route('admin.energy-projects.index') }}"
               class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm transition-all duration-300 flex items-center">
                <i class="fas fa-leaf mr-2"></i> View Projects
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="text-xs uppercase tracking-wide text-gray-500">Search</label>
                <input name="search" value="{{ request('search') }}" class="mt-1 w-60 px-4 py-2 border border-gray-300 rounded-lg" placeholder="Asset, station, owner" />
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-gray-500">Type</label>
                <select name="asset_type" class="mt-1 w-44 px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                        <option value="{{ $type }}" @selected(request('asset_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-gray-500">Status</label>
                <select name="status" class="mt-1 w-44 px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    @foreach(['planned','active','maintenance','offline','retired'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-gray-500">Station</label>
                <select name="station_id" class="mt-1 w-56 px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    @foreach($stations as $station)
                        <option value="{{ $station->id }}" @selected(request('station_id') == $station->id)>{{ $station->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-4 py-2 bg-gray-900 text-white rounded-lg">Filter</button>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-left">Asset</th>
                    <th class="px-5 py-3 text-left">Station</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Capacity</th>
                    <th class="px-5 py-3 text-left">Commissioned</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($assets as $asset)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-gray-900">{{ $asset->name }}</div>
                            <div class="text-xs text-gray-500">{{ $asset->vendor }} {{ $asset->model }}</div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-800">{{ $asset->station?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $asset->owner?->name ?? 'Unassigned' }}</div>
                        </td>
                        <td class="px-5 py-4">{{ ucfirst(str_replace('_', ' ', $asset->asset_type)) }}</td>
                        <td class="px-5 py-4">
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">{{ ucfirst($asset->status) }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="text-gray-800">{{ $asset->capacity_kw ? number_format($asset->capacity_kw, 2) . ' kW' : '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $asset->capacity_kwh ? number_format($asset->capacity_kwh, 2) . ' kWh' : '' }}</div>
                        </td>
                        <td class="px-5 py-4">{{ $asset->commissioned_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.energy-assets.show', $asset) }}" class="text-blue-600 hover:text-blue-800 mr-3">View</a>
                            <a href="{{ route('admin.energy-assets.edit', $asset) }}" class="text-gray-600 hover:text-gray-800 mr-3">Edit</a>
                            <form action="{{ route('admin.energy-assets.destroy', $asset) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:text-red-800" onclick="return confirm('Delete this asset?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-gray-500">No energy assets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $assets->links() }}
    </div>
</div>
@endsection
