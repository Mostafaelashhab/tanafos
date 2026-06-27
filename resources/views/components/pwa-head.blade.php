{{-- PWA: manifest, theme, icons, and service-worker registration.
     ?v=2 busts any cache left by a previous PWA on this domain. --}}
<link rel="manifest" href="/manifest.webmanifest?v=2">
<meta name="theme-color" content="#6c5ce7">
<link rel="icon" href="/icons/icon.svg?v=2" type="image/svg+xml">
<link rel="apple-touch-icon" href="/icons/icon-180.png?v=2">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Tanafos">
@auth
    @if (config('banha.push.enabled'))
        <meta name="vapid-public-key" content="{{ config('banha.push.public_key') }}">
    @endif
@endauth

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            const hadController = !!navigator.serviceWorker.controller;
            navigator.serviceWorker.register('/sw.js').then((reg) => {
                reg.update();
                // After a deploy, when the NEW SW replaces an OLD one, reload once
                // so fresh assets + icon replace anything the old PWA cached.
                // (Skip on first install — there was no previous controller.)
                let refreshing = false;
                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    if (refreshing || !hadController) return;
                    refreshing = true;
                    window.location.reload();
                });
            }).catch(() => {});
        });
    }
</script>
