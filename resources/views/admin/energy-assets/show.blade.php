@extends('layouts.admin')

@section('title', 'Energy Asset Details')
@section('page-title', 'Energy Asset Details')
@section('page-description', 'View renewable asset overview and readings')
@section('breadcrumb', 'Energy Assets / View')

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $energyAsset->name }}</h2>
                <p class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $energyAsset->asset_type)) }} • {{ ucfirst($energyAsset->status) }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.energy-assets.edit', $energyAsset) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg">Edit</a>
                <a href="{{ route('admin.energy-assets.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg">Back</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Station</h3>
            <p class="text-gray-700 mt-2">{{ $energyAsset->station?->name ?? '—' }}</p>
            <p class="text-sm text-gray-500">{{ $energyAsset->station?->address }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Owner</h3>
            <p class="text-gray-700 mt-2">{{ $energyAsset->owner?->name ?? 'Unassigned' }}</p>
            <p class="text-sm text-gray-500">{{ $energyAsset->owner?->phone }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Capacity</h3>
            <p class="text-gray-700 mt-2">{{ $energyAsset->capacity_kw ? number_format($energyAsset->capacity_kw, 2) . ' kW' : '—' }}</p>
            <p class="text-sm text-gray-500">{{ $energyAsset->capacity_kwh ? number_format($energyAsset->capacity_kwh, 2) . ' kWh' : '' }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Asset Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Vendor:</span> {{ $energyAsset->vendor ?? '—' }}</div>
            <div><span class="text-gray-500">Model:</span> {{ $energyAsset->model ?? '—' }}</div>
            <div><span class="text-gray-500">Serial:</span> {{ $energyAsset->serial_number ?? '—' }}</div>
            <div><span class="text-gray-500">Commissioned:</span> {{ $energyAsset->commissioned_at?->format('Y-m-d') ?? '—' }}</div>
            <div><span class="text-gray-500">Asset Cost:</span> {{ $energyAsset->asset_cost ? 'ZAR ' . number_format($energyAsset->asset_cost, 2) : '—' }}</div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Latest Readings</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-gray-500 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-left">Value</th>
                        <th class="px-4 py-2 text-left">Recorded</th>
                        <th class="px-4 py-2 text-left">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($energyAsset->readings->sortByDesc('recorded_at')->take(10) as $reading)
                        <tr>
                            <td class="px-4 py-2">{{ ucfirst(str_replace('_', ' ', $reading->reading_type)) }}</td>
                            <td class="px-4 py-2">{{ number_format($reading->value, 3) }} {{ $reading->unit }}</td>
                            <td class="px-4 py-2">{{ $reading->recorded_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2">{{ $reading->source ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No readings captured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
