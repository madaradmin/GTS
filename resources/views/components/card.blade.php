@props(['title' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col overflow-hidden transition-all duration-150 hover:border-[#2A3440]']) }}>
    @if ($title || $action)
        <div class="px-4 py-3 border-b border-brand-border-subtle flex items-center justify-between gap-3 bg-brand-surface-subtle/40">
            @if ($title)
                <h3 class="text-xs font-bold text-brand-text-primary uppercase tracking-wider flex items-center gap-2">
                    {{ $title }}
                </h3>
            @endif
            @if ($action)
                <div class="text-xs">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif
    <div class="p-4 flex-1">
        {{ $slot }}
    </div>
</div>