@props(['priority'])

@php
    $slug = is_object($priority) ? ($priority->slug ?? 'baja') : strtolower($priority);
    $name = is_object($priority) ? ($priority->name ?? ucfirst($slug)) : ucfirst($slug);

    $badgeClasses = match ($slug) {
        'critica' => 'bg-state-critical-bg text-state-critical border-state-critical-border',
        'alta' => 'bg-state-high-bg text-state-high border-state-high-border',
        'media' => 'bg-state-medium-bg text-state-medium border-state-medium-border',
        default => 'bg-state-low-bg text-state-low border-state-low-border',
    };

    $dotColor = match ($slug) {
        'critica' => 'bg-red-400',
        'alta' => 'bg-orange-400',
        'media' => 'bg-yellow-400',
        default => 'bg-emerald-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wide border {$badgeClasses}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
    <span>{{ $name }}</span>
</span>