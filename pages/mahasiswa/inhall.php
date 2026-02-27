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

// Hitung total ketidakhadiran untuk menentukan status Inhal/Gugur
// Gunakan logika dinamis agar jadwal yang sudah lewat tapi belum ada record tetap terhitung Alpha
$q_absensi = mysqli_query($conn, "SELECT 
                                    SUM(CASE 
                                        WHEN pm.status = 'alpha' THEN 1
                                        WHEN (pm.status IS NULL OR pm.status = 'belum') 
                                             AND CONCAT(j.tanggal, ' ', ADDTIME(j.jam_mulai, SEC_TO_TIME(" . (BATAS_TELAT * 60) . "))) < NOW() 
                                        THEN 1
                                        ELSE 0 
                                    END) as total_alpha,
                                    SUM(CASE WHEN pm.status IN ('izin', 'sakit') THEN 1 ELSE 0 END) as total_valid
                                  FROM jadwal j
                                  LEFT JOIN presensi_mahasiswa pm ON j.id = pm.jadwal_id AND pm.nim = '$nim'
                                  JOIN mahasiswa m ON m.nim = '$nim'
                                  WHERE j.kode_kelas = '$kelas'
                                  AND j.jenis != 'inhall'
                                  AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)
                                  AND (
                                      pm.id IS NOT NULL 
                                      OR 
                                      ((j.sesi = 0 OR j.sesi = '$sesi') AND NOT EXISTS (
                                          SELECT 1 FROM presensi_mahasiswa pm2 
                                          JOIN jadwal j2 ON pm2.jadwal_id = j2.id 
                                          WHERE pm2.nim = '$nim' 
                                          AND j2.kode_mk = j.kode_mk 
                                          AND j2.pertemuan_ke = j.pertemuan_ke
                                          AND j2.id != j.id
                                      ))
                                  )");
$d_absensi = mysqli_fetch_assoc($q_absensi);
$total_alpha = $d_absensi['total_alpha'] ?? 0;
$total_valid = $d_absensi['total_valid'] ?? 0;
$total_absen = $total_alpha + $total_valid;

$status_inhal_info = [
    'status' => 'AMAN',
    'class' => 'success',
    'msg' => "Jumlah ketidakhadiran: $total_absen. Tetap pertahankan kehadiran Anda."
];

if ($total_absen > 3) {
    $status_inhal_info = ['status' => 'GUGUR', 'class' => 'danger', 'msg' => "Anda telah tidak masuk $total_absen kali (> 3 kali). Mohon hubungi Dosen/Admin."];
} else {
    // Cek hak Inhal dari Valid Absen (Izin/Sakit)
    $hak_inhal_msg = "";
    $status_label = "AMAN";
    $status_class = "success";
    
    if ($total_valid == 3) {
        $status_label = "WAJIB INHAL";
        $status_class = "warning";
        $hak_inhal_msg = "Anda tidak masuk 3 kali (Izin/Sakit). Anda <strong>WAJIB</strong> mengikuti 2 kali Inhal.";
    } elseif ($total_valid == 2) {
        $status_label = "BOLEH INHAL";
        $status_class = "info";
        $hak_inhal_msg = "Anda tidak masuk 2 kali (Izin/Sakit). Anda <strong>BOLEH</strong> mengambil 1 kali Inhal (Opsional).";
    }
    
    // Cek Alpha dan gabungkan pesan jika perlu
    if ($total_alpha > 0) {
        $alpha_msg = "Anda memiliki $total_alpha Alpha (Tanpa Keterangan). Hak Inhal hangus untuk pertemuan tersebut.";
        
        if ($total_absen == 3) {
            $status_inhal_info = ['status' => 'KRITIS', 'class' => 'danger', 'msg' => "<strong>PERINGATAN KERAS:</strong> Total absen 3 kali (termasuk $total_alpha Alpha). 1 kali lagi tidak masuk, Anda GUGUR.<br>$alpha_msg" . ($hak_inhal_msg ? "<br><br>$hak_inhal_msg" : "")];
        } else {
            // Total absen < 3, tapi ada Alpha
            $final_status = ($status_label == 'AMAN') ? 'PERINGATAN' : $status_label . ' (ADA ALPHA)';
            $status_inhal_info = ['status' => $final_status, 'class' => 'warning', 'msg' => $alpha_msg . ($hak_inhal_msg ? "<br><br>$hak_inhal_msg" : "")];
        }
    } elseif ($hak_inhal_msg) {
        // Tidak ada Alpha, gunakan status Inhal murni
        $status_inhal_info = ['status' => $status_label, 'class' => $status_class, 'msg' => $hak_inhal_msg];
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
    /* ===== WELCOME BANNER INHALL ===== */
    .welcome-banner-inhall {
        background: var(--banner-gradient);
    border-radius: 24px;
    padding: 40px;
    color: white;
    box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
    animation: fadeInUp 0.5s ease;
    position: relative;
    overflow: hidden;
    }

    .welcome-banner-inhall::before {
       content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse-glow-bantuan 4s ease-in-out infinite;
    }

    @keyframes pulse-glow-inhall {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.05); opacity: 0.6; }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .welcome-banner-inhall h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .welcome-banner-inhall .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
        margin-bottom: 0;
    }

    .welcome-banner-inhall .banner-icon {
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

    .welcome-banner-inhall .banner-badge {
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

    /* Dark Mode Fixes for Warning Badge & Banner */
    [data-theme="dark"] .welcome-banner-inhall {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    [data-theme="dark"] .text-warning {
        color: #ffda6a !important; 
    }
    [data-theme="dark"] .badge.bg-warning.bg-opacity-10 {
        color: #ffda6a !important;
        background-color: rgba(255, 218, 106, 0.1) !important;
        border-color: rgba(255, 218, 106, 0.3) !important;
    }
    
    .text-warning {
        color: var(--putih); /* Sesuaikan variabel jika perlu */
    }

    /* Responsive Design Banner */
    @media (max-width: 576px) {
        .welcome-banner-inhall {
            padding: 24px;
            border-radius: 16px;
        }
        .welcome-banner-inhall h1 {
            font-size: 24px;
        }
        .welcome-banner-inhall .banner-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <div class="welcome-banner-inhall mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Layanan Inhall</h1>
                                    <p class="banner-subtitle">Cek tanggungan dan atur jadwal penggantian praktikum Anda.</p>
                                </div>
                            </div>
                        </div>
                        <span class="banner-badge">
                            <i class="fas fa-calendar-check me-1"></i>Pengajuan Inhall Terbuka Setiap Saat
                        </span>
                    </div>
                </div>

                <!-- Status Akademik / Inhal -->
                <div class="alert alert-<?= $status_inhal_info['class'] ?> border-<?= $status_inhal_info['class'] ?> shadow-sm mb-4 d-flex align-items-center">
                    <div class="fs-1 me-3"><i class="fas fa-info-circle"></i></div>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1"><?= $status_inhal_info['status'] ?></h5>
                        <p class="mb-0"><?= $status_inhal_info['msg'] ?></p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header">
                                <h6 class="mb-0 fw-bold status-warning">
                                    <i class="fas fa-exclamation-circle me-2"></i>Perlu Diganti
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if ($total_absen > 3): ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="fas fa-ban fa-3x text-danger opacity-50"></i>
                                        </div>
                                        <h6 class="fw-bold text-danger">Status GUGUR</h6>
                                        <p class="text-muted small mb-0 px-3">Anda tidak perlu mengganti pertemuan karena status akademik Anda sudah Gugur.</p>
                                    </div>
                                <?php elseif (count($perlu_inhall_data) > 0): ?>
                                        <?php foreach ($perlu_inhall_data as $p): ?>
                                            <div class="p-3 hover-bg-light transition-all">
                                                <div class="d-flex flex-wrap flex-md-nowrap gap-3 align-items-center">
                                                    <div class="d-flex gap-3 flex-grow-1 w-100 w-md-auto align-items-start">
                                                        <div class="flex-shrink-0 text-center rounded p-1 bg-light border d-flex flex-column justify-content-center" style="width: 50px; height: 50px;">
                                                            <div class="fw-bold text-warning" style="font-size: 1.2rem; line-height: 1;"><?= date('d', strtotime($p['tanggal'])) ?></div>
                                                            <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?= date('M', strtotime($p['tanggal'])) ?></div>
                                                        </div>
                                                        
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h6 class="mb-1 fw-bold text-dark text-truncate" title="<?= $p['nama_mk'] ?>"><?= $p['nama_mk'] ?></h6>
                                                            <div class="small text-muted">
                                                                <div class="mb-1 text-truncate" title="<?= $p['materi'] ?>"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 me-1">P<?= $p['pertemuan_ke'] ?></span> <?= $p['materi'] ?></div>
                                                                <?php if ($p['alasan_izin']): ?>
                                                                    <?php 
                                                                    // Cek apakah alasan mengandung kata "Admin" (dari sistem otomatis)
                                                                    $is_admin_change = strpos($p['alasan_izin'], 'Diubah oleh Admin') !== false;
                                                                    $icon_cls = $is_admin_change ? 'fas fa-user-shield text-info' : 'far fa-comment-dots';
                                                                    ?>
                                                                    <div class="d-flex align-items-center text-truncate" title="<?= $p['alasan_izin'] ?>"><i class="<?= $icon_cls ?> me-2 text-center" style="width:16px"></i> <?= $p['alasan_izin'] ?></div>
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
                    
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header">
                                <h6 class="mb-0 fw-bold status-warning"><i class="fas fa-calendar-check me-2"></i>Jadwal Tersedia</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if ($total_absen > 3): ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="fas fa-lock fa-3x text-secondary opacity-25"></i>
                                        </div>
                                        <h6 class="fw-bold text-secondary">Terkunci</h6>
                                        <p class="text-muted small mb-0">Akses Inhall ditutup karena status Gugur.</p>
                                    </div>
                                <?php elseif (mysqli_num_rows($jadwal_inhall) > 0): ?>
                                        <?php while ($j = mysqli_fetch_assoc($jadwal_inhall)): ?>
                                            <div class="p-3 hover-bg-light transition-all">
                                                <div class="d-flex flex-wrap flex-md-nowrap gap-3 align-items-center">
                                                    <div class="d-flex gap-3 flex-grow-1 w-100 w-md-auto align-items-start">
                                                        <div class="flex-shrink-0 text-center rounded p-1 bg-light border d-flex flex-column justify-content-center" style="width: 50px; height: 50px;">
                                                            <div class="fw-bold text-primary" style="font-size: 1.2rem; line-height: 1;"><?= date('d', strtotime($j['tanggal'])) ?></div>
                                                            <div class="small text-muted text-uppercase" style="font-size: 0.65rem;"><?= date('M', strtotime($j['tanggal'])) ?></div>
                                                        </div>
                                                        
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h6 class="mb-1 fw-bold text-dark text-truncate" title="<?= $j['nama_mk'] ?>"><?= $j['nama_mk'] ?></h6>
                                                            <div class="small text-muted">
                                                                <div class="mb-1 d-flex align-items-center"><i class="far fa-clock me-2 text-center" style="width:16px"></i> <?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></div>
                                                                <div class="d-flex align-items-center"><i class="fas fa-map-marker-alt me-2 text-center" style="width:16px"></i> <?= $j['nama_lab'] ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
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