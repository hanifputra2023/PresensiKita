<?php
// Pastikan tidak ada output sebelum JSON
if (ob_get_length()) ob_clean();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

// Include koneksi database
require_once '../config/koneksi.php';

// Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek sesi
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'] ?? ''; // NIM untuk mahasiswa
$notifications = [];

// Set Timezone agar perhitungan waktu akurat (WIB)
date_default_timezone_set('Asia/Jakarta');

// Waktu saat ini
$waktu_sekarang = date('H:i:s');
$tanggal_hari_ini = date('Y-m-d');

if ($role == 'mahasiswa') {
    // 1. Ambil kode kelas mahasiswa - prepared statement
    $stmt_cek_mhs = mysqli_prepare($conn, "SELECT kode_kelas, sesi FROM mahasiswa WHERE nim = ?");
    mysqli_stmt_bind_param($stmt_cek_mhs, "s", $username);
    mysqli_stmt_execute($stmt_cek_mhs);
    $cek_mhs = mysqli_stmt_get_result($stmt_cek_mhs);
    
    if ($cek_mhs && mysqli_num_rows($cek_mhs) > 0) {
        $mhs = mysqli_fetch_assoc($cek_mhs);
        $kode_kelas = $mhs['kode_kelas'];
        $sesi_mhs = $mhs['sesi'] ?? 1;
        
        // 2. Cari jadwal HARI INI yang:
        //    a. Akan mulai dalam 30 menit ke depan (Upcoming)
        //    b. ATAU Sedang berlangsung tapi belum selesai (Ongoing)
        // Kriteria:
        // - Kelas sesuai dengan mahasiswa
        // - Tanggal hari ini
        // - Waktu sesuai (Upcoming / Ongoing)
        // - Belum melakukan presensi (status 'hadir')
        
        $stmt_jadwal = mysqli_prepare($conn, "SELECT j.*, mk.nama_mk, l.nama_lab 
                  FROM jadwal j 
                  JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                  LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                  WHERE j.kode_kelas = ? 
                  AND (j.sesi = 0 OR j.sesi = ?)
                  AND j.tanggal = ?
                  AND (
                      (j.jam_mulai > ? AND j.jam_mulai <= ADDTIME(?, '00:30:00'))
                      OR
                      (j.jam_mulai <= ? AND j.jam_selesai > ?)
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM presensi_mahasiswa p 
                      WHERE p.jadwal_id = j.id AND p.nim = ? AND p.status IN ('hadir', 'izin', 'sakit', 'alpha')
                  )");
        mysqli_stmt_bind_param($stmt_jadwal, "sissssss", $kode_kelas, $sesi_mhs, $tanggal_hari_ini, $waktu_sekarang, $waktu_sekarang, $waktu_sekarang, $waktu_sekarang, $username);
        mysqli_stmt_execute($stmt_jadwal);
        $result = mysqli_stmt_get_result($stmt_jadwal);
        while ($row = mysqli_fetch_assoc($result)) {
            $jam_mulai_ts = strtotime($row['jam_mulai']);
            $jam_selesai_ts = strtotime($row['jam_selesai']);
            $now_ts = time();
            
            $jam_mulai_display = date('H:i', $jam_mulai_ts);
            $jam_selesai_display = date('H:i', $jam_selesai_ts);
            
            if ($now_ts < $jam_mulai_ts) {
                // Case: Akan Mulai
                $menit_lagi = ceil(($jam_mulai_ts - $now_ts) / 60);
                $title = 'Jadwal Kuliah Segera Dimulai!';
                $body = "Matkul: {$row['nama_mk']}\nMulai dalam: {$menit_lagi} menit ({$jam_mulai_display})\nRuang: {$row['nama_lab']}";
            } else {
                // Case: Sedang Berlangsung (Tampilkan Sisa Waktu)
                $sisa_menit = ceil(($jam_selesai_ts - $now_ts) / 60);
                $title = ($sisa_menit <= 15) ? '⚠️ Waktu Presensi Hampir Habis!' : 'Jadwal Sedang Berlangsung';
                $body = "Matkul: {$row['nama_mk']}\nSisa Waktu: {$sisa_menit} menit lagi (Selesai: {$jam_selesai_display})\nJangan lupa presensi!";
            }

            $notifications[] = [
                'id' => 'jadwal_' . $row['id'],
                'title' => $title,
                'body' => $body,
                'url' => 'index.php?page=mahasiswa_scanner'
            ];
        }
    }
}

// Mengembalikan response JSON
echo json_encode([
    'status' => 'success',
    'notifications' => $notifications,
    'timestamp' => time()
]);
exit;
?>