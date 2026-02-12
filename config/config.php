<?php
// Konfigurasi Aplikasi
define('APP_NAME', 'Sistem Presensi Lab');
define('APP_VERSION', '1.0');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Toleransi waktu presensi (dalam menit)
define('TOLERANSI_SEBELUM', 0); // Presensi baru bisa dibuka tepat pada jam mulai (tidak ada toleransi sebelum)
define('TOLERANSI_SESUDAH', 15); // 15 menit setelah jadwal selesai

// Durasi QR Code - sekarang mengikuti jam selesai jadwal
// define('QR_DURASI', 2); // Tidak digunakan lagi

// Base URL
define('BASE_URL', 'http://localhost/presensi%20kampus/');
?>
