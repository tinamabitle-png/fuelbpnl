@extends('layouts.app')

@section('title', 'Upload Bank Statement')

@section('content')
<section class="max-w-3xl mx-auto px-6 py-12">
    <div class="glass rounded-3xl p-8">
        <h1 class="text-2xl font-semibold text-slate-900">Upload Bank Statement</h1>
        <p class="mt-2 text-sm text-slate-500">
            Upload a scanned PDF statement for the last 3 months. We only store extracted data.
        </p>

        @if(session('success'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('bank-statements.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Statement PDF</label>
                <input type="file" name="statement" accept="application/pdf" class="w-full rounded-xl border border-slate-200 px-4 py-2 bg-white" required>
            </div>
            <button type="submit" class="btn-primary px-5 py-2 rounded-xl">Upload & Process</button>
        </form>
    </div>
</section>
@endsection
