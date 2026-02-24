@props([
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-5',
        'md' => 'h-7',
        'lg' => 'h-9',
        'xl' => 'h-12',
    ];
    $logoHeight = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <div class="{{ $logoHeight }} flex items-center">
        {{ $slot }}
    </div>
</div>
