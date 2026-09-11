<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#D83B52] hover:bg-[#C23348] active:bg-[#A82B3E] text-white text-xs font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#D83B52]/50 transition-all duration-150']) }}>
    {{ $slot }}
</button>