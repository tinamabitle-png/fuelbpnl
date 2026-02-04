<svg viewBox="0 0 600 520"
     class="w-full max-w-xl"
     xmlns="http://www.w3.org/2000/svg">

    <!-- Glow defs -->
    <defs>
        <radialGradient id="glow" r="60%">
            <stop offset="0%" stop-color="#f53003" stop-opacity="0.8"/>
            <stop offset="100%" stop-color="#f53003" stop-opacity="0"/>
        </radialGradient>

        <filter id="softGlow">
            <feGaussianBlur stdDeviation="6" result="blur"/>
            <feMerge>
                <feMergeNode in="blur"/>
                <feMergeNode in="SourceGraphic"/>
            </feMerge>
        </filter>
    </defs>

    <!-- Africa silhouette -->
    <path
        d="M310 60
           C360 80 390 130 380 190
           C370 260 410 310 360 360
           C300 420 220 430 190 360
           C160 300 180 260 150 220
           C120 180 150 120 200 100
           C240 80 260 40 310 60Z"
        fill="#0a0a0a"
        opacity="0.85"
        class="dark:fill-[#161615]"
    />

    <!-- Network nodes -->
    <g filter="url(#softGlow)">
        <circle cx="260" cy="160" r="5" fill="#f53003">
            <animate attributeName="r" values="4;7;4" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="310" cy="220" r="5" fill="#f53003">
            <animate attributeName="r" values="4;7;4" dur="2.5s" repeatCount="indefinite"/>
        </circle>
        <circle cx="230" cy="280" r="5" fill="#f53003">
            <animate attributeName="r" values="4;7;4" dur="2.2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="340" cy="300" r="5" fill="#f53003">
            <animate attributeName="r" values="4;7;4" dur="2.8s" repeatCount="indefinite"/>
        </circle>
    </g>

    <!-- Network lines -->
    <g stroke="#f53003" stroke-width="1.5" opacity="0.6">
        <line x1="260" y1="160" x2="310" y2="220">
            <animate attributeName="opacity" values="0.2;0.8;0.2" dur="3s" repeatCount="indefinite"/>
        </line>
        <line x1="310" y1="220" x2="230" y2="280">
            <animate attributeName="opacity" values="0.2;0.8;0.2" dur="3.5s" repeatCount="indefinite"/>
        </line>
        <line x1="310" y1="220" x2="340" y2="300">
            <animate attributeName="opacity" values="0.2;0.8;0.2" dur="4s" repeatCount="indefinite"/>
        </line>
    </g>

    <!-- Fuel tap -->
    <g transform="translate(280 90)">
        <rect x="-10" y="0" width="20" height="40" rx="4" fill="#1b1b18"/>
        <rect x="-30" y="10" width="60" height="10" rx="5" fill="#1b1b18"/>

        <!-- Fuel drop -->
        <circle cx="0" cy="55" r="6" fill="#f53003">
            <animateTransform
                attributeName="transform"
                type="translate"
                from="0 -5"
                to="0 15"
                dur="1.8s"
                repeatCount="indefinite"/>
            <animate
                attributeName="opacity"
                values="0;1;0"
                dur="1.8s"
                repeatCount="indefinite"/>
        </circle>
    </g>

</svg>
