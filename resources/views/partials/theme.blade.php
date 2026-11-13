{{-- =====================================================================
     BizBite design system — SINGLE SOURCE for colour tokens.

     Every colour is a CSS variable holding an "R G B" triplet, so Tailwind
     can apply opacity (`bg-brand-500/20`) and the semantic tokens (bg,
     surface, line, ink…) flip automatically between light and dark mode.
     tailwind.config.js maps the same names for the Vite build; the inline
     `tailwind.config` below does the same for the CDN dev fallback.

     Brand = the BizBite logo purple (#3F2C69 family); accent = saffron.
====================================================================== --}}
<script>
    // Apply the saved theme before first paint (no light→dark flash).
    (function () {
        try {
            var saved = localStorage.getItem('bb-theme');
            var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();

    window.bbToggleTheme = function () {
        var dark = !document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark', dark);
        try { localStorage.setItem('bb-theme', dark ? 'dark' : 'light'); } catch (e) {}
    };
</script>

<style>
    :root {
        /* Brand — logo purple */
        --brand-50: 244 241 251;  --brand-100: 233 227 247; --brand-200: 211 199 239;
        --brand-300: 180 159 226; --brand-400: 149 120 211; --brand-500: 122 90 194;
        --brand-600: 101 69 168;  --brand-700: 82 56 137;   --brand-800: 63 44 105;
        --brand-900: 46 33 80;    --brand-950: 28 20 51;
        /* Accent — saffron */
        --accent-50: 255 244 236; --accent-100: 255 228 207; --accent-400: 251 146 60;
        --accent-500: 249 115 22; --accent-600: 234 88 12;   --accent-700: 194 65 12;

        /* Semantic (light) */
        --bg: 246 245 250;
        --surface: 255 255 255;
        --surface-2: 242 240 247;
        --surface-3: 231 228 239;
        --line: 228 225 236;
        --line-strong: 204 199 219;
        --ink: 24 20 38;
        --ink-2: 74 68 94;
        --ink-3: 118 112 138;
        --brand-ink: 101 69 168;   /* brand text that stays readable */
        --brand-soft: 244 241 251; /* tinted brand background */
        color-scheme: light;
    }

    .dark {
        --bg: 14 11 22;
        --surface: 23 19 34;
        --surface-2: 32 27 46;
        --surface-3: 44 38 62;
        --line: 45 39 64;
        --line-strong: 68 60 94;
        --ink: 243 241 248;
        --ink-2: 199 193 217;
        --ink-3: 143 136 166;
        --brand-ink: 180 159 226;
        --brand-soft: 40 31 66;
        color-scheme: dark;
    }

    html { -webkit-tap-highlight-color: transparent; }
    body { font-feature-settings: "tnum" 1, "cv11" 1; }
    ::selection { background: rgb(var(--brand-500)); color: #fff; }
    [x-cloak] { display: none !important; }

    * { scrollbar-width: thin; scrollbar-color: rgb(var(--line-strong)) transparent; }
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-thumb { background: rgb(var(--line-strong)); border-radius: 999px; border: 2px solid transparent; background-clip: padding-box; }
    ::-webkit-scrollbar-track { background: transparent; }

    /* Keep native number spinners out of the POS keypad flow. */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }

    /* ------------------------------------------------------------------
       Dark-mode bridge for the older admin panels, which still use plain
       Tailwind greys / white. Scoped under .dark so light mode is untouched.
       Receipt paper sets inline colours, so it always stays black-on-white.
    ------------------------------------------------------------------ */
    .dark .bg-white { background-color: rgb(var(--surface)); }
    .dark .bg-slate-50, .dark .bg-slate-100, .dark .bg-gray-50, .dark .bg-gray-100 { background-color: rgb(var(--surface-2)); }
    .dark .bg-slate-200 { background-color: rgb(var(--surface-3)); }
    .dark .text-slate-900, .dark .text-slate-800, .dark .text-gray-900, .dark .text-black { color: rgb(var(--ink)); }
    .dark .text-slate-700, .dark .text-slate-600, .dark .text-gray-700, .dark .text-gray-600 { color: rgb(var(--ink-2)); }
    .dark .text-slate-500, .dark .text-slate-400, .dark .text-gray-500 { color: rgb(var(--ink-3)); }
    .dark .border-slate-100, .dark .border-slate-200, .dark .border-slate-300, .dark .border-gray-200 { border-color: rgb(var(--line)); }
    .dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
    .dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]),
    .dark .divide-card-border > :not([hidden]) ~ :not([hidden]) { border-color: rgb(var(--line)); }
    .dark .bg-brand-50, .dark .hover\:bg-brand-50:hover, .dark .hover\:bg-brand-50\/60:hover { background-color: rgb(var(--brand-soft)); }
    .dark .text-brand-600, .dark .text-brand-700 { color: rgb(var(--brand-ink)); }
    .dark .bg-red-50 { background-color: rgb(239 68 68 / .12); }
    .dark .bg-emerald-50, .dark .bg-green-50 { background-color: rgb(16 185 129 / .12); }
    .dark .bg-amber-50, .dark .bg-warn-50 { background-color: rgb(249 115 22 / .12); }
    .dark .text-emerald-700, .dark .text-green-700 { color: rgb(110 231 183); }
    .dark .text-red-600, .dark .text-red-700 { color: rgb(252 165 165); }
    .dark .hover\:bg-red-50:hover { background-color: rgb(239 68 68 / .12); }
    .dark input:not([type=checkbox]):not([type=radio]):not(.bb-keep),
    .dark select, .dark textarea { background-color: rgb(var(--surface-2)); color: rgb(var(--ink)); border-color: rgb(var(--line)); }
</style>

@unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    {{-- Dev fallback when Vite assets are not built (`npm run build`).
         Production images always build assets (see Dockerfile). --}}
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        (function () {
            var v = function (name) { return 'rgb(var(--' + name + ') / <alpha-value>)'; };
            var scale = function (name, steps) {
                var out = {};
                steps.forEach(function (s) { out[s] = v(name + '-' + s); });
                return out;
            };
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                            display: ['Poppins', 'Figtree', 'ui-sans-serif', 'sans-serif'],
                        },
                        colors: {
                            brand: scale('brand', [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]),
                            accent: scale('accent', [50, 100, 400, 500, 600, 700]),
                            bg: v('bg'), surface: v('surface'), 'surface-2': v('surface-2'), 'surface-3': v('surface-3'),
                            line: v('line'), 'line-strong': v('line-strong'),
                            ink: v('ink'), 'ink-2': v('ink-2'), 'ink-3': v('ink-3'),
                            'brand-ink': v('brand-ink'), 'brand-soft': v('brand-soft'),
                            // Legacy aliases used by the older admin panels.
                            canvas: v('bg'), 'canvas-soft': v('surface-2'), 'surface-muted': v('surface-2'),
                            'surface-subtle': v('surface-3'), 'card-border': v('line'), 'border-muted': v('line-strong'),
                            'grad-start': v('bg'), 'grad-mid': v('surface-2'), 'grad-end': v('surface-3'),
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
            };
        })();
    </script>
@endunless
