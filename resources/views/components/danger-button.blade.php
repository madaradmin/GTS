<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 bg-state-critical hover:bg-red-600 active:bg-red-700 text-white text-xs font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500/50 transition-all duration-150']) }}>
    {{ $slot }}
</button>