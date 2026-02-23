@extends('layouts.app')

@section('title', 'Edit Energy Project')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        @include('merchant.partials.nav')
        <h1 class="brand-font text-2xl text-slate-900">Edit Energy Project</h1>
        <p class="text-slate-600 mt-2">Update your renewable project details.</p>

        <form method="POST" action="{{ route('merchant.energy-projects.update', $energyProject) }}" class="mt-8 space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="text-sm text-slate-600">Station</label>
                <select name="fuel_station_id" class="input mt-2" required>
                    @foreach($stations as $station)
                        <option value="{{ $station->id }}" @selected(old('fuel_station_id', $energyProject->fuel_station_id) == $station->id)>{{ $station->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Linked Asset (optional)</label>
                <select name="energy_asset_id" class="input mt-2">
                    <option value="">No asset linked</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('energy_asset_id', $energyProject->energy_asset_id) == $asset->id)>{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Project Title</label>
                <input name="title" value="{{ old('title', $energyProject->title) }}" class="input mt-2" required />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Type</label>
                    <select name="project_type" class="input mt-2" required>
                        @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                            <option value="{{ $type }}" @selected(old('project_type', $energyProject->project_type) === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Status</label>
                    <select name="status" class="input mt-2" required>
                        @foreach(['planned','active','suspended','completed','cancelled'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $energyProject->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Asset Cost (ZAR)</label>
                    <input type="number" step="0.01" min="0" max="1000000000000" name="total_cost" value="{{ old('total_cost', $energyProject->total_cost) }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">Financed Amount (optional)</label>
                    <input type="number" step="0.01" min="0" max="1000000000000" name="financed_amount" value="{{ old('financed_amount', $energyProject->financed_amount) }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">Interest Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="60" name="interest_rate" value="{{ old('interest_rate', $energyProject->interest_rate) }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">Term (months)</label>
                    <input type="number" step="1" min="3" max="36" name="term_months" value="{{ old('term_months', $energyProject->term_months) }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">Monthly Payment (optional)</label>
                    <input type="number" step="0.01" min="0" max="1000000000000" name="monthly_payment" value="{{ old('monthly_payment', $energyProject->monthly_payment) }}" class="input mt-2" />
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', optional($energyProject->start_date)->format('Y-m-d')) }}" class="input mt-2" />
                </div>
                <div>
                    <label class="text-sm text-slate-600">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date', optional($energyProject->end_date)->format('Y-m-d')) }}" class="input mt-2" />
                </div>
            </div>
            <div>
                <label class="text-sm text-slate-600">Metadata (JSON)</label>
                <textarea name="metadata" rows="4" class="input mt-2" placeholder='{"installer":"SolarCo"}'>{{ old('metadata', $energyProject->metadata ? json_encode($energyProject->metadata, JSON_PRETTY_PRINT) : '') }}</textarea>
            </div>
            <div class="flex gap-3">
                <button class="btn-primary px-5 py-2 rounded-xl text-sm font-semibold">Save Changes</button>
                <a href="{{ route('merchant.energy-projects.show', $energyProject) }}" class="btn-ghost px-5 py-2 rounded-xl text-sm font-semibold">Cancel</a>
            </div>
        </form>
    </div>
</section>
@endsection
