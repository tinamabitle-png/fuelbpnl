@extends('Layouts.app')

@section('title', 'Energy Asset Details')

@section('content')
<section class="max-w-5xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8 space-y-6">
        @include('merchant.partials.nav')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="brand-font text-2xl text-slate-900">{{ $energyAsset->name }}</h1>
                <p class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $energyAsset->asset_type)) }} • {{ ucfirst($energyAsset->status) }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('merchant.energy-assets.edit', $energyAsset) }}" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold">Edit</a>
                <a href="{{ route('merchant.energy-assets.index') }}" class="btn-ghost px-4 py-2 rounded-xl text-sm font-semibold">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Station</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energyAsset->station?->name ?? '—' }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Capacity</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energyAsset->capacity_kw ? number_format($energyAsset->capacity_kw, 2) . ' kW' : '—' }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $energyAsset->capacity_kwh ? number_format($energyAsset->capacity_kwh, 2) . ' kWh' : '' }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-sm text-slate-500">Commissioned</p>
                <p class="text-slate-900 font-semibold mt-2">{{ $energyAsset->commissioned_at?->format('Y-m-d') ?? '—' }}</p>
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <h2 class="text-lg text-slate-900 font-semibold">Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 text-sm text-slate-600">
                <div><span class="text-slate-500">Vendor:</span> {{ $energyAsset->vendor ?? '—' }}</div>
                <div><span class="text-slate-500">Model:</span> {{ $energyAsset->model ?? '—' }}</div>
                <div><span class="text-slate-500">Serial:</span> {{ $energyAsset->serial_number ?? '—' }}</div>
                <div><span class="text-slate-500">Asset Cost:</span> {{ $energyAsset->asset_cost ? 'ZAR ' . number_format($energyAsset->asset_cost, 2) : '—' }}</div>
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <h2 class="text-lg text-slate-900 font-semibold">Latest Readings</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="py-2 pr-4">Type</th>
                            <th class="py-2 pr-4">Value</th>
                            <th class="py-2 pr-4">Recorded</th>
                            <th class="py-2">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($energyAsset->readings->sortByDesc('recorded_at')->take(10) as $reading)
                            <tr>
                                <td class="py-3 pr-4">{{ ucfirst(str_replace('_', ' ', $reading->reading_type)) }}</td>
                                <td class="py-3 pr-4">{{ number_format($reading->value, 3) }} {{ $reading->unit }}</td>
                                <td class="py-3 pr-4">{{ $reading->recorded_at?->format('Y-m-d H:i') }}</td>
                                <td class="py-3">{{ $reading->source ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-5 text-center text-slate-500">No readings yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
