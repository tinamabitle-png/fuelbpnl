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
                <button
                    type="button"
                    id="bw-mrd-use-location"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50"
                >
                    Use my location
                </button>
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
                    <div class="font-semibold text-slate-900">Nearby restaurants only</div>
                    <div class="text-sm text-slate-600 mt-1">
                        Click <span class="font-semibold">Use my location</span> to load restaurants near you.
                    </div>
                </div>
            @endforelse
        </div>

        <div id="bw-mrd-location-modal" class="fixed inset-0 z-[100] hidden">
            <div class="absolute inset-0 bg-slate-900/40" data-bw-mrd-location-close></div>
            <div class="relative h-full w-full flex items-center justify-center p-5">
                <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">Location needed</div>
                            <div class="text-sm text-slate-600 mt-1" id="bw-mrd-location-modal-msg">
                                Location permission was denied. You can still continue by selecting a city or entering coordinates.
                            </div>
                        </div>
                        <button type="button" class="px-2 py-1 text-slate-500 hover:text-slate-700" data-bw-mrd-location-close aria-label="Close">
                            ✕
                        </button>
                    </div>

                    <div class="mt-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Quick pick</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="px-3 py-2 rounded-xl text-sm font-semibold border border-slate-200 hover:bg-slate-50" data-bw-mrd-preset data-lat="-26.2041" data-lng="28.0473">
                                Johannesburg
                            </button>
                            <button type="button" class="px-3 py-2 rounded-xl text-sm font-semibold border border-slate-200 hover:bg-slate-50" data-bw-mrd-preset data-lat="-29.8587" data-lng="31.0218">
                                Durban
                            </button>
                            <button type="button" class="px-3 py-2 rounded-xl text-sm font-semibold border border-slate-200 hover:bg-slate-50" data-bw-mrd-preset data-lat="-33.9249" data-lng="18.4241">
                                Cape Town
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs uppercase tracking-[0.2em] text-slate-500">Latitude</label>
                            <input id="bw-mrd-lat" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="-26.2041">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-[0.2em] text-slate-500">Longitude</label>
                            <input id="bw-mrd-lng" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3" placeholder="28.0473">
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-end">
                        <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50" data-bw-mrd-location-close>
                            Cancel
                        </button>
                        <button type="button" id="bw-mrd-location-apply" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#020DFF;">
                            Use these coordinates
                        </button>
                    </div>

                    <div class="mt-3 text-xs text-slate-500">
                        Tip: if you want browser location, click the lock icon in your address bar and set Location to Allow.
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function () {
                const btn = document.getElementById('bw-mrd-use-location');
                if (!btn) return;

                const modal = document.getElementById('bw-mrd-location-modal');
                const modalMsg = document.getElementById('bw-mrd-location-modal-msg');
                const latEl = document.getElementById('bw-mrd-lat');
                const lngEl = document.getElementById('bw-mrd-lng');
                const applyBtn = document.getElementById('bw-mrd-location-apply');

                const postRefresh = async (lat, lng) => {
                    const resp = await fetch(@json(route('bnpl.mrd.refresh', [], false)), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ lat, lng }),
                    });

                    if (!resp.ok) {
                        let msg = 'HTTP ' + resp.status;
                        try {
                            const j = await resp.json();
                            msg = (j && (j.message || j.error)) ? (j.message || j.error) : msg;
                        } catch (e) {
                            const text = await resp.text().catch(() => '');
                            if (text) msg = text;
                        }
                        throw new Error(msg);
                    }
                    return resp.json();
                };

                const showModal = (msg) => {
                    if (!modal) return;
                    if (modalMsg && msg) modalMsg.textContent = msg;
                    modal.classList.remove('hidden');
                };

                const hideModal = () => {
                    if (!modal) return;
                    modal.classList.add('hidden');
                };

                const applyCoords = async (lat, lng) => {
                    btn.disabled = true;
                    const old = btn.textContent;
                    btn.textContent = 'Fetching nearby...';
	                    try {
	                        await postRefresh(lat, lng);
	                        window.location.reload();
	                    } catch (e) {
	                        console.error(e);
	                        const msg = (e && (e.message || String(e))) || 'Could not fetch nearby items. Try again.';
	                        alert(msg);
	                    } finally {
	                        btn.disabled = false;
	                        btn.textContent = old;
	                    }
	                };

                if (modal) {
                    modal.querySelectorAll('[data-bw-mrd-location-close]').forEach((el) => {
                        el.addEventListener('click', hideModal);
                    });
                    modal.querySelectorAll('[data-bw-mrd-preset]').forEach((el) => {
                        el.addEventListener('click', () => {
                            const lat = Number(el.getAttribute('data-lat'));
                            const lng = Number(el.getAttribute('data-lng'));
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                            hideModal();
                            applyCoords(lat, lng);
                        });
                    });
                    if (applyBtn) {
                        applyBtn.addEventListener('click', () => {
                            const lat = Number((latEl && latEl.value || '').trim());
                            const lng = Number((lngEl && lngEl.value || '').trim());
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                                alert('Enter valid latitude and longitude.');
                                return;
                            }
                            hideModal();
                            applyCoords(lat, lng);
                        });
                    }
                }

	                btn.addEventListener('click', () => {
	                    // Geolocation requires secure context (https or localhost).
	                    if (!window.isSecureContext && location.hostname !== 'localhost') {
	                        showModal('This page is not in a secure context (HTTPS). Select a city or enter coordinates to continue.');
	                        return;
	                    }
	                    if (!navigator.geolocation) {
	                        showModal('Geolocation is not supported on this browser. Select a city or enter coordinates to continue.');
	                        return;
	                    }

	                    navigator.geolocation.getCurrentPosition(async (pos) => {
	                        // applyCoords handles UI state + errors.
	                        await applyCoords(pos.coords.latitude, pos.coords.longitude);
	                    }, (err) => {
	                        console.error(err);
	                        if (err && err.code === 1) {
	                            showModal('Location permission was denied. Select a city or enter coordinates to continue.');
	                            return;
	                        }
	                        showModal('Could not read your location. Select a city or enter coordinates to continue.');
	                    }, { enableHighAccuracy: false, timeout: 10000 });
	                });
            })();
        </script>
    </section>
@endsection
