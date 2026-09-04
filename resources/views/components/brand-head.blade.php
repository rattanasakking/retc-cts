@props(['title' => null])

@php
    // Every layout pulls its name, logo and colour from here so a college can
    // rebrand the whole app from ตั้งค่าระบบ → ข้อมูลระบบ without touching code.
    $branding = \App\Models\SystemSetting::cached();
@endphp

<title>{{ $title ? $title.' | ' : '' }}{{ $branding->displayName() }}</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap">

@if ($branding->primary_color)
    {{-- daisyUI reads these custom properties at runtime, so the brand colour
         can change without rebuilding the stylesheet. --}}
    <style>
        [data-theme="retccts"] {
            --color-primary: {{ $branding->brandColor() }};
            --color-primary-content: {{ $branding->brandContentColor() }};
        }
    </style>
@endif

<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="{{ $branding->brandColor() }}">
<link rel="icon" type="{{ $branding->logoUrl() ? 'image/*' : 'image/svg+xml' }}" href="{{ $branding->logoUrl() ?? asset('icons/icon.svg') }}">
<link rel="apple-touch-icon" href="{{ $branding->logoUrl() ?? asset('icons/icon.svg') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $branding->displayShortName() }}">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {
                // Silently ignore — the app must work identically with no
                // service worker at all (e.g. browsers that block it).
            });
        });
    }
</script>
