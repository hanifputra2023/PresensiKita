<?php
$page = 'mahasiswa_inhall';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kelas = $mahasiswa['kode_kelas'];
$sesi = $mahasiswa['sesi'];

// Daftar pertemuan yang perlu diinhall (izin/sakit, belum diganti)
$perlu_inhall = mysqli_query($conn, "SELECT pi.*, j.pertemuan_ke, j.tanggal, j.materi, j.kode_mk, mk.nama_mk
                                      FROM penggantian_inhall pi
                                      JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                      LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                      WHERE pi.nim = '$nim' AND pi.status = 'terdaftar'
                                      AND pi.status_approval = 'approved'
                                      ORDER BY j.tanggal");

// Ambil kode_mk yang perlu diinhall
$mk_perlu_inhall = [];
$perlu_inhall_data = [];
while ($p = mysqli_fetch_assoc($perlu_inhall)) {
    $mk_perlu_inhall[] = "'" . $p['kode_mk'] . "'";
    $perlu_inhall_data[] = $p;
}
$mk_list = !empty($mk_perlu_inhall) ? implode(',', array_unique($mk_perlu_inhall)) : "'NONE'";

// Jadwal inhall yang tersedia (berdasarkan MK yang perlu diinhall DAN kelas yang sama DAN sesi yang sesuai)
$jadwal_inhall = mysqli_query($conn, "SELECT j.*, mk.nama_mk, l.nama_lab FROM jadwal j 
                                       LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                       WHERE j.jenis = 'inhall' 
                                       AND j.kode_kelas = '$kelas'
                                       AND j.kode_mk IN ($mk_list)
                                       AND j.tanggal >= CURDATE()
                                       AND (j.sesi = 0 OR j.sesi = '$sesi')
                                       ORDER BY j.tanggal");

// Riwayat inhall yang sudah dilakukan
$riwayat_inhall = mysqli_query($conn, "SELECT pi.*, j.pertemuan_ke, j.tanggal as tanggal_asli, j.materi, mk.nama_mk,
                                        ji.tanggal as tanggal_inhall, ji.pertemuan_ke as pertemuan_inhall
                                        FROM penggantian_inhall pi
                                        JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                        LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                        LEFT JOIN jadwal ji ON pi.jadwal_inhall_id = ji.id
                                        WHERE pi.nim = '$nim' AND pi.status = 'hadir'
                                        ORDER BY pi.tanggal_daftar DESC
                                        LIMIT 10");
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Dark Mode Fixes for Warning Badge */
    [data-theme="dark"] .text-warning {
        color: #ffda6a !important; /* Kuning lebih terang agar terbaca di dark mode */
    }
    /* Fix badge text color being black in dark mode due to global override */
    [data-theme="dark"] .badge.bg-warning.bg-opacity-10 {
        color: #ffda6a !important;
        background-color: rgba(255, 218, 106, 0.1) !important;
        border-color: rgba(255, 218, 106, 0.3) !important;
    }
    
    .text-warning {
        color: var(--putih);
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="mb-1 fw-bold text-primary"><i class="fas fa-redo me-2"></i>Inhall (Penggantian)</h4>
                        <p class="text-muted mb-0">Kelola jadwal penggantian praktikum Anda di sini.</p>
                    </div>
                </div>
                
                <div class="row g-4">
                    <!-- Perlu Diinhall -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header">
                                <h6 class="mb-0 fw-bold status-warning">
    <i class="fas fa-exclamation-circle me-2"></i>Perlu Diganti
</h6>

                            </div>
                            <div class="card-body p-0">
                                <?php if (count($perlu_inhall_data) > 0): ?>
                                        <?php foreach ($perlu_inhall_data as $p): ?>
                                            <div class="p-3 hover-bg-light transition-all">
                                                <div class="d-flex flex-wrap flex-md-nowrap gap-3 align-items-center">
                                                    <div class="d-flex gap-3 flex-grow-1 w-100 w-md-auto align-items-start">
                                                        <!-- Date Box (Disamakan dengan Jadwal Tersedia) -->
                                                        <div class="flex-shrink-0 text-center rounded p-1 bg-light border d-flex flex-column justify-content-center" style="width: 50px; height: 50px;">
                                                            <div class="fw-bold text-warning" style="font-size: 1.2rem; line-height: 1;"><?= date('d', strtotime($p['tanggal'])) ?></div>
                                                            <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?= date('M', strtotime($p['tanggal'])) ?></div>
                                                        </div>
                                                        
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h6 class="mb-1 fw-bold text-dark text-truncate" title="<?= $p['nama_mk'] ?>"><?= $p['nama_mk'] ?></h6>
                                                            <div class="small text-muted">
                                                                <div class="mb-1 text-truncate" title="<?= $p['materi'] ?>"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 me-1">P<?= $p['pertemuan_ke'] ?></span> <?= $p['materi'] ?></div>
                                                                <?php if ($p['alasan_izin']): ?>
                                                                    <div class="d-flex align-items-center text-truncate" title="<?= $p['alasan_izin'] ?>"><i class="far fa-comment-dots me-2 text-center" style="width:16px"></i> <?= $p['alasan_izin'] ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="w-100 w-md-auto flex-shrink-0">
                                                        <span class="badge bg-light text-warning border border-warning border-opacity-25 rounded-pill px-3 py-2 w-100 d-block text-center">Belum Diganti</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle fa-3x text-success opacity-50"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Semua Aman!</h6>
                                        <p class="text-muted small mb-0">Tidak ada pertemuan yang perlu diganti saat ini.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jadwal Inhall Tersedia -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header">
                                <h6 class="mb-0 fw-bold status-warning"><i class="fas fa-calendar-check me-2"></i>Jadwal Tersedia</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (mysqli_num_rows($jadwal_inhall) > 0): ?>
                                        <?php while ($j = mysqli_fetch_assoc($jadwal_inhall)): ?>
                                            <div class="p-3 hover-bg-light transition-all">
                                                <div class="d-flex flex-wrap flex-md-nowrap gap-3 align-items-center">
                                                    <div class="d-flex gap-3 flex-grow-1 w-100 w-md-auto align-items-start">
                                                        <!-- Date Box -->
                                                        <div class="flex-shrink-0 text-center rounded p-1 bg-light border d-flex flex-column justify-content-center" style="width: 50px; height: 50px;">
                                                            <div class="fw-bold text-primary" style="font-size: 1.2rem; line-height: 1;"><?= date('d', strtotime($j['tanggal'])) ?></div>
                                                            <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?= date('M', strtotime($j['tanggal'])) ?></div>
                                                        </div>
                                                        
                                                        <!-- Info -->
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h6 class="mb-1 fw-bold text-dark text-truncate" title="<?= $j['nama_mk'] ?>"><?= $j['nama_mk'] ?></h6>
                                                            <div class="small text-muted">
                                                                <div class="mb-1 d-flex align-items-center"><i class="far fa-clock me-2 text-center" style="width:16px"></i> <?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></div>
                                                                <div class="d-flex align-items-center"><i class="fas fa-map-marker-alt me-2 text-center" style="width:16px"></i> <?= $j['nama_lab'] ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Action -->
                                                    <div class="w-100 w-md-auto flex-shrink-0">
                                                        <?php if ($j['tanggal'] == date('Y-m-d')): ?>
                                                            <a href="index.php?page=mahasiswa_scanner" class="btn btn-success btn-sm w-100 rounded-pill px-3 shadow-sm">
                                                                <i class="fas fa-qrcode me-1"></i> Scan
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-secondary rounded-pill px-3 py-2 w-100 d-block text-center">Mendatang</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                <?php elseif (count($perlu_inhall_data) > 0): ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="fas fa-calendar-times fa-3x text-muted opacity-25"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Belum Ada Jadwal</h6>
                                        <p class="text-muted small mb-0">Jadwal inhall untuk mata kuliah Anda belum tersedia.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="fas fa-mug-hot fa-3x text-info opacity-25"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Tidak Ada Tanggungan</h6>
                                        <p class="text-muted small mb-0">Anda tidak perlu mengikuti inhall saat ini.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Riwayat Inhall -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold status-warning"><i class="fas fa-history me-2"></i>Riwayat Inhall</h6>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($riwayat_inhall) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mata Kuliah</th>
                                        <th>Pertemuan Asli</th>
                                        <th>Diganti Pada</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = mysqli_fetch_assoc($riwayat_inhall)): ?>
                                    <tr>
                                        <td class="fw-bold"><?= $r['nama_mk'] ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">P<?= $r['pertemuan_ke'] ?></span>
                                            <span class="small text-muted ms-1"><?= format_tanggal($r['tanggal_asli']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($r['tanggal_inhall']): ?>
                                                <i class="fas fa-check-circle text-success me-1"></i> <?= format_tanggal($r['tanggal_inhall']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-success rounded-pill">Selesai</span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0 py-3 small">Belum ada riwayat inhall.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="card border-0 shadow-sm mt-4 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-info-circle text-primary me-2"></i>Informasi Inhall</h6>
                        <ul class="mb-0 small text-muted ps-3">
                            <li>Inhall adalah sesi penggantian untuk mahasiswa yang <strong>izin/sakit</strong> pada pertemuan materi</li>
                            <li>Anda hanya bisa mengikuti inhall untuk <strong>mata kuliah yang sama</strong> dengan yang Anda izin</li>
                            <li>Datang ke jadwal inhall sesuai yang tersedia (boleh beda kelas) dan lakukan <strong>scan presensi seperti biasa</strong></li>
                            <li>Sistem akan otomatis menandai pertemuan Anda sebagai "Sudah Diganti"</li>
                            <li>Pertemuan yang <strong>alpha</strong> (tanpa keterangan) tidak dapat diganti di inhall</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
