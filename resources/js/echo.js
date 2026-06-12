// ────────────────────────────────────────────────────────────────────
// Laravel Echo + Pusher real-time client
//
// Bu modul tarayicide window.Echo objesini hazirlar. Pusher key/cluster
// .env'den Vite uzerinden gomulur (VITE_PUSHER_APP_KEY + VITE_PUSHER_APP_CLUSTER).
//
// Eger key bos ise (Pusher hesabi kurulmamis) Echo HiC olusturulmaz —
// uygulama eskisi gibi calismaya devam eder, sadece anlik toast dusmez.
// Bu sayede Pusher arizalansa veya hesap silinse bile sayfa cokmez.
// ────────────────────────────────────────────────────────────────────
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const PUSHER_KEY = import.meta.env.VITE_PUSHER_APP_KEY || '';
const PUSHER_CLUSTER = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu';

if (PUSHER_KEY) {
    window.Pusher = Pusher;
    // pusher-js authEndpoint XHR cagrisi icin CSRF token gerekli
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    try {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: PUSHER_KEY,
            cluster: PUSHER_CLUSTER,
            forceTLS: true,
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            },
            enabledTransports: ['ws', 'wss'],
        });

        // Bagliik durumunu logla — uretimde DEBUG gerekmez ama dev'de
        // baglanti sorunu varsa konsoldan teshis edilebilir
        if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
            window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                if (window.__PUSHER_DEBUG) {
                    console.log('[Pusher]', states.previous, '→', states.current);
                }
            });
            window.Echo.connector.pusher.connection.bind('error', (err) => {
                console.warn('[Pusher] error', err && err.error ? err.error : err);
            });
        }
    } catch (err) {
        console.warn('[Echo] init failed', err);
        window.Echo = null;
    }
} else {
    // Pusher key yok — Echo'yu null bırak, notifications.js bunu kontrol ediyor
    window.Echo = null;
}
