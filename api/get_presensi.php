<?php
// API untuk get presensi real-time (untuk monitoring)
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/fungsi.php';

$jadwal_id = isset($_GET['jadwal_id']) ? (int)$_GET['jadwal_id'] : 0;

if (!$jadwal_id) {
    echo json_encode(['success' => false, 'message' => 'Jadwal ID diperlukan']);
    exit;
}

// Ambil data jadwal - prepared statement
$stmt_jadwal = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas FROM jadwal j 
                                                   LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                   WHERE j.id = ?");
mysqli_stmt_bind_param($stmt_jadwal, "i", $jadwal_id);
mysqli_stmt_execute($stmt_jadwal);
$jadwal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jadwal));

if (!$jadwal) {
    echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
    exit;
}

$kelas = $jadwal['kode_kelas'];

// Statistik - prepared statement
$stmt_total = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM mahasiswa WHERE kode_kelas = ?");
mysqli_stmt_bind_param($stmt_total, "s", $kelas);
mysqli_stmt_execute($stmt_total);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total))['total'];

$stmt_hadir = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM presensi_mahasiswa WHERE jadwal_id = ? AND status = 'hadir'");
mysqli_stmt_bind_param($stmt_hadir, "i", $jadwal_id);
mysqli_stmt_execute($stmt_hadir);
$hadir = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_hadir))['total'];

// List mahasiswa - prepared statement
$list = [];
$stmt_list = mysqli_prepare($conn, "SELECT m.nim, m.nama, p.status, p.waktu_presensi
                               FROM mahasiswa m
                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = ?
                               WHERE m.kode_kelas = ?
                               ORDER BY p.waktu_presensi DESC, m.nama");
mysqli_stmt_bind_param($stmt_list, "is", $jadwal_id, $kelas);
mysqli_stmt_execute($stmt_list);
$query = mysqli_stmt_get_result($stmt_list);

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
