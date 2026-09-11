import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // Dark Enterprise / Mission Control Tokens
                'brand-canvas': '#080d16',
                'brand-sidebar': '#0b1220',
                'brand-topbar': '#0d1526',
                'brand-surface': '#0f192d',
                'brand-surface-subtle': '#131f35',
                'brand-surface-hover': '#16243d',
                'brand-surface-active': '#1c2d4d',
                
                // Borders
                'brand-border': '#1b2a47',
                'brand-border-subtle': '#142036',
                'brand-border-focus': '#2563eb',

                // Tech Blue Primary
                'brand-primary': '#2563eb',
                'brand-primary-hover': '#1d4ed8',
                'brand-primary-active': '#1e40af',
                'brand-primary-muted': '#172554',

                // Text Hierarchy
                'brand-text-primary': '#f1f5f9',
                'brand-text-secondary': '#94a3b8',
                'brand-text-muted': '#64748b',
                'brand-text-faint': '#475569',

                // Functional State Colors (Strictly Functional)
                'state-critical': '#ef4444',
                'state-critical-bg': '#2b1116',
                'state-critical-border': '#4c1d25',

                'state-high': '#f97316',
                'state-high-bg': '#2b170c',
                'state-high-border': '#4d2715',

                'state-medium': '#eab308',
                'state-medium-bg': '#29210b',
                'state-medium-border': '#483a13',

                'state-low': '#10b981',
                'state-low-bg': '#0d241c',
                'state-low-border': '#164333',

                'state-info': '#3b82f6',
                'state-info-bg': '#0e1f3d',
                'state-info-border': '#1a376b',

                'state-neutral': '#64748b',
                'state-neutral-bg': '#172033',
                'state-neutral-border': '#26354f',

                // Backward-compatibility aliases with elevated palette
                'surface-base': '#080d16',
                'surface-dim': '#0b1220',
                'surface-subtle': '#0f192d',
                'surface-card': '#0f192d',
                'surface-overlay': '#131f35',
                'surface-container': '#131f35',
                'surface-container-low': '#0d1526',
                'surface-container-high': '#16243d',
                'primary': '#2563eb',
                'primary-container': '#1d4ed8',
                'on-primary': '#ffffff',
                'on-primary-container': '#eff6ff',
                'border-subtle': '#1b2a47',
                'border-active': '#24385e',
                'border-focus': '#2563eb',
                'on-surface': '#f1f5f9',
                'on-surface-variant': '#94a3b8',
                'outline': '#64748b',
                'status-resolved': '#10b981',
                'status-warning': '#f59e0b',
                'status-critical': '#ef4444',
                'status-neutral': '#64748b',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                'code-folio': ['JetBrains Mono', 'monospace'],
            },
            boxShadow: {
                'enterprise': '0 4px 20px -2px rgba(0, 0, 0, 0.45)',
                'enterprise-sm': '0 2px 8px 0 rgba(0, 0, 0, 0.35)',
                'enterprise-lg': '0 10px 30px -4px rgba(0, 0, 0, 0.55)',
                'glow-primary': '0 0 15px -3px rgba(37, 99, 235, 0.35)',
            },
        },
    },

    plugins: [forms],
};