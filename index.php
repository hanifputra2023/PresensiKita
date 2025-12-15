<link rel="icon" type="image/svg+xml" href="includes/08.12.2025_08.44.59_REC.png">
<?php
require_once 'includes/fungsi.php';

// Routing sistem dinamis
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Daftar halaman yang diizinkan tanpa login
$public_pages = ['login', 'logout'];

// Cek Remember Me cookie - auto login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    $token = mysqli_real_escape_string($conn, $_COOKIE['remember_token']);
    $user_id = (int)$_COOKIE['remember_user'];
    
    $query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id' AND remember_token = '$token' AND token_expires > NOW()");
    
    if (mysqli_num_rows($query) == 1) {
        $user = mysqli_fetch_assoc($query);
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
    
    // Auto set alpha untuk jadwal yang sudah selesai
    // Jalankan setiap kali halaman dimuat (langsung tanpa cache)
    auto_set_alpha();
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
