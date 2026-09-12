@props([
    'showBrandText' => false,
    'class' => 'w-9 h-9'
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 select-none']) }}>
    <img 
        src="{{ asset('images/gts-logo.png') }}" 
        alt="GTS - Grupo de Seguridad Tecnológica AIFA" 
        class="{{ $class }} object-contain shrink-0"
        loading="eager"
    />

    @if($showBrandText)
        <div class="flex flex-col text-left leading-tight">
            <span class="font-extrabold text-base tracking-wider text-white font-sans">
                GTS
            </span>
            <span class="text-[9px] uppercase tracking-wider text-[#8A95A4] font-medium">
                Grupo de Seguridad Tecnológica
            </span>
        </div>
    @endif
</div>
