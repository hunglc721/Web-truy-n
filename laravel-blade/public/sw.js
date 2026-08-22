// public/sw.js - WebComics Service Worker
const CACHE_STATIC_NAME = 'webcomics-static-v1';
const CACHE_READER_NAME = 'webcomics-reader-images-v1';

const STATIC_ASSETS = [
  '/',
  '/css/style.css',
  '/js/app.js',
  '/manifest.json',
  '/favicon.ico',
];

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
          if (key !== CACHE_STATIC_NAME && key !== CACHE_READER_NAME) {
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

  // Không cache các POST request hoặc trang admin / auth
  if (event.request.method !== 'GET' || url.pathname.startsWith('/admin') || url.pathname.startsWith('/login') || url.pathname.startsWith('/register')) {
    return;
  }

  // A. Hình ảnh truyện tranh: Cache-First với Stale-While-Revalidate để đọc offline mượt mà
  if (event.request.destination === 'image' || url.pathname.match(/\.(jpg|jpeg|png|webp|avif|gif|svg)$/i)) {
    event.respondWith(
      caches.open(CACHE_READER_NAME).then(async (cache) => {
        const cachedResponse = await cache.match(event.request);
        if (cachedResponse) {
          // Trả về ảnh từ cache ngay lập tức, đồng thời tải ngầm bản mới nếu có
          fetch(event.request).then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              cache.put(event.request, networkResponse.clone());
            }
          }).catch(() => {});
          return cachedResponse;
        }

        // Nếu chưa có trong cache thì fetch từ mạng và lưu vào cache
        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            cache.put(event.request, networkResponse.clone());
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

  // B. Tài nguyên tĩnh (CSS, JS, Fonts): Network-First với Cache Fallback
  event.respondWith(
    fetch(event.request).then((response) => {
      if (response && response.status === 200) {
        const responseToCache = response.clone();
        caches.open(CACHE_STATIC_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
      }
      return response;
    }).catch(async () => {
      const cached = await caches.match(event.request);
      if (cached) return cached;

      // Nếu người dùng đang cố truy cập trang HTML khi offline
      if (event.request.headers.get('accept')?.includes('text/html')) {
        return caches.match('/') || new Response(
          '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Offline - WebComics</title><style>body{background:#0d0f14;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px;}</style></head><body><div><h1>📡 Chế Độ Ngoại Tuyến</h1><p>Bạn đang không có kết nối internet. Các chương truyện bạn đã đọc trước đó vẫn có thể mở lại bình thường.</p><button onclick="window.location.reload()" style="background:#ff5e36;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:12px;">Thử lại</button></div></body></html>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      }
    })
  );
});
