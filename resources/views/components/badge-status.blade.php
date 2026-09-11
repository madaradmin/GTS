@props(['status'])

@php
    $code = is_string($status) ? strtoupper($status) : ($status?->code ?? 'NUEVA');
    $name = is_string($status) ? ucfirst(str_replace('_', ' ', strtolower($status))) : ($status?->name ?? 'Nueva');
@endphp

@if($code === 'EN_ATENCION' || $code === 'EN_PROCESO')
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)] shadow-sm whitespace-nowrap">
        En proceso
    </span>
@elseif($code === 'RESUELTA')
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(32,201,151,0.15)] text-[#20C997] border border-[rgba(32,201,151,0.30)] shadow-sm whitespace-nowrap">
        Resuelta
    </span>
@elseif($code === 'NUEVA' || $code === 'ASIGNADA')
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(61,130,198,0.15)] text-[#6FA2D2] border border-[rgba(61,130,198,0.30)] shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@else
    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#151A21] text-[#9AA5B4] border border-[#252C35] shadow-sm whitespace-nowrap">
        {{ $name }}
    </span>
@endif
