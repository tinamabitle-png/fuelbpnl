@extends('layouts.app')

@section('title', 'Add Energy Asset')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        @include('merchant.partials.nav')
        <h1 class="brand-font text-2xl text-slate-900">Add Energy Asset</h1>
        <p class="text-slate-600 mt-2">Register a renewable asset for your station.</p>

        <form method="POST" action="{{ route('merchant.energy-assets.store') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="text-sm text-slate-600">Station</label>
                <select name="fuel_station_id" class="input mt-2" required>
                    <option value="">Select station</option>
                    @foreach($stations as $station)
                        <option value="{{ $station->id }}" @selected(old('fuel_station_id') == $station->id)>{{ $station->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Asset Name</label>
                <input name="name" value="{{ old('name') }}" class="input mt-2" required />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Type</label>
                    <select name="asset_type" class="input mt-2" required>
                        @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                            <option value="{{ $type }}" @selected(old('asset_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Status</label>
                    <select name="status" class="input mt-2" required>
                        @foreach(['planned','active','maintenance','offline','retired'] as $status)
                            <option value="{{ $status }}" @selected(old('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Capacity (kW)</label>
                    <input type="number" step="0.01" name="capacity_kw" value="{{ old('capacity_kw') }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">Capacity (kWh)</label>
                    <input type="number" step="0.01" name="capacity_kwh" value="{{ old('capacity_kwh') }}" class="input mt-2" />
                </div>
            </div>
            <div>
                <label class="text-sm text-slate-600">Asset Cost (ZAR)</label>
                <input type="number" step="0.01" min="0" max="1000000000000" name="asset_cost" value="{{ old('asset_cost') }}" class="input mt-2" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Vendor</label>
                    <input name="vendor" value="{{ old('vendor') }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">Model</label>
                    <input name="model" value="{{ old('model') }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">Serial Number</label>
                    <input name="serial_number" value="{{ old('serial_number') }}" class="input mt-2" />
                </div>
            </div>
            <div>
                <label class="text-sm text-slate-600">Commissioned At</label>
                <input type="date" name="commissioned_at" value="{{ old('commissioned_at') }}" class="input mt-2" />
            </div>
            <div>
                <label class="text-sm text-slate-600">Metadata (JSON)</label>
                <textarea name="metadata" rows="4" class="input mt-2" placeholder='{"installer":"SolarCo"}'>{{ old('metadata') }}</textarea>
            </div>
            <div class="flex gap-3">
                <button class="btn-primary px-5 py-2 rounded-xl text-sm font-semibold">Save Asset</button>
                <a href="{{ route('merchant.energy-assets.index') }}" class="btn-ghost px-5 py-2 rounded-xl text-sm font-semibold">Cancel</a>
            </div>
        </form>
    </div>
</section>
@endsection
