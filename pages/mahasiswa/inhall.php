<?php
$page = 'mahasiswa_inhall';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kelas = $mahasiswa['kode_kelas'];

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

// Jadwal inhall yang tersedia (berdasarkan MK yang perlu diinhall DAN kelas yang sama)
$jadwal_inhall = mysqli_query($conn, "SELECT j.*, mk.nama_mk, l.nama_lab FROM jadwal j 
                                       LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                       WHERE j.jenis = 'inhall' 
                                       AND j.kode_kelas = '$kelas'
                                       AND j.kode_mk IN ($mk_list)
                                       AND j.tanggal >= CURDATE()
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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-redo me-2"></i>Inhall (Penggantian)</h4>
                
                <div class="row">
                    <!-- Perlu Diinhall -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-warning text-dark">
                                <i class="fas fa-exclamation-triangle me-2"></i>Pertemuan yang Perlu Diganti
                            </div>
                            <div class="card-body">
                                <?php if (count($perlu_inhall_data) > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($perlu_inhall_data as $p): ?>
                                            <li class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong class="text-primary"><?= $p['nama_mk'] ?></strong>
                                                        <br><span class="badge bg-secondary">Pertemuan <?= $p['pertemuan_ke'] ?></span>
                                                        <br><small><?= $p['materi'] ?></small>
                                                        <br><small class="text-muted"><i class="fas fa-calendar me-1"></i><?= format_tanggal($p['tanggal']) ?></small>
                                                        <?php if ($p['alasan_izin']): ?>
                                                            <br><small class="text-muted"><i class="fas fa-comment me-1"></i><?= $p['alasan_izin'] ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge bg-warning text-dark">Belum Diganti</span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <p class="text-muted mb-0">Tidak ada pertemuan yang perlu diganti</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jadwal Inhall Tersedia -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-calendar-alt me-2"></i>Jadwal Inhall Tersedia
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($jadwal_inhall) > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php while ($j = mysqli_fetch_assoc($jadwal_inhall)): ?>
                                            <li class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong class="text-primary"><?= $j['nama_mk'] ?></strong>
                                                        <br><small><i class="fas fa-calendar me-1"></i><?= format_tanggal($j['tanggal']) ?></small>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?>
                                                        </small>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-map-marker-alt me-1"></i><?= $j['nama_lab'] ?>
                                                        </small>
                                                    </div>
                                                    <?php if ($j['tanggal'] == date('Y-m-d')): ?>
                                                        <a href="index.php?page=mahasiswa_scanner" class="btn btn-sm btn-success">
                                                            <i class="fas fa-qrcode me-1"></i>Scan
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Mendatang</span>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php elseif (count($perlu_inhall_data) > 0): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Belum ada jadwal inhall untuk mata kuliah yang perlu diganti</p>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <p class="text-muted mb-0">Anda tidak memiliki pertemuan yang perlu diganti</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Riwayat Inhall -->
                <?php if (mysqli_num_rows($riwayat_inhall) > 0): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-history me-2"></i>Riwayat Inhall yang Sudah Dilakukan
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
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
                                        <td><?= $r['nama_mk'] ?></td>
                                        <td>
                                            Pertemuan <?= $r['pertemuan_ke'] ?>
                                            <br><small class="text-muted"><?= format_tanggal($r['tanggal_asli']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($r['tanggal_inhall']): ?>
                                                Inhall <?= format_tanggal($r['tanggal_inhall']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-success">Sudah Diganti</span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Info -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle text-info me-2"></i>Informasi Inhall:</h6>
                        <ul class="mb-0">
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
