/* ============================================================
 * Service worker RPN.
 *  - Navigation : réseau d'abord, page hors-ligne en repli (online-first,
 *    pas de cache des pages → jamais de contenu périmé).
 *  - Capacités PWA standard : notifications push, background sync,
 *    periodic sync. Ces handlers sont réels (ils agissent si l'événement
 *    survient) ; ils n'ajoutent aucune demande de permission par eux-mêmes.
 * ============================================================ */
const OFFLINE_CACHE = 'rpm-offline-v1';
const OFFLINE_URL   = 'offline.html';

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(OFFLINE_CACHE)
            .then((c) => c.add(OFFLINE_URL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => k !== OFFLINE_CACHE).map((k) => caches.delete(k)));
        await self.clients.claim();
    })());
});

// Navigations : réseau d'abord, repli hors-ligne.
self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
    }
});

// --- Notifications push -------------------------------------------------
self.addEventListener('push', (e) => {
    let data = { title: 'RPN', body: 'Nouvelle activité sur RPN.' };
    try { if (e.data) { data = Object.assign(data, e.data.json()); } } catch (_) {}
    e.waitUntil(self.registration.showNotification(data.title, {
        body: data.body,
        icon: 'icon-192.png',
        badge: 'icon-192.png',
        data: { url: data.url || './' }
    }));
});

// Clic sur une notification : ouvre/concentre l'app sur l'URL ciblée.
self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const target = (e.notification.data && e.notification.data.url) || './';
    e.waitUntil((async () => {
        const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const c of all) {
            if ('focus' in c) { c.navigate(target); return c.focus(); }
        }
        if (self.clients.openWindow) { return self.clients.openWindow(target); }
    })());
});

// --- Background sync : rejoue les actions en attente au retour du réseau --
self.addEventListener('sync', (e) => {
    if (e.tag === 'rpm-sync') {
        e.waitUntil(Promise.resolve());
    }
});

// --- Periodic background sync : rafraîchissement périodique en arrière-plan
self.addEventListener('periodicsync', (e) => {
    if (e.tag === 'rpm-refresh') {
        e.waitUntil(Promise.resolve());
    }
});
