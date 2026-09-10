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
                'surface-base': '#0B0F17',
                'surface-dim': '#0f131c',
                'surface-subtle': '#111827',
                'surface-card': '#161F30',
                'surface-overlay': '#1E293B',
                'surface-container': '#1c2028',
                'surface-container-low': '#181c24',
                'surface-container-high': '#262a33',
                'primary': '#4cd7f6',
                'primary-container': '#06b6d4',
                'on-primary': '#003640',
                'on-primary-container': '#00424f',
                'border-subtle': '#1E293B',
                'border-active': '#334155',
                'border-focus': '#06B6D4',
                'on-surface': '#dfe2ee',
                'on-surface-variant': '#bcc9cd',
                'outline': '#869397',
                'status-resolved': '#10B981',
                'status-warning': '#F59E0B',
                'status-critical': '#EF4444',
                'status-neutral': '#64748B',
                'infra-cctv': '#06B6D4',
                'infra-access': '#8B5CF6',
                'infra-fids': '#F59E0B',
                'infra-network': '#10B981',
                'infra-radio': '#3B82F6',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                'code-folio': ['JetBrains Mono', 'monospace'],
            },
        },
    },

    plugins: [forms],
};

