const CACHE_NAME = "autodash-v3"; // Naikkan versi agar cache lama bersih
const urlsToCache = [
  "./",
  "./manifest.json",
  "./image/icon.png"
];

// 1. Install Service Worker
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("Install SW: Caching static files");
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting();
});

// 2. Activate (Hapus cache lama)
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log("Menghapus cache lama:", cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// 3. Fetch (Strategi: Network First, Falling Back to Cache)
self.addEventListener("fetch", (event) => {
  // Abaikan request selain GET (misal POST form login, upload manual)
  if (event.request.method !== 'GET') {
    return;
  }

  const url = new URL(event.request.url);

  // KUNCI PERBAIKAN: Abaikan request non-HTTP/HTTPS (misal chrome-extension://)
  if (!url.protocol.startsWith('http')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Cek apakah response valid
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        // Simpan salinan ke cache
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });

        return response;
      })
      .catch(() => {
        // Jika offline, fallback ke cache
        return caches.match(event.request);
      })
  );
});