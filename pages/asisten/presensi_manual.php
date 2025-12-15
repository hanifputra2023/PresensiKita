<?php
$page = 'asisten_presensi_manual';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

// Proses presensi manual
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $nim = escape($_POST['nim']);
    $status = escape($_POST['status']);
    
    // Cek apakah ini jadwal sebagai pengganti untuk catat hadir asisten
    $cek_pengganti_post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                               WHERE jadwal_id = '$jadwal_id' 
                                                               AND pengganti = '$kode_asisten'
                                                               AND status IN ('izin', 'sakit')"));
    $is_pengganti_post = $cek_pengganti_post ? true : false;
    
    // CATAT HADIR ASISTEN saat melakukan presensi manual pertama kali
    catat_hadir_asisten($kode_asisten, $jadwal_id, $is_pengganti_post);
    
    // Cek sudah ada presensi
    $cek = mysqli_query($conn, "SELECT * FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'");
    
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE presensi_mahasiswa SET status = '$status', metode = 'manual', validated_by = '$kode_asisten' WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'");
    } else {
        mysqli_query($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, validated_by) VALUES ('$jadwal_id', '$nim', '$status', 'manual', '$kode_asisten')");
    }
    
    log_aktivitas($_SESSION['user_id'], 'PRESENSI_MANUAL', 'presensi_mahasiswa', $jadwal_id, "Presensi manual: $nim - $status");
    set_alert('success', 'Presensi berhasil dicatat!');
    
    header("Location: index.php?page=asisten_presensi_manual&jadwal=$jadwal_id");
    exit;
}

$jadwal_id = isset($_GET['jadwal']) ? (int)$_GET['jadwal'] : 0;

// Jadwal hari ini (yang belum selesai) - termasuk jadwal reguler dan jadwal pengganti
// Jadwal hilang tepat setelah jam_selesai (tanpa toleransi)
$today = date('Y-m-d');
$now_time = date('H:i:s');

// Query jadwal reguler
$jadwal_reguler = mysqli_query($conn, "SELECT j.*, k.nama_kelas, 0 as is_pengganti FROM jadwal j 
                                     LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                     WHERE j.tanggal = '$today' 
                                     AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                     AND j.jam_selesai >= '$now_time'
                                     ORDER BY j.jam_mulai");

// Query jadwal sebagai pengganti
$jadwal_pengganti = mysqli_query($conn, "SELECT j.*, k.nama_kelas, 1 as is_pengganti FROM jadwal j 
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          INNER JOIN absen_asisten aa ON aa.jadwal_id = j.id AND aa.pengganti = '$kode_asisten'
                                          WHERE j.tanggal = '$today' 
                                          AND aa.status IN ('izin', 'sakit')
                                          AND j.jam_selesai >= '$now_time'
                                          ORDER BY j.jam_mulai");

// Gabungkan jadwal
$jadwal_list = [];
while ($row = mysqli_fetch_assoc($jadwal_reguler)) { $jadwal_list[] = $row; }
while ($row = mysqli_fetch_assoc($jadwal_pengganti)) { $jadwal_list[] = $row; }

$jadwal_aktif = null;
$mahasiswa_list = null;
if ($jadwal_id) {
    $jadwal_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.*, k.nama_kelas FROM jadwal j 
                                                             LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                             WHERE j.id = '$jadwal_id'"));
    if ($jadwal_aktif) {
        // Cek apakah ini jadwal sebagai pengganti
        $cek_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                                   WHERE jadwal_id = '$jadwal_id' 
                                                                   AND pengganti = '$kode_asisten'
                                                                   AND status IN ('izin', 'sakit')"));
        $is_pengganti = $cek_pengganti ? true : false;
        
        // TIDAK otomatis catat hadir saat buka halaman
        // Hadir dicatat saat melakukan presensi manual pertama kali
        
        $kelas = $jadwal_aktif['kode_kelas'];
        $jenis_jadwal = $jadwal_aktif['jenis'];
        $kode_mk = $jadwal_aktif['kode_mk'];
        
        // Untuk INHALL: hanya tampilkan mahasiswa yang terdaftar di penggantian_inhall
        if ($jenis_jadwal == 'inhall') {
            $mahasiswa_list = mysqli_query($conn, "SELECT DISTINCT m.*, p.status 
                                                    FROM penggantian_inhall pi
                                                    JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                                    JOIN mahasiswa m ON pi.nim = m.nim
                                                    LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = '$jadwal_id'
                                                    WHERE jx.kode_mk = '$kode_mk' AND m.kode_kelas = '$kelas'
                                                    AND pi.status IN ('terdaftar', 'hadir')
                                                    ORDER BY m.nama");
        } else {
            $mahasiswa_list = mysqli_query($conn, "SELECT m.*, p.status FROM mahasiswa m 
                                                    LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = '$jadwal_id'
                                                    WHERE m.kode_kelas = '$kelas' ORDER BY m.nama");
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_asisten.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-edit me-2"></i>Presensi Manual</h4>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <!-- Mobile: Pilih Jadwal di atas -->
                    <div class="col-12 d-md-none mb-3">
                        <div class="card">
                            <div class="card-header">Pilih Jadwal</div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($jadwal_list as $j): ?>
                                        <a href="index.php?page=asisten_presensi_manual&jadwal=<?= $j['id'] ?>" 
                                           class="btn btn-sm <?= $jadwal_id == $j['id'] ? 'btn-primary' : 'btn-outline-primary' ?>">
                                            <?= $j['nama_kelas'] ?>
                                            <?php if (!empty($j['is_pengganti'])): ?>
                                                <span class="badge bg-info">P</span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Desktop: Sidebar Jadwal -->
                    <div class="col-md-3 mb-4 d-none d-md-block">
                        <div class="card">
                            <div class="card-header">Pilih Jadwal</div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($jadwal_list as $j): ?>
                                        <a href="index.php?page=asisten_presensi_manual&jadwal=<?= $j['id'] ?>" 
                                           class="list-group-item list-group-item-action <?= $jadwal_id == $j['id'] ? 'active' : '' ?>">
                                            <strong>
                                                <?= $j['nama_kelas'] ?>
                                                <?php if (!empty($j['is_pengganti'])): ?>
                                                    <span class="badge bg-info">Pengganti</span>
                                                <?php endif; ?>
                                            </strong>
                                            <br><small><?= format_waktu($j['jam_mulai']) ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-9">
                        <?php if ($jadwal_aktif && $mahasiswa_list): ?>
                            <div class="card">
                                <div class="card-header">
                                    Presensi <?= $jadwal_aktif['nama_kelas'] ?> - <?= format_tanggal($jadwal_aktif['tanggal']) ?>
                                </div>
                                <div class="card-body p-2 p-md-3">
                                    <!-- Desktop Table -->
                                    <div class="table-responsive d-none d-lg-block">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>NIM</th>
                                                    <th>Nama</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                mysqli_data_seek($mahasiswa_list, 0);
                                                $no = 1; 
                                                while ($m = mysqli_fetch_assoc($mahasiswa_list)): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td><?= $m['nim'] ?></td>
                                                        <td><?= $m['nama'] ?></td>
                                                        <td>
                                                            <?php if ($m['status'] && $m['status'] != 'belum'): ?>
                                                                <span class="badge bg-<?= $m['status'] == 'hadir' ? 'success' : ($m['status'] == 'izin' ? 'warning' : ($m['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                                    <?= ucfirst($m['status']) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Belum</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                                                                <input type="hidden" name="nim" value="<?= $m['nim'] ?>">
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="submit" name="status" value="hadir" class="btn btn-success" title="Hadir">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                    <button type="submit" name="status" value="izin" class="btn btn-warning" title="Izin">
                                                                        <i class="fas fa-envelope"></i>
                                                                    </button>
                                                                    <button type="submit" name="status" value="sakit" class="btn btn-info" title="Sakit">
                                                                        <i class="fas fa-hospital"></i>
                                                                    </button>
                                                                    <button type="submit" name="status" value="alpha" class="btn btn-danger" title="Alpha">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Mobile Cards -->
                                    <div class="d-lg-none">
                                        <?php 
                                        mysqli_data_seek($mahasiswa_list, 0);
                                        while ($m = mysqli_fetch_assoc($mahasiswa_list)): ?>
                                            <div class="card mb-2 border <?= $m['status'] == 'hadir' ? 'border-success' : '' ?>">
                                                <div class="card-body p-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <strong class="small"><?= $m['nama'] ?></strong>
                                                            <br><small class="text-muted"><?= $m['nim'] ?></small>
                                                        </div>
                                                        <?php if ($m['status'] && $m['status'] != 'belum'): ?>
                                                            <span class="badge bg-<?= $m['status'] == 'hadir' ? 'success' : ($m['status'] == 'izin' ? 'warning' : ($m['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                                <?= ucfirst($m['status']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Belum</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <form method="POST">
                                                        <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                                                        <input type="hidden" name="nim" value="<?= $m['nim'] ?>">
                                                        <div class="btn-group w-100">
                                                            <button type="submit" name="status" value="hadir" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="submit" name="status" value="izin" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-envelope"></i>
                                                            </button>
                                                            <button type="submit" name="status" value="sakit" class="btn btn-sm btn-info">
                                                                <i class="fas fa-hospital"></i>
                                                            </button>
                                                            <button type="submit" name="status" value="alpha" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Pilih jadwal terlebih dahulu</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
