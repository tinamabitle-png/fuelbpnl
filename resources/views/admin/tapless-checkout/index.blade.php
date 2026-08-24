@extends('Layouts.admin')

@section('title', 'Tapless Checkout')
@section('page-title', 'Tapless Checkout')
@section('page-description', 'Approve filling stations, issue plugin keys, and track checkout intents')
@section('breadcrumb', 'Tapless Checkout')

@section('content')
<div class="p-6 space-y-6">
    @if($newCredentials)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="font-bold text-amber-900">Copy these credentials now. Secret keys are only shown once.</p>
            <div class="mt-4 grid gap-3 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Public key</p>
                    <code class="mt-1 block overflow-x-auto rounded-xl bg-white px-3 py-2 text-xs text-slate-900">{{ $newCredentials['public_key'] ?? '' }}</code>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Secret key</p>
                    <code class="mt-1 block overflow-x-auto rounded-xl bg-white px-3 py-2 text-xs text-slate-900">{{ $newCredentials['secret_key'] ?? '' }}</code>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Webhook secret</p>
                    <code class="mt-1 block overflow-x-auto rounded-xl bg-white px-3 py-2 text-xs text-slate-900">{{ $newCredentials['webhook_secret'] ?? '' }}</code>
                </div>
            </div>
            <div class="mt-4 rounded-xl bg-slate-950 p-4">
                @php($firstStationId = (int) (($newCredentials['station_ids'][0] ?? 0) ?: 0))
                <code class="block overflow-x-auto whitespace-nowrap text-xs text-white">&lt;script src="{{ url('/js/bwiser-checkout.js') }}" data-bwiser-station="{{ $firstStationId ?: 'STATION_ID' }}" data-bwiser-public-key="{{ $newCredentials['public_key'] ?? 'bw_pk_xxxxx' }}" data-bwiser-reference="ORDER-1001" data-bwiser-amount="250.00"&gt;&lt;/script&gt;</code>
            </div>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Approve Station Checkout</h2>
            <p class="mt-1 text-sm text-slate-600">Create public/secret keys and link them to one or more filling stations.</p>

            <form method="POST" action="{{ route('admin.tapless-checkout.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-semibold text-slate-700">Partner or station group name</label>
                    <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Slug optional</label>
                    <input name="slug" value="{{ old('slug') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="engen-kroonstad">
                    @error('slug')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Webhook URL optional</label>
                    <input name="webhook_url" value="{{ old('webhook_url') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="https://pos.example.com/bwiser/webhook">
                    @error('webhook_url')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Stations</label>
                    <select name="station_ids[]" multiple required class="mt-1 min-h-48 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        @foreach($stations as $station)
                            <option value="{{ $station->id }}" @selected(in_array($station->id, old('station_ids', [])))>
                                #{{ $station->id }} {{ $station->company ? $station->company . ' - ' : '' }}{{ $station->name }} {{ $station->city ? '(' . $station->city . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('station_ids')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white">Approve and Issue Keys</button>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Recent Checkout Intents</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Intent</th>
                            <th class="px-3 py-3">Station</th>
                            <th class="px-3 py-3">Amount</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentIntents as $intent)
                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-semibold text-slate-900">{{ $intent->external_reference }}</p>
                                    <p class="text-xs text-slate-500">{{ $intent->public_id }}</p>
                                </td>
                                <td class="px-3 py-3">{{ $intent->station?->name ?? 'Station' }}</td>
                                <td class="px-3 py-3">{{ $intent->currency }} {{ $intent->amount !== null ? number_format((float) $intent->amount, 2) : '0.00' }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ ucfirst($intent->status) }}</span>
                                </td>
                                <td class="px-3 py-3 text-slate-500">{{ $intent->created_at?->format('d M H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">No checkout intents yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-bold text-slate-900">Approved Checkout Partners</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($partners as $partner)
                @php($selectedStations = $partner->stations->pluck('id')->all())
                <div class="p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-bold text-slate-900">{{ $partner->name }}</h3>
                                <span class="rounded-full {{ $partner->isActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} px-2.5 py-1 text-xs font-bold">{{ ucfirst($partner->status) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Public key: <code>{{ $partner->public_key }}</code></p>
                            <p class="mt-1 text-xs text-slate-500">{{ $partner->stations_count }} stations linked, {{ $partner->intents_count }} intents created</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.tapless-checkout.rotate-secret', $partner) }}">
                                @csrf
                                <button class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700" onclick="return confirm('Rotate this partner secret? Existing server-side integrations must update their secret key.')">Rotate Secret</button>
                            </form>
                            <form method="POST" action="{{ route('admin.tapless-checkout.destroy', $partner) }}">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-xl bg-amber-100 px-3 py-2 text-xs font-bold text-amber-700" onclick="return confirm('Suspend this checkout partner?')">Suspend</button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.tapless-checkout.update', $partner) }}" class="mt-5 grid gap-3 lg:grid-cols-[1fr_150px_1.4fr_auto]">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ old('name', $partner->name) }}" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <select name="status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="active" @selected($partner->status === 'active')>Active</option>
                            <option value="suspended" @selected($partner->status === 'suspended')>Suspended</option>
                        </select>
                        <select name="station_ids[]" multiple required class="min-h-24 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            @foreach($stations as $station)
                                <option value="{{ $station->id }}" @selected(in_array($station->id, $selectedStations, true))>
                                    #{{ $station->id }} {{ $station->company ? $station->company . ' - ' : '' }}{{ $station->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="space-y-2">
                            <input name="webhook_url" value="{{ old('webhook_url', $partner->webhook_url) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Webhook URL">
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white">Save</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="p-10 text-center text-slate-500">No checkout partners approved yet.</div>
            @endforelse
        </div>
    </div>

    <div>{{ $partners->links() }}</div>
</div>
@endsection
