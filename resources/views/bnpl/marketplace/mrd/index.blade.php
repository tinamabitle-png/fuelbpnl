@extends('Layouts.app')

@section('title', 'Mr D Style Marketplace - Bwiser')

@push('head')
    <style>
        /* Marketplace styles (scoped) inspired by resources/views/mrd/ template. */
        .bw-mrd-shell {
            --bw-mrd-ink: #222222;
            --bw-mrd-muted: #6b7280;
            --bw-mrd-surface: #ffffff;
            --bw-mrd-soft: #f9f9f9;
            --bw-mrd-border: #e5e7eb;
            --bw-mrd-yellow: #FFC43F;
        }

        .bw-mrd-shell .bw-mrd-card {
            position: relative;
            background: var(--bw-mrd-surface);
            border: 1px solid var(--bw-mrd-border);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 22px 40px -34px rgba(15, 23, 42, 0.45);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .bw-mrd-shell .bw-mrd-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 26px 50px -36px rgba(15, 23, 42, 0.6);
        }

        .bw-mrd-shell .bw-mrd-figure {
            background: var(--bw-mrd-soft);
            border-radius: 12px;
            text-align: center;
            padding: 10px;
            overflow: hidden;
        }

        .bw-mrd-shell .bw-mrd-figure img {
            max-height: 190px;
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .bw-mrd-shell .bw-mrd-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(2, 13, 255, 0.10);
            color: #020DFF;
            border: 1px solid rgba(2, 13, 255, 0.14);
            font-weight: 800;
            font-size: 11px;
            padding: 6px 10px;
            border-radius: 9999px;
        }

        .bw-mrd-shell .bw-mrd-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        .bw-mrd-shell .bw-mrd-qty {
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9D9D9D;
        }

        .bw-mrd-shell .bw-mrd-rating {
            font-weight: 800;
            font-size: 12px;
            color: var(--bw-mrd-ink);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .bw-mrd-shell .bw-mrd-rating-dot {
            width: 10px;
            height: 10px;
            border-radius: 9999px;
            background: var(--bw-mrd-yellow);
            box-shadow: 0 0 0 4px rgba(255, 196, 63, 0.2);
        }

        .bw-mrd-shell .bw-mrd-title {
            font-weight: 800;
            font-size: 16px;
            line-height: 1.25;
            color: #111827;
            margin-top: 10px;
            min-height: 40px;
        }

        .bw-mrd-shell .bw-mrd-desc {
            font-size: 13px;
            color: rgba(100, 116, 139, 0.95);
            margin-top: 8px;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .bw-mrd-shell .bw-mrd-price {
            display: block;
            width: 100%;
            font-weight: 900;
            font-size: 20px;
            color: var(--bw-mrd-ink);
            margin-top: 10px;
        }

        .bw-mrd-shell .bw-mrd-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 12px;
        }

        .bw-mrd-shell .bw-mrd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 14px;
            padding: 10px 12px;
            font-weight: 800;
            font-size: 13px;
            border: 1px solid rgba(2, 13, 255, 0.18);
            background: #020DFF;
            color: white;
            text-decoration: none;
            width: 100%;
        }

        .bw-mrd-shell .bw-mrd-btn:hover {
            filter: brightness(1.05);
        }

        .bw-mrd-shell .bw-mrd-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--bw-mrd-border);
            background: rgba(255, 255, 255, 0.75);
            border-radius: 9999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            color: #111827;
            text-decoration: none;
            transition: background 0.15s ease;
            white-space: nowrap;
        }

        .bw-mrd-shell .bw-mrd-chip:hover {
            background: rgba(255, 255, 255, 1);
        }

        .bw-mrd-shell .bw-mrd-chip.is-active {
            border-color: rgba(2, 13, 255, 0.22);
            background: rgba(2, 13, 255, 0.08);
            color: #020DFF;
        }
    </style>
@endpush

@section('content')
    <section class="bw-mrd-shell max-w-7xl mx-auto px-6 pt-16 pb-20">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Bwiser Marketplace</p>
                <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Mr D Style Template</h1>
                <p class="text-sm text-slate-600 mt-2">
                    Prototype catalog view. We'll replace the sample JSON with scraped products and render them here.
                </p>
                <p class="text-xs text-slate-400 mt-2">Source: {{ $catalog['source'] ?? 'unknown' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('bnpl.marketplace.index', [], false) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
                    Drivers
                </a>
                <a href="{{ route('bnpl.orders.index', [], false) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
                    My Orders
                </a>
            </div>
        </div>

        <div class="mt-6 glass rounded-2xl border border-slate-200 bg-white p-5">
            <form method="GET" action="{{ route('bnpl.mrd.index', [], false) }}" class="flex flex-col lg:flex-row gap-3 lg:items-end">
                <div class="flex-1">
                    <label class="block text-xs uppercase tracking-[0.2em] text-slate-500">Search products</label>
                    <input name="q" value="{{ $q }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="e.g. milk, tomatoes, ketchup" />
                </div>
                <div class="min-w-[220px]">
                    <label class="block text-xs uppercase tracking-[0.2em] text-slate-500">Category</label>
                    <select name="category" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3">
                        <option value="">All</option>
                        @foreach($categories as $c)
                            <option value="{{ $c['id'] }}" @selected($category === $c['id'])>{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-3 rounded-xl text-sm font-semibold text-white" style="background:#020DFF;">
                    Apply
                </button>
            </form>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('bnpl.mrd.index', [], false) }}" class="bw-mrd-chip {{ $category === '' ? 'is-active' : '' }}">All</a>
            @foreach($categories as $c)
                <a
                    href="{{ route('bnpl.mrd.index', ['category' => $c['id'], 'q' => $q], false) }}"
                    class="bw-mrd-chip {{ $category === $c['id'] ? 'is-active' : '' }}"
                >
                    {{ $c['name'] }}
                </a>
            @endforeach
        </div>

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($products as $product)
                <x-mrd.product-card :product="$product" :currency="($catalog['currency'] ?? 'ZAR')" />
            @empty
                <div class="glass rounded-2xl border border-slate-200 bg-white p-8 text-slate-700">
                    No products found.
                </div>
            @endforelse
        </div>
    </section>
@endsection

