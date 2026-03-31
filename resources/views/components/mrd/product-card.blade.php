@props([
    'product',
    'currency' => 'ZAR',
])

@php
    $name = (string) ($product['name'] ?? 'Product');
    $unit = (string) ($product['unit'] ?? '');
    $price = $product['price'] ?? null;
    $rating = $product['rating'] ?? null;
    $badge = (string) ($product['badge'] ?? '');
    $desc = (string) ($product['description'] ?? '');

    $image = (string) ($product['image'] ?? '');
    $imgUrl = $image !== '' ? asset($image) : asset('images/driver-platforms/mrd.png');

    $currencySymbol = $currency === 'ZAR' ? 'R' : $currency;
    $priceText = is_numeric($price) ? $currencySymbol . number_format((float) $price, 2) : $currencySymbol . '0.00';
@endphp

<div class="bw-mrd-card">
    @if($badge !== '')
        <span class="bw-mrd-badge">{{ $badge }}</span>
    @endif

    <div class="bw-mrd-figure">
        <img src="{{ $imgUrl }}" alt="{{ $name }}" loading="lazy">
    </div>

    <div class="bw-mrd-meta">
        <span class="bw-mrd-qty">{{ $unit !== '' ? $unit : '1 unit' }}</span>
        @if(is_numeric($rating))
            <span class="bw-mrd-rating">
                <span class="bw-mrd-rating-dot" aria-hidden="true"></span>
                {{ number_format((float) $rating, 1) }}
            </span>
        @else
            <span class="bw-mrd-rating">
                <span class="bw-mrd-rating-dot" aria-hidden="true"></span>
                4.5
            </span>
        @endif
    </div>

    <div class="bw-mrd-title">{{ $name }}</div>
    @if($desc !== '')
        <div class="bw-mrd-desc">{{ $desc }}</div>
    @endif

    <span class="bw-mrd-price">{{ $priceText }}</span>

    <div class="bw-mrd-actions">
        <a href="javascript:void(0)" class="bw-mrd-btn" onclick="alert('Checkout coming soon. This will create a BNPL order request.'); return false;">
            Pay in 4
        </a>
    </div>
</div>

