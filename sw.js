const CACHE_NAME = "autodash-v2"; // Ganti versi biar cache lama terhapus
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
  self.skipWaiting(); // Paksa SW baru untuk segera aktif
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
// Ini yang memperbaiki masalah Redirect Error
self.addEventListener("fetch", (event) => {
  
  // Abaikan request selain GET (misal POST login)
  if (event.request.method !== 'GET') {
      return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Jika berhasil connect internet/server:
        // 1. Cek apakah response valid (bukan error)
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        // 2. Simpan copy halaman terbaru ke cache (untuk bekal offline nanti)
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });

        return response;
      })
      .catch(() => {
        // Jika OFFLINE / Internet Mati:
        // Ambil dari cache
        return caches.match(event.request);
      })
  );
});