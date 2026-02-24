<?php
// includes/maintenance_check.php

// Pastikan koneksi database tersedia
if (isset($conn)) {
    // Ambil status maintenance dari database
    $stmt_m = mysqli_prepare($conn, "SELECT setting_value FROM app_settings WHERE setting_key = 'maintenance_mode'");
    if ($stmt_m) {
        mysqli_stmt_execute($stmt_m);
        $res_m = mysqli_stmt_get_result($stmt_m);
        $row_m = mysqli_fetch_assoc($res_m);
        
        // Cek apakah value '1' (Aktif)
        $maintenance_active = ($row_m && $row_m['setting_value'] == '1');
        
        if ($maintenance_active) {
            // Cek apakah user adalah admin (Bypass maintenance)
            $is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
            
            // Halaman yang diizinkan diakses publik saat maintenance
            // Login & Logout diizinkan agar Admin bisa masuk/keluar
            $allowed_pages_maintenance = ['login', 'logout'];
            
            // Ambil halaman saat ini
            $current_page_check = isset($_GET['page']) ? $_GET['page'] : 'login';
            
            // LOGIKA UTAMA:
            // Jika BUKAN admin DAN halaman saat ini TIDAK termasuk yang diizinkan
            if (!$is_admin && !in_array($current_page_check, $allowed_pages_maintenance)) {
                
                // Set header HTTP 503 (Service Unavailable) - Bagus untuk SEO saat maintenance
                http_response_code(503);
                
                // Tampilkan halaman maintenance
                include 'pages/maintenance.php';
                
                // Hentikan eksekusi script agar konten website asli tidak dimuat
                exit;
            }
        }
    }
}
?>
