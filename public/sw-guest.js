/**
 * MentorDE Guest Portal — Service Worker
 * K3: PWA + offline destek
 */

const CACHE_NAME = 'mentorde-guest-v2';
const OFFLINE_URL = '/guest/offline';

// Önbelleğe alınacak statik kaynaklar
const PRECACHE = [
    '/guest/offline',
    '/css/portal-unified-v2.css',
    '/favicon.ico',
];

// Install: offline sayfasını önbelleğe al
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE))
    );
    self.skipWaiting();
});

// Activate: eski cache'leri temizle
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch: network first, offline fallback
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // API isteklerini cache'leme
    if (url.pathname.startsWith('/api/') || url.pathname.includes('csrf')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Navigasyon isteklerini cache'e ekle
                if (event.request.mode === 'navigate') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                // Network yoksa: cache'den dene, yoksa offline sayfasını göster
                return caches.match(event.request).then((cached) => {
                    if (cached) return cached;
                    if (event.request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
});

// Push bildirimleri
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let data = {};
    try { data = event.data.json(); } catch { data = { title: 'MentorDE', body: event.data.text() }; }

    const options = {
        body:  data.body  || '',
        icon:  data.icon  || '/icons/guest-icon-192.png',
        badge: '/icons/guest-icon-192.png',
        data:  { url: data.url || '/guest/dashboard' },
        requireInteraction: false,
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'MentorDE', options)
    );
});

// Bildirime tıklama
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data?.url || '/guest/dashboard';
    event.waitUntil(clients.openWindow(target));
});
