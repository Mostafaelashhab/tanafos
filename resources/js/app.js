// Real-time is opt-in: Echo only initializes when a websocket service is
// configured (VITE_REVERB_APP_KEY). On shared hosting it stays off and the UI
// relies on Livewire polling — no failed socket connections in the browser.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    import('./echo');
}

// Web Push helper (window.TanafosPush) — used by the enable-notifications button.
import './push';
