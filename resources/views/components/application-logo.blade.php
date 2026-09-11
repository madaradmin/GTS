@props([
    'showBrandText' => false,
    'class' => 'w-7 h-7'
])

<div class="flex items-center gap-2.5 select-none">
    <!-- Escudo Vectorial SITIA / GIT con Trazas de Seguridad Tecnológica -->
    <svg 
        viewBox="0 0 40 46" 
        fill="none" 
        xmlns="http://www.w3.org/2000/svg"
        class="{{ $class }} shrink-0"
        aria-label="Emblema SITIA / GIT"
    >
        <!-- Resplandor / Filtros -->
        <defs>
            <linearGradient id="shieldBorder" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#3b82f6" />
                <stop offset="50%" stop-color="#2563eb" />
                <stop offset="100%" stop-color="#1d4ed8" />
            </linearGradient>
            <linearGradient id="shieldInner" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#1e3a8a" stop-opacity="0.5" />
                <stop offset="100%" stop-color="#0f172a" stop-opacity="0.8" />
            </linearGradient>
            <linearGradient id="circuitGlow" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#60a5fa" />
                <stop offset="100%" stop-color="#38bdf8" />
            </linearGradient>
        </defs>

        <!-- Silueta Exterior del Escudo Táctico -->
        <path 
            d="M20 2 L36 8 V20 C36 32 20 43 20 43 C20 43 4 32 4 20 V8 L20 2 Z" 
            fill="url(#shieldInner)" 
            stroke="url(#shieldBorder)" 
            stroke-width="2.5" 
            stroke-linejoin="round"
        />

        <!-- Contorno Interior Reflejado -->
        <path 
            d="M20 6 L32 11 V20 C32 29 20 38 20 38 C20 38 8 29 8 20 V11 L20 6 Z" 
            stroke="#1d4ed8" 
            stroke-width="1" 
            opacity="0.4"
            stroke-linejoin="round"
        />

        <!-- Circuito Central / Llave de Seguridad Tecnológica -->
        <!-- Nodo Central -->
        <circle cx="20" cy="20" r="3" fill="url(#circuitGlow)" />
        <circle cx="20" cy="20" r="5" stroke="url(#circuitGlow)" stroke-width="1.2" opacity="0.6" />

        <!-- Trazas de Circuito / Bus de Datos -->
        <path d="M20 15 V8" stroke="url(#circuitGlow)" stroke-width="1.5" stroke-linecap="round" />
        <circle cx="20" cy="8" r="1.5" fill="#93c5fd" />

        <path d="M20 25 V32" stroke="url(#circuitGlow)" stroke-width="1.5" stroke-linecap="round" />
        <circle cx="20" cy="32" r="1.5" fill="#93c5fd" />

        <path d="M15 20 H10" stroke="url(#circuitGlow)" stroke-width="1.5" stroke-linecap="round" />
        <circle cx="10" cy="20" r="1.5" fill="#93c5fd" />

        <path d="M25 20 H30" stroke="url(#circuitGlow)" stroke-width="1.5" stroke-linecap="round" />
        <circle cx="30" cy="20" r="1.5" fill="#93c5fd" />
    </svg>

    @if($showBrandText)
        <div class="flex flex-col text-left leading-tight">
            <span class="font-extrabold text-base tracking-wider text-white font-sans">
                SITIA <span class="text-blue-400 text-xs font-mono font-normal">/ GIT</span>
            </span>
            <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">
                Security Technology Incident Administration
            </span>
        </div>
    @endif
</div>
