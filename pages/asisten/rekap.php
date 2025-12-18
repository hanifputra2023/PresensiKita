<?php
$page = 'asisten_rekap';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_lab = isset($_GET['lab']) ? escape($_GET['lab']) : '';
$filter_mk = isset($_GET['mk']) ? escape($_GET['mk']) : '';

// Ambil jadwal yang pernah diajar
$jadwal_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_kelas, k.nama_kelas 
                                       FROM jadwal j 
                                       JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                       WHERE j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten'");

// Ambil lab yang pernah diajar
$lab_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_lab, l.nama_lab 
                                   FROM jadwal j 
                                   JOIN lab l ON j.kode_lab = l.kode_lab
                                   WHERE j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten'");

// Ambil mata kuliah yang pernah diajar
$mk_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_mk, mk.nama_mk 
                                  FROM jadwal j 
                                  JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                  WHERE j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten'");

// Pagination
$per_page = 20;
$current_page = get_current_page();

$where_kelas = $filter_kelas ? "AND j.kode_kelas = '$filter_kelas'" : '';
$where_lab = $filter_lab ? "AND j.kode_lab = '$filter_lab'" : '';
$where_mk = $filter_mk ? "AND j.kode_mk = '$filter_mk'" : '';

// Export Excel Logic
if (isset($_GET['export'])) {
    // Hentikan dan bersihkan output buffer yang mungkin sudah terisi oleh index.php
    if (ob_get_length()) ob_end_clean();
    
    $filename = 'rekap_presensi_' . date('Y-m-d_His') . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    echo '
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000000; padding: 5px; }
            th { background-color: #f2f2f2; }
            .text-center { text-align: center; }
        </style>
    </head>
    <body>
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>NIM</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Mata Kuliah</th>
                <th>Lab</th>
                <th>Hadir</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Alpha</th>
                <th>Belum</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>';
    
    $query_export = "SELECT m.nim, m.nama, k.nama_kelas,
                       mk.nama_mk, l.nama_lab,
                       SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                       SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                       SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                       SUM(CASE WHEN j.jenis != 'inhall' AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as alpha,
                       SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum
                       FROM mahasiswa m
                       JOIN kelas k ON m.kode_kelas = k.kode_kelas
                       LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas
                           AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                       LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                       LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = j.id
                       WHERE (SELECT COUNT(*) FROM jadwal j2 
                              WHERE j2.kode_kelas = m.kode_kelas 
                              AND (j2.kode_asisten_1 = '$kode_asisten' OR j2.kode_asisten_2 = '$kode_asisten')) > 0 
                              $where_kelas $where_lab $where_mk
                       GROUP BY m.nim, m.nama, k.nama_kelas, mk.nama_mk, l.nama_lab
                       ORDER BY k.nama_kelas, m.nama, mk.nama_mk";
                       
    $result_export = mysqli_query($conn, $query_export);
    $no = 1;
    while ($row = mysqli_fetch_assoc($result_export)) {
        $sudah_presensi = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
        $persen = $sudah_presensi > 0 ? round(($row['hadir'] / $sudah_presensi) * 100) : 0;
        
        echo "<tr>
            <td class='text-center'>{$no}</td>
            <td>'{$row['nim']}</td>
            <td>{$row['nama']}</td>
            <td>{$row['nama_kelas']}</td>
            <td>" . ($row['nama_mk'] ?: '-') . "</td>
            <td>" . ($row['nama_lab'] ?: '-') . "</td>
            <td class='text-center'>{$row['hadir']}</td>
            <td class='text-center'>{$row['izin']}</td>
            <td class='text-center'>{$row['sakit']}</td>
            <td class='text-center'>{$row['alpha']}</td>
            <td class='text-center'>{$row['belum']}</td>
            <td class='text-center'>{$persen}%</td>
        </tr>";
        $no++;
    }
    
    echo '</tbody></table></body></html>';
    exit;
}

// Hitung total - ambil mahasiswa dari kelas yang pernah diajar asisten
$count_sql = "SELECT COUNT(*) as total FROM (
                SELECT 1
                FROM mahasiswa m
                LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                WHERE (SELECT COUNT(*) FROM jadwal j2 WHERE j2.kode_kelas = m.kode_kelas AND (j2.kode_asisten_1 = '$kode_asisten' OR j2.kode_asisten_2 = '$kode_asisten')) > 0
                $where_kelas $where_lab $where_mk
                GROUP BY m.nim, j.kode_mk, j.kode_lab
              ) as subquery";
$count_query = mysqli_query($conn, $count_sql);
$total_data = mysqli_fetch_assoc($count_query)['total'] ?? 0;
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Rekap per mahasiswa
// Status dihitung berdasarkan waktu SEKARANG (NOW()) untuk stabilitas
// Alpha: Jadwal sudah lewat dan tidak ada presensi (hadir/izin/sakit)
// Belum: Jadwal belum selesai
// EXCLUDE jadwal inhall dari statistik (inhall bersifat opsional, tidak mempengaruhi persentase)
$rekap = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas,
                               mk.nama_mk, l.nama_lab,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum,
                               COUNT(CASE WHEN j.jenis != 'inhall' AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN j.id END) as total_pertemuan
                               FROM mahasiswa m
                               JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas
                                   AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = j.id
                               WHERE (SELECT COUNT(*) FROM jadwal j2 
                                      WHERE j2.kode_kelas = m.kode_kelas 
                                      AND (j2.kode_asisten_1 = '$kode_asisten' OR j2.kode_asisten_2 = '$kode_asisten')) > 0 
                                      $where_kelas $where_lab $where_mk
                               GROUP BY m.nim, m.nama, k.nama_kelas, mk.nama_mk, l.nama_lab
                               ORDER BY k.nama_kelas, m.nama, mk.nama_mk
                               LIMIT $offset, $per_page");
?>
<?php
// Query for printing all data (without pagination)
$rekap_print = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas,
                               mk.nama_mk, l.nama_lab,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum
                               FROM mahasiswa m
                               JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas
                                   AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = j.id
                               WHERE (SELECT COUNT(*) FROM jadwal j2 
                                      WHERE j2.kode_kelas = m.kode_kelas 
                                      AND (j2.kode_asisten_1 = '$kode_asisten' OR j2.kode_asisten_2 = '$kode_asisten')) > 0 
                                      $where_kelas $where_lab $where_mk
                               GROUP BY m.nim, m.nama, k.nama_kelas, mk.nama_mk, l.nama_lab
                               ORDER BY k.nama_kelas, m.nama, mk.nama_mk");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_asisten.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4 pt-2 no-print">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Rekap Presensi</h4>
                    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
                        <a href="index.php?page=asisten_rekap&export=1&kelas=<?= $filter_kelas ?>&lab=<?= $filter_lab ?>&mk=<?= $filter_mk ?>" class="btn btn-success">
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
                
                <div class="card mb-4 no-print">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="asisten_rekap">
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Filter Kelas</label>
                                <select name="kelas" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    <?php 
                                    mysqli_data_seek($jadwal_diajar, 0);
                                    while ($j = mysqli_fetch_assoc($jadwal_diajar)): ?>
                                        <option value="<?= $j['kode_kelas'] ?>" <?= $filter_kelas == $j['kode_kelas'] ? 'selected' : '' ?>>
                                            <?= $j['nama_kelas'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Filter Mata Kuliah</label>
                                <select name="mk" class="form-select form-select-sm">
                                    <option value="">Semua MK</option>
                                    <?php 
                                    while ($m = mysqli_fetch_assoc($mk_diajar)): ?>
                                        <option value="<?= $m['kode_mk'] ?>" <?= $filter_mk == $m['kode_mk'] ? 'selected' : '' ?>>
                                            <?= $m['nama_mk'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Filter Lab</label>
                                <select name="lab" class="form-select form-select-sm">
                                    <option value="">Semua Lab</option>
                                    <?php 
                                    while ($l = mysqli_fetch_assoc($lab_diajar)): ?>
                                        <option value="<?= $l['kode_lab'] ?>" <?= $filter_lab == $l['kode_lab'] ? 'selected' : '' ?>>
                                            <?= $l['nama_lab'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Link ke Statistik -->
                <div class="alert alert-info mb-4 no-print">
                    <i class="fas fa-chart-pie me-2"></i>
                    Lihat statistik per kelas, mata kuliah, dan lab di halaman 
                    <a href="index.php?page=asisten_statistik" class="alert-link">Statistik Presensi</a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <strong><i class="fas fa-list me-1"></i> Rekap Per Mahasiswa</strong>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- ==================== FOR SCREEN VIEW (PAGINATED) ==================== -->
                        <div class="no-print">
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-lg-block">
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
                                            <th class="text-center">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($rekap, 0);
                                        $no = get_offset($current_page, $per_page) + 1; 
                                        while ($r = mysqli_fetch_assoc($rekap)): ?>
                                            <?php 
                                            $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                            $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
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
                                                    <span class="badge <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>">
                                                        <?= $persen ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-lg-none p-2">
                                <?php 
                                mysqli_data_seek($rekap, 0);
                                while ($r = mysqli_fetch_assoc($rekap)): 
                                    $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                    $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
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
                                                </div>
                                                <span class="badge <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>" style="font-size: 0.9rem;">
                                                    <?= $persen ?>%
                                                </span>
                                            </div>
                                            <div class="small text-muted mb-2">
                                                <div><i class="fas fa-book me-1"></i> <?= $r['nama_mk'] ?: 'N/A' ?></div>
                                                <div><i class="fas fa-flask me-1"></i> <?= $r['nama_lab'] ?: 'N/A' ?></div>
                                            </div>
                                            <div class="d-flex justify-content-between small flex-wrap gap-1">
                                                <span class="text-success"><i class="fas fa-check me-1"></i>H: <?= $r['hadir'] ?></span>
                                                <span class="text-warning"><i class="fas fa-clock me-1"></i>I: <?= $r['izin'] ?></span>
                                                <span class="text-info"><i class="fas fa-medkit me-1"></i>S: <?= $r['sakit'] ?></span>
                                                <span class="text-danger"><i class="fas fa-times me-1"></i>A: <?= $r['alpha'] ?></span>
                                                <?php if ($r['belum'] > 0): ?>
                                                    <span class="text-secondary"><i class="fas fa-question me-1"></i>B: <?= $r['belum'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2 p-2">
                                <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                                <?= render_pagination($current_page, $total_pages, 'index.php?page=asisten_rekap', ['kelas' => $filter_kelas, 'lab' => $filter_lab, 'mk' => $filter_mk]) ?>
                            </div>
                        </div>

                        <!-- ==================== FOR PRINT VIEW (ALL DATA) ==================== -->
                        <div class="print-only">
                            <h4 class="mb-3 text-center">Rekap Presensi</h4>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Mata Kuliah</th>
                                        <th>Lab</th>
                                        <th class="text-center">Hadir</th>
                                        <th class="text-center">Izin</th>
                                        <th class="text-center">Sakit</th>
                                        <th class="text-center">Alpha</th>
                                        <th class="text-center">Belum</th>
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
                                            <td class="text-center"><?= $r_print['belum'] ?></td>
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
    /* Sembunyikan elemen yang tidak perlu */
    .sidebar, .no-print, #mobileHeader { 
        display: none !important; 
    }

    .print-only {
        display: block !important;
    }

    /* Buat konten tabel menjadi lebar penuh */
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

    /* Atur gaya dasar untuk cetak */
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
</style>

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
        // Ganti warna teks di dalam header menjadi putih
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
        filename:     'rekap_presensi_<?= date("Y-m-d_His") ?>.pdf',
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
<?php include 'includes/footer.php'; ?>
