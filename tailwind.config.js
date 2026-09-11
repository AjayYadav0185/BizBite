import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * BizBite design system — mirrors mobile/lib/presentation/theme/bizbite_theme.dart.
 * Single source of truth: emerald food-brand over charcoal slate + soft green-grey
 * light surfaces. Keep in sync with the `tailwind.config` runtime object embedded
 * in resources/views/layouts/*.blade.php (used by the CDN fallback).
 */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // --- Brand (theme §3 AppColors) -------------------------------
                brand: {
                    50: '#ECFDF5', // infoBg / successBg (emerald-50)
                    100: '#D1FAE5',
                    200: '#A7F3D0',
                    300: '#6EE7B7', // accentSoft (emerald-300)
                    400: '#34D399', // primaryDark / primaryLight
                    500: '#10B981', // primary / success (emerald-500)
                    600: '#059669', // primarySoft / successDeep
                    700: '#047857', // primaryDeep
                    800: '#065F46',
                    900: '#064E3B',
                },
                // --- Light canvas / surfaces ------------------------------------
                canvas: '#F5F7F6', // background (soft green-white)
                'canvas-soft': '#F8FAF8', // surfaceSoft
                'surface-muted': '#F0F3F1', // surfaceMuted
                'surface-subtle': '#EBEFED', // surfaceSubtle
                'card-border': '#E1E7E3', // border / hairline
                'border-muted': '#CBD5E1', // borderMuted (slate-300)
                'grad-start': '#F2F6F4', // page gradient start
                'grad-mid': '#E6EDE8', // page gradient mid
                'grad-end': '#DCE5DF', // page gradient end
                // --- Status (theme §3) -------------------------------------------
                warn: {
                    50: '#FFF3E8', // warningBg (orange-50)
                    100: '#FFEDD5',
                    500: '#FF8C42', // warning — warm food orange
                    600: '#FF6D00', // warningDeep
                },
                info: {
                    500: '#17A8C4', // infoCyan
                },
                whatsapp: '#25D366',
            },
            boxShadow: {
                // theme AppShadows.card / AppShadows.bar
                card: '0 2px 12px 0 rgba(20, 21, 31, 0.05)',
                'card-hover': '0 4px 16px 0 rgba(20, 21, 31, 0.09)',
            },
        },
    },
    plugins: [],
};
