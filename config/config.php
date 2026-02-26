<?php
// Konfigurasi Aplikasi
define('APP_NAME', 'Sistem Presensi Lab');
define('APP_VERSION', '1.0');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Toleransi waktu presensi (dalam menit)
define('TOLERANSI_SEBELUM', 5); // Presensi bisa dibuka 5 menit sebelum mulai
define('BATAS_TELAT', 30); // Batas maksimal keterlambatan (menit) dari jam mulai
define('TOLERANSI_TELAT', 15); // Batas toleransi keterlambatan tanpa sanksi

// Durasi QR Code - sekarang mengikuti jam selesai jadwal
// define('QR_DURASI', 2); // Tidak digunakan lagi

// Base URL
define('BASE_URL', 'http://localhost/presensi%20kampus/');
?>
