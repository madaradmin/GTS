@props([
    'showText' => true,
    'class' => 'h-7 lg:h-8',
    'textClass' => 'text-[#F2F5F8]',
    'subtext' => 'AEROPUERTO INTERNACIONAL FELIPE ÁNGELES'
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 select-none']) }}>
    <!-- Logotipo Oficial AIFA (Alas Oficiales) -->
    <img 
        src="{{ asset('images/aifa-logo.png') }}" 
        alt="Aeropuerto Internacional Felipe Ángeles" 
        class="{{ $class }} w-auto object-contain shrink-0" 
        loading="eager"
    />

    @if($showText)
        <div class="flex items-center gap-2.5">
            <!-- Divisor Sutil -->
            <div class="h-6 w-px bg-[#263241]"></div>
            <div class="flex flex-col text-left leading-none">
                <span class="font-extrabold text-sm sm:text-base tracking-widest text-[#F2F5F8] font-sans">
                    AIFA
                </span>
                @if($subtext)
                    <span class="text-[8px] uppercase tracking-wider text-[#8D98A8] font-medium mt-0.5 whitespace-nowrap">
                        {{ $subtext }}
                    </span>
                @endif
            </div>
        </div>
    @endif
</div>
