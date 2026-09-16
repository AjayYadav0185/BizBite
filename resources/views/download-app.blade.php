<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#047857">
    <title>Download the BizBite App</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(160deg, #f0fdf7 0%, #ecfdf5 45%, #d1fae5 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2.5rem 1.25rem 3rem;
        }
        .card {
            width: 100%;
            max-width: 30rem;
            background: #ffffff;
            border: 1px solid #d1fae5;
            border-radius: 1.5rem;
            box-shadow: 0 20px 45px -18px rgba(4, 120, 87, 0.28);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
        }
        .logo { width: 88px; height: 88px; border-radius: 22px; object-fit: contain; }
        h1 { font-size: 1.55rem; font-weight: 700; color: #064e3b; margin-top: 1.1rem; }
        .tagline { margin-top: 0.4rem; font-size: 0.92rem; color: #64748b; line-height: 1.5; }
        .meta { margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.6rem; flex-wrap: wrap; }
        .badge {
            display: inline-block;
            padding: 0.32rem 0.8rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
        }
        .badge.muted { background: #f1f5f9; border-color: #e2e8f0; color: #64748b; font-weight: 500; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            margin-top: 1.6rem;
            padding: 1rem 1.5rem;
            border-radius: 0.9rem;
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            font-size: 1.02rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 12px 22px -10px rgba(4, 120, 87, 0.55);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 16px 26px -10px rgba(4, 120, 87, 0.6); }
        .btn svg { width: 22px; height: 22px; flex-shrink: 0; }
        .btn.disabled { background: #e2e8f0; color: #94a3b8; box-shadow: none; pointer-events: none; }
        .updated { margin-top: 0.7rem; font-size: 0.78rem; color: #94a3b8; }
        .qr { margin-top: 1.6rem; }
        .qr img {
            width: 128px; height: 128px;
            border-radius: 0.75rem;
            border: 1px solid #d1fae5;
            background: #ffffff;
        }
        .qr p { margin-top: 0.45rem; font-size: 0.75rem; color: #94a3b8; }
        .steps {
            margin-top: 1.8rem;
            padding-top: 1.5rem;
            border-top: 1px dashed #d1fae5;
            text-align: left;
        }
        .steps h2 { font-size: 0.8rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #047857; }
        .steps ol { margin: 0.7rem 0 0 1.2rem; font-size: 0.85rem; color: #475569; line-height: 1.7; }
        .steps .note { margin-top: 0.7rem; font-size: 0.75rem; color: #94a3b8; line-height: 1.5; }
        footer { margin-top: 1.8rem; font-size: 0.78rem; color: #94a3b8; text-align: center; }
        footer strong { color: #047857; font-weight: 600; }
        @media (max-width: 380px) { .card { padding: 2rem 1.4rem 1.6rem; } }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo" src="{{ asset('images/bizbite_logo_mark.png') }}" alt="BizBite logo">

        <h1>BizBite POS App</h1>
        <p class="tagline">Lightweight micro-billing POS client for small food outlets.<br>Download the latest Android build below.</p>

        @if ($apk)
            <div class="meta">
                <span class="badge">v{{ $apk['version'] !== '' ? $apk['version'] : '1.0.0' }}@if($apk['build'] !== '') (build {{ $apk['build'] }})@endif</span>
                <span class="badge muted">Android APK</span>
                <span class="badge muted">{{ number_format($apk['size'] / 1048576, 1) }} MB</span>
            </div>

            <a class="btn" href="{{ $downloadUrl }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download Latest Build
            </a>
            <p class="updated">Updated {{ \Illuminate\Support\Carbon::createFromTimestamp($apk['modified'])->setTimezone('Asia/Kolkata')->format('j M Y, g:i A') }}</p>

            <div class="qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=256x256&margin=8&data={{ urlencode($downloadUrl) }}"
                     alt="QR code — scan to download the app"
                     loading="lazy"
                     onerror="this.closest('.qr').style.display='none'">
                <p>Scan to install on your phone</p>
            </div>

            <div class="steps">
                <h2>How to install</h2>
                <ol>
                    <li>Tap the download button above.</li>
                    <li>Open the downloaded APK file.</li>
                    <li>Allow &ldquo;Install from this source&rdquo; if asked.</li>
                    <li>Install and open BizBite. Done!</li>
                </ol>
                <p class="note">This page always serves the newest build — after an update is released, just download and install again over your existing app.</p>
            </div>
        @else
            <div class="meta">
                <span class="badge muted">Android APK</span>
            </div>
            <span class="btn disabled">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                No build available yet
            </span>
            <p class="updated">The download link will appear as soon as the first build is published.</p>
        @endif
    </div>

    <footer>
        <strong>BizBite</strong> — by BizaroHQ &nbsp;·&nbsp; Always download from this page to get the latest version.
    </footer>

</body>
</html>
