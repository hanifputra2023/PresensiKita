const CACHE_NAME = 'presensi-app-v3'; // Versi dinaikkan agar browser mereset cache lama
const urlsToCache = [
  './',
  './index.php',
  './includes/icon.png',
  './manifest.json?v=2' // Harus sama persis dengan link di index.php
];

// Event Install: Dipanggil saat browser pertama kali mendeteksi PWA
// Kita WAJIB menyimpan file penting ke cache agar lolos syarat "Offline Capability"
self.addEventListener('install', (event) => {
  self.skipWaiting(); // Langsung aktifkan service worker baru
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: Caching files');
        return cache.addAll(urlsToCache);
      })
  );
});

// Event Activate: Membersihkan cache lama jika ada update
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

// Event Fetch: Mengatur lalu lintas data
// Strategi: Network First, Fallback to Cache
// 1. Coba ambil data terbaru dari internet (agar presensi selalu real-time)
// 2. Jika internet mati (Offline), ambil dari cache agar aplikasi tetap bisa dibuka
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request)
      .catch(() => {
        // Jika offline, cari di cache
        return caches.match(event.request)
          .then((response) => {
            if (response) {
              return response;
            }
            // Jika request halaman (HTML) tapi tidak ada di cache, berikan halaman utama (index.php)
            // Ini trik agar halaman dashboard tetap bisa dibuka walau offline
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('./index.php');
            }
          });
      })
  );
});

// Event Notification Click: Menangani interaksi saat notifikasi diklik
// Ini WAJIB ada jika menggunakan showNotification di Service Worker
self.addEventListener('notificationclick', function(event) {
  console.log('[Service Worker] Notification click Received.');

  event.notification.close(); // Tutup notifikasi di bar Android

  // Ambil URL yang dikirim dari index.php via data
  var urlToOpen = event.notification.data && event.notification.data.url ? event.notification.data.url : './';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
      // 1. Cek apakah tab aplikasi sudah terbuka? Jika ya, fokus ke sana
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url.indexOf(urlToOpen) !== -1 && 'focus' in client) {
          return client.focus();
        }
      }
      // 2. Jika tidak ada, buka tab baru
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});