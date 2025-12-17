<?php
$page = 'admin_statistik';

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

// Hitung range tanggal untuk optimasi query (menggantikan DATE_FORMAT)
$start_date = $filter_bulan . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// ============ STATISTIK (QUERIES REBUILT FOR ACCURACY) ============
// Basis perhitungan adalah setiap mahasiswa yang terdaftar di setiap jadwal yang sudah lewat
// Ini memastikan 'alpha' dihitung dengan benar dari pasangan (mahasiswa, jadwal) yang tidak memiliki rekor presensi.

// ============ STATISTIK PER KELAS ============
$stat_per_kelas = mysqli_query($conn, "SELECT
    k.kode_kelas, k.nama_kelas,
    COUNT(DISTINCT m.nim) as jumlah_mhs,
    COUNT(DISTINCT j.id) as total_jadwal,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit') THEN 1 ELSE 0 END) as alpha
FROM jadwal j
JOIN kelas k ON j.kode_kelas = k.kode_kelas
JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
  AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))
  AND j.jenis != 'inhall'
  AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
  $where_kelas $where_mk $where_lab
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
    SUM(CASE WHEN p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit') THEN 1 ELSE 0 END) as alpha
FROM jadwal j
JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
  AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))
  AND j.jenis != 'inhall'
  AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
  $where_kelas $where_mk $where_lab
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
    SUM(CASE WHEN p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit') THEN 1 ELSE 0 END) as alpha
FROM jadwal j
JOIN lab l ON j.kode_lab = l.kode_lab
JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
  AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))
  AND j.jenis != 'inhall'
  AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
  $where_kelas $where_mk $where_lab
GROUP BY l.kode_lab, l.nama_lab
ORDER BY l.nama_lab");

// ============ TOTAL KESELURUHAN ============
$total_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(DISTINCT j.id) as total_jadwal,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit') THEN 1 ELSE 0 END) as alpha
FROM jadwal j
JOIN mahasiswa m ON m.kode_kelas = j.kode_kelas
LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
  AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))
  AND j.jenis != 'inhall'
  AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
  $where_kelas $where_mk $where_lab"));

$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY kode_kelas");
$mk_list = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk");
$lab_list = mysqli_query($conn, "SELECT * FROM lab ORDER BY kode_lab");

// ============ FUNCTION: GET DETAIL MAHASISWA PER STATUS ============
function get_detail_mahasiswa($conn, $status, $filter_kelas = '', $filter_mk = '', $filter_lab = '', $filter_bulan = '') {
    $where_kelas = $filter_kelas ? "AND j.kode_kelas = '$filter_kelas'" : '';
    $where_mk = $filter_mk ? "AND j.kode_mk = '$filter_mk'" : '';
    $where_lab = $filter_lab ? "AND j.kode_lab = '$filter_lab'" : '';

    $start_date = $filter_bulan . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));

    // Base query starts from jadwal and mahasiswa to correctly identify all attendance events
    $base_from = "
        FROM jadwal j
        JOIN mahasiswa m ON j.kode_kelas = m.kode_kelas
        JOIN kelas k ON j.kode_kelas = k.kode_kelas
        JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
        LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
    ";

    // For this detail view, we only care about past schedules where status is determined
    $base_where = "
        WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
          AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))
          AND j.jenis != 'inhall'
          AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
          $where_kelas $where_mk $where_lab
    ";

    // Dynamic condition based on the requested status
    if ($status == 'alpha') {
        $status_condition = "AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit'))";
    } else {
        $status_condition = "AND p.status = '$status'";
    }

    $query = "SELECT DISTINCT m.nim, m.nama, k.nama_kelas, mk.nama_mk, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, 
                     IF(p.status IS NULL, 'alpha', p.status) as status
              $base_from
              $base_where
              $status_condition
              ORDER BY m.nama, j.tanggal";
    
    return mysqli_query($conn, $query);
}

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
    background: linear-gradient(90deg, #0066cc, #0099ff, #16a1fdff);
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

/* Print Styles */
@media print {
    .sidebar, .no-print, .filter-card, .stat-tabs { 
        display: none !important; 
    }
    .col-md-9, .col-lg-10 { 
        width: 100% !important; 
        margin: 0 !important; 
        padding: 0 !important;
    }
    .statistik-content {
        padding: 0;
    }
    .page-header-banner {
        background: #0066cc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        margin-bottom: 20px;
    }
    .summary-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    .summary-card {
        box-shadow: none;
        border: 1px solid #ddd;
        flex-direction: row;
    }
    .data-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    .stat-table thead th {
        background: #f0f0f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Dark Mode Specific Styles for Statistik */
[data-theme="dark"] .page-header-banner {
    background: var(--banner-gradient);
}
[data-theme="dark"] .summary-icon {
    background: rgba(255,255,255,0.1) !important;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1);
}
[data-theme="dark"] .summary-icon.green { color: #66cc00; }
[data-theme="dark"] .summary-icon.yellow { color: #ffaa00; }
[data-theme="dark"] .summary-icon.red { color: #ff3333; }
[data-theme="dark"] .summary-icon.blue { color: #0066cc; }

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
[data-theme="dark"] .stat-table tbody tr:hover {
    background-color: rgba(255,255,255,0.05);
}
[data-theme="dark"] .stat-tab:hover {
    background-color: rgba(255,255,255,0.1);
}

[data-theme="dark"] .modal-header {
    background: linear-gradient(135deg, #1a2a6c, #2c5364) !important;
    border-bottom: 1px solid var(--border-color) !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_admin.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="statistik-content">
                
                <!-- Header Banner -->
                <div class="page-header-banner no-print">
                    <div class="header-content">
                        <div>
                            <h4><i class="fas fa-chart-pie me-2"></i>Statistik Presensi</h4>
                            <p class="subtitle">Analisis data kehadiran per kelas, mata kuliah, dan lab</p>
                        </div>
                        <button class="btn btn-print" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Cetak Laporan
                        </button>
                    </div>
                </div>
                
                <!-- Filter Card -->
                <div class="filter-card no-print">
                    <div class="filter-title">
                        <i class="fas fa-sliders-h"></i>
                        Filter Data
                    </div>
                    <form method="GET">
                        <input type="hidden" name="page" value="admin_statistik">
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
                                    <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
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
                                    <i class="fas fa-search me-1"></i>Terapkan
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
                       href="index.php?page=admin_statistik&view=kelas&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>&mk=<?= $filter_mk ?>&lab=<?= $filter_lab ?>">
                        <i class="fas fa-users"></i>
                        <span>Per Kelas</span>
                    </a>
                    <a class="stat-tab <?= $view == 'mk' ? 'active' : '' ?>" 
                       href="index.php?page=admin_statistik&view=mk&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>&mk=<?= $filter_mk ?>&lab=<?= $filter_lab ?>">
                        <i class="fas fa-book"></i>
                        <span>Per Mata Kuliah</span>
                    </a>
                    <a class="stat-tab <?= $view == 'lab' ? 'active' : '' ?>" 
                       href="index.php?page=admin_statistik&view=lab&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>&mk=<?= $filter_mk ?>&lab=<?= $filter_lab ?>">
                        <i class="fas fa-desktop"></i>
                        <span>Per Lab</span>
                    </a>
                </div>
                
                <!-- Data Table Card -->
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
                                
                                if ($view == 'kelas'):
                                    while ($row = mysqli_fetch_assoc($stat_per_kelas)):
                                        if ($row['total_jadwal'] == 0) continue;
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
                                elseif ($view == 'mk'):
                                    while ($row = mysqli_fetch_assoc($stat_per_mk)):
                                        if ($row['total_jadwal'] == 0) continue;
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
                                else:
                                    while ($row = mysqli_fetch_assoc($stat_per_lab)):
                                        if ($row['total_jadwal'] == 0) continue;
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

<!-- Modal Detail Mahasiswa -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%); border: none;">
                <h5 class="modal-title" id="modalTitle" style="color: #fff; font-weight: 600;">
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

/* Dark Mode Specific Styles for Detail Modal */
[data-theme="dark"] #detailModal .modal-body {
    color: var(--text-muted);
}
[data-theme="dark"] #detailModal table {
    color: var(--text-muted);
}
[data-theme="dark"] #detailModal table td,
[data-theme="dark"] #detailModal table th {
    color: var(--text-muted) !important;
    border-color: var(--border-color) !important;
}
[data-theme="dark"] #detailModal table th {
    color: var(--text-main) !important;
}
[data-theme="dark"] #detailModal table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.08) !important;
}
[data-theme="dark"] #detailModal table tbody tr:hover td,
[data-theme="dark"] #detailModal table tbody tr:hover th {
    color: var(--text-main) !important;
}
[data-theme="dark"] #detailModal .detail-jadwal {
    color: var(--text-main) !important;
}
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
    
    // Fetch detail data
    fetch(`api/get_detail_statistik.php?${queryString}`)
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
