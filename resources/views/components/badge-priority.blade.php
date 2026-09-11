@props(['priority'])

@php
    $slug = is_string($priority) ? strtolower($priority) : ($priority?->slug ?? 'media');
    $name = is_string($priority) ? ucfirst($priority) : ($priority?->name ?? 'Media');
@endphp

@if($slug === 'critica' || $slug === 'alta')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#451219] text-[#f87171] border border-[#7f1d1d]/40 shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@elseif($slug === 'media')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#452809] text-[#fbbf24] border border-[#b45309]/40 shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@else
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#13285c] text-[#60a5fa] border border-[#1d4ed8]/40 shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@endif
