<?php
$page = 'asisten_statistik';
$asisten = get_asisten_login();

// Validasi data asisten
if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Data asisten tidak ditemukan. Pastikan akun Anda sudah terdaftar sebagai asisten.</div>';
    return;
}

$kode_asisten = $asisten['kode_asisten'];

// Helper clause: asisten bisa lihat jadwal sendiri ATAU jadwal yang digantikan
// - Jadwal sendiri: kode_asisten_1 atau kode_asisten_2
// - Jadwal asli yang diizinkan: dari absen_asisten.kode_asisten (asisten yang izin tetap bisa lihat)
// - Jadwal pengganti: dari absen_asisten.pengganti dengan status_approval = 'approved'
$jadwal_asisten_clause = "(
    (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE kode_asisten = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE pengganti = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
)";

// Export Excel (CSV format)
if (isset($_GET['export'])) {
    // Hentikan dan bersihkan output buffer yang mungkin sudah terisi oleh index.php
    if (ob_get_length()) ob_end_clean();
    
    $filter_bulan_exp = isset($_GET['bulan']) ? escape($_GET['bulan']) : date('Y-m');
    $filter_kelas_exp = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
    $view_exp = isset($_GET['view']) ? escape($_GET['view']) : 'kelas';

    $where_exp = $filter_kelas_exp ? "AND j.kode_kelas = '$filter_kelas_exp'" : '';
    $where_asisten_exp = "AND $jadwal_asisten_clause";

    $filename = 'statistik_presensi_' . $view_exp . '_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    // BOM untuk Excel agar UTF-8 terbaca dengan benar
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);

    // Hitung range tanggal untuk optimasi query
    $start_date = $filter_bulan_exp . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));

    // Common WHERE clause for all export queries
    $base_where_exp = "
        WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
        AND j.jenis != 'inhall'
        AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
        $where_asisten_exp
        $where_exp
    ";

    if ($view_exp == 'kelas') {
        echo "No;Kelas;Jumlah Mahasiswa;Total Jadwal;Hadir;Izin;Sakit;Alpha;Total Presensi;Persentase Kehadiran\n";
        $stat_per_kelas_exp = mysqli_query($conn, "SELECT
            k.kode_kelas, k.nama_kelas,
            COUNT(DISTINCT m.nim) as jumlah_mhs,
            COUNT(DISTINCT j.id) as total_jadwal,
            SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN p.status = 'alpha' OR ((j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha'))) THEN 1 ELSE 0 END) as alpha
            FROM jadwal j
            JOIN kelas k ON j.kode_kelas = k.kode_kelas
            JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
            LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
            $base_where_exp
            GROUP BY k.kode_kelas, k.nama_kelas
            ORDER BY k.nama_kelas");

        $no = 1;
        while ($row = mysqli_fetch_assoc($stat_per_kelas_exp)) {
            $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
            $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
            echo "$no;{$row['nama_kelas']};{$row['jumlah_mhs']};{$row['total_jadwal']};{$row['hadir']};{$row['izin']};{$row['sakit']};{$row['alpha']};$total;{$persen}%\n";
            $no++;
        }

    } elseif ($view_exp == 'mk') {
        echo "No;Mata Kuliah;Jumlah Kelas;Total Jadwal;Hadir;Izin;Sakit;Alpha;Total Presensi;Persentase Kehadiran\n";
        $stat_per_mk_exp = mysqli_query($conn, "SELECT
            mk.kode_mk, mk.nama_mk,
            COUNT(DISTINCT j.kode_kelas) as jumlah_kelas,
            COUNT(DISTINCT j.id) as total_jadwal,
            SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN p.status = 'alpha' OR ((j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha'))) THEN 1 ELSE 0 END) as alpha
            FROM jadwal j
            JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
            JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
            LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
            $base_where_exp
            GROUP BY mk.kode_mk, mk.nama_mk
            ORDER BY mk.nama_mk");
        
        $no = 1;
        while ($row = mysqli_fetch_assoc($stat_per_mk_exp)) {
            $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
            $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
            echo "$no;{$row['nama_mk']};{$row['jumlah_kelas']};{$row['total_jadwal']};{$row['hadir']};{$row['izin']};{$row['sakit']};{$row['alpha']};$total;{$persen}%\n";
            $no++;
        }

    } else { // lab
        echo "No;Lab;Jumlah Kelas;Total Jadwal;Hadir;Izin;Sakit;Alpha;Total Presensi;Persentase Kehadiran\n";
        $stat_per_lab_exp = mysqli_query($conn, "SELECT
            l.kode_lab, l.nama_lab,
            COUNT(DISTINCT j.kode_kelas) as jumlah_kelas,
            COUNT(DISTINCT j.id) as total_jadwal,
            SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN p.status = 'alpha' OR ((j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha'))) THEN 1 ELSE 0 END) as alpha
            FROM jadwal j
            JOIN lab l ON j.kode_lab = l.kode_lab
            JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
            LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
            $base_where_exp
            GROUP BY l.kode_lab, l.nama_lab
            ORDER BY l.nama_lab");

        $no = 1;
        while ($row = mysqli_fetch_assoc($stat_per_lab_exp)) {
            $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
            $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
            echo "$no;{$row['nama_lab']};{$row['jumlah_kelas']};{$row['total_jadwal']};{$row['hadir']};{$row['izin']};{$row['sakit']};{$row['alpha']};$total;{$persen}%\n";
            $no++;
        }
    }
    exit;
}

// Filter
$filter_bulan = isset($_GET['bulan']) ? escape($_GET['bulan']) : date('Y-m');
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_mk = isset($_GET['mk']) ? escape($_GET['mk']) : '';
$filter_lab = isset($_GET['lab']) ? escape($_GET['lab']) : '';
$view = isset($_GET['view']) ? escape($_GET['view']) : 'kelas'; // kelas, mk, lab

// Build WHERE conditions
$where_kelas = $filter_kelas ? "AND j.kode_kelas = '$filter_kelas'" : '';
$where_mk = $filter_mk ? "AND j.kode_mk = '$filter_mk'" : '';
$where_lab = $filter_lab ? "AND j.kode_lab = '$filter_lab'" : '';
$where_asisten = "AND $jadwal_asisten_clause";

// Ambil jadwal yang pernah diajar (termasuk jadwal yang digantikan)
$jadwal_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_kelas, k.nama_kelas 
                                       FROM jadwal j 
                                       JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                       WHERE $jadwal_asisten_clause");

// Ambil mata kuliah yang pernah diajar (termasuk jadwal yang digantikan)
$mk_list = mysqli_query($conn, "SELECT DISTINCT j.kode_mk, mk.nama_mk 
                                  FROM jadwal j 
                                  JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                  WHERE $jadwal_asisten_clause");

// Ambil lab yang pernah diajar (termasuk jadwal yang digantikan)
$lab_list = mysqli_query($conn, "SELECT DISTINCT j.kode_lab, l.nama_lab 
                                   FROM jadwal j 
                                   JOIN lab l ON j.kode_lab = l.kode_lab
                                   WHERE $jadwal_asisten_clause");

// Hitung range tanggal untuk optimasi query
$start_date = $filter_bulan . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// Common WHERE clause for all statistics queries
$base_where = "
    WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
    AND j.jenis != 'inhall'
    AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
    $where_asisten
    $where_kelas
    $where_mk
    $where_lab
";

// ============ STATISTIK PER KELAS ============
$stat_per_kelas = mysqli_query($conn, "SELECT
    k.kode_kelas, k.nama_kelas,
    COUNT(DISTINCT m.nim) as jumlah_mhs,
    COUNT(DISTINCT j.id) as total_jadwal,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN p.status = 'alpha' OR ((j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha'))) THEN 1 ELSE 0 END) as alpha
    FROM jadwal j
    JOIN kelas k ON j.kode_kelas = k.kode_kelas
    JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
    LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
    $base_where
    GROUP BY k.kode_kelas, k.nama_kelas
    ORDER BY k.nama_kelas");

// ============ STATISTIK PER MATA KULIAH ============
$stat_per_mk = mysqli_query($conn, "SELECT
    mk.kode_mk, mk.nama_mk,
    COUNT(DISTINCT j.kode_kelas) as jumlah_kelas,
    COUNT(DISTINCT j.id) as total_jadwal,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN p.status = 'alpha' OR ((j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha'))) THEN 1 ELSE 0 END) as alpha
    FROM jadwal j
    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
    JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
    LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
    $base_where
    GROUP BY mk.kode_mk, mk.nama_mk
    ORDER BY mk.nama_mk");

// ============ STATISTIK PER LAB ============
$stat_per_lab = mysqli_query($conn, "SELECT
    l.kode_lab, l.nama_lab,
    COUNT(DISTINCT j.kode_kelas) as jumlah_kelas,
    COUNT(DISTINCT j.id) as total_jadwal,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN p.status = 'alpha' OR ((j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha'))) THEN 1 ELSE 0 END) as alpha
    FROM jadwal j
    JOIN lab l ON j.kode_lab = l.kode_lab
    JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
    LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
    $base_where
    GROUP BY l.kode_lab, l.nama_lab
    ORDER BY l.nama_lab");

// ============ TOTAL KESELURUHAN ============
$total_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(DISTINCT j.id) as total_jadwal,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN p.status = 'alpha' OR ((j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME())) AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha'))) THEN 1 ELSE 0 END) as alpha
    FROM jadwal j
    JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
    LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
    $base_where"));

$total_presensi = ($total_all['hadir'] ?? 0) + ($total_all['izin'] ?? 0) + ($total_all['sakit'] ?? 0) + ($total_all['alpha'] ?? 0);
$persen_all = $total_presensi > 0 ? round(($total_all['hadir'] / $total_presensi) * 100) : 0;
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== STATISTIK PAGE STYLES ===== */
.statistik-content {
    padding: 20px;
    max-width: 1600px;
}

/* Header Banner */
.page-header-banner {
    background: var(--banner-gradient);
    border-radius: 20px;
    padding: 24px 28px;
    color: white;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.page-header-banner::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
}
.page-header-banner::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: 20%;
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, rgba(54, 185, 204, 0.2) 0%, transparent 70%);
}
.page-header-banner .header-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.page-header-banner h4 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
}
.page-header-banner .subtitle {
    margin: 4px 0 0 0;
    opacity: 0.85;
    font-size: 0.9rem;
}
.btn-print {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 500;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}
.btn-print:hover {
    background: rgba(255,255,255,0.3);
    color: #fff;
    transform: translateY(-2px);
}

/* Filter Card */
.filter-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}
.filter-card .filter-title {
    font-weight: 600;
    color: var(--text-main);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.filter-card .filter-title i {
    color: #0066cc;
}

/* Summary Stats Grid */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.summary-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.summary-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 80px;
    height: 80px;
    border-radius: 0 16px 0 80px;
    opacity: 0.15;
}
.summary-card.green::after { background: #66cc00; }
.summary-card.yellow::after { background: #ffaa00; }
.summary-card.red::after { background: #ff3333; }
.summary-card.blue::after { background: #0066cc; }

.summary-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.summary-icon.green { background: linear-gradient(135deg, #d4edda, #b8e0c4); color: #66cc00; }
.summary-icon.yellow { background: linear-gradient(135deg, #fff3cd, #ffe69c); color: #dda20a; }
.summary-icon.red { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #ff3333; }
.summary-icon.blue { background: linear-gradient(135deg, #cce5ff, #b8daff); color: #0066cc; }

.summary-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
    line-height: 1;
}
.summary-info p {
    color: var(--text-muted);
    margin: 6px 0 0 0;
    font-size: 0.85rem;
    font-weight: 500;
}

/* Tab Navigation */
.stat-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    background: var(--bg-card);
    padding: 8px;
    border-radius: 14px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    flex-wrap: wrap;
}
.stat-tab {
    padding: 12px 24px;
    border-radius: 10px;
    text-decoration: none;
    color: var(--text-main);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.25s ease;
    flex: 1;
    justify-content: center;
    min-width: 120px;
}
.stat-tab:hover {
    background: var(--bg-body);
    color: #0066cc;
}
.stat-tab.active {
    background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
}
.stat-tab i {
    font-size: 1rem;
}

/* Data Card */
.data-card {
    background: var(--bg-card);
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
}
.data-card-header {
    padding: 18px 24px;
    background: var(--bg-body);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.data-card-header h5 {
    margin: 0;
    font-weight: 600;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 10px;
}
.data-card-header h5 i {
    color: #0066cc;
}
.data-card-header .period-badge {
    background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Table Styling */
.stat-table {
    width: 100%;
    margin: 0;
}
.stat-table thead th {
    background: var(--bg-body);
    padding: 14px 16px;
    font-weight: 600;
    color: var(--text-main);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border-color);
    white-space: nowrap;
}
.stat-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
}
.stat-table tbody tr:hover {
    background: var(--bg-body);
}
.stat-table tbody tr:last-child td {
    border-bottom: none;
}

/* Stat Badge */
.stat-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.2s ease;
}
.stat-badge[data-status] {
    cursor: pointer;
}
.stat-badge[data-status]:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.stat-badge.hadir { background: #d4edda; color: #155724; }
.stat-badge.izin { background: #fff3cd; color: #856404; }
.stat-badge.sakit { background: #d1ecf1; color: #0c5460; }
.stat-badge.alpha { background: #f8d7da; color: #721c24; }

[data-theme="dark"] .stat-badge.hadir { background: rgba(40, 167, 69, 0.2); color: #75b798; }
[data-theme="dark"] .stat-badge.izin { background: rgba(255, 193, 7, 0.2); color: #ffda6a; }
[data-theme="dark"] .stat-badge.sakit { background: rgba(23, 162, 184, 0.2); color: #6edff6; }
[data-theme="dark"] .stat-badge.alpha { background: rgba(220, 53, 69, 0.2); color: #ea868f; }
/* Percentage Badge */
.persen-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
}
.persen-badge.high { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; }
.persen-badge.medium { background: linear-gradient(135deg, #fff3cd, #ffeaa7); color: #856404; }
.persen-badge.low { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}
.empty-state i {
    font-size: 4rem;
    color: var(--border-color);
    margin-bottom: 16px;
}
.empty-state h5 {
    color: var(--text-main);
    margin-bottom: 8px;
}
.empty-state p {
    font-size: 0.9rem;
}

/* Cell Name */
.cell-name {
    font-weight: 600;
    color: var(--text-main);
}
.cell-code {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 2px;
}

/* Count Badge */
.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 26px;
    padding: 0 8px;
    background: var(--bg-body);
    color: var(--text-main);
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.8rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 991px) {
    .statistik-content {
        padding: 16px;
    }
    .page-header-banner {
        padding: 20px;
        border-radius: 16px;
    }
    .page-header-banner h4 {
        font-size: 1.25rem;
    }
    .summary-card {
        padding: 16px;
    }
    .summary-icon {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
    }
    .summary-info h3 {
        font-size: 1.5rem;
    }
    .stat-table thead th,
    .stat-table tbody td {
        padding: 10px 12px;
        font-size: 0.75rem;
    }
    .stat-badge, .persen-badge, .count-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
}

@media (max-width: 768px) {
    .page-header-banner .header-content {
        flex-direction: column;
        text-align: center;
    }
    .page-header-banner .header-content > div:last-child {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .page-header-banner .header-content > div:last-child .btn {
        width: 100%;
    }
    .btn-print {
        width: 100%;
    }
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .summary-card {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    .stat-tabs {
        padding: 6px;
        gap: 6px;
    }
    .stat-tab {
        padding: 10px 12px;
        font-size: 0.85rem;
        min-width: auto;
    }
    .stat-tab span {
        display: none;
    }
    .data-card-header {
        padding: 14px 16px;
        flex-direction: column;
        text-align: center;
    }
    /* Horizontal scroll table */
    .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .stat-table {
        min-width: 650px;
    }
}

@media (max-width: 576px) {
    .statistik-content {
        padding: 12px;
    }
    .page-header-banner {
        padding: 16px;
        border-radius: 14px;
        margin-bottom: 16px;
    }
    .page-header-banner h4 {
        font-size: 1.1rem;
    }
    .page-header-banner .subtitle {
        font-size: 0.8rem;
    }
    .filter-card {
        padding: 14px;
        border-radius: 12px;
    }
    .summary-grid {
        gap: 10px;
        margin-bottom: 16px;
    }
    .summary-card {
        padding: 14px;
        border-radius: 12px;
    }
    .summary-icon {
        width: 42px;
        height: 42px;
        font-size: 1rem;
        border-radius: 10px;
    }
    .summary-info h3 {
        font-size: 1.3rem;
    }
    .summary-info p {
        font-size: 0.75rem;
    }
    .stat-tabs {
        border-radius: 12px;
    }
    .stat-tab {
        padding: 10px 10px;
        border-radius: 8px;
    }
    .data-card {
        border-radius: 12px;
    }
}

/* Print Only Style */
.print-only {
    display: none;
}

/* ==================== DARK MODE SUPPORT ==================== */
[data-theme="dark"] .page-header-banner {
    background: var(--banner-gradient);
}
[data-theme="dark"] .page-header-banner .btn-print {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    color: #fff;
}
[data-theme="dark"] .page-header-banner .btn-print:hover {
    background: rgba(255,255,255,0.2);
}

[data-theme="dark"] .filter-card,
[data-theme="dark"] .summary-card,
[data-theme="dark"] .stat-tabs,
[data-theme="dark"] .data-card {
    background-color: var(--bg-card);
    border-color: var(--border-color);
    box-shadow: none;
}

[data-theme="dark"] .filter-card .filter-title,
[data-theme="dark"] .data-card-header h5,
[data-theme="dark"] .summary-info h3,
[data-theme="dark"] .cell-name {
    color: var(--text-main);
}

[data-theme="dark"] .filter-card .filter-title i,
[data-theme="dark"] .data-card-header h5 i {
    color: #66b0ff;
}

[data-theme="dark"] .summary-icon {
    background: rgba(255,255,255,0.1) !important;
}
[data-theme="dark"] .summary-icon.green { color: #85e085; }
[data-theme="dark"] .summary-icon.yellow { color: #ffcc00; }
[data-theme="dark"] .summary-icon.red { color: #ff8080; }
[data-theme="dark"] .summary-icon.blue { color: #66b0ff; }

[data-theme="dark"] .stat-tab {
    color: var(--text-main);
}
[data-theme="dark"] .stat-tab:hover {
    background-color: rgba(255,255,255,0.1);
    color: #66b0ff;
}
[data-theme="dark"] .stat-tab.active {
    background: linear-gradient(135deg, #3a8fd9 0%, #2c7bc0 100%);
    color: #fff;
}

[data-theme="dark"] .data-card-header {
    background: var(--bg-body);
    border-color: var(--border-color);
}
[data-theme="dark"] .data-card-header .period-badge {
    background: linear-gradient(135deg, #3a8fd9 0%, #2c7bc0 100%);
}

[data-theme="dark"] .stat-badge.hadir { background: rgba(40, 167, 69, 0.2); color: #75b798; }
[data-theme="dark"] .stat-badge.izin { background: rgba(255, 193, 7, 0.2); color: #ffda6a; }
[data-theme="dark"] .stat-badge.sakit { background: rgba(23, 162, 184, 0.2); color: #6edff6; }
[data-theme="dark"] .stat-badge.alpha { background: rgba(220, 53, 69, 0.2); color: #ea868f; }

[data-theme="dark"] .persen-badge.high { background: rgba(40, 167, 69, 0.2); color: #75b798; }
[data-theme="dark"] .persen-badge.medium { background: rgba(255, 193, 7, 0.2); color: #ffda6a; }
[data-theme="dark"] .persen-badge.low { background: rgba(220, 53, 69, 0.2); color: #ea868f; }

[data-theme="dark"] .count-badge {
    background-color: rgba(255,255,255,0.1);
    color: var(--text-main);
}

[data-theme="dark"] .empty-state i {
    color: var(--border-color);
}

[data-theme="dark"] .modal-header {
    background: linear-gradient(135deg, #1a2a6c, #2c5364) !important;
    border-bottom: 1px solid var(--border-color) !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="statistik-content">
                <!-- Header Banner -->
                <div class="page-header-banner no-print">
                    <div class="header-content">
                        <div>
                            <h4><i class="fas fa-chart-pie me-2"></i>Statistik Presensi</h4>
                            <p class="subtitle">Analisis data kehadiran kelas yang Anda ajar</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="index.php?page=asisten_statistik&export=1&view=<?= $view ?>&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </a>
                            <button class="btn btn-danger" onclick="exportPDF()">
                                <i class="fas fa-file-pdf me-2"></i>Export PDF
                            </button>
                        </div>
                    </div>
                </div>
                    
                <!-- Filter Card -->
                <div class="filter-card no-print">
                    <div class="filter-title">
                        <i class="fas fa-sliders-h"></i>
                        Filter Data
                    </div>
                    <form method="GET">
                        <input type="hidden" name="page" value="asisten_statistik">
                        <input type="hidden" name="view" value="<?= $view ?>">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-muted">Periode Bulan</label>
                                <input type="month" name="bulan" class="form-control" value="<?= $filter_bulan ?>">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-muted">Kelas</label>
                                <select name="kelas" class="form-select">
                                    <option value="">Semua Kelas</option>
                                    <?php 
                                    mysqli_data_seek($jadwal_diajar, 0);
                                    while ($k = mysqli_fetch_assoc($jadwal_diajar)): ?>
                                        <option value="<?= $k['kode_kelas'] ?>" <?= $filter_kelas == $k['kode_kelas'] ? 'selected' : '' ?>>
                                            <?= $k['nama_kelas'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-semibold text-muted">Mata Kuliah</label>
                                <select name="mk" class="form-select">
                                    <option value="">Semua</option>
                                    <?php while ($m = mysqli_fetch_assoc($mk_list)): ?>
                                        <option value="<?= $m['kode_mk'] ?>" <?= $filter_mk == $m['kode_mk'] ? 'selected' : '' ?>>
                                            <?= $m['nama_mk'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-semibold text-muted">Lab</label>
                                <select name="lab" class="form-select">
                                    <option value="">Semua</option>
                                    <?php while ($l = mysqli_fetch_assoc($lab_list)): ?>
                                        <option value="<?= $l['kode_lab'] ?>" <?= $filter_lab == $l['kode_lab'] ? 'selected' : '' ?>>
                                            <?= $l['nama_lab'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Stats -->
                <div class="summary-grid">
                    <div class="summary-card green">
                        <div class="summary-icon green">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="summary-info">
                            <h3><?= number_format($total_all['hadir'] ?? 0) ?></h3>
                            <p>Total Hadir</p>
                        </div>
                    </div>
                    <div class="summary-card yellow">
                        <div class="summary-icon yellow">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="summary-info">
                            <h3><?= number_format(($total_all['izin'] ?? 0) + ($total_all['sakit'] ?? 0)) ?></h3>
                            <p>Izin / Sakit</p>
                        </div>
                    </div>
                    <div class="summary-card red">
                        <div class="summary-icon red">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="summary-info">
                            <h3><?= number_format($total_all['alpha'] ?? 0) ?></h3>
                            <p>Total Alpha</p>
                        </div>
                    </div>
                    <div class="summary-card blue">
                        <div class="summary-icon blue">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="summary-info">
                            <h3><?= $persen_all ?>%</h3>
                            <p>Kehadiran</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Navigation -->
                <div class="stat-tabs no-print">
                    <a class="stat-tab <?= $view == 'kelas' ? 'active' : '' ?>" 
                       href="index.php?page=asisten_statistik&view=kelas&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>">
                        <i class="fas fa-users"></i>
                        <span>Per Kelas</span>
                    </a>
                    <a class="stat-tab <?= $view == 'mk' ? 'active' : '' ?>" 
                       href="index.php?page=asisten_statistik&view=mk&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>">
                        <i class="fas fa-book"></i>
                        <span>Per Mata Kuliah</span>
                    </a>
                    <a class="stat-tab <?= $view == 'lab' ? 'active' : '' ?>" 
                       href="index.php?page=asisten_statistik&view=lab&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>">
                        <i class="fas fa-desktop"></i>
                        <span>Per Lab</span>
                    </a>
                </div>
                <div class="data-card">
                    <div class="data-card-header">
                        <h5>
                            <?php if ($view == 'kelas'): ?>
                                <i class="fas fa-users"></i>Statistik Per Kelas
                            <?php elseif ($view == 'mk'): ?>
                                <i class="fas fa-book"></i>Statistik Per Mata Kuliah
                            <?php else: ?>
                                <i class="fas fa-desktop"></i>Statistik Per Lab
                            <?php endif; ?>
                        </h5>
                        <span class="period-badge">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= date('F Y', strtotime($filter_bulan . '-01')) ?>
                        </span>
                    </div>
                    
                    <div class="table-responsive-wrapper">
                        <table class="stat-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px" class="text-center">No</th>
                                    <?php if ($view == 'kelas'): ?>
                                        <th>Kelas</th>
                                        <th class="text-center">Mhs</th>
                                    <?php elseif ($view == 'mk'): ?>
                                        <th>Mata Kuliah</th>
                                        <th class="text-center">Kelas</th>
                                    <?php else: ?>
                                        <th>Lab</th>
                                        <th class="text-center">Kelas</th>
                                    <?php endif; ?>
                                    <th class="text-center">Jadwal</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Izin</th>
                                    <th class="text-center">Sakit</th>
                                    <th class="text-center">Alpha</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $has_data = false;
                                
                                if ($view == 'kelas' && mysqli_num_rows($stat_per_kelas) > 0):
                                    while ($row = mysqli_fetch_assoc($stat_per_kelas)):
                                        $has_data = true;
                                        $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
                                        $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
                                        $persen_class = $persen >= 75 ? 'high' : ($persen >= 50 ? 'medium' : 'low');
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= $row['nama_kelas'] ?></td>
                                        <td class="text-center"><span class="count-badge"><?= $row['jumlah_mhs'] ?></span></td>
                                        <td class="text-center"><span class="count-badge"><?= $row['total_jadwal'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge hadir" data-status="hadir" data-kelas="<?= $row['kode_kelas'] ?>" role="button"><?= $row['hadir'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge izin" data-status="izin" data-kelas="<?= $row['kode_kelas'] ?>" role="button"><?= $row['izin'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge sakit" data-status="sakit" data-kelas="<?= $row['kode_kelas'] ?>" role="button"><?= $row['sakit'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge alpha" data-status="alpha" data-kelas="<?= $row['kode_kelas'] ?>" role="button"><?= $row['alpha'] ?></span></td>
                                        <td class="text-center"><strong><?= $total ?></strong></td>
                                        <td class="text-center">
                                            <span class="persen-badge <?= $persen_class ?>">
                                                <?php if ($persen >= 75): ?>
                                                    <i class="fas fa-arrow-up"></i>
                                                <?php elseif ($persen < 50): ?>
                                                    <i class="fas fa-arrow-down"></i>
                                                <?php endif; ?>
                                                <?= $persen ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                elseif ($view == 'mk' && mysqli_num_rows($stat_per_mk) > 0):
                                    while ($row = mysqli_fetch_assoc($stat_per_mk)):
                                        $has_data = true;
                                        $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
                                        $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
                                        $persen_class = $persen >= 75 ? 'high' : ($persen >= 50 ? 'medium' : 'low');
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= $row['nama_mk'] ?></td>
                                        <td class="text-center"><span class="count-badge"><?= $row['jumlah_kelas'] ?></span></td>
                                        <td class="text-center"><span class="count-badge"><?= $row['total_jadwal'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge hadir" data-status="hadir" data-mk="<?= $row['kode_mk'] ?>" role="button"><?= $row['hadir'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge izin" data-status="izin" data-mk="<?= $row['kode_mk'] ?>" role="button"><?= $row['izin'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge sakit" data-status="sakit" data-mk="<?= $row['kode_mk'] ?>" role="button"><?= $row['sakit'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge alpha" data-status="alpha" data-mk="<?= $row['kode_mk'] ?>" role="button"><?= $row['alpha'] ?></span></td>
                                        <td class="text-center"><strong><?= $total ?></strong></td>
                                        <td class="text-center">
                                            <span class="persen-badge <?= $persen_class ?>">
                                                <?php if ($persen >= 75): ?>
                                                    <i class="fas fa-arrow-up"></i>
                                                <?php elseif ($persen < 50): ?>
                                                    <i class="fas fa-arrow-down"></i>
                                                <?php endif; ?>
                                                <?= $persen ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                elseif ($view == 'lab' && mysqli_num_rows($stat_per_lab) > 0):
                                    while ($row = mysqli_fetch_assoc($stat_per_lab)):
                                        $has_data = true;
                                        $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
                                        $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
                                        $persen_class = $persen >= 75 ? 'high' : ($persen >= 50 ? 'medium' : 'low');
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= $row['nama_lab'] ?></td>
                                        <td class="text-center"><span class="count-badge"><?= $row['jumlah_kelas'] ?></span></td>
                                        <td class="text-center"><span class="count-badge"><?= $row['total_jadwal'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge hadir" data-status="hadir" data-lab="<?= $row['kode_lab'] ?>" role="button"><?= $row['hadir'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge izin" data-status="izin" data-lab="<?= $row['kode_lab'] ?>" role="button"><?= $row['izin'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge sakit" data-status="sakit" data-lab="<?= $row['kode_lab'] ?>" role="button"><?= $row['sakit'] ?></span></td>
                                        <td class="text-center"><span class="stat-badge alpha" data-status="alpha" data-lab="<?= $row['kode_lab'] ?>" role="button"><?= $row['alpha'] ?></span></td>
                                        <td class="text-center"><strong><?= $total ?></strong></td>
                                        <td class="text-center">
                                            <span class="persen-badge <?= $persen_class ?>">
                                                <?php if ($persen >= 75): ?>
                                                    <i class="fas fa-arrow-up"></i>
                                                <?php elseif ($persen < 50): ?>
                                                    <i class="fas fa-arrow-down"></i>
                                                <?php endif; ?>
                                                <?= $persen ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                endif;
                                
                                if (!$has_data):
                                ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="empty-state">
                                                <i class="fas fa-chart-bar"></i>
                                                <h5>Tidak Ada Data</h5>
                                                <p>Belum ada data presensi untuk periode <?= date('F Y', strtotime($filter_bulan . '-01')) ?></p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Print Only Content for PDF -->
<div class="print-only">
    <div class="text-center mb-4">
        <h3>Laporan Statistik Presensi</h3>
        <p class="mb-1">Periode: <?= date('F Y', strtotime($filter_bulan . '-01')) ?></p>
        <p>Kategori: <?= ucfirst($view) ?> <?= $filter_kelas ? '| Kelas: ' . $filter_kelas : '' ?></p>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width: 5%" class="text-center">No</th>
                <?php if ($view == 'kelas'): ?>
                    <th style="width: 25%">Kelas</th>
                    <th style="width: 10%" class="text-center">Mhs</th>
                <?php elseif ($view == 'mk'): ?>
                    <th style="width: 25%">Mata Kuliah</th>
                    <th style="width: 10%" class="text-center">Kelas</th>
                <?php else: ?>
                    <th style="width: 25%">Lab</th>
                    <th style="width: 10%" class="text-center">Kelas</th>
                <?php endif; ?>
                <th style="width: 10%" class="text-center">Jadwal</th>
                <th style="width: 8%" class="text-center">Hadir</th>
                <th style="width: 8%" class="text-center">Izin</th>
                <th style="width: 8%" class="text-center">Sakit</th>
                <th style="width: 8%" class="text-center">Alpha</th>
                <th style="width: 8%" class="text-center">Total</th>
                <th style="width: 10%" class="text-center">%</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $result_set = null;
            
            if ($view == 'kelas') $result_set = $stat_per_kelas;
            elseif ($view == 'mk') $result_set = $stat_per_mk;
            elseif ($view == 'lab') $result_set = $stat_per_lab;
            
            if ($result_set && mysqli_num_rows($result_set) > 0) {
                mysqli_data_seek($result_set, 0); // Reset pointer agar bisa di-loop ulang
                while ($row = mysqli_fetch_assoc($result_set)) {
                    $total = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
                    $persen = $total > 0 ? round(($row['hadir'] / $total) * 100) : 0;
                    
                    $name = '';
                    $count_val = 0;
                    
                    if ($view == 'kelas') {
                        $name = $row['nama_kelas'];
                        $count_val = $row['jumlah_mhs'];
                    } elseif ($view == 'mk') {
                        $name = $row['nama_mk'];
                        $count_val = $row['jumlah_kelas'];
                    } else {
                        $name = $row['nama_lab'];
                        $count_val = $row['jumlah_kelas'];
                    }
            ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $name ?></td>
                    <td class="text-center"><?= $count_val ?></td>
                    <td class="text-center"><?= $row['total_jadwal'] ?></td>
                    <td class="text-center"><?= $row['hadir'] ?></td>
                    <td class="text-center"><?= $row['izin'] ?></td>
                    <td class="text-center"><?= $row['sakit'] ?></td>
                    <td class="text-center"><?= $row['alpha'] ?></td>
                    <td class="text-center"><?= $total ?></td>
                    <td class="text-center"><?= $persen ?>%</td>
                </tr>
            <?php 
                }
            } else {
                echo '<tr><td colspan="10" class="text-center">Tidak ada data</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Library html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    const originalElement = document.querySelector('.print-only');
    const elementToPrint = originalElement.cloneNode(true);
    
    // Hapus class 'print-only' agar tidak terpengaruh style display:none
    elementToPrint.classList.remove('print-only');
    
    // Atur style agar terlihat dan terbaca jelas di PDF (mengatasi isu Dark Mode)
    elementToPrint.style.display = 'block';
    elementToPrint.style.backgroundColor = '#ffffff';
    elementToPrint.style.color = '#000000';
    elementToPrint.style.padding = '20px';
    
    // Paksa warna teks hitam untuk semua elemen di dalamnya
    elementToPrint.querySelectorAll('*').forEach(el => {
        el.style.color = '#000000';
    });
    
    // Style header tabel secara spesifik agar lebih kontras
    const tableHeader = elementToPrint.querySelector('thead');
    if (tableHeader) {
        tableHeader.style.backgroundColor = '#0066cc'; // Warna biru primer
        tableHeader.querySelectorAll('th').forEach(th => {
            th.style.color = '#ffffff';
        });
    }
    
    // Gunakan wrapper untuk menyembunyikan dari view user tapi tetap renderable
    const wrapper = document.createElement('div');
    wrapper.style.position = 'fixed';
    wrapper.style.left = '-10000px';
    wrapper.style.top = '0';
    wrapper.style.width = '1100px'; // Lebar A4 Landscape
    wrapper.appendChild(elementToPrint);
    
    document.body.appendChild(wrapper);
    
    const opt = {
        margin:       10,
        filename:     'statistik_presensi_<?= date("Y-m-d_His") ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    // Generate PDF dari elemen clone, lalu hapus elemen tersebut setelah selesai
    html2pdf().set(opt).from(elementToPrint).save().then(function() {
        document.body.removeChild(wrapper);
    });
}
</script>

<!-- Modal Detail Mahasiswa -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%); border: none;">
                <h5 class="modal-title" id="modalTitle" style="color: #fff; font-weight: 600 !important;">
                    <i class="fas fa-users me-2"></i>Detail Mahasiswa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status" style="color: #0066cc;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3 small">Memuat data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles for Detail Mahasiswa (general) */
.detail-table { width: 100%; margin-bottom: 0; }
.detail-table th, .detail-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color); }
.detail-table th { width: 30%; font-weight: 600; color: var(--text-main); }
.detail-table td { color: var(--text-muted); }
.detail-table tbody tr:last-child th,
.detail-table tbody tr:last-child td { border-bottom: none; }

</style>
<?php include 'includes/footer.php'; ?>

<script>
// Function to show detail mahasiswa modal
function showDetailMahasiswa(status, kode_kelas = '', kode_mk = '', kode_lab = '') {
    const bulan = document.querySelector('input[name="bulan"]').value;
    
    // Build query string
    let queryString = `bulan=${bulan}&status=${status}`;
    if (kode_kelas) queryString += `&kelas=${kode_kelas}`;
    if (kode_mk) queryString += `&mk=${kode_mk}`;
    if (kode_lab) queryString += `&lab=${kode_lab}`;
    
    // Fetch detail data dari API asisten (filtered by asisten yang login)
    fetch(`api/get_detail_statistik_asisten.php?${queryString}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('modalContent').innerHTML = data;
            
            // Update modal title
            const statusLabel = {
                'hadir': 'Hadir',
                'izin': 'Izin',
                'sakit': 'Sakit',
                'alpha': 'Alpha',
                'belum': 'Belum Presensi'
            };
            document.getElementById('modalTitle').textContent = `Daftar Mahasiswa - ${statusLabel[status] || status}`;
            
            // Show modal
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        })
        .catch(error => {
            document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Error loading data</div>';
            console.error('Error:', error);
        });
}

// Make stat badges clickable
document.addEventListener('DOMContentLoaded', function() {
    // Attach click handlers to stat badges
    const badges = document.querySelectorAll('[data-status]');
    badges.forEach(badge => {
        badge.style.cursor = 'pointer';
        badge.addEventListener('click', function() {
            const status = this.getAttribute('data-status');
            const kelas = this.getAttribute('data-kelas') || '';
            const mk = this.getAttribute('data-mk') || '';
            const lab = this.getAttribute('data-lab') || '';
            showDetailMahasiswa(status, kelas, mk, lab);
        });
    });
});
</script>
