<?php
$page = 'mahasiswa_riwayat';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];

// Pagination
$per_page = 10;
$current_page = get_current_page();

// Hitung total (exclude status 'belum' karena itu jadwal yang masih berjalan)
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM presensi_mahasiswa WHERE nim = '$nim' AND status != 'belum'");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

$riwayat = mysqli_query($conn, "SELECT p.*, j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, j.jenis,
                                 l.nama_lab, mk.nama_mk
                                 FROM presensi_mahasiswa p
                                 JOIN jadwal j ON p.jadwal_id = j.id
                                 LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                 LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                 WHERE p.nim = '$nim' AND p.status != 'belum'
                                 ORDER BY j.tanggal DESC, j.jam_mulai DESC
                                 LIMIT $offset, $per_page");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-history me-2"></i>Riwayat Presensi</h4>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($riwayat) > 0): ?>
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pertemuan</th>
                                            <th>Tanggal</th>
                                            <th>Waktu</th>
                                            <th>Mata Kuliah</th>
                                            <th>Materi</th>
                                            <th>Lab</th>
                                            <th>Status</th>
                                            <th>Metode</th>
                                            <th>Waktu Presensi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($riwayat, 0);
                                        while ($r = mysqli_fetch_assoc($riwayat)): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?= $r['pertemuan_ke'] ?></span></td>
                                                <td><?= format_tanggal($r['tanggal']) ?></td>
                                                <td><?= format_waktu($r['jam_mulai']) ?> - <?= format_waktu($r['jam_selesai']) ?></td>
                                                <td><?= $r['nama_mk'] ?></td>
                                                <td><?= $r['materi'] ?></td>
                                                <td><?= $r['nama_lab'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $r['status'] == 'hadir' ? 'success' : ($r['status'] == 'izin' ? 'warning' : ($r['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                        <?= ucfirst($r['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $r['metode'] == 'qr' ? 'primary' : 'secondary' ?>">
                                                        <?= strtoupper($r['metode']) ?>
                                                    </span>
                                                </td>
                                                <td><small><?= date('d/m/Y H:i', strtotime($r['waktu_presensi'])) ?></small></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-md-none">
                                <?php 
                                mysqli_data_seek($riwayat, 0);
                                while ($r = mysqli_fetch_assoc($riwayat)): ?>
                                    <div class="card mb-3 border">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?= $r['nama_mk'] ?></h6>
                                                    <small class="text-muted"><?= $r['materi'] ?></small>
                                                </div>
                                                <span class="badge bg-<?= $r['status'] == 'hadir' ? 'success' : ($r['status'] == 'izin' ? 'warning' : ($r['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                    <?= ucfirst($r['status']) ?>
                                                </span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="row small">
                                                <div class="col-6">
                                                    <i class="fas fa-calendar me-1 text-muted"></i><?= format_tanggal($r['tanggal']) ?>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <span class="badge bg-secondary">P<?= $r['pertemuan_ke'] ?></span>
                                                </div>
                                            </div>
                                            <div class="row small mt-1">
                                                <div class="col-6">
                                                    <i class="fas fa-clock me-1 text-muted"></i><?= format_waktu($r['jam_mulai']) ?>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <i class="fas fa-map-marker-alt me-1 text-muted"></i><?= $r['nama_lab'] ?>
                                                </div>
                                            </div>
                                            <div class="row small mt-1">
                                                <div class="col-6">
                                                    <span class="badge bg-<?= $r['metode'] == 'qr' ? 'primary' : 'secondary' ?>"><?= strtoupper($r['metode']) ?></span>
                                                </div>
                                                <div class="col-6 text-end text-muted">
                                                    <?= date('d/m H:i', strtotime($r['waktu_presensi'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                                <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                                <?= render_pagination($current_page, $total_pages, 'index.php?page=mahasiswa_riwayat', []) ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada riwayat presensi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
