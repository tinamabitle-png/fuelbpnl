@extends('Layouts.guest')

@php
    /** @var array<string, mixed> $post */
    $title = (string) ($post['title'] ?? 'Bwiser Blog');
    $desc = (string) ($post['description'] ?? '');
    $date = (string) ($post['date'] ?? '');
    $img = (string) ($post['image_url'] ?? '');
    $imgAlt = (string) ($post['image_alt'] ?? 'Fuel image');
    $keywords = (array) ($post['keywords'] ?? []);
    $keywords = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $keywords)));
@endphp

@section('title', $title)
@section('meta_description', $desc)
@section('canonical', url()->current())
@section('og_image', $img !== '' ? $img : asset('images/brand-logo.png'))
@section('og_image_alt', $imgAlt)

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $title,
    'datePublished' => $date,
    'dateModified' => $date,
    'image' => $img !== '' ? [$img] : [],
    'author' => [
        '@type' => 'Organization',
        'name' => (string) config('seo.site_name', 'Bwiser'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => (string) config('seo.site_name', 'Bwiser'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/brand-logo.png'),
        ],
    ],
    'mainEntityOfPage' => url()->current(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<section class="min-h-screen py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="glass rounded-2xl p-6 md:p-10 border border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs uppercase tracking-[0.18em] text-blue-700">Bwiser Blog</p>
                <a class="text-sm font-semibold text-slate-700 hover:text-slate-900" href="{{ url('/blog') }}">All posts</a>
            </div>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-3 leading-tight">
                {{ $title }}
            </h1>
            <p class="text-slate-600 mt-3 text-sm">{{ $date }}</p>
            <p class="text-slate-800 mt-4 leading-relaxed">{{ $desc }}</p>

            @if($img !== '')
                <div class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <img src="{{ $img }}" alt="{{ $imgAlt }}" class="w-full h-[280px] object-cover" loading="lazy">
                </div>
            @endif

            @if(!empty($keywords))
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach(array_slice($keywords, 0, 8) as $kw)
                        <span class="px-2 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">{{ $kw }}</span>
                    @endforeach
                </div>
            @endif

            <div class="prose max-w-none mt-8">
                @foreach((array) ($post['sections'] ?? []) as $section)
                    @php
                        $h2 = (string) ($section['h2'] ?? '');
                        $body = (array) ($section['body'] ?? []);
                    @endphp
                    @if($h2 !== '')
                        <h2 class="brand-font text-2xl font-semibold text-slate-900 mt-8">{{ $h2 }}</h2>
                    @endif
                    @foreach($body as $p)
                        <p class="text-slate-800 leading-relaxed mt-3">{{ $p }}</p>
                    @endforeach
                @endforeach
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="surface-card p-6">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Drivers</p>
                    <p class="text-sm text-slate-700 mt-2">Apply for voucher-based fuel credit access and get queued for rollout approvals.</p>
                    <a class="btn-primary inline-flex px-4 py-2.5 rounded-xl text-sm font-semibold mt-4" href="{{ route('register.driver') }}">Driver signup</a>
                </div>
                <div class="surface-card p-6">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Merchants</p>
                    <p class="text-sm text-slate-700 mt-2">Accept voucher redemptions, track settlements, and reconcile with audit visibility.</p>
                    <a class="btn-ghost inline-flex px-4 py-2.5 rounded-xl text-sm font-semibold mt-4" href="{{ route('register.merchant') }}">Merchant signup</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
