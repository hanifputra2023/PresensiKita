// service-worker.js
// PWA Service Worker untuk Sistem Presensi Kampus


const CACHE_NAME = 'presensi-app-v11';
const urlsToCache = [
  './',
  'index.php',
  'assets/img/52452554464_81be58f500_m.png',
  'assets/img/512x512-logo-barcelona-logo-png-0.png',
  'manifest.json'
];

// Event Install: Cache file penting saat pertama kali install
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: Caching files');
        return cache.addAll(urlsToCache).catch((err) => {
          console.log('Cache addAll error (non-critical):', err);
        });
      })
  );
});

// Event Activate: Hapus cache lama saat update
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cacheName);
          }
        })
      ).then(() => self.clients.claim()); // Claim clients segera setelah update
    })
  );
});

// Event Fetch: Network First strategy dengan fallback ke cache
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;
  
  // Skip chrome-extension dan requests lain yang tidak perlu
  if (!event.request.url.startsWith('http')) return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Clone response untuk disimpan ke cache
        const responseClone = response.clone();
        
        // Simpan ke cache untuk offline access
        caches.open(CACHE_NAME).then((cache) => {
          // Hanya cache request yang sukses
          if (response.status === 200) {
            cache.put(event.request, responseClone);
          }
        });
        
        return response;
      })
      .catch(() => {
        // Jika offline, coba ambil dari cache
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          // Untuk navigasi (halaman HTML), fallback ke halaman utama
          if (event.request.mode === 'navigate' || (event.request.method === 'GET' && event.request.headers.get('accept').includes('text/html'))) {
            return caches.match('./index.php');
          }
          
          // Return offline response untuk request lain
          return new Response('Offline - Konten tidak tersedia', {
            status: 503,
            statusText: 'Service Unavailable'
          });
        });
      })
  );
});