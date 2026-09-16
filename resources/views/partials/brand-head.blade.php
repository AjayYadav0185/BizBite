<?php // BizBite — shared <head> branding (favicons + PWA/social metadata). ?>
{{-- =====================================================================
     Both layouts (`app` for the portals, `guest` for the sign-in screen)
     include this so the browser tab, iOS home-screen shortcut and link
     previews all show the BizBite badge.

     Files live in public/ and public/images/ — generated from the master
     logo by `bash mobile/scripts/generate_brand_icons.sh`. They are plain
     static assets (no Vite/Node step), so the branding also survives the
     tailwind-CDN dev fallback.
====================================================================== --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
<meta name="theme-color" content="#3A2D56">
<meta property="og:image" content="{{ asset('images/bizbite_app_icon.png') }}">
<meta property="og:site_name" content="BizBite">
