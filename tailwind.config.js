import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * BizBite design system.
 *
 * Colour VALUES live in resources/views/partials/theme.blade.php as CSS
 * variables ("R G B" triplets) so light/dark mode is a single class flip on
 * <html>. This file only maps Tailwind names onto those variables — keep it
 * in sync with the inline `tailwind.config` (CDN fallback) in that partial.
 */
const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;
const scale = (name, steps) => Object.fromEntries(steps.map((s) => [s, v(`${name}-${s}`)]));

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: scale('brand', [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]),
                accent: scale('accent', [50, 100, 400, 500, 600, 700]),
                bg: v('bg'),
                surface: v('surface'),
                'surface-2': v('surface-2'),
                'surface-3': v('surface-3'),
                line: v('line'),
                'line-strong': v('line-strong'),
                ink: v('ink'),
                'ink-2': v('ink-2'),
                'ink-3': v('ink-3'),
                'brand-ink': v('brand-ink'),
                'brand-soft': v('brand-soft'),
                // Legacy aliases used by the older admin panels.
                canvas: v('bg'),
                'canvas-soft': v('surface-2'),
                'surface-muted': v('surface-2'),
                'surface-subtle': v('surface-3'),
                'card-border': v('line'),
                'border-muted': v('line-strong'),
                'grad-start': v('bg'),
                'grad-mid': v('surface-2'),
                'grad-end': v('surface-3'),
                warn: { 50: v('accent-50'), 100: v('accent-100'), 500: v('accent-500'), 600: v('accent-600') },
                info: { 500: '#17A8C4' },
                whatsapp: '#25D366',
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(20 16 40 / .04), 0 2px 10px -2px rgb(20 16 40 / .06)',
                'card-hover': '0 4px 20px -4px rgb(20 16 40 / .14)',
                pop: '0 12px 40px -8px rgb(20 16 40 / .28)',
            },
        },
    },
    plugins: [],
};
