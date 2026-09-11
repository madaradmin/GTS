@props(['status'])

@php
    $code = is_object($status) ? ($status->code ?? 'NUEVA') : strtoupper($status);
    $name = is_object($status) ? ($status->name ?? ucfirst(strtolower($code))) : ucfirst(strtolower($code));

    $badgeClasses = match ($code) {
        'NUEVA' => 'bg-state-info-bg text-state-info border-state-info-border',
        'ASIGNADA' => 'bg-[#1a1c3d] text-[#818cf8] border-[#2d3269]',
        'EN_ATENCION' => 'bg-state-medium-bg text-state-medium border-state-medium-border',
        'RESUELTA' => 'bg-state-low-bg text-state-low border-state-low-border',
        'CERRADA' => 'bg-state-neutral-bg text-state-neutral border-state-neutral-border',
        default => 'bg-state-neutral-bg text-state-neutral border-state-neutral-border',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium border {$badgeClasses}"]) }}>
    {{ $name }}
</span>