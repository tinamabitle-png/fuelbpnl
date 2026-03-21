@extends('Layouts.admin')

@section('title', 'Investor Outreach')
@section('page-title', 'Investor Outreach')

@section('breadcrumb')
    <span class="text-gray-500">Investor Outreach</span>
@endsection

@section('content')
<div class="space-y-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[1px] text-blue-600">Pre-seed campaign</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Compose a VC outreach email</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    This sends as <span class="font-semibold text-slate-900">{{ $defaults['from_email'] }}</span> with the Bwiser-styled template.
                    Add one or many recipients, tailor the pitch, and attach your deck or supporting material before sending.
                </p>
            </div>
            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                <div class="font-semibold">Suggested use</div>
                <div class="mt-1 text-blue-800">Pre-seed outreach to VCs, angels, and strategic partners.</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="font-semibold">Please fix the highlighted fields.</div>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.communications.investor-outreach.send') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
                    <div>
                        <label for="recipients" class="block text-sm font-medium text-slate-900">Recipients</label>
                        <p class="mt-1 text-xs text-slate-500">Separate email addresses with commas, spaces, or new lines.</p>
                        <textarea id="recipients" name="recipients" rows="4" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('recipients') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-slate-900">Subject</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject', $defaults['subject']) }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="headline" class="block text-sm font-medium text-slate-900">Headline</label>
                            <input id="headline" name="headline" type="text" value="{{ old('headline', $defaults['headline']) }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label for="preheader" class="block text-sm font-medium text-slate-900">Preheader</label>
                        <input id="preheader" name="preheader" type="text" value="{{ old('preheader', $defaults['preheader']) }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="intro" class="block text-sm font-medium text-slate-900">Intro</label>
                        <textarea id="intro" name="intro" rows="6" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('intro', $defaults['intro']) }}</textarea>
                    </div>

                    <div>
                        <label for="thesis" class="block text-sm font-medium text-slate-900">Investment thesis / problem statement</label>
                        <textarea id="thesis" name="thesis" rows="6" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('thesis', $defaults['thesis']) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label for="traction" class="block text-sm font-medium text-slate-900">Traction</label>
                            <textarea id="traction" name="traction" rows="5" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('traction', $defaults['traction']) }}</textarea>
                        </div>
                        <div>
                            <label for="ask" class="block text-sm font-medium text-slate-900">Funding ask</label>
                            <textarea id="ask" name="ask" rows="5" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('ask', $defaults['ask']) }}</textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cta_text" class="block text-sm font-medium text-slate-900">CTA text</label>
                            <input id="cta_text" name="cta_text" type="text" value="{{ old('cta_text', $defaults['cta_text']) }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="cta_url" class="block text-sm font-medium text-slate-900">CTA URL</label>
                            <input id="cta_url" name="cta_url" type="url" value="{{ old('cta_url', $defaults['cta_url']) }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label for="closing" class="block text-sm font-medium text-slate-900">Closing</label>
                        <textarea id="closing" name="closing" rows="6" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('closing', $defaults['closing']) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-slate-900">Sender</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">From name</dt>
                            <dd class="font-medium text-slate-900 text-right">{{ $defaults['from_name'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">From email</dt>
                            <dd class="font-medium text-slate-900 text-right">{{ $defaults['from_email'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Reply-to</dt>
                            <dd class="font-medium text-slate-900 text-right">{{ config('mail.investor_outreach_from.reply_to', 'support@bwiser.co.za') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-slate-900">Attachments</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Attach your deck, memo, screenshots, or one-pager. Up to 6 files, 10MB each.
                    </p>
                    <input name="attachments[]" type="file" multiple class="mt-4 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-3 text-xs text-slate-500">Supported: PDF, PowerPoint, Word, Excel, JPG, PNG.</p>
                </div>

                <div class="bg-slate-900 rounded-xl shadow-sm p-6 text-white">
                    <h3 class="text-base font-semibold">What gets sent</h3>
                    <ul class="mt-4 space-y-3 text-sm text-slate-200">
                        <li>Branded Bwiser email layout</li>
                        <li>Investment-focused narrative and CTA</li>
                        <li>Each recipient emailed individually</li>
                        <li>Attachments included on every send</li>
                    </ul>
                    <button type="submit" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        Send outreach email
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
