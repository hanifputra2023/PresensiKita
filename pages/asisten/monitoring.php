<?php
$page = 'asisten_monitoring';
$asisten = get_asisten_login();

// Validasi data asisten
if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Data asisten tidak ditemukan. Pastikan akun Anda sudah terdaftar sebagai asisten.</div>';
    return;
}

$kode_asisten = $asisten['kode_asisten'];

$jadwal_id = isset($_GET['jadwal']) ? (int)$_GET['jadwal'] : 0;
$jadwal_aktif = null;

if ($jadwal_id) {
    $stmt_jadwal_aktif = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk 
                                                             FROM jadwal j 
                                                             LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                             LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                                             LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                             WHERE j.id = ?");
    mysqli_stmt_bind_param($stmt_jadwal_aktif, "i", $jadwal_id);
    mysqli_stmt_execute($stmt_jadwal_aktif);
    $jadwal_aktif = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jadwal_aktif));
    
    // Cek apakah ini jadwal sebagai pengganti
    $is_pengganti = false;
    if ($jadwal_aktif) {
        $stmt_cek_pengganti = mysqli_prepare($conn, "SELECT id FROM absen_asisten 
                                                                   WHERE jadwal_id = ? 
                                                                   AND pengganti = ?
                                                                   AND status IN ('izin', 'sakit')
                                                                   AND status_approval = 'approved'");
        mysqli_stmt_bind_param($stmt_cek_pengganti, "is", $jadwal_id, $kode_asisten);
        mysqli_stmt_execute($stmt_cek_pengganti);
        $cek_pengganti = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_pengganti));
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
    // FIX: Query diubah untuk hanya menampilkan mahasiswa yang pengajuan izin/sakitnya disetujui (approved)
    // dan terdaftar untuk jadwal inhall ini.
    if ($jenis_jadwal == 'inhall') {
        $stmt_total_inhall = mysqli_prepare($conn, "SELECT COUNT(pi.id) as total 
                            FROM penggantian_inhall pi
                            WHERE pi.jadwal_inhall_id = ?
                            AND pi.status_approval = 'approved'");
        mysqli_stmt_bind_param($stmt_total_inhall, "i", $jadwal_id);
        mysqli_stmt_execute($stmt_total_inhall);
        $total_mhs = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total_inhall))['total'];
        
        // List presensi untuk INHALL - hanya mahasiswa yang pengajuannya disetujui untuk jadwal inhall ini
        $stmt_presensi_inhall = mysqli_prepare($conn, "SELECT m.nim, m.nama, p.status, p.waktu_presensi, p.metode
                                FROM penggantian_inhall pi
                                JOIN mahasiswa m ON pi.nim = m.nim
                                LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = ?
                                WHERE pi.jadwal_inhall_id = ?
                                AND pi.status_approval = 'approved'
                                ORDER BY p.waktu_presensi DESC, m.nama");
        mysqli_stmt_bind_param($stmt_presensi_inhall, "ii", $jadwal_id, $jadwal_id);
        mysqli_stmt_execute($stmt_presensi_inhall);
        $presensi_list = mysqli_stmt_get_result($stmt_presensi_inhall);
    } else {
        // Untuk MATERI dan UJIKOM: semua mahasiswa di kelas
        $sesi_jadwal = $jadwal_aktif['sesi'];
        
        $stmt_total_mhs = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM mahasiswa WHERE kode_kelas = ? AND (sesi = ? OR ? = 0)");
        mysqli_stmt_bind_param($stmt_total_mhs, "sii", $kelas, $sesi_jadwal, $sesi_jadwal);
        mysqli_stmt_execute($stmt_total_mhs);
        $total_mhs = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total_mhs))['total'];
        
        // List presensi
        $stmt_presensi_list = mysqli_prepare($conn, "SELECT m.nim, m.nama, p.status, p.waktu_presensi, p.metode
                                               FROM mahasiswa m 
                                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = ?
                                               WHERE m.kode_kelas = ?
                                               AND (m.sesi = ? OR ? = 0)
                                               ORDER BY p.waktu_presensi DESC, m.nama");
        mysqli_stmt_bind_param($stmt_presensi_list, "isii", $jadwal_id, $kelas, $sesi_jadwal, $sesi_jadwal);
        mysqli_stmt_execute($stmt_presensi_list);
        $presensi_list = mysqli_stmt_get_result($stmt_presensi_list);
    }
}

// Jadwal hari ini (yang belum selesai) - termasuk jadwal reguler dan jadwal pengganti
// Gunakan CURDATE() dan CURTIME() MySQL agar konsisten dengan timezone server database
// Jadwal hilang tepat setelah jam_selesai (tanpa toleransi)
$kode_asisten = $asisten['kode_asisten'];

// Query jadwal reguler - gunakan fungsi waktu MySQL
$stmt_jadwal_reguler = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, 0 as is_pengganti FROM jadwal j 
                                     LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                     WHERE j.tanggal = CURDATE() 
                                     AND (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)
                                     AND j.jam_selesai >= CURTIME()
                                     ORDER BY j.jam_mulai");
mysqli_stmt_bind_param($stmt_jadwal_reguler, "ss", $kode_asisten, $kode_asisten);
mysqli_stmt_execute($stmt_jadwal_reguler);
$jadwal_reguler = mysqli_stmt_get_result($stmt_jadwal_reguler);

// Query jadwal sebagai pengganti (hanya yang sudah disetujui admin)
$stmt_jadwal_pengganti = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, 1 as is_pengganti FROM jadwal j 
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          INNER JOIN absen_asisten aa ON aa.jadwal_id = j.id AND aa.pengganti = ?
                                          WHERE j.tanggal = CURDATE() 
                                          AND aa.status IN ('izin', 'sakit')
                                          AND aa.status_approval = 'approved'
                                          AND j.jam_selesai >= CURTIME()
                                          ORDER BY j.jam_mulai");
mysqli_stmt_bind_param($stmt_jadwal_pengganti, "s", $kode_asisten);
mysqli_stmt_execute($stmt_jadwal_pengganti);
$jadwal_pengganti = mysqli_stmt_get_result($stmt_jadwal_pengganti);

// Gabungkan jadwal (hindari duplikasi)
$jadwal_list = [];
$jadwal_ids = [];

while ($row = mysqli_fetch_assoc($jadwal_reguler)) {
    // Skip jika jadwal ini adalah jadwal yang kita gantikan (sudah di-update oleh admin)
    $stmt_cek = mysqli_prepare($conn, "SELECT id FROM absen_asisten 
                                        WHERE jadwal_id = ? 
                                        AND pengganti = ? 
                                        AND status IN ('izin', 'sakit')
                                        AND status_approval = 'approved'");
    mysqli_stmt_bind_param($stmt_cek, "is", $row['id'], $kode_asisten);
    mysqli_stmt_execute($stmt_cek);
    $cek_sbg_pengganti = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
    
    if ($cek_sbg_pengganti) {
        continue; // Akan diambil dari query pengganti
    }
    $jadwal_list[] = $row;
    $jadwal_ids[] = $row['id'];
}

while ($row = mysqli_fetch_assoc($jadwal_pengganti)) {
    if (!in_array($row['id'], $jadwal_ids)) {
        $jadwal_list[] = $row;
        $jadwal_ids[] = $row['id'];
    }
}

// AJAX Handler untuk refresh tabel presensi (Real-time & Ringan)
if (isset($_GET['ajax_refresh']) && $jadwal_id) {
    $no = 1;
    if (mysqli_num_rows($presensi_list) > 0) {
        while ($p = mysqli_fetch_assoc($presensi_list)) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . $p['nim'] . '</td>';
            echo '<td>' . $p['nama'] . '</td>';
            echo '<td>';
            if ($p['status'] && $p['status'] != 'belum') {
                $bg_class = $p['status'] == 'hadir' ? 'success' : ($p['status'] == 'izin' ? 'warning' : ($p['status'] == 'sakit' ? 'info' : 'danger'));
                echo '<span class="badge badge-' . $p['status'] . ' bg-' . $bg_class . '">' . ucfirst($p['status']) . '</span>';
                
                // Cek keterlambatan (AJAX)
                if ($p['status'] == 'hadir' && $p['waktu_presensi']) {
                    $jam_mulai_ts = strtotime($jadwal_aktif['tanggal'] . ' ' . $jadwal_aktif['jam_mulai']);
                    $presensi_ts = strtotime($p['waktu_presensi']);
                    $telat_menit = ceil(($presensi_ts - $jam_mulai_ts) / 60);
                    if ($telat_menit > 15) {
                        echo '<br><span class="badge bg-danger mt-1" style="font-size:0.65rem">Telat ' . $telat_menit . 'm (Sanksi)</span>';
                    } elseif ($telat_menit > 0) {
                        echo '<br><span class="badge bg-warning text-dark mt-1" style="font-size:0.65rem">Telat ' . $telat_menit . 'm</span>';
                    }
                }
            } else {
                echo '<span class="badge bg-secondary">Belum Presensi</span>';
            }
            echo '</td>';
            echo '<td>' . ($p['waktu_presensi'] ? date('H:i:s', strtotime($p['waktu_presensi'])) : '-') . '</td>';
            echo '<td>' . ($p['metode'] ? ucfirst($p['metode']) : '-') . '</td>';
            echo '</tr>';
        }
    }
    exit; // Stop eksekusi agar tidak me-load header/footer
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* Welcome Banner Modern */
.welcome-banner-monitoring {
    background: var(--banner-gradient);
    border-radius: 24px;
    padding: 40px;
    color: white;
    box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
    animation: fadeInUp 0.5s ease;
    position: relative;
    overflow: hidden;
}

.welcome-banner-monitoring::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse-glow-monitoring 4s ease-in-out infinite;
}

@keyframes pulse-glow-monitoring {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 0.6; }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.welcome-banner-monitoring h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
}

.welcome-banner-monitoring .banner-subtitle {
    font-size: 16px;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

.welcome-banner-monitoring .banner-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    position: relative;
    z-index: 1;
}

.welcome-banner-monitoring .banner-badge {
    display: inline-block;
    padding: 8px 20px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
}

.welcome-banner-monitoring .btn-banner {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.welcome-banner-monitoring .btn-banner:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    color: white;
}

/* Dark Mode Support */
[data-theme="dark"] .welcome-banner-monitoring {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* Responsive Design */
@media (max-width: 768px) {
    .welcome-banner-monitoring {
        padding: 30px;
    }
    .welcome-banner-monitoring h1 {
        font-size: 28px;
    }
}
@media (max-width: 576px) {
    .welcome-banner-monitoring {
        padding: 20px;
        border-radius: 16px;
    }
    
    .welcome-banner-monitoring h1 {
        font-size: 24px;
    }
    
    .welcome-banner-monitoring .banner-icon {
        width: 50px;
        height: 50px;
        font-size: 22px;
    }
    
    .welcome-banner-monitoring .banner-subtitle {
        font-size: 14px;
    }
    
    .welcome-banner-monitoring .btn-banner {
        width: 100%;
        justify-content: center;
        margin-top: 10px;
    }
}

/* Dark Mode Fixes for Monitoring */
[data-theme="dark"] .card.border-primary {
    border-color: #66b0ff !important;
}
[data-theme="dark"] .card.border-primary:hover {
    background-color: rgba(102, 176, 255, 0.1);
}
[data-theme="dark"] .btn-outline-primary {
    color: #66b0ff;
    border-color: #66b0ff;
}
[data-theme="dark"] .btn-outline-primary:hover {
    color: #212529;
    background-color: #66b0ff;
}
/* Mobile Card Borders */
[data-theme="dark"] .card.border-success { border-color: #2ecc71 !important; }
[data-theme="dark"] .card.border-warning { border-color: #ffc107 !important; }

/* Fix Nested Cards & Text Visibility */
[data-theme="dark"] .card .card {
    background-color: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
}
[data-theme="dark"] .card-body {
    color: var(--text-main);
}

/* Fix Primary Button & Badges */
[data-theme="dark"] .btn-primary {
    background-color: #3a8fd9;
    border-color: #3a8fd9;
    color: #fff !important;
}
[data-theme="dark"] .btn-primary:hover {
    background-color: #2c7bc0;
    border-color: #2c7bc0;
}
[data-theme="dark"] .badge.bg-warning,
[data-theme="dark"] .badge.bg-info {
    color: #212529 !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <!-- Welcome Banner -->
                <div class="welcome-banner-monitoring mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-tv"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Monitoring Presensi</h1>
                                    <p class="banner-subtitle mb-0">Pantau kehadiran mahasiswa secara real-time</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="banner-badge">
                                <i class="fas fa-chart-line me-1"></i>Live Data
                            </span>
                            <?php if ($jadwal_aktif): ?>
                                <a href="index.php?page=asisten_qrcode&jadwal=<?= $jadwal_id ?>" class="btn btn-banner">
                                    <i class="fas fa-qrcode me-2"></i>Lihat QR Code
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
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
                                                        <?php 
                                                        // Cek keterlambatan (Desktop)
                                                        if ($p['status'] == 'hadir' && $p['waktu_presensi']) {
                                                            $jam_mulai_ts = strtotime($jadwal_aktif['tanggal'] . ' ' . $jadwal_aktif['jam_mulai']);
                                                            $presensi_ts = strtotime($p['waktu_presensi']);
                                                            $telat_menit = ceil(($presensi_ts - $jam_mulai_ts) / 60);
                                                            if ($telat_menit > 15) {
                                                                echo '<br><span class="badge bg-danger mt-1" style="font-size:0.65rem">Telat ' . $telat_menit . 'm (Sanksi)</span>';
                                                            } elseif ($telat_menit > 0) {
                                                                echo '<br><span class="badge bg-warning text-dark mt-1" style="font-size:0.65rem">Telat ' . $telat_menit . 'm</span>';
                                                            }
                                                        }
                                                        ?>
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
                                                        <?php 
                                                        // Cek keterlambatan (Mobile)
                                                        if ($p['status'] == 'hadir' && $p['waktu_presensi']) {
                                                            $jam_mulai_ts = strtotime($jadwal_aktif['tanggal'] . ' ' . $jadwal_aktif['jam_mulai']);
                                                            $presensi_ts = strtotime($p['waktu_presensi']);
                                                            $telat_menit = ceil(($presensi_ts - $jam_mulai_ts) / 60);
                                                            if ($telat_menit > 15) {
                                                                echo '<span class="badge bg-danger ms-1">Telat ' . $telat_menit . 'm</span>';
                                                            } elseif ($telat_menit > 0) {
                                                                echo '<span class="badge bg-warning text-dark ms-1">Telat ' . $telat_menit . 'm</span>';
                                                            }
                                                        }
                                                        ?>
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
                        // Auto refresh menggunakan AJAX setiap 5 detik (Real-time & Ringan)
                        // Hanya memperbarui isi tabel, tidak reload seluruh halaman
                        setInterval(function() {
                            fetch('index.php?page=asisten_monitoring&jadwal=<?= $jadwal_id ?>&ajax_refresh=1')
                                .then(response => response.text())
                                .then(html => {
                                    if(html.trim()) document.querySelector('table tbody').innerHTML = html;
                                });
                        }, 5000);
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>