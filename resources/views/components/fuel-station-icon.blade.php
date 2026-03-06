@props([
    'size' => 64,
    'stroke' => 4,
])

<span {{ $attributes->merge(['class' => 'inline-flex']) }} aria-hidden="true">
    <svg
        width="{{ $size }}"
        height="{{ $size }}"
        viewBox="0 0 512 512"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <defs>
            <linearGradient id="fuelStationGradient" x1="44" y1="44" x2="468" y2="468" gradientUnits="userSpaceOnUse">
                <stop stop-color="#2563EB"/>
                <stop offset="1" stop-color="#0EA5E9"/>
            </linearGradient>
        </defs>
        <circle cx="256" cy="256" r="248" fill="url(#fuelStationGradient)"/>
        <g stroke="#F8FAFC" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round">
            <rect x="96" y="212" width="320" height="30" rx="7"/>
            <path d="M255 242V386"/>
            <rect x="203" y="148" width="106" height="52" rx="4"/>
            <path d="M203 180H308"/>
            <path d="M203 166H308"/>
            <rect x="134" y="282" width="72" height="86" rx="4"/>
            <rect x="306" y="282" width="72" height="86" rx="4"/>
            <rect x="151" y="300" width="38" height="24" rx="3"/>
            <rect x="323" y="300" width="38" height="24" rx="3"/>
            <path d="M120 294L108 287"/>
            <rect x="104" y="305" width="18" height="30" rx="4"/>
            <path d="M394 294L406 287"/>
            <rect x="390" y="305" width="18" height="30" rx="4"/>
            <rect x="126" y="370" width="94" height="30" rx="6"/>
            <rect x="292" y="370" width="94" height="30" rx="6"/>
            <rect x="94" y="400" width="324" height="34" rx="8"/>
        </g>
    </svg>
</span>
