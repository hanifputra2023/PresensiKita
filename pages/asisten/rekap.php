<?php
$page = 'asisten_rekap';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';

// Ambil jadwal yang pernah diajar
$jadwal_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_kelas, k.nama_kelas 
                                       FROM jadwal j 
                                       JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                       WHERE j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten'");

// Pagination
$per_page = 20;
$current_page = get_current_page();

$where = $filter_kelas ? "AND j.kode_kelas = '$filter_kelas'" : '';

// Hitung total - ambil mahasiswa dari kelas yang pernah diajar asisten
$count_sql = "SELECT COUNT(DISTINCT m.nim) as total
              FROM mahasiswa m
              JOIN jadwal j ON m.kode_kelas = j.kode_kelas
              WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten') 
              $where
              GROUP BY m.nim
              LIMIT 999999";
$count_query = mysqli_query($conn, $count_sql);
$total_data = mysqli_num_rows($count_query);
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Rekap per mahasiswa
// Status dihitung berdasarkan waktu SEKARANG (NOW()) untuk stabilitas
// Alpha: Jadwal sudah lewat dan tidak ada presensi (hadir/izin/sakit)
// Belum: Jadwal belum selesai
// EXCLUDE jadwal inhall dari statistik (inhall bersifat opsional, tidak mempengaruhi persentase)
$rekap = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas,
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
                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = j.id
                               WHERE (SELECT COUNT(*) FROM jadwal j2 
                                      WHERE j2.kode_kelas = m.kode_kelas 
                                      AND (j2.kode_asisten_1 = '$kode_asisten' OR j2.kode_asisten_2 = '$kode_asisten')) > 0
                                      $where
                               GROUP BY m.nim, m.nama, k.nama_kelas
                               ORDER BY k.nama_kelas, m.nama
                               LIMIT $offset, $per_page");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_asisten.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4 pt-2">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Rekap Presensi</h4>
                    <button class="btn btn-success w-100 w-md-auto" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Cetak
                    </button>
                </div>
                
                <div class="card mb-4 no-print">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="asisten_rekap">
                            <div class="col-8 col-md-4">
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
                            <div class="col-4 col-md-2">
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
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($rekap, 0);
                                    $no = 1; 
                                    while ($r = mysqli_fetch_assoc($rekap)): ?>
                                        <?php 
                                        // Hitung persentase hanya dari yang sudah ada status (bukan belum presensi)
                                        $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                        $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
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
                        <div class="d-lg-none">
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
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2 no-print">
                            <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                            <?= render_pagination($current_page, $total_pages, 'index.php?page=asisten_rekap', ['kelas' => $filter_kelas]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .no-print { display: none !important; }
    .col-md-9, .col-lg-10 { width: 100% !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
