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
                // Dark Enterprise / Mission Control Tokens (Calibrados con mockup SITIA/AIFA)
                'brand-canvas': '#070d1e',
                'brand-sidebar': '#091126',
                'brand-topbar': '#080f24',
                'brand-surface': '#0c1633',
                'brand-surface-subtle': '#101c40',
                'brand-surface-hover': '#14234d',
                'brand-surface-active': '#182b5e',
                
                // Borders
                'brand-border': '#19274e',
                'brand-border-subtle': '#131f3f',
                'brand-border-focus': '#1b64f2',

                // Tech Blue Primary (Píldoras y Botones de Acción Rápida)
                'brand-primary': '#1b64f2',
                'brand-primary-hover': '#1551c6',
                'brand-primary-active': '#1040a0',
                'brand-primary-muted': '#172b5c',

                // Text Hierarchy
                'brand-text-primary': '#f8fafc',
                'brand-text-secondary': '#94a3b8',
                'brand-text-muted': '#64748b',
                'brand-text-faint': '#475569',

                // Functional State Colors (Strictly Functional)
                'state-critical': '#ef4444',
                'state-critical-bg': '#3b1219',
                'state-critical-border': '#5c1d28',
                'state-critical-text': '#fca5a5',

                'state-high': '#f59e0b',
                'state-high-bg': '#3d2806',
                'state-high-border': '#663d08',
                'state-high-text': '#fcd34d',

                'state-medium': '#eab308',
                'state-medium-bg': '#3b3208',
                'state-medium-border': '#5c4e0d',
                'state-medium-text': '#fde047',

                'state-low': '#10b981',
                'state-low-bg': '#064e3b',
                'state-low-border': '#059669',
                'state-low-text': '#6ee7b7',

                'state-info': '#3b82f6',
                'state-info-bg': '#172554',
                'state-info-border': '#1e40af',
                'state-info-text': '#93c5fd',

                'state-neutral': '#64748b',
                'state-neutral-bg': '#172033',
                'state-neutral-border': '#26354f',

                // Backward-compatibility aliases
                'surface-base': '#070d1e',
                'surface-dim': '#091126',
                'surface-subtle': '#0c1633',
                'surface-card': '#0c1633',
                'surface-overlay': '#101c40',
                'surface-container': '#101c40',
                'surface-container-low': '#080f24',
                'surface-container-high': '#14234d',
                'primary': '#1b64f2',
                'primary-container': '#1551c6',
                'on-primary': '#ffffff',
                'on-primary-container': '#eff6ff',
                'border-subtle': '#19274e',
                'border-active': '#223566',
                'border-focus': '#1b64f2',
                'on-surface': '#f8fafc',
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
                'enterprise': '0 4px 20px -2px rgba(0, 0, 0, 0.55)',
                'enterprise-sm': '0 2px 8px 0 rgba(0, 0, 0, 0.45)',
                'enterprise-lg': '0 10px 30px -4px rgba(0, 0, 0, 0.65)',
                'glow-primary': '0 0 15px -3px rgba(27, 100, 242, 0.45)',
            },
            borderRadius: {
                'xl': '0.75rem',
                '2xl': '1rem',
            }
        },
    },

    plugins: [forms],
};
