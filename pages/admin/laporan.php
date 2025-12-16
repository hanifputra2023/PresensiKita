<?php
$page = 'admin_laporan';

// Filter
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_mk = isset($_GET['mk']) ? escape($_GET['mk']) : '';
$filter_bulan = isset($_GET['bulan']) ? escape($_GET['bulan']) : date('Y-m');

$where_mhs = ["1=1"];
$where_jadwal = "";
if ($filter_kelas) $where_mhs[] = "m.kode_kelas = '$filter_kelas'";
if ($filter_mk) $where_jadwal = "AND j.kode_mk = '$filter_mk'";
$where_mhs_sql = implode(" AND ", $where_mhs);

// Pagination
$per_page = 20;
$current_page = get_current_page();

// Hitung total data untuk pagination - hitung semua mahasiswa tanpa LEFT JOIN jadwal
$count_sql = "SELECT COUNT(DISTINCT m.nim) as total
              FROM mahasiswa m 
              WHERE $where_mhs_sql";
$count_query = mysqli_query($conn, $count_sql);
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Data rekap dengan pagination
// Status dihitung berdasarkan waktu SEKARANG (NOW()) untuk stabilitas
// Alpha: Jadwal sudah lewat dan tidak ada presensi (hadir/izin/sakit)
// Belum: Jadwal belum selesai
// EXCLUDE jadwal inhall dari statistik (inhall bersifat opsional, tidak mempengaruhi persentase)
$rekap = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas, 
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() THEN 1 ELSE 0 END) as belum,
                               COUNT(CASE WHEN j.jenis != 'inhall' THEN j.id END) as total_pertemuan,
                               (SELECT COUNT(*) FROM penggantian_inhall pi 
                                JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id 
                                WHERE pi.nim = m.nim AND pi.status = 'terdaftar' AND pi.status_approval = 'approved'
                                AND DATE_FORMAT(jpi.tanggal, '%Y-%m') = '$filter_bulan') as perlu_inhall,
                               (SELECT COUNT(*) FROM penggantian_inhall pi 
                                JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id 
                                WHERE pi.nim = m.nim AND pi.status = 'hadir' AND pi.status_approval = 'approved'
                                AND DATE_FORMAT(jpi.tanggal, '%Y-%m') = '$filter_bulan') as sudah_inhall
                               FROM mahasiswa m 
                               LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas 
                                   AND DATE_FORMAT(j.tanggal, '%Y-%m') = '$filter_bulan'
                                   $where_jadwal
                               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                               WHERE $where_mhs_sql
                               GROUP BY m.nim, m.nama, k.nama_kelas
                               ORDER BY k.nama_kelas, m.nama
                               LIMIT $offset, $per_page");

$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY kode_kelas");
$mk_list = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_admin.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4 pt-2">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Laporan Presensi</h4>
                    <button class="btn btn-success w-100 w-md-auto" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Cetak Laporan
                    </button>
                </div>
                
                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 g-md-3 align-items-end">
                            <input type="hidden" name="page" value="admin_laporan">
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Bulan</label>
                                <input type="month" name="bulan" class="form-control form-control-sm" value="<?= $filter_bulan ?>">
                            </div>
                            <div class="col-6 col-md-3">
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
                    <div class="card-body p-2 p-md-3">
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
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
                        <div class="d-lg-none">
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
                            <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_laporan', ['kelas' => $filter_kelas, 'mk' => $filter_mk, 'bulan' => $filter_bulan]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .btn, form, .no-print { display: none !important; }
    .col-md-9, .col-lg-10 { width: 100% !important; margin: 0 !important; }
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

<?php include 'includes/footer.php'; ?>
