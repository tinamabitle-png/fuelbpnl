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
    $link = (string) ($product['url'] ?? '');

    $image = (string) ($product['image'] ?? '');
    $imageUrl = (string) ($product['image_url'] ?? '');
    $imgUrl = $imageUrl !== ''
        ? $imageUrl
        : ($image !== '' ? asset($image) : asset('images/driver-platforms/mrd.png'));

    $currencySymbol = $currency === 'ZAR' ? 'R' : $currency;
    $priceText = is_numeric($price) ? $currencySymbol . number_format((float) $price, 2) : '';
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

    @if($priceText !== '')
        <span class="bw-mrd-price">{{ $priceText }}</span>
    @endif

    <div class="bw-mrd-actions">
        <a href="{{ $link !== '' ? $link : 'javascript:void(0)' }}" class="bw-mrd-btn" onclick="alert('Checkout coming soon. This will create a BNPL order request.'); return false;">
            Pay in 4
        </a>
    </div>
</div>
