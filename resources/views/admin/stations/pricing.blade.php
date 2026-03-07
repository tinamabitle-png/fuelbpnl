@extends('Layouts.admin')

@section('title', 'Station Pricing - ' . $station->name)
@section('page-title', 'Station Pricing')
@section('page-description', $station->name)
@section('breadcrumb')
<a href="{{ route('admin.stations.index') }}">Fuel Stations</a>
<i class="fas fa-chevron-right mx-2 text-xs"></i>
<a href="{{ route('admin.stations.show', $station) }}">{{ $station->name }}</a>
<i class="fas fa-chevron-right mx-2 text-xs"></i>
<span class="text-blue-600">Pricing</span>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Add or Update Price</h3>
            <form method="POST" action="{{ route('admin.stations.pricing.sync') }}">
                @csrf
                <button class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    <i class="fas fa-rotate mr-2"></i> Sync Prices
                </button>
            </form>
        </div>
        <form method="POST" action="{{ route('admin.stations.pricing.store', $station) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-600">Fuel Type</label>
                <select name="fuel_type" class="mt-1 w-full border-gray-300 rounded-lg">
                    <option value="petrol">Petrol</option>
                    <option value="super">Super</option>
                    <option value="diesel">Diesel</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Price Per Liter (ZAR)</label>
                <input type="number" step="0.01" name="price_per_liter" class="mt-1 w-full border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Currency</label>
                <input type="text" name="currency" value="ZAR" readonly class="mt-1 w-full border-gray-200 rounded-lg bg-gray-50">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Effective At</label>
                <input type="datetime-local" name="effective_at" class="mt-1 w-full border-gray-300 rounded-lg">
            </div>
            <div class="md:col-span-4">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Price</button>
                <a href="{{ route('admin.stations.show', $station) }}" class="ml-2 px-4 py-2 border border-gray-300 rounded-lg">Back</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Current Prices</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Fuel Type</th>
                        <th class="py-2">Price Per Liter</th>
                        <th class="py-2">Currency</th>
                        <th class="py-2">Effective At</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prices as $price)
                        <tr class="border-b">
                            <td class="py-2 font-medium text-gray-700">{{ ucfirst($price->fuel_type) }}</td>
                            <td class="py-2">ZAR {{ number_format($price->price_per_liter, 2) }}</td>
                            <td class="py-2">{{ $price->currency }}</td>
                            <td class="py-2">{{ $price->effective_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                            <td class="py-2">
                                <form method="POST" action="{{ route('admin.stations.pricing.update', [$station, $price]) }}" class="inline-flex items-center space-x-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" step="0.01" name="price_per_liter" value="{{ $price->price_per_liter }}" class="w-24 border-gray-300 rounded-lg">
                                    <input type="hidden" name="currency" value="ZAR">
                                    <input type="datetime-local" name="effective_at" class="border-gray-300 rounded-lg"
                                        value="{{ $price->effective_at ? $price->effective_at->format('Y-m-d\\TH:i') : '' }}">
                                    <button class="px-3 py-1 bg-gray-800 text-white rounded-lg">Update</button>
                                </form>
                                <form method="POST" action="{{ route('admin.stations.pricing.destroy', [$station, $price]) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-2 px-3 py-1 bg-red-600 text-white rounded-lg">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-gray-500 text-center">No prices recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
