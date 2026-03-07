@extends('Layouts.admin')

@section('title', 'Account Approvals')
@section('page-title', 'Account Approvals')
@section('breadcrumb', 'Account Approvals')

@section('content')
<div class="space-y-6">
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All</option>
                    @foreach(['pending', 'approved', 'rejected'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Role</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All</option>
                    @foreach(['driver', 'merchant'] as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">Filter</button>
                <a href="{{ route('admin.users.account-approvals') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        @if($approvals->isEmpty())
            <div class="p-6 text-sm text-gray-600">No account approvals found.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Franchise</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Documents</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($approvals as $approval)
                            @php
                                $user = $approval->user;
                                $docsByType = $user?->driverDocuments?->keyBy('document_type') ?? collect();
                            @endphp
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm font-medium text-gray-900">{{ $user?->name ?? 'Deleted user' }}</div>
                                    <div class="text-xs text-gray-500">{{ $user?->email ?? 'No email' }}</div>
                                    <div class="text-xs text-gray-500">{{ $user?->phone ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-800">{{ ucfirst($approval->role) }}</td>
                                <td class="px-4 py-3 align-top">
                                    @if($approval->franchise)
                                        <div class="text-sm text-gray-800">{{ $approval->franchise->name }}</div>
                                    @else
                                        <span class="text-xs text-gray-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        @if($user?->id_document_path)
                                            <a href="{{ asset('storage/' . $user->id_document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">ID</a>
                                        @endif
                                        @if($user?->driver_license_path)
                                            <a href="{{ asset('storage/' . $user->driver_license_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">License</a>
                                        @endif
                                        @if($docsByType->has('vehicle_license'))
                                            <a href="{{ asset('storage/' . $docsByType->get('vehicle_license')->document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">Vehicle</a>
                                        @endif
                                        @if($docsByType->has('merchant_ck'))
                                            <a href="{{ asset('storage/' . $docsByType->get('merchant_ck')->document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">CK</a>
                                        @endif
                                        @if($docsByType->has('merchant_bbbee'))
                                            <a href="{{ asset('storage/' . $docsByType->get('merchant_bbbee')->document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">B-BBEE</a>
                                        @endif
                                        @if($user?->bank_statement_path)
                                            <a href="{{ asset('storage/' . $user->bank_statement_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100">Bank</a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs
                                        {{ $approval->status === 'approved' ? 'bg-green-100 text-green-700' : ($approval->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ strtoupper($approval->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-xs text-gray-600">
                                    {{ optional($approval->submitted_at)->format('Y-m-d H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if($approval->status === 'pending')
                                        <form action="{{ route('admin.users.account-approvals.approve', $approval) }}" method="POST" class="space-y-2">
                                            @csrf
                                            <input type="text" name="notes" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Approval notes (optional)">
                                            <button type="submit" class="w-full px-3 py-1.5 text-xs font-semibold rounded bg-emerald-600 text-white hover:bg-emerald-700">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.users.account-approvals.reject', $approval) }}" method="POST" class="mt-2 space-y-2">
                                            @csrf
                                            <input type="text" name="notes" required class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Rejection reason" value="{{ old('notes') }}">
                                            <button type="submit" class="w-full px-3 py-1.5 text-xs font-semibold rounded bg-rose-600 text-white hover:bg-rose-700">
                                                Reject
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-xs text-gray-600">
                                            <p>{{ optional($approval->reviewer)->name ?? 'System' }}</p>
                                            <p>{{ optional($approval->reviewed_at)->format('Y-m-d H:i') ?? '-' }}</p>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $approvals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

