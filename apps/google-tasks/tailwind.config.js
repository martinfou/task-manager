import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gt: {
                    canvas: 'rgb(var(--gt-canvas) / <alpha-value>)',
                    raised: 'rgb(var(--gt-raised) / <alpha-value>)',
                    field: 'rgb(var(--gt-field) / <alpha-value>)',
                    'field-muted': 'rgb(var(--gt-field-muted) / <alpha-value>)',
                    border: 'rgb(var(--gt-border) / <alpha-value>)',
                    'border-strong': 'rgb(var(--gt-border-strong) / <alpha-value>)',
                    ink: 'rgb(var(--gt-ink) / <alpha-value>)',
                    'ink-secondary': 'rgb(var(--gt-ink-secondary) / <alpha-value>)',
                    muted: 'rgb(var(--gt-muted) / <alpha-value>)',
                    subtle: 'rgb(var(--gt-subtle) / <alpha-value>)',
                    accent: 'rgb(var(--gt-accent) / <alpha-value>)',
                    'accent-hover': 'rgb(var(--gt-accent-hover) / <alpha-value>)',
                    'accent-strong': 'rgb(var(--gt-accent-strong) / <alpha-value>)',
                    'accent-strong-hover':
                        'rgb(var(--gt-accent-strong-hover) / <alpha-value>)',
                    'accent-ring': 'rgb(var(--gt-accent-ring) / <alpha-value>)',
                    'accent-tint': 'rgb(var(--gt-accent-tint) / <alpha-value>)',
                    nav: 'rgb(var(--gt-nav) / <alpha-value>)',
                    'nav-border': 'rgb(var(--gt-nav-border) / <alpha-value>)',
                },
            },
        },
    },

    plugins: [forms],
};
