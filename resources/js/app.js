// Real-time is opt-in: Echo only initializes when a websocket service is
// configured (VITE_REVERB_APP_KEY). On shared hosting it stays off and the UI
// relies on Livewire polling — no failed socket connections in the browser.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    import('./echo');
}

// Web Push helper (window.TanafosPush) — used by the enable-notifications button.
import './push';

// --- Splash screen (installed app) ------------------------------------------
function hideSplash() {
    const s = document.getElementById('splash');
    if (!s) return;
    setTimeout(() => {
        s.classList.add('hide');
        setTimeout(() => s.remove(), 500);
    }, 650);
}
window.addEventListener('load', hideSplash);

// --- App-like page transition on SPA navigation -----------------------------
function playPageEnter() {
    const main = document.getElementById('app-main');
    if (!main) return;
    main.classList.remove('page-enter');
    void main.offsetWidth; // restart animation
    main.classList.add('page-enter');
}

// --- Skeleton loader during navigation (only if it takes a moment) ----------
let skeletonTimer = null;
function startSkeleton() {
    clearTimeout(skeletonTimer);
    skeletonTimer = setTimeout(() => {
        document.getElementById('nav-skeleton')?.removeAttribute('hidden');
    }, 180);
}
function stopSkeleton() {
    clearTimeout(skeletonTimer);
    document.getElementById('nav-skeleton')?.setAttribute('hidden', '');
}
// Trigger on any wire:navigate link tap.
document.addEventListener('click', (e) => {
    if (e.target.closest('a[wire\\:navigate], a[wire\\:navigate\\.hover]')) startSkeleton();
}, true);

document.addEventListener('livewire:navigated', () => {
    stopSkeleton();
    resetPtr();
    playPageEnter();
});

// --- Pull to refresh --------------------------------------------------------
const TRIGGER = 65, MAX = 95;
let ptrStartY = 0, ptrPulling = false, ptrDist = 0;
function ptrEl() { return document.getElementById('ptr'); }
function resetPtr() {
    const p = ptrEl();
    if (!p) return;
    p.classList.remove('spin');
    p.style.transition = 'transform .2s ease, opacity .2s ease';
    p.style.transform = 'translateY(-44px)';
    p.style.opacity = '0';
}
window.addEventListener('touchstart', (e) => {
    if (window.scrollY <= 0 && e.touches.length === 1) {
        ptrStartY = e.touches[0].clientY; ptrPulling = true; ptrDist = 0;
    }
}, { passive: true });
window.addEventListener('touchmove', (e) => {
    if (!ptrPulling) return;
    ptrDist = e.touches[0].clientY - ptrStartY;
    const p = ptrEl();
    if (ptrDist > 0 && window.scrollY <= 0 && p) {
        const d = Math.min(ptrDist, MAX);
        p.style.transition = 'none';
        p.style.transform = `translateY(${d - 44}px) rotate(${d * 3}deg)`;
        p.style.opacity = Math.min(d / TRIGGER, 1);
    } else {
        ptrPulling = false;
    }
}, { passive: true });
window.addEventListener('touchend', () => {
    if (!ptrPulling) return;
    ptrPulling = false;
    const p = ptrEl();
    if (ptrDist > TRIGGER && p) {
        p.classList.add('spin');
        if (window.Livewire?.navigate) window.Livewire.navigate(window.location.href);
        else window.location.reload();
    } else {
        resetPtr();
    }
});

// --- Add-to-home-screen (install) prompt ------------------------------------
let deferredPrompt = null;
const installEl = () => document.getElementById('install-banner');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (localStorage.getItem('a2hs-dismissed') === '1') return;
    installEl()?.removeAttribute('hidden');
});

window.addEventListener('appinstalled', () => {
    installEl()?.setAttribute('hidden', '');
    deferredPrompt = null;
});

window.TanafosInstall = {
    async install() {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
        installEl()?.setAttribute('hidden', '');
    },
    dismiss() {
        localStorage.setItem('a2hs-dismissed', '1');
        installEl()?.setAttribute('hidden', '');
    },
};
