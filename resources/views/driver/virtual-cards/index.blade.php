@extends('Layouts.app')

@section('title', 'Virtual Cards - Bwiser')

@section('content')
@php
    $walletBalance = (float) ($wallet?->balance ?? 0);
    $availableBalance = (float) ($wallet?->available_balance ?? 0);
    $reservedVoucherBalance = (float) ($wallet?->reserved_voucher_balance ?? 0);
    $allocatedCardBalance = (float) ($wallet?->allocated_card_balance ?? 0);
    $openCardCount = (int) ($cards?->whereIn('status', ['active', 'frozen'])->count() ?? 0);
    $cardholderName = (string) (auth()->user()?->name ?? 'Cardholder');
    $cardsByBrand = ($cards ?? collect())
        ->sortByDesc('id')
        ->groupBy('brand');
    $visaLogo = 'https://assets.codepen.io/14762/visa-virtual.svg';
@endphp

<section class="max-w-6xl mx-auto px-6 pt-16 pb-20">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-blue-600">Driver Portal</p>
            <h1 class="brand-font text-3xl font-semibold text-slate-900 mt-2">Virtual Cards</h1>
            <p class="text-sm text-slate-600 mt-2">You can keep up to <span class="font-semibold">3</span> open cards (active/frozen).</p>
        </div>
    </div>
    @include('driver.partials.nav', ['backUrl' => route('driver.dashboard')])

    @if(session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="glass rounded-2xl p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Wallet Balance</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">R {{ number_format($walletBalance, 2) }}</p>
            <p class="text-xs text-slate-500 mt-2">Available: <span class="font-semibold text-slate-700">R {{ number_format($availableBalance, 2) }}</span></p>
        </div>
        <div class="glass rounded-2xl p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Reserved Vouchers</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">R {{ number_format($reservedVoucherBalance, 2) }}</p>
            <p class="text-xs text-slate-500 mt-2">Issued/approved wallet-funded vouchers.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Allocated to Cards</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">R {{ number_format($allocatedCardBalance, 2) }}</p>
            <p class="text-xs text-slate-500 mt-2">Funds set aside for virtual card spending.</p>
        </div>
        <div class="glass rounded-2xl p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Open Cards</p>
            <p class="text-2xl font-semibold text-slate-900 mt-2">{{ $openCardCount }} / 3</p>
            <p class="text-xs text-slate-500 mt-2">Close one to create another.</p>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 mt-6">
        <p class="text-sm font-semibold text-slate-900">Virtual cards by retail brand</p>
        <p class="text-xs text-slate-500 mt-1">Each brand has its own card slot. You can only keep up to <span class="font-semibold">3</span> cards open at once (active/frozen).</p>
    </div>

    <div class="vc-surface mt-6 rounded-2xl border border-slate-200 overflow-hidden">
        <div class="wrapper">
            <div class="card-wrapper">
                @foreach(($brands ?? collect()) as $brand)
                    @php
                        $slug = (string) ($brand['slug'] ?? 'generic');
                        $brandName = (string) ($brand['name'] ?? $slug);
                        $brandLogo = (string) ($brand['logo'] ?? '');
                        $theme = (string) ($brand['theme'] ?? 'two');
                        $brandCards = $cardsByBrand->get($slug) ?? collect();
                        $card = $brandCards->first(function ($c) {
                            return in_array($c->status, ['active', 'frozen'], true);
                        }) ?: $brandCards->first();
                        $status = (string) ($card?->status ?? 'none');
                        $isFrozen = $status === 'frozen';
                        $isOpen = in_array($status, ['active', 'frozen'], true);
                        $allocated = (float) ($card?->allocated_amount ?? 0);
                    @endphp

                    <div class="vc-brand-block">
                        <div class="card digital {{ $theme }} {{ $isFrozen ? 'frozen' : '' }} {{ !$isOpen ? 'vc-inactive' : '' }}">
                            @if($isFrozen)
                                <span class="frozen-label">Frozen</span>
                            @endif
                            <div class="card-top">
                                <span>Virtual</span>
                                <img src="{{ $brandLogo ? asset($brandLogo) : 'https://assets.codepen.io/14762/airwallex-virtual.svg' }}" alt="{{ $brandName }} logo">
                            </div>

                            @php
                                $maskedPan = (string) ($card?->masked_pan ?: ($card?->last4 ? '•••• •••• •••• ' . $card->last4 : '•••• •••• •••• ••••'));
                                $expMonth = (int) ($card?->expiry_month ?? 0);
                                $expYear = (int) ($card?->expiry_year ?? 0);
                                $expiry = ($expMonth > 0 && $expYear > 0)
                                    ? sprintf('%02d/%s', $expMonth, substr((string) $expYear, -2))
                                    : '--/--';
                            @endphp
	                            <div class="vc-mid">
	                                <div class="vc-pan" data-pan-masked="{{ $maskedPan }}">{{ $maskedPan }}</div>
	                                <div class="vc-exp">
	                                    <div class="vc-meta">
	                                        <div class="vc-meta-block">
	                                            <span class="vc-exp-label">EXP</span>
	                                            <span class="vc-exp-value">{{ $expiry }}</span>
	                                        </div>
	                                        <div class="vc-meta-block">
	                                            <span class="vc-cvv-label">CVV</span>
	                                            <span class="vc-cvv-value" data-cvv-masked="•••">•••</span>
	                                        </div>
	                                    </div>
	                                    <span class="vc-amount">R {{ number_format($allocated, 2) }}</span>
	                                </div>
	                            </div>

                            <div class="card-bottom">
                                <div class="card-name">
                                    <p>{{ $brandName }}</p>
                                    <p>{{ $cardholderName }}</p>
                                </div>
                                <img src="{{ $visaLogo }}" alt="Visa">
                            </div>
                        </div>

                            <div class="vc-actions">
                            @if(!$isOpen)
                                <form method="POST" action="{{ route('driver.virtual-cards.store') }}" class="vc-action-row">
                                    @csrf
                                    <input type="hidden" name="brand" value="{{ $slug }}">
                                    <input
                                        name="label"
                                        value="{{ old('label') }}"
                                        placeholder="Label (optional)"
                                        class="vc-input"
                                    />
                                    <button class="btn-primary vc-btn {{ $openCardCount >= 3 ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $openCardCount >= 3 ? 'disabled' : '' }}>
                                        Create
                                    </button>
                                </form>
                            @else
                                <div class="vc-action-row">
                                    <form method="POST" action="{{ route('driver.virtual-cards.reveal', $card) }}" class="vc-inline js-reveal-form">
                                        @csrf
                                        <button type="submit" class="btn-ghost vc-btn js-reveal-btn">Show</button>
                                    </form>
                                    <form method="POST" action="{{ route('driver.virtual-cards.allocate', $card) }}" class="vc-inline">
                                        @csrf
                                        <input name="amount" type="number" step="0.01" min="10" placeholder="Amount" class="vc-input vc-input-sm" />
                                        <button class="btn-ghost vc-btn">Allocate</button>
                                    </form>

                                    @if($status === 'active')
                                        <form method="POST" action="{{ route('driver.virtual-cards.freeze', $card) }}" class="vc-inline">
                                            @csrf
                                            <button class="btn-ghost vc-btn">Freeze</button>
                                        </form>
                                    @elseif($status === 'frozen')
                                        <form method="POST" action="{{ route('driver.virtual-cards.unfreeze', $card) }}" class="vc-inline">
                                            @csrf
                                            <button class="btn-ghost vc-btn">Unfreeze</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('driver.virtual-cards.close', $card) }}" class="vc-inline" onsubmit="return confirm('Close this card? Allocated amount will be reset to 0.');">
                                        @csrf
                                        <button class="vc-close-btn">Close</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<style>
    .vc-surface { background: #fff; color: #0f172a; }
    .wrapper { padding: 36px 18px 2px; background: #fff; }

    @keyframes cardAnimation {
        60% {
            background-size: 400px 267px;
            background-position-x: 60%;
            background-position-y: 60%;
        }
    }

    @keyframes cardGradient {
        0% { background-position: 0% 10%; }
        50% { background-position: 100% 91%; }
        100% { background-position: 0% 10%; }
    }

    .card-wrapper {
        box-sizing: border-box;
        grid-gap: 24px;
        display: grid;
        justify-content: center;
        margin-bottom: 24px;
        grid-template-columns: repeat(auto-fit, minmax(311px, 360px));
    }

    .vc-brand-block { display: grid; gap: 10px; justify-items: center; }

    .card {
        color: #fff;
        background-color: rgb(42, 41, 45);
        border-radius: 10px;
        height: 227px;
        width: 360px;
        min-width: 311px;
        position: relative;
        box-sizing: border-box;
        padding: 24px;
        display: grid;
        grid-template-rows: 1fr auto;
        font-family: AxLLCircular, Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        font-size: 14px;
        box-shadow: 0 0px 8px rgb(0 0 0 / 12%), 0 2px 16px rgb(0 0 0 / 12%),
            0 4px 20px rgb(0 0 0 / 12%), 0 12px 28px rgb(0 0 0 / 12%);
    }

    .card.digital {
        background-size: 360px 227px;
        animation-name: cardAnimation;
        animation-duration: 10s;
        animation-iteration-count: infinite;
        transform: translateZ(0);
        color: #1a1d21;
    }

    .card.digital img {
        filter: drop-shadow(0px 1px 0px rgba(255, 255, 255, 0.3))
            drop-shadow(0 2px 16px rgba(0, 0, 0, 0.12))
            drop-shadow(0px 0px 12px rgba(255, 255, 255, 1));
    }

    .card.digital:before {
        content: "";
        width: 100%;
        height: 100%;
        box-shadow: 0 -1px 0 0 rgb(255 255 255 / 90%), 0 1px 0 0 rgb(0 0 0 / 20%);
        position: absolute;
        z-index: 1;
        border-radius: 10px;
        left: 0;
        top: 0;
    }

    .card.digital:after {
        content: "";
        width: 100%;
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(120deg, rgb(255 255 255 / 2%) 30%, rgb(255 255 255 / 25%) 40%, rgb(255 255 255 / 8%) 40%),
            linear-gradient(0deg, rgb(255 255 255 / 20%), rgb(255 255 255 / 30%));
        background-size: 150% 150%;
        animation: cardGradient 45s ease-in-out infinite;
        transform: translateZ(0);
        position: absolute;
        left: 0;
        top: 0;
    }

    .card.digital.one { background-image: url("https://assets.codepen.io/14762/snowy-mint.jpg"); }
    .card.digital.two { background-image: url("https://assets.codepen.io/14762/egg-sour.jpg"); }
    .card.digital.three { background-image: url("https://assets.codepen.io/14762/columbia-blue.jpg"); }
    .card.digital.four { background-image: url("https://assets.codepen.io/14762/my-pink.jpg"); }
    .card.digital.five { background-image: url("https://assets.codepen.io/14762/buttercup.jpg"); }
    .card.digital.six { background-image: url("https://assets.codepen.io/14762/cream-whisper.jpg"); }
    .card.digital.seven { background-image: url("https://assets.codepen.io/14762/honeysuckle.jpg"); }
    .card.digital.eight { background-image: url("https://assets.codepen.io/14762/tonys-pink.jpg"); }

    .card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        z-index: 2;
    }

    .card-top span {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 500;
    }

    .card-top img { height: 22px; }

    .vc-mid {
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 12px;
        margin-top: 18px;
        margin-bottom: 8px;
    }

    .vc-pan {
        font-weight: 800;
        letter-spacing: 2.2px;
        font-size: 16px;
        text-shadow: 0 0px 8px rgb(0 0 0 / 12%);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 245px;
    }

    .vc-exp { display: flex; flex-direction: column; gap: 8px; text-align: right; align-items: flex-end; }
    .vc-meta { display: flex; gap: 14px; align-items: flex-end; }
    .vc-meta-block { display: grid; gap: 2px; }
    .vc-exp-label, .vc-cvv-label { font-size: 10px; font-weight: 800; letter-spacing: 1.2px; opacity: 0.85; }
    .vc-exp-value, .vc-cvv-value { font-size: 14px; font-weight: 900; letter-spacing: 1.1px; }

    .card-bottom {
        display: flex;
        justify-content: space-between;
        z-index: 2;
        align-items: flex-end;
    }

    .card-bottom img { height: 40px; }

    .card-name {
        display: grid;
        grid-gap: 8px;
    }

    .card-name p {
        margin: 0;
        font-size: 16px;
        letter-spacing: 1.2px;
        text-shadow: 0 0px 8px rgb(0 0 0 / 12%);
        font-weight: 700;
        max-width: 232px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .card-name p:first-child {
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        opacity: 0.9;
    }

    .frozen { filter: grayscale(100%); transition: filter 0.2s ease-in-out; position: relative; }
    .frozen .card-top, .frozen .card-bottom { filter: blur(6px); transition: filter 0.2s ease-in-out; }
    .frozen:hover { filter: grayscale(0%); }
    .frozen:hover .card-top, .frozen:hover .card-bottom { filter: blur(0); }

    .frozen-label {
        width: 80px;
        text-align: center;
        position: absolute;
        background: rgba(255,255,255,0.75);
        z-index: 3;
        text-transform: uppercase;
        padding: 6px 12px;
        border-radius: 4px;
        color: #111827;
        top: calc(50% - 14px);
        left: calc(50% - 40px);
        box-sizing: border-box;
        font-size: 12px;
        letter-spacing: 1.2px;
        font-weight: 700;
    }

    .vc-amount {
        margin-top: 6px;
        justify-self: end;
        padding: 5px 9px;
        border-radius: 999px;
        background: rgba(255,255,255,0.72);
        color: #111827;
        font-size: 11px;
        letter-spacing: 0.4px;
        font-weight: 900;
        line-height: 1;
    }

    .vc-inactive { opacity: 0.9; }

    .vc-actions { width: 360px; max-width: 100%; }
    .vc-action-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: center; }
    .vc-inline { display: inline-flex; gap: 8px; align-items: center; }
    .vc-input {
        padding: 10px 12px;
        border: 1px solid rgba(15, 23, 42, 0.14);
        border-radius: 12px;
        background: rgba(255,255,255,0.92);
        color: #0f172a;
        outline: none;
        width: 220px;
        max-width: 100%;
    }
    .vc-input::placeholder { color: rgba(15, 23, 42, 0.45); }
    .vc-input-sm { width: 120px; }
    .vc-btn { padding: 10px 14px; border-radius: 12px; }
    .vc-close-btn {
        padding: 10px 14px;
        border-radius: 12px;
        border: 1px solid rgba(244, 63, 94, 0.45);
        background: rgba(244, 63, 94, 0.08);
        color: rgb(190 18 60);
        font-weight: 700;
        font-size: 12px;
    }
</style>

<script>
    (function () {
        const forms = document.querySelectorAll('.js-reveal-form');
        forms.forEach((form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = form.querySelector('.js-reveal-btn');
                const card = form.closest('.vc-brand-block')?.querySelector('.card');
                const panEl = form.closest('.vc-brand-block')?.querySelector('.vc-pan');
                const cvvEl = form.closest('.vc-brand-block')?.querySelector('.vc-cvv-value');
                if (!btn || !card || !panEl) return;

                const isRevealed = btn.dataset.revealed === '1';
                if (isRevealed) {
                    panEl.textContent = panEl.dataset.panMasked || panEl.textContent;
                    if (cvvEl) cvvEl.textContent = cvvEl.dataset.cvvMasked || '•••';
                    btn.textContent = 'Show';
                    btn.dataset.revealed = '0';
                    return;
                }

                btn.disabled = true;
                const original = btn.textContent;
                btn.textContent = 'Loading...';

                try {
                    const token = form.querySelector('input[name=_token]')?.value;
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token || '',
                        },
                        body: JSON.stringify({}),
                    });
                    const json = await res.json();
                    if (!res.ok || !json?.success) {
                        throw new Error(json?.message || 'Failed to reveal card number');
                    }
                    panEl.textContent = (json.data?.pan || '').toString();
                    if (cvvEl) {
                        const cvv = (json.data?.cvv || '').toString().trim();
                        cvvEl.textContent = cvv !== '' ? cvv : '---';
                    }
                    btn.textContent = 'Hide';
                    btn.dataset.revealed = '1';

                    // Auto-hide after 15 seconds.
                    window.setTimeout(() => {
                        if (btn.dataset.revealed === '1') {
                            panEl.textContent = panEl.dataset.panMasked || panEl.textContent;
                            if (cvvEl) cvvEl.textContent = cvvEl.dataset.cvvMasked || '•••';
                            btn.textContent = 'Show';
                            btn.dataset.revealed = '0';
                        }
                    }, 15000);
                } catch (err) {
                    alert(err?.message || 'Failed to reveal card number');
                    btn.textContent = original;
                } finally {
                    btn.disabled = false;
                    if (btn.textContent === 'Loading...') btn.textContent = original;
                }
            });
        });
    })();
</script>
@endsection
