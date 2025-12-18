<link rel="icon" type="image/png" href="includes/icon.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0066cc">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<script>
// Registrasi Service Worker untuk PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('service-worker.js').then(function(registration) {
      console.log('PWA ServiceWorker registered');
    }, function(err) {
      console.log('PWA ServiceWorker failed: ', err);
    });
  });
}
</script>
<?php
require_once 'includes/fungsi.php';

// Routing sistem dinamis
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Daftar halaman yang diizinkan tanpa login
$public_pages = ['login', 'logout'];

// Cek Remember Me cookie - auto login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_token'];
    $user_id = $_COOKIE['remember_user'];
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND remember_token = ? AND token_expires > NOW()");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Refresh token expiry
        $expires = time() + (30 * 24 * 60 * 60);
        mysqli_query($conn, "UPDATE users SET token_expires = FROM_UNIXTIME($expires) WHERE id = '{$user['id']}'");
        setcookie('remember_token', $token, $expires, '/', '', false, true);
        setcookie('remember_user', $user['id'], $expires, '/', '', false, true);
    } else {
        // Token tidak valid, hapus cookie
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
    }
}

// Cek apakah perlu login
if (!in_array($page, $public_pages)) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?page=login");
        exit;
    }
    
    // [OPTIMASI] Fungsi auto_set_alpha() dinonaktifkan dari sini karena membebani setiap page load.
    // Fungsi ini sebaiknya dijalankan sebagai cron job (tugas terjadwal) di server
    // agar berjalan di background tanpa memperlambat user.
    // Contoh: membuat file `cron_jobs/set_alpha.php` lalu menjalankannya setiap 5 menit.
    auto_set_alpha();
}

// [FITUR BARU] Script Notifikasi Presensi (Web Notification API)
// Hanya dijalankan jika user login dan bukan sedang mengakses halaman API
if (isset($_SESSION['user_id']) && strpos($page, 'api_') !== 0) {
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Cek dukungan browser
    if (!("Notification" in window)) {
        console.log("Browser ini tidak mendukung notifikasi desktop.");
        return;
    }

    // 2. Minta izin jika belum diberikan
    // [FIX MOBILE] Browser mobile memblokir requestPermission otomatis. Harus via interaksi user (klik).
    if (Notification.permission === "default") {
        const btn = document.createElement('button');
        btn.innerHTML = '🔔 Aktifkan Notifikasi';
        btn.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; padding: 12px 20px; background: #4e73df; color: white; border: none; border-radius: 50px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); cursor: pointer; font-weight: bold; font-family: sans-serif;';
        
        btn.onclick = function() {
            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    new Notification("Sistem Presensi", {
                        body: "Notifikasi aktif! Anda akan diingatkan jadwal presensi.",
                        icon: "includes/08.12.2025_08.44.59_REC.png"
                    });
                    btn.remove();
                    checkNotifications(); // Langsung cek jadwal setelah izin diberikan
                }
            });
        };
        document.body.appendChild(btn);
    }

    // 3. Fungsi cek notifikasi ke server
    function checkNotifications() {
        if (Notification.permission === "granted") {
            fetch('index.php?page=api_check_notif')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            const n = new Notification(notif.title, {
                                body: notif.body,
                                icon: "includes/08.12.2025_08.44.59_REC.png",
                                tag: notif.id // Mencegah notifikasi duplikat
                            });
                            n.onclick = function() { window.focus(); window.location.href = notif.url; };
                        });
                    }
                })
                .catch(err => console.error('Gagal cek notifikasi:', err));
        }
    }

    // Jalankan pengecekan setiap 1 menit (60000 ms)
    setInterval(checkNotifications, 60000);
    
    // [OPTIMASI] Cek instan saat tab/browser dibuka kembali
    document.addEventListener("visibilitychange", function() {
        if (document.visibilityState === 'visible') {
            checkNotifications();
        }
    });
    
    // Cek sekali saat halaman dimuat
    setTimeout(checkNotifications, 5000);
});
</script>
<?php
}

// Routing berdasarkan page
switch ($page) {
    // Public pages
    case 'login':
        include 'pages/login.php';
        break;
    case 'login_staff':
        // Redirect ke halaman login terpadu
        header("Location: index.php?page=login");
        exit;
    case 'logout':
        include 'pages/logout.php';
        break;
    case 'unauthorized':
        include 'pages/unauthorized.php';
        break;
    
    // Admin pages
    case 'admin_dashboard':
        cek_role(['admin']);
        include 'pages/admin/dashboard.php';
        break;
    case 'admin_kelas':
        cek_role(['admin']);
        include 'pages/admin/kelas.php';
        break;
    case 'admin_lab':
        cek_role(['admin']);
        include 'pages/admin/lab.php';
        break;
    case 'admin_matakuliah':
        cek_role(['admin']);
        include 'pages/admin/matakuliah.php';
        break;
    case 'admin_mahasiswa':
        cek_role(['admin']);
        include 'pages/admin/mahasiswa.php';
        break;
    case 'admin_asisten':
        cek_role(['admin']);
        include 'pages/admin/asisten.php';
        break;
    case 'admin_statistik':
        cek_role(['admin']);
        include 'pages/admin/statistik.php';
        break;
    case 'admin_jadwal':
        cek_role(['admin']);
        include 'pages/admin/jadwal.php';
        break;
    case 'admin_users':
        cek_role(['admin']);
        include 'pages/admin/users.php';
        break;
    case 'admin_laporan':
        cek_role(['admin']);
        include 'pages/admin/laporan.php';
        break;
    case 'admin_log':
        cek_role(['admin']);
        include 'pages/admin/log.php';
        break;
    case 'admin_materi':
        cek_role(['admin']);
        include 'pages/admin/materi.php';
        break;
    
    // Asisten pages
    case 'asisten_dashboard':
        cek_role(['asisten']);
        include 'pages/asisten/dashboard.php';
        break;
    case 'asisten_statistik':
        cek_role(['asisten']);
        include 'pages/asisten/statistik.php';
        break;
    case 'asisten_jadwal':
        cek_role(['asisten']);
        include 'pages/asisten/jadwal.php';
        break;
    case 'asisten_qrcode':
        cek_role(['asisten']);
        include 'pages/asisten/qrcode.php';
        break;
    case 'asisten_monitoring':
        cek_role(['asisten']);
        include 'pages/asisten/monitoring.php';
        break;
    case 'asisten_presensi_manual':
        cek_role(['asisten']);
        include 'pages/asisten/presensi_manual.php';
        break;
    case 'asisten_izin':
        cek_role(['asisten']);
        include 'pages/asisten/izin.php';
        break;
    case 'asisten_pengajuan_izin':
        cek_role(['asisten']);
        include 'pages/asisten/pengajuan_izin.php';
        break;
    case 'asisten_rekap':
        cek_role(['asisten']);
        include 'pages/asisten/rekap.php';
        break;
    case 'asisten_profil':
        cek_role(['asisten']);
        include 'pages/asisten/profil.php';
        break;
    case 'asisten_materi':
        cek_role(['asisten']);
        include 'pages/asisten/materi.php';
        break;
    
    // Mahasiswa pages
    case 'mahasiswa_dashboard':
        cek_role(['mahasiswa']);
        include 'pages/mahasiswa/dashboard.php';
        break;
    case 'mahasiswa_jadwal':
        cek_role(['mahasiswa']);
        include 'pages/mahasiswa/jadwal.php';
        break;
    case 'mahasiswa_scanner':
        cek_role(['mahasiswa']);
        include 'pages/mahasiswa/scanner.php';
        break;
    case 'mahasiswa_riwayat':
        cek_role(['mahasiswa']);
        include 'pages/mahasiswa/riwayat.php';
        break;
    case 'mahasiswa_izin':
        cek_role(['mahasiswa']);
        include 'pages/mahasiswa/izin.php';
        break;
    case 'mahasiswa_inhall':
        cek_role(['mahasiswa']);
        include 'pages/mahasiswa/inhall.php';
        break;
    case 'mahasiswa_profil':
        cek_role(['mahasiswa']);
        include 'pages/mahasiswa/profil.php';
        break;
    
    // API endpoints
    case 'api_scan_qr':
        include 'api/scan_qr.php';
        break;
    case 'api_get_presensi':
        include 'api/get_presensi.php';
        break;
    case 'api_generate_qr':
        include 'api/generate_qr.php';
        break;
    case 'api_check_notif':
        include 'api/check_notif.php';
        break;
    
    default:
        // Redirect berdasarkan role
        if (isset($_SESSION['role'])) {
            switch ($_SESSION['role']) {
                case 'admin':
                    header("Location: index.php?page=admin_dashboard");
                    break;
                case 'asisten':
                    header("Location: index.php?page=asisten_dashboard");
                    break;
                case 'mahasiswa':
                    header("Location: index.php?page=mahasiswa_dashboard");
                    break;
            }
        } else {
            header("Location: index.php?page=login");
        }
        exit;
}
?>
