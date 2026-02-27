// Registrasi Service Worker untuk PWA
let deferredPrompt;

// Cek HTTPS (PWA Wajib HTTPS kecuali localhost)
if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
    console.warn('⚠️ PERINGATAN PWA: Aplikasi berjalan di HTTP biasa. Fitur "Install App" di HP mungkin diblokir browser. Gunakan HTTPS atau Ngrok untuk testing di HP.');
}

if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('./service-worker.js')
      .then(function(registration) {
        console.log('ServiceWorker berhasil didaftarkan dengan scope: ', registration.scope);
      }, function(err) {
        console.log('ServiceWorker gagal didaftarkan: ', err);
      });
  });
}

// Tangkap event install agar bisa dipanggil manual
window.addEventListener('beforeinstallprompt', (e) => {
  // Mencegah Chrome 67+ otomatis menampilkan prompt (opsional, agar kita bisa kontrol)
  e.preventDefault();
  // Simpan event untuk dipanggil nanti
  deferredPrompt = e;
  console.log('PWA Install Prompt siap digunakan');

  // Tampilkan tombol install jika ada di halaman
  const installBtn = document.getElementById('pwa-install-btn');
  if (installBtn) {
    installBtn.style.display = 'flex';
  }
});

window.addEventListener('appinstalled', (evt) => {
  console.log('Aplikasi berhasil diinstall');
  const installBtn = document.getElementById('pwa-install-btn');
  if (installBtn) {
    installBtn.style.display = 'none';
  }
});

function installPWA() {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
      console.log('User choice:', choiceResult.outcome);
      deferredPrompt = null;
    });
  }
}