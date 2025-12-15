<?php
// API untuk get presensi real-time (untuk monitoring)
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/fungsi.php';

$jadwal_id = isset($_GET['jadwal_id']) ? (int)$_GET['jadwal_id'] : 0;

if (!$jadwal_id) {
    echo json_encode(['success' => false, 'message' => 'Jadwal ID diperlukan']);
    exit;
}

// Ambil data jadwal
$jadwal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.*, k.nama_kelas FROM jadwal j 
                                                   LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                   WHERE j.id = '$jadwal_id'"));

if (!$jadwal) {
    echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
    exit;
}

$kelas = $jadwal['kode_kelas'];

// Statistik
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa WHERE kode_kelas = '$kelas'"))['total'];
$hadir = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id' AND status = 'hadir'"))['total'];

// List mahasiswa
$list = [];
$query = mysqli_query($conn, "SELECT m.nim, m.nama, p.status, p.waktu_presensi
                               FROM mahasiswa m
                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = '$jadwal_id'
                               WHERE m.kode_kelas = '$kelas'
                               ORDER BY p.waktu_presensi DESC, m.nama");

while ($row = mysqli_fetch_assoc($query)) {
    $list[] = $row;
}

echo json_encode([
    'success' => true,
    'jadwal' => $jadwal,
    'statistik' => [
        'total' => $total,
        'hadir' => $hadir,
        'belum' => $total - $hadir
    ],
    'mahasiswa' => $list
]);
?>
