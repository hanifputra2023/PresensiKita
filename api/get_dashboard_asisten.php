<?php
// API untuk get statistik dashboard asisten secara real-time
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/fungsi.php';

// Authorization check - hanya asisten yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Akses ditolak']);
    exit;
}

$asisten = get_asisten_login();

if (!$asisten) {
    echo json_encode(['success' => false, 'message' => 'Data asisten tidak ditemukan']);
    exit;
}

$kode_asisten = $asisten['kode_asisten'];

// Helper clause: asisten bisa lihat jadwal sendiri ATAU jadwal yang digantikan
// Konsisten dengan rekap.php
$jadwal_asisten_clause = "(
    (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE kode_asisten = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE pengganti = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
)";

// Statistik kehadiran MAHASISWA bulan ini (di jadwal yang diajar asisten ini)
// Menggunakan logika yang sama dengan rekap.php untuk konsistensi
$start_month = date('Y-m-01');
$end_month = date('Y-m-t');

$stat_hadir = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE 
            WHEN j.jenis != 'inhall' 
                 AND (p.status = 'alpha' OR ((p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha')) 
                 AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() 
                 AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)))
            THEN 1 
            ELSE 0 
        END) as alpha
    FROM jadwal j
    JOIN mahasiswa m ON j.kode_kelas = m.kode_kelas
    LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND m.nim = p.nim
    WHERE $jadwal_asisten_clause
    AND j.tanggal BETWEEN '$start_month' AND '$end_month'
"));

// Hitung persentase kehadiran
$total_kehadiran = ($stat_hadir['hadir'] ?? 0) + ($stat_hadir['izin'] ?? 0) + ($stat_hadir['sakit'] ?? 0) + ($stat_hadir['alpha'] ?? 0);
$persen_hadir = $total_kehadiran > 0 ? round((($stat_hadir['hadir'] ?? 0) / $total_kehadiran) * 100) : 0;

// Presensi terbaru yang di-scan di jadwal asisten ini
$recent_presensi = mysqli_query($conn, "
    SELECT pm.*, m.nama as nama_mhs, m.nim, j.tanggal, mk.nama_mk, pm.waktu_presensi
    FROM presensi_mahasiswa pm
    JOIN mahasiswa m ON pm.nim = m.nim
    JOIN jadwal j ON pm.jadwal_id = j.id
    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
    WHERE $jadwal_asisten_clause
    ORDER BY pm.waktu_presensi DESC
    LIMIT 6
");

$recent_list = [];
while ($act = mysqli_fetch_assoc($recent_presensi)) {
    $recent_list[] = [
        'nama_mhs' => $act['nama_mhs'],
        'nim' => $act['nim'],
        'nama_mk' => $act['nama_mk'],
        'status' => $act['status'],
        'waktu_presensi' => date('H:i', strtotime($act['waktu_presensi']))
    ];
}

// Total presensi mahasiswa bulan ini (di jadwal asisten)
$total_jadwal_bulan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM presensi_mahasiswa pm
    JOIN jadwal j ON pm.jadwal_id = j.id
    WHERE j.tanggal BETWEEN '$start_month' AND '$end_month'
    AND $jadwal_asisten_clause
    AND pm.status != 'belum'
"))['total'];

// Statistik minggu ini
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

$stat_minggu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal j
                                                  WHERE j.tanggal BETWEEN '$week_start' AND '$week_end'
                                                  AND $jadwal_asisten_clause"));

echo json_encode([
    'success' => true,
    'data' => [
        'stat_hadir' => [
            'hadir' => (int)($stat_hadir['hadir'] ?? 0),
            'izin' => (int)($stat_hadir['izin'] ?? 0),
            'sakit' => (int)($stat_hadir['sakit'] ?? 0),
            'alpha' => (int)($stat_hadir['alpha'] ?? 0)
        ],
        'persen_hadir' => $persen_hadir,
        'total_jadwal_bulan' => (int)$total_jadwal_bulan,
        'jadwal_minggu_ini' => (int)$stat_minggu['total'],
        'recent_presensi' => $recent_list
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
