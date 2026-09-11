<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 bg-brand-primary hover:bg-brand-primary-hover active:bg-brand-primary-active text-white text-xs font-semibold rounded-lg shadow-sm border border-white/10 focus:outline-none focus:ring-1 focus:ring-[#164A8A] transition-all duration-150']) }}>
    {{ $slot }}
</button>