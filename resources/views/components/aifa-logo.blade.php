@props([
    'showText' => true,
    'class' => 'h-7',
    'textClass' => 'text-white',
    'subtext' => 'AEROPUERTO INTERNACIONAL FELIPE ÁNGELES'
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 select-none']) }}>
    <!-- Isotipo Oficial Vectorial AIFA (Alas Geométricas / Perspectiva de Pista) -->
    <svg viewBox="0 0 76 44" fill="currentColor" class="{{ $class }} shrink-0" xmlns="http://www.w3.org/2000/svg" aria-label="Logotipo Oficial AIFA">
        <!-- Ala Izquierda (5 plumas/trazos ascendentes) -->
        <path d="M4 38 L14 38 L24 24 L16 24 Z" opacity="0.65" />
        <path d="M10 38 L18 38 L27 20 L21 20 Z" opacity="0.75" />
        <path d="M16 38 L23 38 L30 15 L25 15 Z" opacity="0.85" />
        <path d="M22 38 L28 38 L33 10 L29 10 Z" opacity="0.95" />
        <path d="M28 38 L33 38 L36 4 L33 4 Z" opacity="1.0" />
        
        <!-- Vértice Central / Eje de Pista -->
        <polygon points="36,2 40,2 39,38 37,38" fill="currentColor" opacity="1.0" />
        
        <!-- Ala Derecha (5 plumas/trazos simétricos ascendentes) -->
        <path d="M43 38 L48 38 L43 4 L40 4 Z" opacity="1.0" />
        <path d="M48 38 L54 38 L47 10 L43 10 Z" opacity="0.95" />
        <path d="M53 38 L60 38 L51 15 L46 15 Z" opacity="0.85" />
        <path d="M58 38 L66 38 L55 20 L49 20 Z" opacity="0.75" />
        <path d="M62 38 L72 38 L60 24 L52 24 Z" opacity="0.65" />
        
        <!-- Base de Pista Subyacente -->
        <rect x="2" y="40" width="72" height="2" rx="1" opacity="0.4" />
    </svg>

    @if($showText)
        <div class="flex items-center gap-2.5">
            <!-- Divisor Sutil -->
            <div class="h-6 w-px bg-slate-700/60"></div>
            <div class="flex flex-col text-left leading-none">
                <span class="font-extrabold text-sm sm:text-base tracking-widest text-white font-sans">
                    AIFA
                </span>
                @if($subtext)
                    <span class="text-[8px] uppercase tracking-wider text-slate-400 font-medium mt-0.5 whitespace-nowrap">
                        {{ $subtext }}
                    </span>
                @endif
            </div>
        </div>
    @endif
</div>
