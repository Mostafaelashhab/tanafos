// Web Push subscription helper for the Tanafos PWA.
// Exposed as window.TanafosPush for the enable/disable button.

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

function meta(name) {
    return document.querySelector(`meta[name="${name}"]`)?.getAttribute('content');
}

async function api(method, body) {
    return fetch('/push/subscribe', {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': meta('csrf-token'),
            Accept: 'application/json',
        },
        body: body ? JSON.stringify(body) : undefined,
    });
}

export const TanafosPush = {
    supported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && !!meta('vapid-public-key');
    },

    async status() {
        if (!this.supported()) return 'unsupported';
        if (Notification.permission === 'denied') return 'denied';
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        return sub ? 'subscribed' : 'default';
    },

    async subscribe() {
        if (!this.supported()) return 'unsupported';

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return 'denied';

        const reg = await navigator.serviceWorker.ready;
        let sub = await reg.pushManager.getSubscription();
        if (!sub) {
            sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(meta('vapid-public-key')),
            });
        }

        const json = sub.toJSON();
        await api('POST', {
            endpoint: sub.endpoint,
            keys: json.keys,
            contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
        });
        return 'subscribed';
    },

    async unsubscribe() {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (sub) {
            await api('DELETE', { endpoint: sub.endpoint });
            await sub.unsubscribe();
        }
        return 'default';
    },
};

window.TanafosPush = TanafosPush;
