// ============================================================
// Temizci Burada — Service Worker (PWA)
// ============================================================

const CACHE_VERSION = 'temizci-v5';
const STATIC_ASSETS = [
    '/',
    '/assets/css/style.css',
    '/assets/css/dark-mode.css',
    '/assets/js/app.js',
    '/assets/js/theme.js',
    '/logo.png',
    '/manifest.json',
];

// Yükleme — static dosyaları önbelleğe al
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Aktivasyon — eski önbellekleri temizle
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => key !== CACHE_VERSION)
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch — önce ağ, sonra önbellek (Network First stratejisi)
self.addEventListener('fetch', (event) => {
    const { request } = event;
    
    // API ve POST isteklerini önbelleğe alma
    if (request.method !== 'GET' || request.url.includes('/api/') || request.url.includes('notifications_ajax')) {
        return;
    }

    // Statik dosyalar için Cache First
    if (STATIC_ASSETS.some(asset => request.url.endsWith(asset))) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request))
        );
        return;
    }

    // Dinamik sayfalar için Network First
    event.respondWith(
        fetch(request)
            .then(response => {
                // Başarılı yanıtı önbelleğe kopyala
                const clone = response.clone();
                caches.open(CACHE_VERSION).then(cache => cache.put(request, clone));
                return response;
            })
            .catch(() => {
                // Ağ yoksa önbellekten servis et
                return caches.match(request).then(cached => {
                    if (cached) return cached;
                    // Offline sayfası
                    return new Response(
                        `<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Çevrimdışı — Temizci Burada</title><style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:Inter,sans-serif;background:#0f0c29;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:20px;}h1{font-size:2rem;margin-bottom:12px;}.icon{font-size:4rem;margin-bottom:20px;}p{opacity:0.7;max-width:400px;}button{margin-top:24px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;padding:14px 32px;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;}</style></head><body><div><div class="icon">📡</div><h1>Çevrimdışısınız</h1><p>İnternet bağlantınız yok gibi görünüyor. Bağlantınızı kontrol edip tekrar deneyin.</p><button onclick="location.reload()">🔄 Tekrar Dene</button></div></body></html>`,
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                });
            })
    );
});

// Push Bildirimleri (gelecekte kullanılabilir)
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Temizci Burada';
    const options = {
        body: data.body || 'Yeni bir bildiriminiz var.',
        icon: '/logo.png',
        badge: '/logo.png',
        vibrate: [100, 50, 100],
        data: { url: data.url || '/' },
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data.url || '/';
    event.waitUntil(clients.openWindow(url));
});
