@props(['status'])

@php
    $code = is_string($status) ? strtoupper($status) : ($status?->code ?? 'NUEVA');
    $name = is_string($status) ? ucfirst(str_replace('_', ' ', strtolower($status))) : ($status?->name ?? 'Nueva');
@endphp

@if($code === 'EN_ATENCION' || $code === 'EN_PROCESO')
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#2a1e09]/60 text-[#f59e0b] border border-[#d97706]/70 shadow-sm whitespace-nowrap">
        En proceso
    </span>
@elseif($code === 'RESUELTA')
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#06291e]/60 text-[#10b981] border border-[#059669]/70 shadow-sm whitespace-nowrap">
        Resuelta
    </span>
@elseif($code === 'NUEVA' || $code === 'ASIGNADA')
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#0b1e38]/60 text-[#38bdf8] border border-[#0284c7]/70 shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@else
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#141b2b]/60 text-[#94a3b8] border border-[#475569]/70 shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@endif
