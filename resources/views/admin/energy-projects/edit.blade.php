@extends('layouts.admin')

@section('title', 'Edit Energy Project')
@section('page-title', 'Edit Energy Project')
@section('page-description', 'Update renewable energy financing project details')
@section('breadcrumb', 'Energy Projects / Edit')

@section('content')
<div class="p-6 max-w-4xl">
    <form action="{{ route('admin.energy-projects.update', $energyProject) }}" method="POST" class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="text-sm font-medium text-gray-700">Station</label>
            <select name="fuel_station_id" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                @foreach($stations as $station)
                    <option value="{{ $station->id }}" @selected(old('fuel_station_id', $energyProject->fuel_station_id) == $station->id)>{{ $station->name }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-gray-500">Owner is auto-assigned from the selected station.</p>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700">Linked Asset (optional)</label>
            <select name="energy_asset_id" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">No asset linked</option>
                @foreach($assets as $asset)
                    <option value="{{ $asset->id }}" @selected(old('energy_asset_id', $energyProject->energy_asset_id) == $asset->id)>{{ $asset->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700">Project Title</label>
            <input name="title" value="{{ old('title', $energyProject->title) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Type</label>
                <select name="project_type" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                        <option value="{{ $type }}" @selected(old('project_type', $energyProject->project_type) === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    @foreach(['planned','active','suspended','completed','cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $energyProject->status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" value="{{ old('start_date', optional($energyProject->start_date)->format('Y-m-d')) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Asset Cost (ZAR)</label>
                <input type="number" step="0.01" min="0" max="1000000000000" name="total_cost" value="{{ old('total_cost', $energyProject->total_cost) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Financed Amount (optional)</label>
                <input type="number" step="0.01" min="0" max="1000000000000" name="financed_amount" value="{{ old('financed_amount', $energyProject->financed_amount) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Interest Rate (%)</label>
                <input type="number" step="0.01" min="0" max="60" name="interest_rate" value="{{ old('interest_rate', $energyProject->interest_rate) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Term (months)</label>
                <input type="number" step="1" min="3" max="36" name="term_months" value="{{ old('term_months', $energyProject->term_months) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Monthly Payment (optional)</label>
                <input type="number" step="0.01" min="0" max="1000000000000" name="monthly_payment" value="{{ old('monthly_payment', $energyProject->monthly_payment) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">End Date</label>
                <input type="date" name="end_date" value="{{ old('end_date', optional($energyProject->end_date)->format('Y-m-d')) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Activated At</label>
                <input type="date" name="activated_at" value="{{ old('activated_at', optional($energyProject->activated_at)->format('Y-m-d')) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Completed At</label>
                <input type="date" name="completed_at" value="{{ old('completed_at', optional($energyProject->completed_at)->format('Y-m-d')) }}" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" />
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700">Metadata (JSON)</label>
            <textarea name="metadata" rows="4" class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder='{"installer":"SolarCo"}'>{{ old('metadata', $energyProject->metadata ? json_encode($energyProject->metadata, JSON_PRETTY_PRINT) : '') }}</textarea>
        </div>

        <div class="flex gap-3">
            <button class="px-5 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">Save Changes</button>
            <a href="{{ route('admin.energy-projects.show', $energyProject) }}" class="px-5 py-3 bg-white border border-gray-300 rounded-xl font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
