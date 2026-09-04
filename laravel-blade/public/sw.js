const VERSION = 'v3';
const CACHE_STATIC_NAME = `webcomics-static-${VERSION}`;
const CACHE_READER_NAME = `webcomics-reader-images-${VERSION}`;

const STATIC_ASSETS = [
  '/css/style.css',
  '/css/responsive.css',
  '/js/app.js',
  '/js/roadmap.js',
  '/manifest.json',
  '/favicon.ico',
];

const MAX_CACHED_IMAGES = 500;

async function trimCache(cacheName, maxItems) {
  try {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxItems) {
      const keysToDelete = keys.slice(0, keys.length - maxItems);
      await Promise.all(keysToDelete.map((key) => cache.delete(key)));
    }
  } catch (err) {
    console.debug('[SW] Trim cache error:', err);
  }
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_STATIC_NAME)
      .then((cache) => cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('[SW] Caching static assets warning:', err);
      }))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.map((key) => {
          if (![CACHE_STATIC_NAME, CACHE_READER_NAME].includes(key)) {
            return caches.delete(key);
          }
        })
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  if (event.request.method !== 'GET') return;

  // Authenticated/private/API areas are always network-only. HTML pages in general are
  // also no longer persisted because comic detail and reader HTML can contain account state.
  if (
    url.pathname.startsWith('/admin') ||
    url.pathname.startsWith('/api') ||
    url.pathname.startsWith('/user') ||
    url.pathname.startsWith('/2fa') ||
    url.pathname.startsWith('/email') ||
    url.pathname.startsWith('/auth') ||
    url.pathname.startsWith('/login') ||
    url.pathname.startsWith('/register') ||
    url.pathname.startsWith('/logout') ||
    url.pathname.startsWith('/forgot-password') ||
    url.pathname.startsWith('/reset-password') ||
    url.pathname.startsWith('/dang-truyen') ||
    url.pathname.startsWith('/publish') ||
    url.pathname.startsWith('/sanctum') ||
    url.pathname.startsWith('/livewire') ||
    url.pathname.startsWith('/reports')
  ) {
    return;
  }

  // Comic images are safe to cache as public assets. Limit the cache so long reading
  // sessions do not grow storage forever.
  if (event.request.destination === 'image' || url.pathname.match(/\.(jpg|jpeg|png|webp|avif|gif)$/i)) {
    event.respondWith(
      caches.open(CACHE_READER_NAME).then(async (cache) => {
        const cachedResponse = await cache.match(event.request);
        if (cachedResponse) return cachedResponse;

        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200 && networkResponse.type !== 'opaque') {
            cache.put(event.request, networkResponse.clone());
            setTimeout(() => trimCache(CACHE_READER_NAME, MAX_CACHED_IMAGES), 1000);
          }
          return networkResponse;
        }).catch(() => new Response(
          '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="400" viewBox="0 0 300 400"><rect fill="#13161e" width="300" height="400"/><text fill="#888" x="50%" y="50%" text-anchor="middle" font-family="sans-serif" font-size="14">Ảnh chưa được lưu offline</text></svg>',
          { headers: { 'Content-Type': 'image/svg+xml' } }
        ));
      })
    );
    return;
  }

  if (
    event.request.destination === 'style' ||
    event.request.destination === 'script' ||
    event.request.destination === 'font' ||
    url.pathname.match(/\.(css|js|woff|woff2|ttf|otf|json|ico)$/i)
  ) {
    event.respondWith(
      caches.open(CACHE_STATIC_NAME).then(async (cache) => {
        const cachedResponse = await cache.match(event.request);
        const networkFetch = fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200 && networkResponse.type !== 'opaque') {
            cache.put(event.request, networkResponse.clone());
          }
          return networkResponse;
        }).catch(() => null);

        return cachedResponse || networkFetch || new Response('', { status: 503 });
      })
    );
    return;
  }

  // Navigations are network-only to avoid leaking stale personalized HTML after logout/account switch.
  if (event.request.mode === 'navigate' || event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(event.request).catch(() => new Response(
        '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline - WebComics</title><style>body{background:#0d0f14;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;padding:20px;box-sizing:border-box}button{background:#ff5e36;color:#fff;border:0;padding:10px 20px;border-radius:8px;font-weight:700}</style></head><body><div><h1>📡 Đang ngoại tuyến</h1><p>Trang HTML cần kết nối mạng để tránh hiển thị dữ liệu tài khoản đã cache. Ảnh truyện công khai đã tải trước đó vẫn được giữ trong cache.</p><button onclick="window.location.reload()">Thử lại</button></div></body></html>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
      ))
    );
  }
});
