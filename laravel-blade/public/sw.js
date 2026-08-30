const VERSION = 'v2';
const CACHE_STATIC_NAME = `webcomics-static-${VERSION}`;
const CACHE_PAGES_NAME = `webcomics-pages-${VERSION}`;
const CACHE_READER_NAME = `webcomics-reader-images-${VERSION}`;

const STATIC_ASSETS = [
  '/css/style.css',
  '/js/app.js',
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
      await Promise.all(keysToDelete.map(key => cache.delete(key)));
    }
  } catch (err) {
    console.debug('[SW] Trim cache error:', err);
  }
}

// 1. Cài đặt Service Worker và cache trước các file giao diện cốt lõi
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_STATIC_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('[SW] Caching static assets warning:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// 2. Kích hoạt và dọn dẹp các cache phiên bản cũ
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (![CACHE_STATIC_NAME, CACHE_PAGES_NAME, CACHE_READER_NAME].includes(key)) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// 3. Xử lý Fetch Request
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // --- LOẠI TRỪ (BYPASS CACHE) ---
  // Không cache các POST request, API, Admin, Auth, Tracking...
  if (
    event.request.method !== 'GET' ||
    url.pathname.startsWith('/admin') ||
    url.pathname.startsWith('/api') ||
    url.pathname.startsWith('/login') ||
    url.pathname.startsWith('/register') ||
    url.pathname.startsWith('/history') ||
    url.pathname.startsWith('/comments') ||
    url.pathname.startsWith('/logout') ||
    url.pathname.startsWith('/sanctum') ||
    url.pathname.startsWith('/livewire') ||
    url.pathname.startsWith('/reports')
  ) {
    return; // Mặc định trình duyệt tự lo (Network-Only)
  }

  // --- CHIẾN LƯỢC 1: HÌNH ẢNH (CACHE-FIRST) ---
  if (event.request.destination === 'image' || url.pathname.match(/\.(jpg|jpeg|png|webp|avif|gif)$/i)) {
    event.respondWith(
      caches.open(CACHE_READER_NAME).then(async (cache) => {
        const cachedResponse = await cache.match(event.request);
        if (cachedResponse) {
          return cachedResponse;
        }

        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            cache.put(event.request, networkResponse.clone());
            // Gọi dọn dẹp ngầm
            setTimeout(() => trimCache(CACHE_READER_NAME, MAX_CACHED_IMAGES), 1000);
          }
          return networkResponse;
        }).catch(() => {
          // Trả về ảnh fallback nếu offline hoàn toàn và ảnh chưa được cache
          return new Response(
            '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="400" viewBox="0 0 300 400"><rect fill="#13161e" width="300" height="400"/><text fill="#888" x="50%" y="50%" text-anchor="middle" font-family="sans-serif" font-size="14">Ảnh chưa được lưu offline</text></svg>',
            { headers: { 'Content-Type': 'image/svg+xml' } }
          );
        });
      })
    );
    return;
  }

  // --- CHIẾN LƯỢC 2: TÀI NGUYÊN TĨNH (STALE-WHILE-REVALIDATE) ---
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
          if (networkResponse && networkResponse.status === 200) {
            cache.put(event.request, networkResponse.clone());
          }
          return networkResponse;
        }).catch(() => {});

        return cachedResponse || networkFetch;
      })
    );
    return;
  }

  // --- CHIẾN LƯỢC 3: CÁC TRANG HTML (NETWORK-FIRST VỚI FALLBACK OFFLINE) ---
  if (event.request.mode === 'navigate' || event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(event.request).then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_PAGES_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      }).catch(async () => {
        const cache = await caches.open(CACHE_PAGES_NAME);
        const cachedResponse = await cache.match(event.request);
        if (cachedResponse) {
          return cachedResponse;
        }

        // Fallback UI offline nếu chưa từng cache trang này
        return caches.match('/') || new Response(
          '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Offline - WebComics</title><style>body{background:#0d0f14;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px;}</style></head><body><div><h1>📡 Chế Độ Ngoại Tuyến</h1><p>Bạn đang không có kết nối internet. Các chương truyện bạn đã đọc trước đó vẫn có thể mở lại bình thường.</p><button onclick="window.location.reload()" style="background:#ff5e36;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:12px;">Thử lại</button></div></body></html>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      })
    );
    return;
  }
});
