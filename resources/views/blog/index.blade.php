@extends('Layouts.guest')

@php
    /** @var array<int, array<string, mixed>> $posts */
    $metaTitle = 'Bwiser Blog';
    $metaDescription = 'Insights on South African fuel operations, voucher redemption, driver fuel credit, and merchant settlement workflows.';
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDescription)
@section('canonical', url()->current())

@section('content')
<section class="min-h-screen py-12 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="glass rounded-2xl p-6 md:p-10 border border-slate-200">
            <p class="text-xs uppercase tracking-[0.18em] text-blue-700">Bwiser Blog</p>
            <h1 class="brand-font text-3xl md:text-4xl font-semibold text-slate-900 mt-3">Fuel, Mobility, and Voucher Operations</h1>
            <p class="text-slate-700 mt-4 leading-relaxed max-w-3xl">
                Practical writing for drivers, stations, and finance teams building reliable fuel operations across South Africa.
            </p>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($posts as $post)
                    @php
                        $href = url('/blog/' . $post['slug']);
                        $img = (string) ($post['image_url'] ?? '');
                        $alt = (string) ($post['image_alt'] ?? 'Fuel image');
                        $date = (string) ($post['date'] ?? '');
                        $desc = (string) ($post['description'] ?? '');
                        $title = (string) ($post['title'] ?? '');
                        $keywords = (array) ($post['keywords'] ?? []);
                        $keywords = array_slice(array_values(array_filter(array_map(fn ($v) => trim((string) $v), $keywords))), 0, 3);
                    @endphp
                    <article class="surface-card overflow-hidden">
                        @if($img !== '')
                            <a href="{{ $href }}" aria-label="{{ $title }}">
                                <img src="{{ $img }}" alt="{{ $alt }}" class="h-44 w-full object-cover" loading="lazy">
                            </a>
                        @endif
                        <div class="p-5">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">
                                {{ $date }}
                            </p>
                            <h2 class="brand-font text-lg font-semibold text-slate-900 mt-2 leading-snug">
                                <a class="hover:text-blue-800" href="{{ $href }}">{{ $title }}</a>
                            </h2>
                            <p class="text-sm text-slate-700 mt-2 leading-relaxed">
                                {{ $desc }}
                            </p>
                            @if(!empty($keywords))
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($keywords as $kw)
                                        <span class="px-2 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                                            {{ $kw }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mt-5 flex items-center justify-between">
                                <a class="text-sm font-semibold text-blue-700 hover:text-blue-900" href="{{ $href }}">Read</a>
                                <div class="text-xs text-slate-500">
                                    <a class="hover:text-slate-700" href="{{ url('/drivers') }}">Drivers</a>
                                    <span class="mx-2">·</span>
                                    <a class="hover:text-slate-700" href="{{ url('/merchants') }}">Merchants</a>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection

