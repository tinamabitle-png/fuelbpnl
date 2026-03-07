@extends('Layouts.admin')

@section('title', 'Energy Projects')
@section('page-title', 'Energy Projects')
@section('page-description', 'Manage renewable energy financing projects')
@section('breadcrumb', 'Energy Projects')

@section('content')
<div class="p-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Energy Projects</h2>
            <p class="text-gray-600">Track financing, deployment, and operational status.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.energy-projects.create') }}"
               class="px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-plus mr-2"></i> Add Energy Project
            </a>
            <a href="{{ route('admin.energy-assets.index') }}"
               class="px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 shadow-sm transition-all duration-300 flex items-center">
                <i class="fas fa-solar-panel mr-2"></i> View Assets
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="text-xs uppercase tracking-wide text-gray-500">Search</label>
                <input name="search" value="{{ request('search') }}" class="mt-1 w-60 px-4 py-2 border border-gray-300 rounded-lg" placeholder="Project, station, owner" />
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-gray-500">Type</label>
                <select name="project_type" class="mt-1 w-44 px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                        <option value="{{ $type }}" @selected(request('project_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-gray-500">Status</label>
                <select name="status" class="mt-1 w-44 px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    @foreach(['planned','active','suspended','completed','cancelled'] as $status)
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
                    <th class="px-5 py-3 text-left">Project</th>
                    <th class="px-5 py-3 text-left">Station</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Asset Cost</th>
                    <th class="px-5 py-3 text-left">Term</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($projects as $project)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-gray-900">{{ $project->title }}</div>
                            <div class="text-xs text-gray-500">{{ $project->asset?->name ?? 'No asset linked' }}</div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-800">{{ $project->station?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $project->owner?->name ?? 'Unassigned' }}</div>
                        </td>
                        <td class="px-5 py-4">{{ ucfirst(str_replace('_', ' ', $project->project_type)) }}</td>
                        <td class="px-5 py-4">
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">{{ ucfirst($project->status) }}</span>
                        </td>
                        <td class="px-5 py-4">
                            {{ $project->total_cost ? 'ZAR ' . number_format($project->total_cost, 2) : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            {{ $project->term_months ? $project->term_months . ' months' : '—' }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.energy-projects.show', $project) }}" class="text-blue-600 hover:text-blue-800 mr-3">View</a>
                            <a href="{{ route('admin.energy-projects.edit', $project) }}" class="text-gray-600 hover:text-gray-800 mr-3">Edit</a>
                            <form action="{{ route('admin.energy-projects.destroy', $project) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:text-red-800" onclick="return confirm('Delete this project?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-gray-500">No energy projects found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
</div>
@endsection
