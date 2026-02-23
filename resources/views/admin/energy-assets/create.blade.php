@extends('layouts.admin')

@section('title', 'Add Energy Asset')
@section('page-title', 'Add Energy Asset')
@section('page-description', 'Register a new renewable energy asset')
@section('breadcrumb', 'Energy Assets / Create')

@section('content')
<div class="p-6 max-w-4xl">
    <form action="{{ route('admin.energy-assets.store') }}" method="POST" class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Station</label>
                <select name="fuel_station_id" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Select station</option>
                    @foreach($stations as $station)
                        <option value="{{ $station->id }}" @selected(old('fuel_station_id') == $station->id)>{{ $station->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Owner (optional)</label>
                <select name="owner_id" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Auto from station</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700">Asset Name</label>
            <input name="name" value="{{ old('name') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Type</label>
                <select name="asset_type" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                        <option value="{{ $type }}" @selected(old('asset_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    @foreach(['planned','active','maintenance','offline','retired'] as $status)
                        <option value="{{ $status }}" @selected(old('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Commissioned</label>
                <input type="date" name="commissioned_at" value="{{ old('commissioned_at') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Capacity (kW)</label>
                <input type="number" step="0.01" min="0" name="capacity_kw" value="{{ old('capacity_kw') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Capacity (kWh)</label>
                <input type="number" step="0.01" min="0" name="capacity_kwh" value="{{ old('capacity_kwh') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Asset Cost (ZAR)</label>
            <input type="number" step="0.01" min="0" max="1000000000000" name="asset_cost" value="{{ old('asset_cost') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Vendor</label>
                <input name="vendor" value="{{ old('vendor') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Model</label>
                <input name="model" value="{{ old('model') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Serial Number</label>
                <input name="serial_number" value="{{ old('serial_number') }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700">Metadata (JSON)</label>
            <textarea name="metadata" rows="4" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder='{"installer":"SolarCo"}'>{{ old('metadata') }}</textarea>
        </div>

        <div class="flex gap-3">
            <button class="px-5 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">Save Asset</button>
            <a href="{{ route('admin.energy-assets.index') }}" class="px-5 py-3 bg-white border border-gray-300 rounded-xl font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
