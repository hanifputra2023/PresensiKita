<?php
// Set header untuk Service Worker
header('Content-Type: application/javascript');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store, must-revalidate');
?>
// service-worker.js
// PWA Service Worker untuk Sistem Presensi Kampus

const CACHE_NAME = 'presensi-app-v5';
const urlsToCache = [
  './',
  './index.php',
  './includes/icon-192.png',
  './includes/icon-512.png'
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
      );
    })
  );
  return self.clients.claim();
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
          if (event.request.mode === 'navigate') {
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
