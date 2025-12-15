<?php
$page = 'asisten_monitoring';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

$jadwal_id = isset($_GET['jadwal']) ? (int)$_GET['jadwal'] : 0;
$jadwal_aktif = null;

if ($jadwal_id) {
    $jadwal_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk 
                                                             FROM jadwal j 
                                                             LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                             LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                                             LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                             WHERE j.id = '$jadwal_id'"));
    
    // Cek apakah ini jadwal sebagai pengganti
    $is_pengganti = false;
    if ($jadwal_aktif) {
        $cek_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                                   WHERE jadwal_id = '$jadwal_id' 
                                                                   AND pengganti = '$kode_asisten'
                                                                   AND status IN ('izin', 'sakit')"));
        $is_pengganti = $cek_pengganti ? true : false;
    }
    
    // TIDAK otomatis catat hadir saat buka halaman monitoring
    // Hadir dicatat saat Generate QR atau Presensi Manual pertama
}

// Total mahasiswa di kelas
$total_mhs = 0;
$presensi_list = [];
if ($jadwal_aktif) {
    $kelas = $jadwal_aktif['kode_kelas'];
    $jenis_jadwal = $jadwal_aktif['jenis'];
    $kode_mk = $jadwal_aktif['kode_mk'];
    
    // Untuk INHALL: hanya tampilkan mahasiswa yang terdaftar di penggantian_inhall
    if ($jenis_jadwal == 'inhall') {
        $total_mhs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT pi.nim) as total 
                                                               FROM penggantian_inhall pi
                                                               JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                                               JOIN mahasiswa m ON pi.nim = m.nim
                                                               WHERE j.kode_mk = '$kode_mk' AND m.kode_kelas = '$kelas'
                                                               AND pi.status IN ('terdaftar', 'hadir')"))['total'];
        
        // List presensi untuk INHALL - hanya mahasiswa yang terdaftar
        $presensi_list = mysqli_query($conn, "SELECT DISTINCT m.nim, m.nama, p.status, p.waktu_presensi, p.metode
                                               FROM penggantian_inhall pi
                                               JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                               JOIN mahasiswa m ON pi.nim = m.nim
                                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = '$jadwal_id'
                                               WHERE jx.kode_mk = '$kode_mk' AND m.kode_kelas = '$kelas'
                                               AND pi.status IN ('terdaftar', 'hadir')
                                               ORDER BY p.waktu_presensi DESC, m.nama");
    } else {
        // Untuk MATERI dan UJIKOM: semua mahasiswa di kelas
        $total_mhs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa WHERE kode_kelas = '$kelas'"))['total'];
        
        // List presensi
        $presensi_list = mysqli_query($conn, "SELECT m.nim, m.nama, p.status, p.waktu_presensi, p.metode
                                               FROM mahasiswa m 
                                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = '$jadwal_id'
                                               WHERE m.kode_kelas = '$kelas'
                                               ORDER BY p.waktu_presensi DESC, m.nama");
    }
}

// Jadwal hari ini (yang belum selesai) - termasuk jadwal reguler dan jadwal pengganti
// Gunakan CURDATE() dan CURTIME() MySQL agar konsisten dengan timezone server database
// Jadwal hilang tepat setelah jam_selesai (tanpa toleransi)
$kode_asisten = $asisten['kode_asisten'];

// Query jadwal reguler - gunakan fungsi waktu MySQL
$jadwal_reguler = mysqli_query($conn, "SELECT j.*, k.nama_kelas, 0 as is_pengganti FROM jadwal j 
                                     LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                     WHERE j.tanggal = CURDATE() 
                                     AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                     AND j.jam_selesai >= CURTIME()
                                     ORDER BY j.jam_mulai");

// Query jadwal sebagai pengganti
$jadwal_pengganti = mysqli_query($conn, "SELECT j.*, k.nama_kelas, 1 as is_pengganti FROM jadwal j 
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          INNER JOIN absen_asisten aa ON aa.jadwal_id = j.id AND aa.pengganti = '$kode_asisten'
                                          WHERE j.tanggal = CURDATE() 
                                          AND aa.status IN ('izin', 'sakit')
                                          AND j.jam_selesai >= CURTIME()
                                          ORDER BY j.jam_mulai");

// Gabungkan jadwal
$jadwal_list = [];
while ($row = mysqli_fetch_assoc($jadwal_reguler)) { $jadwal_list[] = $row; }
while ($row = mysqli_fetch_assoc($jadwal_pengganti)) { $jadwal_list[] = $row; }
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
                    <h4 class="mb-0"><i class="fas fa-tv me-2"></i>Monitoring Presensi</h4>
                    <?php if ($jadwal_aktif): ?>
                        <a href="index.php?page=asisten_qrcode&jadwal=<?= $jadwal_id ?>" class="btn btn-primary w-100 w-md-auto">
                            <i class="fas fa-qrcode me-1"></i>Lihat QR Code
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (!$jadwal_id): ?>
                    <!-- Pilih Jadwal -->
                    <div class="card">
                        <div class="card-header">Pilih Jadwal untuk Monitoring</div>
                        <div class="card-body">
                            <?php if (count($jadwal_list) > 0): ?>
                                <div class="row">
                                    <?php foreach ($jadwal_list as $j): ?>
                                        <div class="col-6 col-md-4 mb-3">
                                            <a href="index.php?page=asisten_monitoring&jadwal=<?= $j['id'] ?>" class="text-decoration-none">
                                                <div class="card border-primary h-100">
                                                    <div class="card-body text-center p-3">
                                                        <h6 class="h5 mb-1">
                                                            <?= $j['nama_kelas'] ?>
                                                            <?php if (!empty($j['is_pengganti'])): ?>
                                                                <span class="badge bg-info">Pengganti</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <p class="mb-0 text-muted small"><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Tidak ada jadwal hari ini</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Info Jadwal -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-8 mb-3 mb-md-0 text-center text-md-start">
                                    <h5><?= $jadwal_aktif['nama_mk'] ?> - <?= $jadwal_aktif['nama_kelas'] ?></h5>
                                    <p class="text-muted mb-0 small">
                                        <?= $jadwal_aktif['materi'] ?><br class="d-md-none">
                                        <span class="d-none d-md-inline"> | </span>
                                        <?= format_tanggal($jadwal_aktif['tanggal']) ?> | 
                                        <?= format_waktu($jadwal_aktif['jam_mulai']) ?> - <?= format_waktu($jadwal_aktif['jam_selesai']) ?>
                                        <br class="d-md-none">
                                        <span class="d-none d-md-inline"> | </span>
                                        <?= $jadwal_aktif['nama_lab'] ?>
                                    </p>
                                </div>
                                <div class="col-12 col-md-4 text-center text-md-end">
                                    <?php
                                    $hadir = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id' AND status = 'hadir'"))['total'];
                                    ?>
                                    <div class="h1 mb-0 text-primary"><?= $hadir ?>/<?= $total_mhs ?></div>
                                    <small class="text-muted">Mahasiswa Hadir</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- List Presensi -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Daftar Presensi</span>
                            <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>NIM</th>
                                            <th>Nama</th>
                                            <th>Status</th>
                                            <th>Waktu</th>
                                            <th>Metode</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; 
                                        mysqli_data_seek($presensi_list, 0);
                                        while ($p = mysqli_fetch_assoc($presensi_list)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= $p['nim'] ?></td>
                                                <td><?= $p['nama'] ?></td>
                                                <td>
                                                    <?php if ($p['status'] && $p['status'] != 'belum'): ?>
                                                        <span class="badge badge-<?= $p['status'] ?> bg-<?= $p['status'] == 'hadir' ? 'success' : ($p['status'] == 'izin' ? 'warning' : ($p['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                            <?= ucfirst($p['status']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Belum Presensi</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $p['waktu_presensi'] ? date('H:i:s', strtotime($p['waktu_presensi'])) : '-' ?></td>
                                                <td><?= $p['metode'] ? ucfirst($p['metode']) : '-' ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-md-none">
                                <?php $no = 1; 
                                mysqli_data_seek($presensi_list, 0);
                                while ($p = mysqli_fetch_assoc($presensi_list)): ?>
                                    <div class="card mb-2 <?= $p['status'] == 'hadir' ? 'border-success' : (($p['status'] && $p['status'] != 'belum') ? 'border-warning' : '') ?>">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong class="small"><?= $p['nama'] ?></strong>
                                                    <br><small class="text-muted"><?= $p['nim'] ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <?php if ($p['status'] && $p['status'] != 'belum'): ?>
                                                        <span class="badge bg-<?= $p['status'] == 'hadir' ? 'success' : ($p['status'] == 'izin' ? 'warning' : ($p['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                            <?= ucfirst($p['status']) ?>
                                                        </span>
                                                        <br><small class="text-muted"><?= date('H:i', strtotime($p['waktu_presensi'])) ?></small>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Belum</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        // Auto refresh setiap 10 detik
                        setTimeout(function() {
                            location.reload();
                        }, 10000);
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
