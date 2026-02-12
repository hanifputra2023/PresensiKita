<?php
// 1. HUBUNGKAN KE DATABASE
// Sesuaikan path ini dengan lokasi file koneksi/config database Anda
// Biasanya ada di folder includes atau config
include '../../config/koneksi.php'; // <--- PASTIKAN PATH INI BENAR (Naik 3 folder ke root)
// Jika Anda punya file helper untuk fungsi escape(), include juga di sini
// include '../../../includes/functions.php'; 

// Fungsi escape manual jika belum ada
if (!function_exists('escape')) {
    function escape($string) {
        global $conn;
        return mysqli_real_escape_string($conn, $string);
    }
}

// 2. AMBIL VARIABEL GET (Sama seperti statistik.php)
$filter_bulan = isset($_GET['bulan']) ? escape($_GET['bulan']) : date('Y-m');
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_mk    = isset($_GET['mk']) ? escape($_GET['mk']) : '';
$filter_lab   = isset($_GET['lab']) ? escape($_GET['lab']) : '';
$view         = isset($_GET['view']) ? escape($_GET['view']) : 'kelas';

// Range Tanggal
$start_date = $filter_bulan . '-01';
$end_date   = date('Y-m-t', strtotime($start_date));

// 3. BUILD QUERY (Sama seperti statistik.php)
$where_kelas = $filter_kelas ? "AND j.kode_kelas = '$filter_kelas'" : '';
$where_mk    = $filter_mk ? "AND j.kode_mk = '$filter_mk'" : '';
$where_lab   = $filter_lab ? "AND j.kode_lab = '$filter_lab'" : '';

$sql = "";
if ($view == 'kelas') {
    $where_kelas_fixed = str_replace('j.kode_kelas', 'k.kode_kelas', $where_kelas);
    $sql = "SELECT
        k.kode_kelas, k.nama_kelas,
        COUNT(DISTINCT m.nim) as jumlah_mhs,
        COUNT(DISTINCT j.id) as total_jadwal,
        SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN m.nim IS NOT NULL AND j.id IS NOT NULL AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) THEN 1 ELSE 0 END) as alpha
        FROM kelas k
        LEFT JOIN jadwal j ON j.kode_kelas = k.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date' AND j.jenis != 'inhall' $where_mk $where_lab
        LEFT JOIN mahasiswa m ON m.kode_kelas = k.kode_kelas AND (j.id IS NULL OR m.tanggal_daftar IS NULL OR m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))
        LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
        WHERE 1=1 $where_kelas_fixed
        GROUP BY k.kode_kelas, k.nama_kelas
        ORDER BY k.nama_kelas";
} elseif ($view == 'mk') {
    $sql = "SELECT
        mk.kode_mk, mk.nama_mk,
        COUNT(DISTINCT j.kode_kelas) as jumlah_kelas,
        COUNT(DISTINCT j.id) as total_jadwal,
        SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN m.nim IS NOT NULL AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) THEN 1 ELSE 0 END) as alpha
        FROM jadwal j
        JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
        LEFT JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas AND (m.tanggal_daftar IS NULL OR m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))
        LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
        WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
        AND j.jenis != 'inhall'
        $where_kelas $where_mk $where_lab
        GROUP BY mk.kode_mk, mk.nama_mk
        ORDER BY mk.nama_mk";
} else { // lab
    $sql = "SELECT
        l.kode_lab, l.nama_lab,
        COUNT(DISTINCT j.kode_kelas) as jumlah_kelas,
        COUNT(DISTINCT j.id) as total_jadwal,
        SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN m.nim IS NOT NULL AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) THEN 1 ELSE 0 END) as alpha
        FROM jadwal j
        JOIN lab l ON j.kode_lab = l.kode_lab
        LEFT JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas AND (m.tanggal_daftar IS NULL OR m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))
        LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
        WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
        AND j.jenis != 'inhall'
        $where_kelas $where_mk $where_lab
        GROUP BY l.kode_lab, l.nama_lab
        ORDER BY l.nama_lab";
}

$q_data = mysqli_query($conn, $sql);

// 4. EKSEKUSI HEADER CSV (Disini aman karena tidak ada HTML sebelumnya)
$filename = 'statistik_presensi_admin_' . $view . '_' . date('Y-m-d_His') . '.csv';

// Bersihkan buffer output jika ada
if (ob_get_length()) ob_end_clean();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM Excel

// Tulis Header Kolom
if ($view == 'kelas') {
    fputcsv($output, ['No', 'Kelas', 'Jumlah Mahasiswa', 'Total Jadwal', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Presensi', 'Persentase Kehadiran'], ';');
} elseif ($view == 'mk') {
    fputcsv($output, ['No', 'Mata Kuliah', 'Jumlah Kelas', 'Total Jadwal', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Presensi', 'Persentase Kehadiran'], ';');
} else {
    fputcsv($output, ['No', 'Lab', 'Jumlah Kelas', 'Total Jadwal', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Presensi', 'Persentase Kehadiran'], ';');
}

// Tulis Data
$no = 1;
if ($q_data) {
    while ($row = mysqli_fetch_assoc($q_data)) {
        $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
        $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
        
        $name = '';
        $col3 = '';
        if ($view == 'kelas') {
            $name = $row['nama_kelas'];
            $col3 = $row['jumlah_mhs'];
        } elseif ($view == 'mk') {
            $name = $row['nama_mk'];
            $col3 = $row['jumlah_kelas'];
        } else {
            $name = $row['nama_lab'];
            $col3 = $row['jumlah_kelas'];
        }

        fputcsv($output, [
            $no++,
            $name,
            $col3,
            $row['total_jadwal'],
            $row['hadir'],
            $row['izin'],
            $row['sakit'],
            $row['alpha'],
            $total,
            $persen . '%'
        ], ';');
    }
}

fclose($output);
exit;
?>