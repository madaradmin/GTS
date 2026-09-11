@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[#1E64B7] text-start text-sm font-medium text-[#F2F5F8] bg-[#123A70]/30 focus:outline-none focus:text-white focus:bg-[#123A70]/50 focus:border-[#3D82C6] transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-sm font-medium text-[#8A95A4] hover:text-[#F2F5F8] hover:bg-[#151B23] hover:border-[#252C35] focus:outline-none focus:text-[#F2F5F8] focus:bg-[#151B23] focus:border-[#252C35] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
