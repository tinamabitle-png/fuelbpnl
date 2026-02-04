<svg viewBox="0 0 600 520" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">

    <!-- Gradients -->
    <defs>
        <linearGradient id="fuelFlow" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#00f5a0"/>
            <stop offset="100%" stop-color="#00b3ff"/>
        </linearGradient>

        <radialGradient id="pulse">
            <stop offset="0%" stop-color="#00f5a0" stop-opacity="1"/>
            <stop offset="100%" stop-color="#00f5a0" stop-opacity="0"/>
        </radialGradient>
    </defs>

    <!-- Africa outline (simplified, stylised) -->
    <path d="M280 80
             L340 120 L360 180 L330 260
             L300 320 L260 360 L220 330
             L200 280 L210 210 L240 150 Z"
          fill="none"
          stroke="rgba(255,255,255,.15)"
          stroke-width="2"/>

    <!-- Network nodes -->
    <g fill="#00f5a0">
        <circle cx="260" cy="160" r="4"/>
        <circle cx="300" cy="210" r="5"/>
        <circle cx="240" cy="250" r="4"/>
        <circle cx="290" cy="300" r="4"/>
    </g>

    <!-- Pulsing nodes -->
    <g>
        <circle cx="300" cy="210" r="8" fill="url(#pulse)">
            <animate attributeName="r" from="6" to="22" dur="2s" repeatCount="indefinite"/>
            <animate attributeName="opacity" from="1" to="0" dur="2s" repeatCount="indefinite"/>
        </circle>
    </g>

    <!-- Fuel pipe -->
    <path d="M90 130 H220"
          stroke="url(#fuelFlow)"
          stroke-width="6"
          stroke-linecap="round"
          stroke-dasharray="8 14">
        <animate attributeName="stroke-dashoffset"
                 from="0" to="-100"
                 dur="2s"
                 repeatCount="indefinite"/>
    </path>

    <!-- Fuel tap -->
    <g transform="translate(40 100)">
        <rect x="0" y="20" width="50" height="20" rx="6" fill="#1c243b"/>
        <rect x="20" y="0" width="10" height="40" rx="4" fill="#00f5a0"/>
        <circle cx="25" cy="50" r="6" fill="#00b3ff">
            <animate attributeName="cy" from="50" to="60" dur="1s" repeatCount="indefinite"/>
        </circle>
    </g>

</svg>
