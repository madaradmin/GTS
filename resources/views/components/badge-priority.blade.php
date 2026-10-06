@props(['priority'])

@php
    $slug = is_string($priority) ? strtolower($priority) : ($priority?->slug ?? 'media');
    $name = is_string($priority) ? ucfirst($priority) : ($priority?->name ?? 'Media');
@endphp

@if($slug === 'critica')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(216,59,82,0.15)] text-[#E66A7A] border border-[rgba(216,59,82,0.30)] shadow-sm whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-[#D83B52] animate-pulse"></span>
        {{ $name }}
    </span>
@elseif($slug === 'alta')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(224,109,36,0.15)] text-[#E8813C] border border-[rgba(224,109,36,0.30)] shadow-sm whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-[#E06D24]"></span>
        {{ $name }}
    </span>
@elseif($slug === 'media')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(16,185,129,0.15)] text-[#34D399] border border-[rgba(16,185,129,0.30)] shadow-sm whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
        {{ $name }}
    </span>
@else
    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(91,134,180,0.15)] text-[#82A8D1] border border-[rgba(91,134,180,0.30)] shadow-sm whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-[#5B86B4]"></span>
        {{ $name }}
    </span>
@endif
