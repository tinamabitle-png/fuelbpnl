@props(['class' => 'h-6 w-auto', 'title' => 'Bwiser'])

@php
    $logoPath = public_path('images/brand-logo.png');
    $logoUrl = asset('images/brand-logo.png') . (file_exists($logoPath) ? '?v=' . filemtime($logoPath) : '');
@endphp

<img
    src="{{ $logoUrl }}"
    alt="{{ $title }}"
    class="{{ $class }}"
    style="transform: scale(2); transform-origin: center;"
    {{ $attributes->except(['class']) }}
/>
