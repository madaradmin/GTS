@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-brand-surface-subtle border border-brand-border text-brand-text-primary placeholder:text-brand-text-muted focus:border-brand-primary focus:ring-1 focus:ring-brand-primary rounded-lg shadow-sm text-sm px-3 py-2 transition-colors']) }}>
