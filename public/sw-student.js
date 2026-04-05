/**
 * MentorDE Student Portal — Service Worker
 * PWA + offline destek
 */

const CACHE_NAME = 'mentorde-student-v1';
const OFFLINE_URL = '/student/offline';

const PRECACHE = [
    '/student/offline',
    '/favicon.ico',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    if (url.pathname.startsWith('/api/') || url.pathname.includes('csrf')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (event.request.mode === 'navigate') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((cached) => {
                    if (cached) return cached;
                    if (event.request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
});

self.addEventListener('push', (event) => {
    const data = event.data?.json() ?? {};
    const options = {
        body:    data.body    || 'MentorDE\'den yeni bir bildirim var.',
        icon:    data.icon    || '/icons/student-icon-192.png',
        badge:   data.badge   || '/icons/student-icon-192.png',
        data:    { url: data.url || '/student/dashboard' },
        vibrate: [200, 100, 200],
    };
    event.waitUntil(
        self.registration.showNotification(data.title || 'MentorDE', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data?.url || '/student/dashboard';
    event.waitUntil(clients.openWindow(target));
});
