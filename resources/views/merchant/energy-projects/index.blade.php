@extends('Layouts.app')

@section('title', 'Energy Projects')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        @include('merchant.partials.nav')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="brand-font text-2xl text-slate-900">Energy Projects</h1>
                <p class="text-slate-600 mt-2">Manage renewable energy financing projects.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('merchant.energy-projects.create') }}" class="btn-primary px-5 py-2 rounded-xl text-sm font-semibold">
                    Add Project
                </a>
                <a href="{{ route('merchant.energy-assets.index') }}" class="btn-ghost px-5 py-2 rounded-xl text-sm font-semibold">
                    View Assets
                </a>
            </div>
        </div>

        <form method="GET" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input name="search" value="{{ request('search') }}" class="input" placeholder="Search projects" />
            <select name="project_type" class="input">
                <option value="">All Types</option>
                @foreach(['solar','battery','ev_charger','efficiency','wind','other'] as $type)
                    <option value="{{ $type }}" @selected(request('project_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All Status</option>
                @foreach(['planned','active','suspended','completed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-primary px-5 py-2 rounded-xl text-sm font-semibold">Filter</button>
        </form>

        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full text-sm text-slate-700">
                <thead class="text-left text-slate-500">
                    <tr>
                        <th class="py-3 pr-4">Project</th>
                        <th class="py-3 pr-4">Station</th>
                        <th class="py-3 pr-4">Type</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4">Asset Cost</th>
                        <th class="py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($projects as $project)
                        <tr>
                            <td class="py-4 pr-4">
                                <div class="font-semibold text-slate-900">{{ $project->title }}</div>
                                <div class="text-xs text-slate-500">{{ $project->asset?->name ?? 'No asset linked' }}</div>
                            </td>
                            <td class="py-4 pr-4">{{ $project->station?->name ?? '—' }}</td>
                            <td class="py-4 pr-4">{{ ucfirst(str_replace('_', ' ', $project->project_type)) }}</td>
                            <td class="py-4 pr-4">{{ ucfirst($project->status) }}</td>
                            <td class="py-4 pr-4">{{ $project->total_cost ? 'ZAR ' . number_format($project->total_cost, 2) : '—' }}</td>
                            <td class="py-4 text-right">
                                <a href="{{ route('merchant.energy-projects.show', $project) }}" class="text-blue-600">View</a>
                                <a href="{{ route('merchant.energy-projects.edit', $project) }}" class="ml-3 text-slate-500">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">No energy projects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $projects->links() }}</div>
    </div>
</section>
@endsection
