<!DOCTYPE html>
<html lang="en-IN" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => 'Restaurant billing for Delhi NCR'])
    <meta name="description" content="BizBite POS — fast GST billing for restaurants, cafés, QSRs and cloud kitchens in Delhi NCR. UPI QR, KOT, WhatsApp bills, stock and staff — on the web and Android.">
    <meta property="og:title" content="BizBite POS — Restaurant billing for Delhi NCR">
    <meta property="og:description" content="GST tax invoices, UPI QR, KOT printing, WhatsApp e-bills and live sales reports.">
</head>
<body class="min-h-screen bg-bg font-sans text-ink antialiased">
@php
    $apk = \App\Http\Controllers\AppDownloadController::latestApk();
    // Direct APK download from GitHub (the APK is committed in storage/app/public/apks).
    $apkFile = $apk['filename'] ?? 'bizbite-1.1.1+3.apk';
    $downloadUrl = 'https://github.com/AjayYadav0185/BizBite/raw/main/storage/app/public/apks/'.rawurlencode($apkFile);
    $version = ($apk['version'] ?? '') !== '' ? $apk['version'] : '1.1.1';
    $features = [
        ['receipt', 'GST tax invoice', 'GSTIN, FSSAI, CGST/SGST per slab, SAC 996331 and amount in words — or a Bill of Supply if you are not registered.'],
        ['qr', 'UPI QR with the exact amount', 'Customers scan with PhonePe, Google Pay, Paytm or BHIM. No typing, no wrong amounts.'],
        ['chef', 'KOT & kitchen board', 'Kitchen tickets with notes, and a live order board from pending to ready.'],
        ['whatsapp', 'WhatsApp e-bills', 'Send the bill to the customer’s WhatsApp in one tap — no paper needed.'],
        ['pause', 'Hold & resume bills', 'Park a table’s bill, serve the next customer, pick it up later.'],
        ['chart', 'Sales, stock & shifts', 'Today’s sales, best-sellers, low-stock alerts and cash-drawer variance.'],
    ];
@endphp

    {{-- ===================================================== NAV --}}
    <header class="sticky top-0 z-30 border-b border-line bg-surface/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/bizbite_logo_mark.png') }}" alt="" width="36" height="36" class="h-9 w-9 rounded-full" />
                <span class="font-display text-lg font-semibold">BizBite POS</span>
            </a>
            <nav class="ml-auto flex items-center gap-1.5 sm:gap-2">
                <a href="#features" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-ink-2 hover:bg-surface-2 sm:block">Features</a>
                <a href="#download" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-ink-2 hover:bg-surface-2 sm:block">Android app</a>
                <button type="button" onclick="bbToggleTheme()" title="Toggle light / dark" class="grid h-9 w-9 place-items-center rounded-lg text-ink-3 hover:bg-surface-2 hover:text-ink">
                    <x-icon name="moon" class="h-[18px] w-[18px] dark:hidden" /><x-icon name="sun" class="hidden h-[18px] w-[18px] dark:block" />
                </button>
                <a href="{{ route('login') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">Sign in</a>
            </nav>
        </div>
    </header>

    {{-- ===================================================== HERO --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -right-32 -top-32 h-[28rem] w-[28rem] rounded-full bg-brand-500/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-accent-500/10 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 md:grid-cols-2 md:py-20">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1 text-xs font-semibold text-brand-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span> Built for Delhi · Gurugram · Noida · Ghaziabad · Faridabad
                </span>
                <h1 class="mt-5 font-display text-4xl font-semibold leading-tight sm:text-5xl">
                    Fast billing for busy <span class="text-brand-ink">restaurant counters</span>.
                </h1>
                <p class="mt-4 max-w-lg text-lg text-ink-2">
                    GST invoices, UPI QR, KOT and WhatsApp bills — on your counter PC and on any Android phone or tablet.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $downloadUrl }}" download="{{ $apkFile }}" class="inline-flex h-12 items-center gap-2 rounded-xl bg-brand-600 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            <x-icon name="download" class="h-5 w-5" /> Download Android app
                        </a>
                    <a href="{{ route('login') }}" class="inline-flex h-12 items-center gap-2 rounded-xl border border-line-strong bg-surface px-6 text-sm font-semibold text-ink transition hover:border-brand-400">
                        Open web POS <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
                @if ($apk)
                    <p class="mt-3 text-xs text-ink-3">Version {{ $version }}@if ($apk['build'] !== '') (build {{ $apk['build'] }})@endif · {{ number_format($apk['size'] / 1048576, 1) }} MB · Android 7+</p>
                @endif
            </div>

            {{-- Mini bill mock --}}
            <div class="mx-auto w-full max-w-sm">
                <div class="rotate-1 rounded-2xl border border-line bg-surface p-5 shadow-pop">
                    <div class="flex items-center justify-between">
                        <p class="font-display text-sm font-semibold">Table 4 · Dine-in</p>
                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Paid · UPI</span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ([['Paneer Butter Masala', 1, 280], ['Butter Naan', 3, 150], ['Masala Chaas', 2, 120]] as [$n, $q, $a])
                            <li class="flex justify-between"><span class="text-ink-2">{{ $q }} × {{ $n }}</span><span class="font-semibold tabular-nums">₹{{ $a }}</span></li>
                        @endforeach
                    </ul>
                    <div class="mt-4 space-y-1 border-t border-dashed border-line-strong pt-3 text-xs text-ink-3">
                        <div class="flex justify-between"><span>Coupon NCR10</span><span>−₹55</span></div>
                        <div class="flex justify-between"><span>CGST 2.5% + SGST 2.5%</span><span>incl.</span></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between rounded-xl bg-brand-600 px-4 py-3 text-white">
                        <span class="text-sm font-semibold">Total</span><span class="font-display text-xl font-semibold">₹495</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================================================== FEATURES --}}
    <section id="features" class="border-y border-line bg-surface py-16">
        <div class="mx-auto max-w-6xl px-4">
            <h2 class="font-display text-3xl font-semibold">Everything the counter needs</h2>
            <p class="mt-2 text-ink-3">Same features on the web POS and the Android app — with offline billing on Android.</p>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as [$icon, $title, $text])
                    <div class="rounded-2xl border border-line bg-bg p-5">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-soft text-brand-ink"><x-icon :name="$icon" class="h-5 w-5" /></span>
                        <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-ink-3">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================================================== DOWNLOAD --}}
    <section id="download" class="py-16">
        <div class="mx-auto max-w-6xl px-4">
            <div class="grid items-center gap-8 overflow-hidden rounded-3xl bg-brand-900 p-8 text-white md:grid-cols-[1fr_auto] md:p-12">
                <div>
                    <h2 class="font-display text-3xl font-semibold">Get the BizBite Android app</h2>
                    <p class="mt-2 max-w-xl text-brand-100/80">Bill from any Android phone or tablet, print to a Bluetooth thermal printer, and keep billing even when the internet drops — bills sync automatically.</p>

                        <a href="{{ $downloadUrl }}" download="{{ $apkFile }}" class="mt-6 inline-flex h-14 items-center gap-3 rounded-2xl bg-white px-7 text-base font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50">
                            <x-icon name="download" class="h-5 w-5" /> Download APK · v{{ $version }}
                        </a>
                    @if ($apk)
                        <p class="mt-3 text-xs text-brand-100/70">
                            {{ number_format($apk['size'] / 1048576, 1) }} MB · updated {{ \Illuminate\Support\Carbon::createFromTimestamp($apk['modified'])->setTimezone('Asia/Kolkata')->format('j M Y') }}
                            · <a href="{{ route('download-app') }}" class="underline hover:text-white">more details</a>
                        </p>
                    @endif

                    <ol class="mt-8 grid gap-3 text-sm text-brand-50/90 sm:grid-cols-3">
                        <li class="rounded-xl bg-white/10 p-3"><b class="block text-white">1. Download</b>Tap the button on your phone (or scan the QR).</li>
                        <li class="rounded-xl bg-white/10 p-3"><b class="block text-white">2. Allow install</b>If asked, allow your browser to install apps.</li>
                        <li class="rounded-xl bg-white/10 p-3"><b class="block text-white">3. Sign in</b>Use the same login as the web POS.</li>
                    </ol>
                </div>

                @if ($apk)
                    <div class="mx-auto text-center">
                        <div id="apk-qr" class="h-44 w-44 rounded-2xl bg-white p-3 [&_svg]:h-full [&_svg]:w-full"></div>
                        <p class="mt-2 text-xs text-brand-100/70">Scan to download on your phone</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <footer class="border-t border-line py-8 text-center text-sm text-ink-3">
        © {{ date('Y') }} BizBite POS · Made in India ·
        <a href="{{ route('login') }}" class="hover:text-brand-ink">Sign in</a> ·
        <a href="{{ route('download-app') }}" class="hover:text-brand-ink">Android app</a>
    </footer>

    @if ($apk)
        <script src="{{ asset('js/vendor/qrcode.js') }}"></script>
        <script>
            (function () {
                var el = document.getElementById('apk-qr');
                if (!el || typeof qrcode === 'undefined') return;
                var qr = qrcode(0, 'M');
                qr.addData(@json($downloadUrl));
                qr.make();
                el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
            })();
        </script>
    @endif
    @livewireScripts
</body>
</html>
