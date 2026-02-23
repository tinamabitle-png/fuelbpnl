@props([
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-10 w-10',
        'md' => 'h-12 w-12',
        'lg' => 'h-16 w-16',
        'xl' => 'h-20 w-20',
    ];
    $innerSize = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'group p-1.5 border rounded-full border-gray-200 bg-gray-100 hover:bg-gray-100 hover:border-gray-200 transition-all']) }} style="background-color: rgba(241,245,249,0.65);">
    <div class="p-2 border border-white rounded-full shadow-xl group-hover:shadow-2xl group-active:shadow-md transition-all bg-gradient-to-b from-white to-slate-100">
        <div class="border border-white rounded-full border-2">
            <div class="bg-gradient-to-b from-gray-100 to-white border border-gray-200 group-hover:border-gray-300 group-active:border-gray-300 transition-all {{ $innerSize }} flex items-center justify-center rounded-full overflow-hidden">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
