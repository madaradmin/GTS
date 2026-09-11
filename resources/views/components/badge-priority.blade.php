@props(['priority'])

@php
    $slug = is_string($priority) ? strtolower($priority) : ($priority?->slug ?? 'media');
    $name = is_string($priority) ? ucfirst($priority) : ($priority?->name ?? 'Media');
@endphp

@if($slug === 'critica' || $slug === 'alta')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(216,59,82,0.15)] text-[#E66A7A] border border-[rgba(216,59,82,0.30)] shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@elseif($slug === 'media')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)] shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@else
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(61,130,198,0.15)] text-[#6FA2D2] border border-[rgba(61,130,198,0.30)] shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@endif
