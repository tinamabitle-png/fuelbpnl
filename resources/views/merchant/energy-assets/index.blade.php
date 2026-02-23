@extends('layouts.app')

@section('title', 'Energy Assets')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        @include('merchant.partials.nav')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="brand-font text-2xl text-slate-900">Energy Assets</h1>
                <p class="text-slate-600 mt-2">Track renewable assets installed at your stations.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('merchant.energy-assets.create') }}" class="btn-primary px-5 py-2 rounded-xl text-sm font-semibold">
                    Add Asset
                </a>
                <a href="{{ route('merchant.energy-projects.index') }}" class="btn-ghost px-5 py-2 rounded-xl text-sm font-semibold">
                    View Projects
                </a>
            </div>
        </div>

        <form method="GET" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input name="search" value="{{ request('search') }}" class="input" placeholder="Search assets" />
            <select name="asset_type" class="input">
                <option value="">All Types</option>
                @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                    <option value="{{ $type }}" @selected(request('asset_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All Status</option>
                @foreach(['planned','active','maintenance','offline','retired'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-primary px-5 py-2 rounded-xl text-sm font-semibold">Filter</button>
        </form>

        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full text-sm text-slate-700">
                <thead class="text-left text-slate-500">
                    <tr>
                        <th class="py-3 pr-4">Asset</th>
                        <th class="py-3 pr-4">Station</th>
                        <th class="py-3 pr-4">Type</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4">Capacity</th>
                        <th class="py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assets as $asset)
                        <tr>
                            <td class="py-4 pr-4">
                                <div class="font-semibold text-slate-900">{{ $asset->name }}</div>
                                <div class="text-xs text-slate-500">{{ $asset->vendor }} {{ $asset->model }}</div>
                            </td>
                            <td class="py-4 pr-4">{{ $asset->station?->name ?? '—' }}</td>
                            <td class="py-4 pr-4">{{ ucfirst(str_replace('_', ' ', $asset->asset_type)) }}</td>
                            <td class="py-4 pr-4">{{ ucfirst($asset->status) }}</td>
                            <td class="py-4 pr-4">
                                {{ $asset->capacity_kw ? number_format($asset->capacity_kw, 2) . ' kW' : '—' }}
                            </td>
                            <td class="py-4 text-right">
                                <a href="{{ route('merchant.energy-assets.show', $asset) }}" class="text-blue-600">View</a>
                                <a href="{{ route('merchant.energy-assets.edit', $asset) }}" class="ml-3 text-slate-500">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">No energy assets yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $assets->links() }}</div>
    </div>
</section>
@endsection
