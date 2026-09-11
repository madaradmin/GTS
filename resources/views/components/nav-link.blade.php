@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[#1E64B7] text-xs font-semibold leading-5 text-[#F2F5F8] focus:outline-none focus:border-[#3D82C6] transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-xs font-medium leading-5 text-[#8A95A4] hover:text-[#D6DCE4] hover:border-[#252C35] focus:outline-none focus:text-[#F2F5F8] focus:border-[#252C35] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
