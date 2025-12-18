<?php
$page = 'admin_laporan';

// Export Excel (CSV format)
if (isset($_GET['export'])) {
    // Hentikan dan bersihkan output buffer yang mungkin sudah terisi oleh index.php
    if (ob_get_length()) ob_end_clean();
    
    $filter_kelas_exp = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
    $filter_mk_exp = isset($_GET['mk']) ? escape($_GET['mk']) : '';
    $filter_lab_exp = isset($_GET['lab']) ? escape($_GET['lab']) : '';
    $filter_bulan_exp = isset($_GET['bulan']) ? escape($_GET['bulan']) : date('Y-m');

    // Definisikan tanggal SEBELUM digunakan
    $start_date_exp = $filter_bulan_exp . '-01';
    $end_date_exp = date('Y-m-t', strtotime($start_date_exp));

    $where_mhs_exp = ["1=1"];
    $where_jadwal_exp_arr = [];
    if ($filter_kelas_exp) $where_mhs_exp[] = "m.kode_kelas = '$filter_kelas_exp'";
    if ($filter_mk_exp) $where_jadwal_exp_arr[] = "j.kode_mk = '$filter_mk_exp'";
    if ($filter_lab_exp) $where_jadwal_exp_arr[] = "j.kode_lab = '$filter_lab_exp'";
    $where_jadwal_exp = !empty($where_jadwal_exp_arr) ? " AND " . implode(" AND ", $where_jadwal_exp_arr) : "";
    $where_mhs_sql_exp = implode(" AND ", $where_mhs_exp);

    // FIX: Filter mahasiswa yang memiliki jadwal sesuai filter MK/Lab
    if (!empty($where_jadwal_exp)) {
        $where_mhs_sql_exp .= " AND EXISTS (
            SELECT 1 FROM jadwal j_check 
            WHERE j_check.kode_kelas = m.kode_kelas 
            AND j_check.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp'
            " . str_replace('j.', 'j_check.', $where_jadwal_exp) . "
        )";
    }

    $filename = 'laporan_presensi_' . $filter_bulan_exp . '_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    // BOM untuk Excel agar UTF-8 terbaca dengan benar
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);

    // Header CSV (menggunakan semicolon)
    echo "No;NIM;Nama;Kelas;Mata Kuliah;Lab;Hadir;Izin;Sakit;Alpha;Belum;Perlu Inhall;Sudah Inhall;Total Pertemuan;Persentase Kehadiran\n";

    $rekap_export_query = "SELECT m.nim, m.nama, k.nama_kelas, GROUP_CONCAT(DISTINCT mk.nama_mk SEPARATOR ', ') as nama_mk, GROUP_CONCAT(DISTINCT l.nama_lab SEPARATOR ', ') as nama_lab,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum,
                               COUNT(CASE WHEN j.jenis != 'inhall' AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN j.id END) as total_pertemuan,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'terdaftar' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp' AND (jpi.kode_mk = j.kode_mk OR j.kode_mk IS NULL)) as perlu_inhall,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'hadir' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp' AND (jpi.kode_mk = j.kode_mk OR j.kode_mk IS NULL)) as sudah_inhall
                               FROM mahasiswa m LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas AND j.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp' $where_jadwal_exp 
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                               WHERE $where_mhs_sql_exp GROUP BY m.nim, m.nama, k.nama_kelas ORDER BY k.nama_kelas, m.nama";
    $rekap_export = mysqli_query($conn, $rekap_export_query);
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($rekap_export)) {
        $sudah_presensi = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
        $persen = $sudah_presensi > 0 ? round(($row['hadir'] / $sudah_presensi) * 100) : 0;
        
        $line = [$no++, $row['nim'], $row['nama'], $row['nama_kelas'], $row['nama_mk'] ?: '-', $row['nama_lab'] ?: '-', $row['hadir'], $row['izin'], $row['sakit'], $row['alpha'], $row['belum'], $row['perlu_inhall'], $row['sudah_inhall'], $row['total_pertemuan'], $persen . '%'];
        echo implode(';', $line) . "\n";
    }
    exit;
}

// Filter
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_mk = isset($_GET['mk']) ? escape($_GET['mk']) : '';
$filter_lab = isset($_GET['lab']) ? escape($_GET['lab']) : '';
$filter_bulan = isset($_GET['bulan']) ? escape($_GET['bulan']) : date('Y-m');

// Hitung range tanggal untuk optimasi query SEBELUM digunakan
$start_date = $filter_bulan . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$where_mhs = ["1=1"];
$where_jadwal_arr = [];
if ($filter_kelas) $where_mhs[] = "m.kode_kelas = '$filter_kelas'";
if ($filter_mk) $where_jadwal_arr[] = "j.kode_mk = '$filter_mk'";
if ($filter_lab) $where_jadwal_arr[] = "j.kode_lab = '$filter_lab'";
$where_jadwal = !empty($where_jadwal_arr) ? " AND " . implode(" AND ", $where_jadwal_arr) : "";
$where_mhs_sql = implode(" AND ", $where_mhs);

// FIX: Filter mahasiswa yang memiliki jadwal sesuai filter MK/Lab
if (!empty($where_jadwal)) {
    $where_mhs_sql .= " AND EXISTS (
        SELECT 1 FROM jadwal j_check 
        WHERE j_check.kode_kelas = m.kode_kelas 
        AND j_check.tanggal BETWEEN '$start_date' AND '$end_date'
        " . str_replace('j.', 'j_check.', $where_jadwal) . "
    )";
}

// Pagination
$per_page = 20;
$current_page = get_current_page();

// Hitung total data untuk pagination - hitung berdasarkan grouping
$count_sql = "SELECT COUNT(*) as total FROM (
              SELECT m.nim
              FROM mahasiswa m 
              LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date' $where_jadwal
              WHERE $where_mhs_sql
              GROUP BY m.nim, m.nama, m.kode_kelas, j.kode_mk, j.kode_lab
              ) as t";
$count_query = mysqli_query($conn, $count_sql);
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Data rekap dengan pagination
// Status dihitung berdasarkan waktu SEKARANG (NOW()) untuk stabilitas
// Alpha: Jadwal sudah lewat dan tidak ada presensi (hadir/izin/sakit)
// Belum: Jadwal belum selesai
// EXCLUDE jadwal inhall dari statistik (inhall bersifat opsional, tidak mempengaruhi persentase)
$rekap = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas, mk.nama_mk, l.nama_lab,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum,
                               COUNT(CASE WHEN j.jenis != 'inhall' AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN j.id END) as total_pertemuan,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'terdaftar' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date' AND '$end_date' AND (jpi.kode_mk = j.kode_mk OR j.kode_mk IS NULL)) as perlu_inhall,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'hadir' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date' AND '$end_date' AND (jpi.kode_mk = j.kode_mk OR j.kode_mk IS NULL)) as sudah_inhall
                               FROM mahasiswa m 
                               LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas 
                                   AND j.tanggal BETWEEN '$start_date' AND '$end_date'
                                   $where_jadwal
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                               WHERE $where_mhs_sql
                               GROUP BY m.nim, m.nama, k.nama_kelas, mk.nama_mk, l.nama_lab
                               ORDER BY k.nama_kelas, m.nama, mk.nama_mk
                               LIMIT $offset, $per_page");

// Query untuk cetak/PDF (tanpa pagination)
$rekap_print = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas, mk.nama_mk, l.nama_lab,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum,
                               COUNT(CASE WHEN j.jenis != 'inhall' AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN j.id END) as total_pertemuan,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'terdaftar' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date' AND '$end_date' AND (jpi.kode_mk = j.kode_mk OR j.kode_mk IS NULL)) as perlu_inhall,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'hadir' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date' AND '$end_date' AND (jpi.kode_mk = j.kode_mk OR j.kode_mk IS NULL)) as sudah_inhall
                               FROM mahasiswa m 
                               LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date' $where_jadwal
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                               WHERE $where_mhs_sql
                               GROUP BY m.nim, m.nama, k.nama_kelas, mk.nama_mk, l.nama_lab
                               ORDER BY k.nama_kelas, m.nama, mk.nama_mk");

$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY kode_kelas");
$mk_list = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk");
$lab_list = mysqli_query($conn, "SELECT * FROM lab ORDER BY kode_lab");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_admin.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4 pt-2 no-print">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Laporan Presensi</h4>
                    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
                        <a href="index.php?page=admin_laporan&export=1&bulan=<?= $filter_bulan ?>&kelas=<?= $filter_kelas ?>&mk=<?= $filter_mk ?>&lab=<?= $filter_lab ?>" class="btn btn-success">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </a>
                        <button class="btn btn-danger" onclick="exportPDF()">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </button>
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Cetak
                        </button>
                    </div>
                </div>
                
                <!-- Filter -->
                <div class="card mb-4 no-print">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 g-md-3 align-items-end">
                            <input type="hidden" name="page" value="admin_laporan">
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Bulan</label>
                                <input type="month" name="bulan" class="form-control form-control-sm" value="<?= $filter_bulan ?>">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Kelas</label>
                                <select name="kelas" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                        <option value="<?= $k['kode_kelas'] ?>" <?= $filter_kelas == $k['kode_kelas'] ? 'selected' : '' ?>>
                                            <?= $k['nama_kelas'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Mata Kuliah</label>
                                <select name="mk" class="form-select form-select-sm">
                                    <option value="">Semua MK</option>
                                    <?php while ($m = mysqli_fetch_assoc($mk_list)): ?>
                                        <option value="<?= $m['kode_mk'] ?>" <?= $filter_mk == $m['kode_mk'] ? 'selected' : '' ?>>
                                            <?= $m['nama_mk'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Lab</label>
                                <select name="lab" class="form-select form-select-sm">
                                    <option value="">Semua Lab</option>
                                    <?php while ($l = mysqli_fetch_assoc($lab_list)): ?>
                                        <option value="<?= $l['kode_lab'] ?>" <?= $filter_lab == $l['kode_lab'] ? 'selected' : '' ?>>
                                            <?= $l['nama_lab'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Link ke Statistik -->
                <div class="alert alert-info mb-4 no-print">
                    <i class="fas fa-chart-pie me-2"></i>
                    Lihat statistik per kelas, mata kuliah, dan lab di halaman 
                    <a href="index.php?page=admin_statistik&bulan=<?= $filter_bulan ?>" class="alert-link">Statistik Presensi</a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <strong>Rekap Presensi Per Mahasiswa - <?= date('F Y', strtotime($filter_bulan . '-01')) ?></strong>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block no-print">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Mata Kuliah</th>
                                        <th>Lab</th>
                                        <th class="text-center text-success">Hadir</th>
                                        <th class="text-center text-warning">Izin</th>
                                        <th class="text-center text-info">Sakit</th>
                                        <th class="text-center text-danger">Alpha</th>
                                        <th class="text-center text-secondary">Belum</th>
                                        <th class="text-center text-purple">Inhall</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($rekap, 0);
                                    $no = 1; 
                                    while ($r = mysqli_fetch_assoc($rekap)): ?>
                                        <?php 
                                        // Persentase dihitung dari yang sudah ada status (bukan belum presensi)
                                        $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                        $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
                                        $total_inhall = $r['perlu_inhall'] + $r['sudah_inhall'];
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $r['nim'] ?></td>
                                            <td><?= $r['nama'] ?></td>
                                            <td><span class="badge bg-primary"><?= $r['nama_kelas'] ?></span></td>
                                                <td><?= $r['nama_mk'] ?: '-' ?></td>
                                                <td><?= $r['nama_lab'] ?: '-' ?></td>
                                            <td class="text-center"><?= $r['hadir'] ?></td>
                                            <td class="text-center"><?= $r['izin'] ?></td>
                                            <td class="text-center"><?= $r['sakit'] ?></td>
                                            <td class="text-center"><?= $r['alpha'] ?></td>
                                            <td class="text-center">
                                                <?php if ($r['belum'] > 0): ?>
                                                    <span class="badge bg-secondary"><?= $r['belum'] ?></span>
                                                <?php else: ?>
                                                    0
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($total_inhall > 0): ?>
                                                    <span class="badge <?= $r['perlu_inhall'] > 0 ? 'bg-warning' : 'bg-success' ?>" 
                                                          title="<?= $r['sudah_inhall'] ?> sudah diganti, <?= $r['perlu_inhall'] ?> belum">
                                                        <?= $r['sudah_inhall'] ?>/<?= $total_inhall ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>">
                                                    <?= $persen ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile/Tablet Cards -->
                        <div class="d-lg-none no-print">
                            <?php 
                            mysqli_data_seek($rekap, 0);
                            while ($r = mysqli_fetch_assoc($rekap)): 
                                $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
                                $total_inhall = $r['perlu_inhall'] + $r['sudah_inhall'];
                            ?>
                                <div class="card mb-2 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1"><?= $r['nama'] ?></h6>
                                                <div class="small text-muted">
                                                    <span class="badge bg-secondary"><?= $r['nim'] ?></span>
                                                    <span class="badge bg-primary ms-1"><?= $r['nama_kelas'] ?></span>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    <i class="fas fa-book me-1"></i> <?= $r['nama_mk'] ?: '-' ?>
                                                    <i class="fas fa-flask ms-2 me-1"></i> <?= $r['nama_lab'] ?: '-' ?>
                                                </div>
                                            </div>
                                            <span class="badge <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>" style="font-size: 0.9rem;">
                                                <?= $persen ?>%
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between small flex-wrap gap-1">
                                            <span class="text-success"><i class="fas fa-check me-1"></i>H: <?= $r['hadir'] ?></span>
                                            <span class="text-warning"><i class="fas fa-clock me-1"></i>I: <?= $r['izin'] ?></span>
                                            <span class="text-info"><i class="fas fa-medkit me-1"></i>S: <?= $r['sakit'] ?></span>
                                            <span class="text-danger"><i class="fas fa-times me-1"></i>A: <?= $r['alpha'] ?></span>
                                            <?php if ($r['belum'] > 0): ?>
                                                <span class="text-secondary"><i class="fas fa-question me-1"></i>B: <?= $r['belum'] ?></span>
                                            <?php endif; ?>
                                            <?php if ($total_inhall > 0): ?>
                                                <span class="text-purple"><i class="fas fa-redo me-1"></i>Inhall: <?= $r['sudah_inhall'] ?>/<?= $total_inhall ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2 no-print">
                            <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                            <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_laporan', ['kelas' => $filter_kelas, 'mk' => $filter_mk, 'lab' => $filter_lab, 'bulan' => $filter_bulan]) ?>
                        </div>

                        <!-- ==================== FOR PRINT/PDF VIEW (ALL DATA) ==================== -->
                        <div class="print-only">
                            <h4 class="mb-3 text-center">Laporan Presensi Mahasiswa</h4>
                            <p class="text-center text-muted">Periode: <?= date('F Y', strtotime($filter_bulan . '-01')) ?></p>
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Mata Kuliah</th>
                                        <th>Lab</th>
                                        <th class="text-center">H</th>
                                        <th class="text-center">I</th>
                                        <th class="text-center">S</th>
                                        <th class="text-center">A</th>
                                        <th class="text-center">Inhall</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($rekap_print, 0);
                                    $no_print = 1; 
                                    while ($r_print = mysqli_fetch_assoc($rekap_print)): 
                                        $sudah_presensi_print = $r_print['hadir'] + $r_print['izin'] + $r_print['sakit'] + $r_print['alpha'];
                                        $persen_print = $sudah_presensi_print > 0 ? round(($r_print['hadir'] / $sudah_presensi_print) * 100) : 0;
                                        $total_inhall_print = $r_print['perlu_inhall'] + $r_print['sudah_inhall'];
                                    ?>
                                        <tr>
                                            <td><?= $no_print++ ?></td>
                                            <td><?= $r_print['nim'] ?></td>
                                            <td><?= $r_print['nama'] ?></td>
                                            <td><?= $r_print['nama_kelas'] ?></td>
                                            <td><?= $r_print['nama_mk'] ?: '-' ?></td>
                                            <td><?= $r_print['nama_lab'] ?: '-' ?></td>
                                            <td class="text-center"><?= $r_print['hadir'] ?></td>
                                            <td class="text-center"><?= $r_print['izin'] ?></td>
                                            <td class="text-center"><?= $r_print['sakit'] ?></td>
                                            <td class="text-center"><?= $r_print['alpha'] ?></td>
                                            <td class="text-center">
                                                <?php if ($total_inhall_print > 0): ?>
                                                    <?= $r_print['sudah_inhall'] ?>/<?= $total_inhall_print ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= $persen_print ?>%</td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.print-only {
    display: none;
}
@media print {
    .sidebar, .no-print, #mobileHeader { 
        display: none !important; 
    }
    .print-only {
        display: block !important;
    }
    .col-md-3.col-lg-2.px-0 {
        display: none !important;
    }
    .col-md-9.col-lg-10 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .content-wrapper {
        padding: 0 !important;
    }
    body {
        background: #fff !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
    .table thead.table-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000 !important;
    }
    a {
        text-decoration: none;
        color: inherit;
    }
}

[data-theme="dark"] .alert-info {
    background-color: rgba(13, 202, 240, 0.15);
    border-color: rgba(13, 202, 240, 0.3);
    color: #6edff6;
}
[data-theme="dark"] .alert-info .alert-link { color: #fff; }

/* Custom Colors */
.text-purple { color: #6f42c1; }
[data-theme="dark"] .text-purple { color: #a685e0 !important; }
</style>

<!-- Library html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    const originalElement = document.querySelector('.print-only');
    const elementToPrint = originalElement.cloneNode(true);
    
    elementToPrint.classList.remove('print-only');
    elementToPrint.style.display = 'block';
    elementToPrint.style.backgroundColor = '#ffffff';
    elementToPrint.style.color = '#000000';
    elementToPrint.style.padding = '20px';
    elementToPrint.style.fontSize = '11px'; // Perkecil font agar muat
    
    elementToPrint.querySelectorAll('*').forEach(el => { el.style.color = '#000000'; });
    
    const tableHeader = elementToPrint.querySelector('thead');
    if (tableHeader) {
        tableHeader.style.backgroundColor = '#0066cc';
        tableHeader.querySelectorAll('th').forEach(th => { th.style.color = '#ffffff'; });
    }
    
    // Perkecil padding tabel agar lebih hemat tempat
    elementToPrint.querySelectorAll('th, td').forEach(cell => {
        cell.style.padding = '4px 5px';
    });
    
    const wrapper = document.createElement('div');
    wrapper.style.position = 'fixed';
    wrapper.style.left = '-10000px';
    wrapper.style.top = '0';
    wrapper.style.width = '1100px';
    wrapper.appendChild(elementToPrint);
    document.body.appendChild(wrapper);

    const opt = {
        margin: 10,
        filename: 'laporan_presensi_<?= date("Y-m-d_His") ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
        pagebreak: { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(elementToPrint).save().then(function() {
        document.body.removeChild(wrapper);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
