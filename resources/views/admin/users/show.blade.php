@extends('Layouts.admin')

@section('title', $user->name . ' - User Profile')
@section('page-title', 'User Profile')
@section('page-description', 'View and manage user account details')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800">Users</a>
    <i class="fas fa-chevron-right mx-2 text-xs"></i>
    <span>{{ $user->name }}</span>
@endsection

@section('stats')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Wallet Balance -->
    <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl shadow-sm border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-600 text-sm font-semibold">Wallet Balance</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    ZAR {{ number_format($user->wallet->balance ?? 0, 2) }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                <i class="fas fa-wallet text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Outstanding: <span class="font-semibold">ZAR {{ number_format($user->wallet->outstanding_balance ?? 0, 2) }}</span>
        </div>
    </div>

    <!-- Credit Limit -->
    <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-2xl shadow-sm border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-600 text-sm font-semibold">Credit Limit</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    ZAR {{ number_format($user->creditLimit->limit ?? 0) }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-xl">
                <i class="fas fa-credit-card text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                @php
                    $utilization = $user->creditLimit && $user->creditLimit->limit > 0 ? 
                        ($user->creditLimit->used / $user->creditLimit->limit) * 100 : 0;
                    $color = $utilization > 80 ? 'bg-red-500' : 
                             ($utilization > 50 ? 'bg-yellow-500' : 'bg-green-500');
                @endphp
                <div class="h-2 rounded-full {{ $color }}" 
                     style="width: {{ min($utilization, 100) }}%"></div>
            </div>
            <div class="text-xs text-gray-600 mt-1">
                {{ number_format($utilization, 1) }}% utilized (ZAR {{ number_format($user->creditLimit->used ?? 0) }})
            </div>
        </div>
    </div>

    <!-- Credit Score -->
    <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl shadow-sm border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-600 text-sm font-semibold">Credit Score</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    {{ $user->credit_score ?? 'N/A' }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            @if($user->credit_score)
                @php
                    $scoreColor = $user->credit_score >= 700 ? 'bg-green-100 text-green-800' : 
                                 ($user->credit_score >= 600 ? 'bg-yellow-100 text-yellow-800' : 
                                 'bg-red-100 text-red-800');
                    $scoreText = $user->credit_score >= 700 ? 'Excellent' : 
                                 ($user->credit_score >= 600 ? 'Good' : 'Poor');
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $scoreColor }}">
                    {{ $scoreText }}
                </span>
            @endif
        </div>
    </div>

    <!-- Account Status -->
    <div class="bg-gradient-to-br from-yellow-50 to-white p-5 rounded-2xl shadow-sm border border-yellow-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Account Status</p>
                <p class="text-3xl font-bold text-gray-900 mt-2 capitalize">
                    {{ $user->status }}
                </p>
            </div>
            <div class="p-3 bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl">
                <i class="fas fa-user-shield text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            @php
                $statusColors = [
                    'active' => ['bg-green-100', 'text-green-800', 'Active'],
                    'suspended' => ['bg-yellow-100', 'text-yellow-800', 'Suspended'],
                    'flagged' => ['bg-orange-100', 'text-orange-800', 'Flagged'],
                    'blocked' => ['bg-red-100', 'text-red-800', 'Blocked'],
                ];
                $status = $statusColors[$user->status] ?? $statusColors['active'];
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $status[0] }} {{ $status[1] }}">
                {{ $status[2] }}
            </span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-6">
    <!-- Profile Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-lg overflow-hidden mb-6 z-10">
        <div class="p-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-white/20 to-white/10 flex items-center justify-center border-4 border-white/30 backdrop-blur-sm">
                            <span class="text-white font-bold text-4xl">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        @if($user->status == 'active')
                            <div class="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-2 border-white rounded-full"></div>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ $user->name }}</h1>
                        <div class="flex flex-wrap gap-2 mt-3">
                            @if($user->email)
                                <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                    <i class="fas fa-envelope mr-2"></i> {{ $user->email }}
                                </span>
                            @endif
                            <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                <i class="fas fa-phone mr-2"></i> {{ $user->phone }}
                            </span>
                            <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                <i class="fas fa-user-tag mr-2"></i> ID: {{ $user->id }}
                            </span>
                            <span class="px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                <i class="fas fa-calendar-alt mr-2"></i> Joined {{ $user->created_at->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3 mt-6 md:mt-0">
                    <a href="{{ route('admin.users.edit', $user) }}" 
                       class="px-5 py-2.5 bg-white text-blue-600 rounded-xl font-semibold hover:bg-blue-50 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-edit mr-2"></i> Edit Profile
                    </a>
                    @if($user->email)
                        <a href="mailto:{{ $user->email }}" 
                           class="px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl font-semibold hover:bg-white/30 shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-paper-plane mr-2"></i> Send Email
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Information -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-user-circle text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Personal Information</h3>
                            <p class="text-gray-600 text-sm">User's personal and contact details</p>
                        </div>
                    </div>
                    <span class="text-xs bg-gray-100 text-gray-800 px-3 py-1 rounded-full font-medium">
                        Member since {{ $user->created_at->format('F Y') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Full Name
                            </label>
                            <p class="text-gray-900 font-medium">{{ $user->name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Email Address
                            </label>
                            <div class="flex items-center">
                                <p class="text-gray-900 font-medium">{{ $user->email ?? 'Not provided' }}</p>
                                @if($user->email_verified_at)
                                    <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full">
                                        <i class="fas fa-check mr-1"></i> Verified
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Phone Number
                            </label>
                            <p class="text-gray-900 font-medium">{{ $user->phone }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Account Status
                            </label>
                            <div class="flex items-center space-x-2">
                                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $status[0] }} {{ $status[1] }}">
                                    {{ $status[2] }}
                                </span>
                                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                                            onclick="return confirm('Are you sure you want to {{ $user->status === 'active' ? 'suspend' : 'activate' }} this user?')">
                                        {{ $user->status === 'active' ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Assigned Role
                            </label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->roles as $role)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                        {{ $role->name == 'admin' ? 'bg-red-100 text-red-800' : 
                                           ($role->name == 'borrower' ? 'bg-blue-100 text-blue-800' : 
                                           'bg-gray-100 text-gray-800') }}">
                                        <i class="fas {{ $role->name == 'admin' ? 'fa-shield-alt' : 'fa-user' }} mr-1.5"></i>
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Last Login
                            </label>
                            <p class="text-gray-900">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->format('M d, Y \a\t h:i A') }}
                                    <span class="text-gray-500 text-sm">({{ $user->last_login_at->diffForHumans() }})</span>
                                @else
                                    Never logged in
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($underwritingSummary))
                <div class="bg-white rounded-2xl shadow-sm border border-blue-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-sliders-h text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Driver Underwriting</h3>
                                <p class="text-gray-600 text-sm">Internal voucher risk policy snapshot</p>
                            </div>
                        </div>
                        <span class="text-xs px-3 py-1 rounded-full font-medium {{ ($underwritingSummary['tier'] ?? 'starter') === 'growth' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $underwritingSummary['tier_label'] ?? 'STARTER' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500">Current Voucher Cap</p>
                            <p class="text-xl font-semibold text-gray-900 mt-1">ZAR {{ number_format((float) ($underwritingSummary['max_amount'] ?? 0), 2) }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500">Rate Adjustment</p>
                            <p class="text-xl font-semibold {{ (float) ($underwritingSummary['rate_penalty'] ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' }} mt-1">
                                {{ (float) ($underwritingSummary['rate_penalty'] ?? 0) > 0 ? '+' : '' }}{{ number_format((float) ($underwritingSummary['rate_penalty'] ?? 0), 2) }}%
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500">Account Age</p>
                            <p class="text-xl font-semibold text-gray-900 mt-1">{{ (int) ($underwritingSummary['account_age_days'] ?? 0) }} days</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500">Late Repayment Flag</p>
                            <p class="text-xl font-semibold {{ !empty($underwritingSummary['late_repayment_detected']) ? 'text-rose-700' : 'text-emerald-700' }} mt-1">
                                {{ !empty($underwritingSummary['late_repayment_detected']) ? 'Detected' : 'Clear' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if($user->hasRole('driver'))
                @php
                    $docsByType = $user->driverDocuments->keyBy('document_type');
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-indigo-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-id-card text-indigo-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Driver Documents</h3>
                                <p class="text-gray-600 text-sm">Schema-restored compliance verification workflow</p>
                            </div>
                        </div>
                        <span class="text-xs px-3 py-1 rounded-full font-medium {{ ($user->id_verification_status ?? 'unverified') === 'verified' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ strtoupper((string) ($user->id_verification_status ?? 'unverified')) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach(['sa_id' => 'SA ID', 'driver_license' => 'Driver License'] as $docType => $docLabel)
                            @php $doc = $docsByType->get($docType); @endphp
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs uppercase tracking-wider text-gray-500">{{ $docLabel }}</p>
                                @if($doc)
                                    <p class="mt-2 text-sm text-gray-800">
                                        Status:
                                        <span class="font-semibold {{ $doc->verified ? 'text-emerald-700' : 'text-amber-700' }}">
                                            {{ $doc->verified ? 'Verified' : 'Pending' }}
                                        </span>
                                    </p>
                                    <a href="{{ asset('storage/' . $doc->document_path) }}" target="_blank" rel="noopener" class="inline-flex mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="fas fa-file-alt mr-2 mt-1"></i> Open Document
                                    </a>
                                    <form action="{{ route('admin.users.driver-documents.verify', [$user, $docType]) }}" method="POST" class="mt-3 flex flex-wrap gap-2">
                                        @csrf
                                        <input type="hidden" name="action" value="verify">
                                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Verify</button>
                                    </form>
                                    <form action="{{ route('admin.users.driver-documents.verify', [$user, $docType]) }}" method="POST" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <input type="text" name="notes" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm" placeholder="Reason for re-submission (optional)">
                                        <button type="submit" class="mt-2 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700">Mark For Re-Upload</button>
                                    </form>
                                @else
                                    <p class="mt-2 text-sm text-gray-500">No document uploaded.</p>
                                @endif
                            </div>
                        @endforeach

                        @php
                            $latestUpload = $user->bankStatementUploads->sortByDesc('id')->first();
                            $latestDecision = $latestUpload?->creditDecisions?->sortByDesc('decided_at')->first();
                            $recommendedLimit = (float) data_get($latestDecision?->explanation_json, 'agent_recommendation.recommendation.recommended_limit', 0);
                            $decisionLabel = strtoupper((string) ($latestDecision?->decision ?? 'pending'));
                        @endphp
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500">Bank Statement</p>
                            @if($user->bank_statement_path)
                                <a href="{{ asset('storage/' . $user->bank_statement_path) }}" target="_blank" rel="noopener" class="inline-flex mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="fas fa-file-pdf mr-2 mt-1"></i> Open Statement
                                </a>
                                <p class="mt-2 text-sm text-gray-700">
                                    Upload status:
                                    <span class="font-semibold">{{ strtoupper((string) ($latestUpload->status ?? 'pending')) }}</span>
                                </p>
                                <p class="mt-1 text-sm text-gray-700">
                                    AI decision:
                                    <span class="font-semibold">{{ $decisionLabel }}</span>
                                    @if($latestDecision)
                                        (score {{ (int) $latestDecision->score }})
                                    @endif
                                </p>
                                @if($recommendedLimit > 0)
                                    <p class="mt-1 text-sm text-gray-700">
                                        Recommended limit:
                                        <span class="font-semibold">ZAR {{ number_format($recommendedLimit, 2) }}</span>
                                    </p>
                                @endif
                                <form action="{{ route('admin.users.bank-statement.review', $user) }}" method="POST" class="mt-3 space-y-2">
                                    @csrf
                                    <input type="hidden" name="upload_id" value="{{ $latestUpload?->id }}">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" name="action" value="approve" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reassess" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                                            Reassess
                                        </button>
                                        <button type="submit" name="action" value="reject" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700">
                                            Mark Review Needed
                                        </button>
                                    </div>
                                    <label class="inline-flex items-center text-xs text-gray-700">
                                        <input type="checkbox" name="apply_recommended_limit" value="1" class="rounded border-gray-300 mr-2">
                                        Apply recommended credit limit (capped by system threshold)
                                    </label>
                                    <input type="text" name="notes" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm" placeholder="Notes (optional)">
                                </form>
                            @else
                                <p class="mt-2 text-sm text-gray-500">No bank statement uploaded.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Financial Overview -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-chart-pie text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Financial Overview</h3>
                            <p class="text-gray-600 text-sm">User's financial status and limits</p>
                        </div>
                    </div>
                    @if($user->creditLimit)
                        <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                            Next review: {{ $user->creditLimit->review_date->format('M d, Y') }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Credit Limit Card -->
                    <div class="bg-gradient-to-br from-gray-50 to-white p-5 rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-gray-900">Credit Limit</h4>
                            <button onclick="showCreditLimitModal()" 
                                    class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200">
                                <i class="fas fa-edit mr-1"></i> Adjust
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Total Limit:</span>
                                    <span class="font-bold text-gray-900">ZAR {{ number_format($user->creditLimit->limit ?? 0) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full {{ $color }}" 
                                         style="width: {{ min($utilization, 100) }}%"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Used:</span>
                                    <span class="font-medium text-gray-900 block">ZAR {{ number_format($user->creditLimit->used ?? 0) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Available:</span>
                                    <span class="font-medium text-green-600 block">
                                        ZAR {{ number_format(($user->creditLimit->limit ?? 0) - ($user->creditLimit->used ?? 0)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wallet Card -->
                    <div class="bg-gradient-to-br from-gray-50 to-white p-5 rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-gray-900">Wallet Balance</h4>
                            <button onclick="showWalletModal()" 
                                    class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200">
                                <i class="fas fa-exchange-alt mr-1"></i> Adjust
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">
                                    ZAR {{ number_format($user->wallet->balance ?? 0, 2) }}
                                </div>
                                <div class="text-sm text-gray-600">Current balance</div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Outstanding:</span>
                                    <span class="font-medium text-red-600 block">
                                        ZAR {{ number_format($user->wallet->outstanding_balance ?? 0, 2) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Currency:</span>
                                    <span class="font-medium text-gray-900 block">{{ $user->wallet->currency ?? 'ZAR' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credit Score -->
                <div class="mt-6 bg-gradient-to-br from-gray-50 to-white p-5 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold text-gray-900">Credit Score & History</h4>
                        <span class="text-xs bg-purple-100 text-purple-800 px-3 py-1 rounded-full">
                            {{ $user->credit_score ?? 'Not set' }}
                        </span>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600">Credit Score</span>
                                <span class="font-bold text-gray-900">{{ $user->credit_score ?? 'N/A' }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                @php
                                    $scorePercentage = $user->credit_score ? (($user->credit_score - 300) / (850 - 300)) * 100 : 0;
                                    $scoreBarColor = $user->credit_score >= 700 ? 'bg-green-500' : 
                                                    ($user->credit_score >= 600 ? 'bg-yellow-500' : 'bg-red-500');
                                @endphp
                                <div class="h-3 rounded-full {{ $scoreBarColor }}" 
                                     style="width: {{ min(max($scorePercentage, 0), 100) }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>300 (Poor)</span>
                                <span>580 (Fair)</span>
                                <span>670 (Good)</span>
                                <span>740 (Very Good)</span>
                                <span>800 (Excellent)</span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                            Credit limit is automatically calculated based on this score
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-history text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                            <p class="text-gray-600 text-sm">User's recent transactions and actions</p>
                        </div>
                    </div>
                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        View All Activity
                    </button>
                </div>

                <!-- Activity Timeline -->
                <div class="space-y-4">
                    <!-- Placeholder for activity feed -->
                    <div class="flex items-start p-3 hover:bg-gray-50 rounded-xl transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">Account created</p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-clock mr-1"></i> {{ $user->created_at->format('M d, Y \a\t h:i A') }}
                            </p>
                        </div>
                    </div>

                    @if($user->last_login_at)
                    <div class="flex items-start p-3 hover:bg-gray-50 rounded-xl transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-sign-in-alt text-green-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">Last login</p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-clock mr-1"></i> {{ $user->last_login_at->format('M d, Y \a\t h:i A') }}
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($user->wallet && $user->wallet->transactions->count() > 0)
                        @foreach($user->wallet->transactions()->latest()->take(3)->get() as $transaction)
                        <div class="flex items-start p-3 hover:bg-gray-50 rounded-xl transition-colors">
                            <div class="w-8 h-8 rounded-lg {{ $transaction->type == 'credit' ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center mr-3">
                                <i class="fas {{ $transaction->type == 'credit' ? 'fa-plus' : 'fa-minus' }} {{ $transaction->type == 'credit' ? 'text-green-600' : 'text-red-600' }} text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">
                                    {{ $transaction->type == 'credit' ? 'Wallet credited' : 'Wallet debited' }} - ZAR {{ number_format($transaction->amount, 2) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $transaction->description }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <i class="fas fa-clock mr-1"></i> {{ $transaction->created_at->format('M d, Y \a\t h:i A') }}
                                </p>
                            </div>
                        </div>
                        @endforeach
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-exchange-alt text-3xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">No recent activity</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-bolt text-yellow-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        <p class="text-gray-600 text-sm">Manage user account quickly</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('admin.users.edit', $user) }}" 
                       class="flex items-center p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                        <i class="fas fa-edit mr-3"></i>
                        <span class="font-medium">Edit Profile</span>
                    </a>
                    
                    @if($user->email)
                    <a href="mailto:{{ $user->email }}" 
                       class="flex items-center p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-100 transition-colors">
                        <i class="fas fa-envelope mr-3"></i>
                        <span class="font-medium">Send Email</span>
                    </a>
                    @endif
                    
                    <a href="sms:{{ $user->phone }}" 
                       class="flex items-center p-3 bg-purple-50 text-purple-700 rounded-xl hover:bg-purple-100 transition-colors">
                        <i class="fas fa-sms mr-3"></i>
                        <span class="font-medium">Send SMS</span>
                    </a>
                    
                    <button onclick="showResetPasswordModal()" 
                            class="w-full flex items-center p-3 bg-red-50 text-red-700 rounded-xl hover:bg-red-100 transition-colors text-left">
                        <i class="fas fa-key mr-3"></i>
                        <span class="font-medium">Reset Password</span>
                    </button>
                    
                    @if($user->id != auth()->id())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirm('WARNING: This will permanently delete this user and all associated data. This action cannot be undone. Are you sure?')"
                                    class="w-full flex items-center p-3 bg-red-50 text-red-700 rounded-xl hover:bg-red-100 transition-colors text-left">
                                <i class="fas fa-trash mr-3"></i>
                                <span class="font-medium">Delete Account</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Vouchers Summary -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-ticket-alt text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Vouchers</h3>
                            <p class="text-gray-600 text-sm">Fuel voucher usage</p>
                        </div>
                    </div>
                    <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-medium">
                        {{ $user->vouchers->count() }} total
                    </span>
                </div>

                <div class="space-y-3">
                    @php
                        $activeVouchers = $user->vouchers->where('status', 'active')->count();
                        $usedVouchers = $user->vouchers->where('status', 'used')->count();
                        $expiredVouchers = $user->vouchers->where('status', 'expired')->count();
                    @endphp
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active</span>
                        <span class="font-bold text-green-600">{{ $activeVouchers }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Used</span>
                        <span class="font-bold text-blue-600">{{ $usedVouchers }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Expired</span>
                        <span class="font-bold text-red-600">{{ $expiredVouchers }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-external-link-alt mr-2"></i> View All Vouchers
                    </a>
                </div>
            </div>

            <!-- Leases Summary -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-contract text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Leases</h3>
                            <p class="text-gray-600 text-sm">Active loan agreements</p>
                        </div>
                    </div>
                    <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                        {{ $user->leases->count() }} total
                    </span>
                </div>

                <div class="space-y-3">
                    @php
                        $activeLeases = $user->leases->where('status', 'active')->count();
                        $completedLeases = $user->leases->where('status', 'completed')->count();
                        $overdueLeases = $user->leases->where('status', 'overdue')->count();
                    @endphp
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active</span>
                        <span class="font-bold text-green-600">{{ $activeLeases }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Completed</span>
                        <span class="font-bold text-blue-600">{{ $completedLeases }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Overdue</span>
                        <span class="font-bold text-red-600">{{ $overdueLeases }}</span>
                    </div>
                    
                    @if($user->leases->count() > 0)
                        @php
                            $totalRepayments = $user->leases->sum(function($lease) {
                                return $lease->repayments->sum('amount');
                            });
                        @endphp
                        <div class="pt-3 border-t border-gray-200">
                            <div class="text-sm text-gray-600">Total Repayments:</div>
                            <div class="font-bold text-gray-900">ZAR {{ number_format($totalRepayments, 2) }}</div>
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-external-link-alt mr-2"></i> View All Leases
                    </a>
                </div>
            </div>

            <!-- System Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-info-circle text-gray-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
                        <p class="text-gray-600 text-sm">Technical details</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            User ID
                        </label>
                        <p class="text-sm font-mono text-gray-900">{{ $user->id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Created At
                        </label>
                        <p class="text-sm text-gray-900">{{ $user->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Updated At
                        </label>
                        <p class="text-sm text-gray-900">{{ $user->updated_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Database Records
                        </label>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>Wallet: {{ $user->wallet ? 'Yes' : 'No' }}</div>
                            <div>Credit Limit: {{ $user->creditLimit ? 'Yes' : 'No' }}</div>
                            <div>Roles: {{ $user->roles->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Credit Limit Modal -->
<div id="creditLimitModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form action="{{ route('admin.users.credit-limit.update', $user) }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Adjust Credit Limit</h3>
                    <button type="button" onclick="closeCreditLimitModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">User: <span class="font-semibold text-gray-900">{{ $user->name }}</span></p>
                    <p class="text-sm text-gray-500 mt-1">
                        Current Limit: <span class="font-medium">ZAR {{ number_format($user->creditLimit->limit ?? 0) }}</span>
                    </p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Limit (ZAR)
                        </label>
                        <input type="number" 
                               name="limit" 
                               required
                               min="0"
                               step="100"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter new credit limit"
                               value="{{ $user->creditLimit->limit ?? 0 }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for Adjustment
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain the reason for this adjustment..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeCreditLimitModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Limit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Wallet Adjustment Modal -->
<div id="walletModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form action="{{ route('admin.users.wallet.update', $user) }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Adjust Wallet Balance</h3>
                    <button type="button" onclick="closeWalletModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">User: <span class="font-semibold text-gray-900">{{ $user->name }}</span></p>
                    <p class="text-sm text-gray-500 mt-1">
                        Current Balance: <span class="font-medium">ZAR {{ number_format($user->wallet->balance ?? 0, 2) }}</span>
                    </p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type
                        </label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="credit" 
                                       checked
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Credit (Add)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="debit" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Debit (Subtract)</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Amount (ZAR)
                        </label>
                        <input type="number" 
                               name="amount" 
                               required
                               min="0"
                               step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter amount">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reason
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Explain the reason for this adjustment..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="closeWalletModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Wallet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <form action="{{ route('admin.users.force-password-reset', $user) }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Force Password Reset</h3>
                    <button type="button" onclick="closeResetPasswordModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600">Force password reset for <span class="font-semibold text-gray-900">{{ $user->name }}</span>?</p>
                    <p class="text-sm text-gray-500 mt-2">
                        User will be required to set a new password on next login. An email notification will be sent.
                    </p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            onclick="closeResetPasswordModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Confirm Reset
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Credit Limit Modal
    function showCreditLimitModal() {
        document.getElementById('creditLimitModal').classList.remove('hidden');
    }
    
    function closeCreditLimitModal() {
        document.getElementById('creditLimitModal').classList.add('hidden');
    }

    // Wallet Modal
    function showWalletModal() {
        document.getElementById('walletModal').classList.remove('hidden');
    }
    
    function closeWalletModal() {
        document.getElementById('walletModal').classList.add('hidden');
    }

    // Reset Password Modal
    function showResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.remove('hidden');
    }
    
    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
    }

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreditLimitModal();
            closeWalletModal();
            closeResetPasswordModal();
        }
    });

    // Handle form submissions with AJAX for better UX
    document.querySelectorAll('#creditLimitModal form, #walletModal form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            submitButton.disabled = true;
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(data.message || 'Operation completed successfully!');
                    
                    // Close modal
                    if (this.closest('#creditLimitModal')) {
                        closeCreditLimitModal();
                    } else {
                        closeWalletModal();
                    }
                    
                    // Reload page to show updated data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(data.message || 'An error occurred');
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
    });
</script>
@endsection
