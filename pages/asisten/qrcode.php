<?php
$page = 'asisten_qrcode';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

// Jika ada jadwal yang dipilih
$jadwal_id = isset($_GET['jadwal']) ? (int)$_GET['jadwal'] : 0;
$jadwal_aktif = null;
$qr_session = null;

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
        // Cek apakah asisten ini sebagai pengganti di jadwal ini
        $cek_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                                   WHERE jadwal_id = '$jadwal_id' 
                                                                   AND pengganti = '$kode_asisten'
                                                                   AND status IN ('izin', 'sakit')"));
        $is_pengganti = $cek_pengganti ? true : false;
    }
    
    // TIDAK otomatis catat hadir saat buka halaman
    // Hadir dicatat saat Generate QR
    
    // Cek QR session yang sudah ada
    $qr_session = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM qr_code_session 
                                                           WHERE jadwal_id = '$jadwal_id' AND expired_at > NOW()
                                                           ORDER BY id DESC LIMIT 1"));
}

// Generate QR baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $qr_code = generate_qr_code();
    
    // Ambil jam selesai jadwal untuk expired time
    $jadwal_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tanggal, jam_selesai FROM jadwal WHERE id = '$jadwal_id'"));
    $expired = $jadwal_info['tanggal'] . ' ' . $jadwal_info['jam_selesai']; // Expired saat jadwal selesai
    
    // Cek apakah ini jadwal sebagai pengganti
    $cek_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                               WHERE jadwal_id = '$jadwal_id' 
                                                               AND pengganti = '$kode_asisten'
                                                               AND status IN ('izin', 'sakit')"));
    $is_pengganti_gen = $cek_pengganti ? true : false;
    
    // CATAT HADIR ASISTEN saat Generate QR (bukan saat buka halaman)
    catat_hadir_asisten($kode_asisten, $jadwal_id, $is_pengganti_gen);
    
    // INIT presensi 'belum' untuk semua mahasiswa di jadwal ini
    init_presensi_jadwal($jadwal_id);
    
    // Hapus QR session lama untuk jadwal ini
    mysqli_query($conn, "DELETE FROM qr_code_session WHERE jadwal_id = '$jadwal_id'");
    
    mysqli_query($conn, "INSERT INTO qr_code_session (jadwal_id, qr_code, expired_at) VALUES ('$jadwal_id', '$qr_code', '$expired')");
    
    log_aktivitas($_SESSION['user_id'], 'GENERATE_QR', 'qr_code_session', mysqli_insert_id($conn), "QR Code untuk jadwal #$jadwal_id, expired: $expired");
    
    header("Location: index.php?page=asisten_qrcode&jadwal=$jadwal_id");
    exit;
}

// Ambil jadwal hari ini (yang belum selesai)
// Jadwal hilang tepat setelah jam_selesai (tanpa toleransi)
$today = date('Y-m-d');
$now_time = date('H:i:s');

// Jadwal sendiri
$jadwal_sendiri = mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk, 'sendiri' as tipe_jadwal, NULL as asisten_asli
                                     FROM jadwal j 
                                     LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                     LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                     LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                     WHERE j.tanggal = '$today' 
                                     AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                     AND j.jam_selesai >= '$now_time'
                                     ORDER BY j.jam_mulai");

// Jadwal sebagai pengganti
$jadwal_pengganti = mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk, 'pengganti' as tipe_jadwal, a.nama as asisten_asli
                                          FROM absen_asisten aa
                                          JOIN jadwal j ON aa.jadwal_id = j.id
                                          JOIN asisten a ON aa.kode_asisten = a.kode_asisten
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                          WHERE aa.pengganti = '$kode_asisten'
                                          AND aa.status IN ('izin', 'sakit')
                                          AND j.tanggal = '$today'
                                          AND j.jam_selesai >= '$now_time'
                                          ORDER BY j.jam_mulai");

// Gabungkan
$jadwal_list = [];
while ($j = mysqli_fetch_assoc($jadwal_sendiri)) { $jadwal_list[] = $j; }
while ($j = mysqli_fetch_assoc($jadwal_pengganti)) { $jadwal_list[] = $j; }
?>
<?php include 'includes/header.php'; ?>

<style>
@media (max-width: 767.98px) {
    .qr-page .card-body {
        padding: 1rem !important;
    }
    
    .qr-page h5 {
        font-size: 1rem;
    }
    
    .qr-page .info-jadwal {
        font-size: 0.85rem;
    }
    
    .qr-page #qrcode canvas {
        max-width: 220px !important;
        height: auto !important;
    }
    
    .qr-page .alert {
        padding: 0.5rem 0.75rem !important;
    }
    
    .qr-page .alert .fs-4 {
        font-size: 1.1rem !important;
    }
    
    .qr-page #countdown {
        font-size: 1.25rem !important;
    }
    
    .qr-page .btn-action-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .qr-page .btn-action-group .btn {
        width: 100%;
        margin: 0 !important;
    }
    
    .qr-page .btn-fullscreen-group .col-md-6 {
        margin-bottom: 0.5rem;
    }
    
    .jadwal-btn-mobile {
        flex: 1 1 calc(50% - 0.5rem);
        min-width: 100px;
        text-align: center;
        padding: 0.5rem !important;
    }
    
    .jadwal-btn-mobile small {
        font-size: 0.7rem;
    }
}
@media (min-width: 768px) {
    .btn-action-group form,
    .btn-action-group a {
        width: auto !important;
        display: inline-block !important;
        margin-right: 0.5rem;
    }
}

/* Dark Mode Fixes */
[data-theme="dark"] .list-group-item {
    background-color: var(--bg-card);
    border-color: var(--border-color);
    color: var(--text-main);
}
[data-theme="dark"] .list-group-item-action:hover {
    background-color: rgba(255,255,255,0.05);
    color: var(--text-main);
}
[data-theme="dark"] .list-group-item.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: #fff;
}
[data-theme="dark"] .list-group-item.active small {
    color: rgba(255,255,255,0.8);
}
[data-theme="dark"] .btn-warning, 
[data-theme="dark"] .btn-info {
    color: #212529 !important;
}
[data-theme="dark"] .btn-warning { background-color: #ffc107; border-color: #ffc107; }
[data-theme="dark"] .btn-info { background-color: #0dcaf0; border-color: #0dcaf0; }
[data-theme="dark"] .btn-warning:hover { background-color: #ffb300; border-color: #ffb300; }
[data-theme="dark"] .btn-info:hover { background-color: #0bacd9; border-color: #0bacd9; }

[data-theme="dark"] .alert-info {
    background-color: rgba(13, 202, 240, 0.15);
    border-color: rgba(13, 202, 240, 0.3);
    color: #6edff6;
}
[data-theme="dark"] .alert-info .text-muted {
    color: rgba(255,255,255,0.6) !important;
}
/* Fix QR Container Background in Dark Mode */
[data-theme="dark"] .qr-container {
    background-color: var(--bg-card);
    border: 1px solid var(--border-color);
    box-shadow: none;
    color: var(--text-main);
}
/* Fix Dark Button Visibility in Dark Mode */
[data-theme="dark"] .btn-dark {
    background-color: #4a5568;
    border-color: #4a5568;
    color: #fff;
}
[data-theme="dark"] .btn-dark:hover {
    background-color: #3e4a5c;
    border-color: #3e4a5c;
}
/* Fix Other Buttons in Dark Mode */
[data-theme="dark"] .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}
[data-theme="dark"] .btn-secondary:hover {
    background-color: #5c636a;
    border-color: #5c636a;
}
[data-theme="dark"] .btn-primary {
    background-color: #3a8fd9;
    border-color: #3a8fd9;
    color: #fff !important;
}
[data-theme="dark"] .btn-primary:hover {
    background-color: #2c7bc0;
    border-color: #2c7bc0;
}
[data-theme="dark"] .btn-outline-primary {
    color: #66b0ff;
    border-color: #66b0ff;
}
[data-theme="dark"] .btn-outline-primary:hover {
    background-color: #66b0ff;
    color: #212529;
}
[data-theme="dark"] .btn-success {
    background-color: #2ecc71;
    border-color: #2ecc71;
    color: #fff !important;
}
[data-theme="dark"] .btn-success:hover {
    background-color: #27ae60;
    border-color: #27ae60;
}

</style>

<div class="container-fluid qr-page">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-qrcode me-2"></i>Generate QR Code Presensi</h4>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <!-- Mobile: Pilih Jadwal di atas -->
                    <div class="col-12 d-md-none mb-3">
                        <div class="card">
                            <div class="card-header py-2">
                                <i class="fas fa-calendar-day me-2"></i>Jadwal Hari Ini
                            </div>
                            <div class="card-body p-2">
                                <?php if (count($jadwal_list) > 0): ?>
                                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                                        <?php foreach ($jadwal_list as $j): ?>
                                            <a href="index.php?page=asisten_qrcode&jadwal=<?= $j['id'] ?>" 
                                               class="btn btn-sm jadwal-btn-mobile <?= $jadwal_id == $j['id'] ? 'btn-primary' : 'btn-outline-primary' ?>">
                                                <strong><?= $j['nama_kelas'] ?></strong>
                                                <?php if ($j['tipe_jadwal'] == 'pengganti'): ?>
                                                    <span class="badge bg-info" style="font-size: 0.6rem;">P</span>
                                                <?php endif; ?>
                                                <small class="d-block text-nowrap"><?= format_waktu($j['jam_mulai']) ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-2">
                                        <i class="fas fa-calendar-times text-muted mb-2"></i>
                                        <p class="text-muted small mb-0">Tidak ada jadwal hari ini</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Desktop: Sidebar Jadwal -->
                    <div class="col-md-4 mb-4 d-none d-md-block">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-calendar-day me-2"></i>Jadwal Hari Ini
                            </div>
                            <div class="card-body">
                                <?php if (count($jadwal_list) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($jadwal_list as $j): ?>
                                            <a href="index.php?page=asisten_qrcode&jadwal=<?= $j['id'] ?>" 
                                               class="list-group-item list-group-item-action <?= $jadwal_id == $j['id'] ? 'active' : '' ?>">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?= $j['nama_kelas'] ?></strong>
                                                        <?php if (!empty($j['is_pengganti'])): ?>
                                                            <span class="badge bg-info ms-1">Pengganti</span>
                                                        <?php endif; ?>
                                                        <br><small><?= $j['nama_mk'] ?></small>
                                                    </div>
                                                    <small><?= format_waktu($j['jam_mulai']) ?></small>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">Tidak ada jadwal hari ini</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- QR Code Display -->
                    <div class="col-12 col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-qrcode me-2"></i>QR Code Presensi
                            </div>
                            <div class="card-body">
                                <?php if ($jadwal_aktif): ?>
                                    <div class="text-center mb-3">
                                        <h5 class="mb-2"><?= $jadwal_aktif['nama_mk'] ?></h5>
                                        <span class="badge bg-primary mb-2"><?= $jadwal_aktif['nama_kelas'] ?></span>
                                        <p class="text-muted info-jadwal mb-0">
                                            <?= $jadwal_aktif['materi'] ?>
                                        </p>
                                        <p class="text-muted info-jadwal small">
                                            <i class="fas fa-calendar me-1"></i><?= format_tanggal($jadwal_aktif['tanggal']) ?>
                                            <span class="mx-1">•</span>
                                            <i class="fas fa-clock me-1"></i><?= format_waktu($jadwal_aktif['jam_mulai']) ?> - <?= format_waktu($jadwal_aktif['jam_selesai']) ?>
                                            <span class="mx-1">•</span>
                                            <i class="fas fa-map-marker-alt me-1"></i><?= $jadwal_aktif['nama_lab'] ?>
                                        </p>
                                    </div>
                                    
                                    <?php if ($qr_session): ?>
                                        <div class="qr-container text-center">
                                            <div id="qrcode" class="mb-3 d-flex justify-content-center"></div>
                                            <p class="text-success mb-2"><i class="fas fa-check-circle me-1"></i>QR Code Aktif</p>
                                            
                                            <!-- Kode untuk input manual -->
                                            <div class="alert alert-info py-2 px-3 mb-2">
                                                <small class="d-block text-muted">Kode input manual:</small>
                                                <strong class="fs-4 font-monospace user-select-all"><?= $qr_session['qr_code'] ?></strong>
                                            </div>
                                            
                                            <p class="text-muted mb-1">
                                                <small><i class="fas fa-hourglass-half me-1"></i>Berlaku sampai: <?= date('H:i', strtotime($qr_session['expired_at'])) ?> WIB</small>
                                            </p>
                                            <div id="countdown" class="h4 text-primary mb-0"></div>
                                        </div>
                                        
                                        <script>
                                            // Generate QR Code - tunggu DOM ready
                                            document.addEventListener('DOMContentLoaded', function() {
                                                var qrContainer = document.getElementById('qrcode');
                                                var canvas = document.createElement('canvas');
                                                
                                                // Responsive QR size
                                                var qrSize = window.innerWidth < 576 ? 200 : 280;
                                                
                                                QRCode.toCanvas(canvas, '<?= $qr_session['qr_code'] ?>', {
                                                    width: qrSize,
                                                    margin: 2,
                                                    color: {
                                                        dark: '#000000',
                                                        light: '#ffffff'
                                                    }
                                                }, function(error) {
                                                    if (error) {
                                                        console.error(error);
                                                        qrContainer.innerHTML = '<p class="text-danger">Gagal generate QR Code</p>';
                                                        return;
                                                    }
                                                    canvas.style.border = '5px solid #0066cc';
                                                    canvas.style.borderRadius = '10px';
                                                    qrContainer.appendChild(canvas);
                                                });
                                                
                                                // Countdown
                                                var expired = new Date('<?= $qr_session['expired_at'] ?>').getTime();
                                                var countdownEl = document.getElementById('countdown');
                                                
                                                var countdown = setInterval(function() {
                                                    var now = new Date().getTime();
                                                    var distance = expired - now;
                                                    
                                                    if (distance < 0) {
                                                        clearInterval(countdown);
                                                        countdownEl.innerHTML = '<span class="text-danger">QR Code Expired! Silakan generate ulang.</span>';
                                                        return;
                                                    }
                                                    
                                                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                                    
                                                    countdownEl.innerHTML = 
                                                        String(hours).padStart(2, '0') + ':' + 
                                                        String(minutes).padStart(2, '0') + ':' + 
                                                        String(seconds).padStart(2, '0');
                                                }, 1000);
                                            });
                                        </script>
                                        
                                        <div class="text-center mt-3 btn-action-group">

    <form method="POST">
        <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
        <button type="submit" name="generate" class="btn btn-warning w-100 mb-2">
            <i class="fas fa-sync me-1"></i>Generate Ulang
        </button>
    </form>

    <a href="index.php?page=asisten_monitoring&jadwal=<?= $jadwal_id ?>" 
       class="btn btn-success w-100 mb-2">
        <i class="fas fa-tv me-1"></i>Monitoring
    </a>

    <a href="index.php?page=asisten_presensi_manual&jadwal=<?= $jadwal_id ?>" 
       class="btn btn-info w-100 mb-2">
        <i class="fas fa-edit me-1"></i>Manual
    </a>

</div>

                                        
                                        <hr class="my-3">
                                        
                                        <div class="row text-center btn-fullscreen-group g-2">
                                            <div class="col-6 col-md-6">
                                                <button onclick="openFullscreen()" class="btn btn-dark btn-sm w-100">
                                                    <i class="fas fa-expand me-1"></i><span class="d-none d-sm-inline">Fullscreen</span><span class="d-sm-none">Full</span>
                                                </button>
                                            </div>
                                            <div class="col-6 col-md-6">
                                                <button onclick="downloadQR()" class="btn btn-secondary btn-sm w-100">
                                                    <i class="fas fa-download me-1"></i><span class="d-none d-sm-inline">Download</span><span class="d-sm-none">Save</span>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <script>
                                        function openFullscreen() {
                                            var win = window.open('', '_blank');
                                            win.document.write(`
                                                <!DOCTYPE html>
                                                <html>
                                                <head>
                                                    <title>QR Code Presensi</title>
                                                    <style>
                                                        body { 
                                                            margin: 0; 
                                                            display: flex; 
                                                            flex-direction: column;
                                                            justify-content: center; 
                                                            align-items: center; 
                                                            min-height: 100vh; 
                                                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                            font-family: Arial, sans-serif;
                                                        }
                                                        .container { 
                                                            text-align: center; 
                                                            background: white; 
                                                            padding: 40px 60px; 
                                                            border-radius: 20px; 
                                                            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                                                        }
                                                        h1 { color: #333; margin-bottom: 10px; }
                                                        h2 { color: #666; font-weight: normal; margin-bottom: 30px; }
                                                        .info { color: #888; margin-top: 20px; }
                                                        #countdown { font-size: 48px; color: #0066cc; margin-top: 20px; font-weight: bold; }
                                                        .expired { color: #dc3545 !important; font-size: 24px !important; }
                                                        canvas { border: 8px solid #0066cc; border-radius: 15px; }
                                                    </style>
                                                    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"><\/script>
                                                </head>
                                                <body>
                                                    <div class="container">
                                                        <h1><?= $jadwal_aktif['nama_mk'] ?></h1>
                                                        <h2><?= $jadwal_aktif['nama_kelas'] ?> - <?= $jadwal_aktif['nama_lab'] ?></h2>
                                                        <div id="qrcode"></div>
                                                        <div class="info">Scan QR Code untuk presensi</div>
                                                        <div id="countdown"></div>
                                                    </div>
                                                    <script>
                                                        var canvas = document.createElement('canvas');
                                                        QRCode.toCanvas(canvas, '<?= $qr_session['qr_code'] ?>', { width: 350, margin: 2 }, function(error) {
                                                            if (!error) document.getElementById('qrcode').appendChild(canvas);
                                                        });
                                                        
                                                        var expired = new Date('<?= $qr_session['expired_at'] ?>').getTime();
                                                        setInterval(function() {
                                                            var now = new Date().getTime();
                                                            var distance = expired - now;
                                                            var el = document.getElementById('countdown');
                                                            if (distance < 0) {
                                                                el.className = 'expired';
                                                                el.innerHTML = 'QR CODE EXPIRED - Generate ulang!';
                                                                return;
                                                            }
                                                            var h = Math.floor((distance % (1000*60*60*24)) / (1000*60*60));
                                                            var m = Math.floor((distance % (1000*60*60)) / (1000*60));
                                                            var s = Math.floor((distance % (1000*60)) / 1000);
                                                            el.innerHTML = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                                                        }, 1000);
                                                    <\/script>
                                                </body>
                                                </html>
                                            `);
                                            win.document.close();
                                        }
                                        
                                        function downloadQR() {
                                            var canvas = document.querySelector('#qrcode canvas');
                                            if (canvas) {
                                                var link = document.createElement('a');
                                                link.download = 'qrcode_presensi_<?= $jadwal_id ?>.png';
                                                link.href = canvas.toDataURL();
                                                link.click();
                                            }
                                        }
                                        </script>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-qrcode fa-5x text-muted mb-3"></i>
                                            <p class="text-muted">Belum ada QR Code untuk sesi ini</p>
                                            <form method="POST">
                                                <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                                                <button type="submit" name="generate" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-plus me-2"></i>Generate QR Code
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Pilih jadwal terlebih dahulu</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
