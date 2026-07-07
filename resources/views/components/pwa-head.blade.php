<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2563a8">
<link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
<link rel="apple-touch-icon" href="/icons/icon.svg">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="RETC-CTS">

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
