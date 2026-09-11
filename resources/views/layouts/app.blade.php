<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'BizBite' }} · BizaroHQ</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&family=poppins:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Dev fallback: Vite assets not built (`npm install && npm run build`).
             Keeps the portals fully usable without a Node toolchain. --}}
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            // BizBite design tokens — MIRRORS tailwind.config.js AND
            // mobile/lib/presentation/theme/bizbite_theme.dart (single source).
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Poppins', 'Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        },
                        colors: {
                            brand: {
                                50: '#ECFDF5', 100: '#D1FAE5', 200: '#A7F3D0', 300: '#6EE7B7',
                                400: '#34D399', 500: '#0c4f8c', 600: '#059669', 700: '#047857',
                                800: '#065F46', 900: '#064E3B',
                            },
                            canvas: '#F5F7F6',
                            'canvas-soft': '#F8FAF8',
                            'surface-muted': '#F0F3F1',
                            'surface-subtle': '#EBEFED',
                            'card-border': '#E1E7E3',
                            'border-muted': '#CBD5E1',
                            'grad-start': '#F2F6F4',
                            'grad-mid': '#E6EDE8',
                            'grad-end': '#DCE5DF',
                            warn: { 50: '#FFF3E8', 100: '#FFEDD5', 500: '#FF8C42', 600: '#FF6D00' },
                            info: { 500: '#17A8C4' },
                            whatsapp: '#25D366',
                        },
                        boxShadow: {
                            card: '0 2px 12px 0 rgba(20, 21, 31, 0.05)',
                            'card-hover': '0 4px 16px 0 rgba(20, 21, 31, 0.09)',
                        },
                    },
                },
            };
        </script>
    @endif
    @livewireStyles
</head>
<body class="font-sans antialiased bg-canvas min-h-screen text-slate-900">
    {{ $slot }}

    @livewireScripts
</body>
</html>
