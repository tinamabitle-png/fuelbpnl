@extends('Layouts.admin')

@section('title', 'Registration Documents')
@section('page-title', 'Registration Documents')
@section('breadcrumb', 'Registration Documents')

@section('content')
<div class="space-y-6">
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Name, email, phone, ID number"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Role</label>
                <select
                    name="role"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">All roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('admin.users.registration-documents') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 text-sm text-gray-600">
            Showing users with registration document uploads (driver and merchant compliance docs).
        </div>

        @if($users->isEmpty())
            <div class="p-6 text-sm text-gray-600">No registration documents found.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Documents</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Payment Preference</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Extracted</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($users as $user)
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email ?? 'No email' }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->phone }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 mr-1 mb-1">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-500">No role</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @php
                                        $docsByType = $user->driverDocuments->keyBy('document_type');
                                    @endphp
                                    <div class="flex flex-wrap gap-2">
                                        @if($user->id_document_path)
                                            <a href="{{ asset('storage/' . $user->id_document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">ID</a>
                                        @endif
                                        @if($user->driver_license_path)
                                            <a href="{{ asset('storage/' . $user->driver_license_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">License</a>
                                        @endif
                                        @if($docsByType->has('vehicle_license'))
                                            <a href="{{ asset('storage/' . $docsByType->get('vehicle_license')->document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">Vehicle License</a>
                                        @endif
                                        @if($docsByType->has('merchant_ck'))
                                            <a href="{{ asset('storage/' . $docsByType->get('merchant_ck')->document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">CK</a>
                                        @endif
                                        @if($docsByType->has('merchant_bbbee'))
                                            <a href="{{ asset('storage/' . $docsByType->get('merchant_bbbee')->document_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">B-BBEE</a>
                                        @endif
                                        @if($user->bank_statement_path)
                                            <a href="{{ asset('storage/' . $user->bank_statement_path) }}" target="_blank" class="text-xs px-2 py-1 rounded border border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100">Bank Statement</a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm text-gray-800">{{ $user->payment_method_preference ?? 'Not set' }}</div>
                                    @if($user->payment_bank_name || $user->payment_account_number)
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $user->payment_bank_name ?? 'Bank N/A' }} | {{ $user->payment_account_number ?? 'Account N/A' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @php
                                        $uploads = $user->bankStatementUploads;
                                        $latestUpload = $uploads->sortByDesc('id')->first();
                                        $latestDecision = $latestUpload ? $latestUpload->creditDecisions->sortByDesc('decided_at')->first() : null;
                                        $hasExtracted = $uploads->contains(function ($upload) {
                                            return $upload->creditDecisions->isNotEmpty();
                                        });
                                        $hasProcessing = $uploads->contains(function ($upload) {
                                            return in_array($upload->status, ['pending', 'processing'], true);
                                        });
                                        $hasFailed = $uploads->contains(function ($upload) {
                                            return $upload->status === 'failed';
                                        });

                                        if ($hasExtracted) {
                                            $extractBadgeClass = 'bg-green-100 text-green-700';
                                            $extractLabel = 'AI extracted';
                                        } elseif ($hasProcessing) {
                                            $extractBadgeClass = 'bg-yellow-100 text-yellow-700';
                                            $extractLabel = 'Processing';
                                        } elseif ($hasFailed) {
                                            $extractBadgeClass = 'bg-red-100 text-red-700';
                                            $extractLabel = 'Failed';
                                        } elseif ($user->bank_statement_path) {
                                            $extractBadgeClass = 'bg-blue-100 text-blue-700';
                                            $extractLabel = 'Uploaded only';
                                        } else {
                                            $extractBadgeClass = 'bg-gray-100 text-gray-600';
                                            $extractLabel = 'Pending / None';
                                        }
                                    @endphp
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs {{ $extractBadgeClass }}">
                                        {{ $extractLabel }}
                                    </span>
                                    @if($latestDecision)
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ strtoupper((string) $latestDecision->decision) }} | score {{ (int) $latestDecision->score }}
                                        </div>
                                        <div class="mt-1 max-w-xs text-xs text-blue-800">
                                            {{ \App\Support\CreditDecisionSummary::brief($latestDecision) }}
                                        </div>
                                    @endif
                                    @if($latestUpload && $latestUpload->error_message)
                                        <div class="mt-1 max-w-xs text-xs text-red-600">
                                            {{ \Illuminate\Support\Str::limit($latestUpload->error_message, 140) }}
                                        </div>
                                    @endif
                                    @if($user->bank_statement_path && (!$hasProcessing || $hasFailed))
                                        <form action="{{ route('admin.users.bank-statement.review', $user) }}" method="POST" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="action" value="reassess">
                                            @if($latestUpload)
                                                <input type="hidden" name="upload_id" value="{{ $latestUpload->id }}">
                                            @endif
                                            <button type="submit" class="text-xs px-2 py-1 rounded border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100">
                                                {{ $hasFailed ? 'Retry AI extraction' : 'Run AI extraction' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700">
                                    <div>{{ optional($user->created_at)->format('Y-m-d H:i') }}</div>
                                    <div class="text-xs text-gray-500">{{ optional($user->created_at)->diffForHumans() }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                        Open User
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
