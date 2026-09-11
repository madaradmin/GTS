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
                // GTS Dark Enterprise / Security Operations Tokens
                // Fondo Principal y Secundario
                'brand-canvas': '#05070A',
                'brand-canvas-subtle': '#0A0D12',
                'brand-sidebar': '#070A0F',
                'brand-topbar': '#070A0F',

                // Cards y Paneles (Gris grafito / negro elevado)
                'brand-surface': '#11151B',
                'brand-surface-subtle': '#151A21',
                'brand-surface-elevated': '#191F27',
                'brand-surface-hover': '#151B23',
                'brand-surface-active': '#191F27',
                
                // Bordes
                'brand-border': '#252C35',
                'brand-border-subtle': '#1B2129',
                'brand-border-focus': '#164A8A',

                // Azul Corporativo de GTS (Sobrio, institucional)
                'brand-primary': '#123A70',
                'brand-primary-hover': '#164A8A',
                'brand-primary-light': '#1B5AA3',
                'brand-primary-accent': '#1E64B7',
                'brand-primary-active': '#164A8A',
                'brand-primary-muted': '#101923',

                // Jerarquía de Textos
                'brand-text-primary': '#F2F5F8',
                'brand-text-secondary': '#9AA5B4',
                'brand-text-muted': '#707B8A',
                'brand-text-faint': '#505A68',

                // Colores Semánticos Sobrios
                'state-critical': '#D83B52',
                'state-critical-bg': 'rgba(216, 59, 82, 0.15)',
                'state-critical-border': 'rgba(216, 59, 82, 0.30)',
                'state-critical-text': '#E66A7A',

                'state-high': '#D83B52',
                'state-high-bg': 'rgba(216, 59, 82, 0.15)',
                'state-high-border': 'rgba(216, 59, 82, 0.30)',
                'state-high-text': '#E66A7A',

                'state-medium': '#E0A21A',
                'state-medium-bg': 'rgba(224, 162, 26, 0.15)',
                'state-medium-border': 'rgba(224, 162, 26, 0.30)',
                'state-medium-text': '#E2B24C',

                'state-low': '#3D82C6',
                'state-low-bg': 'rgba(61, 130, 198, 0.15)',
                'state-low-border': 'rgba(61, 130, 198, 0.30)',
                'state-low-text': '#6FA2D2',

                'state-info': '#3D82C6',
                'state-info-bg': 'rgba(61, 130, 198, 0.15)',
                'state-info-border': 'rgba(61, 130, 198, 0.30)',
                'state-info-text': '#6FA2D2',

                'state-neutral': '#687383',
                'state-neutral-bg': '#151A21',
                'state-neutral-border': '#252C35',

                // Compatibilidad con vistas CRUD y componentes compartidos
                'surface-base': '#05070A',
                'surface-dim': '#070A0F',
                'surface-subtle': '#11151B',
                'surface-card': '#11151B',
                'surface-overlay': '#151A21',
                'surface-container': '#151A21',
                'surface-container-low': '#0A0D12',
                'surface-container-high': '#191F27',
                'primary': '#3D82C6',
                'primary-container': '#123A70',
                'on-primary': '#FFFFFF',
                'on-primary-container': '#F2F5F8',
                'border-subtle': '#1B2129',
                'border-active': '#252C35',
                'border-focus': '#164A8A',
                'on-surface': '#F2F5F8',
                'on-surface-variant': '#9AA5B4',
                'outline': '#707B8A',
                'status-resolved': '#20C997',
                'status-warning': '#E0A21A',
                'status-critical': '#D83B52',
                'status-neutral': '#687383',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                'code-folio': ['JetBrains Mono', 'monospace'],
            },
            boxShadow: {
                'enterprise': '0 4px 16px rgba(0, 0, 0, 0.35)',
                'enterprise-sm': '0 2px 8px rgba(0, 0, 0, 0.25)',
                'enterprise-lg': '0 8px 24px rgba(0, 0, 0, 0.45)',
                'glow-primary': '0 0 10px -2px rgba(18, 58, 112, 0.35)',
            },
            borderRadius: {
                'xl': '0.75rem',
                '2xl': '1rem',
            }
        },
    },

    plugins: [forms],
};
