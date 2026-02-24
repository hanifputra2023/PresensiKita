<?php
$page = 'mahasiswa_scanner';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kelas = $mahasiswa['kode_kelas'];
$sesi = $mahasiswa['sesi'] ?? 1;

// Cek apakah ada jadwal yang sedang berlangsung
$today = date('Y-m-d');
$now_time = date('H:i:s');
$toleransi_sebelum = TOLERANSI_SEBELUM; // menit sebelum jam_mulai
$toleransi_sesudah = TOLERANSI_SESUDAH; // menit setelah jam_selesai

// Inhall hanya ditampilkan untuk mahasiswa yang terdaftar di penggantian_inhall
$jadwal_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.*, l.nama_lab, mk.nama_mk, p.status as presensi_status
                                                         FROM jadwal j 
                                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                                         LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                         LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = '$nim'
                                                         WHERE j.tanggal = '$today' 
                                                         AND j.kode_kelas = '$kelas'
                                                         AND (j.sesi = 0 OR j.sesi = '$sesi')
                                                         AND SUBTIME(j.jam_mulai, SEC_TO_TIME($toleransi_sebelum * 60)) <= '$now_time'
                                                         AND ADDTIME(j.jam_selesai, SEC_TO_TIME($toleransi_sesudah * 60)) >= '$now_time'
                                                         AND (
                                                             j.jenis != 'inhall'
                                                             OR EXISTS (
                                                                 SELECT 1 FROM penggantian_inhall pi 
                                                                 JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                                                 WHERE pi.nim = '$nim' 
                                                                 AND pi.status IN ('terdaftar', 'hadir')
                                                                 AND jx.kode_mk = j.kode_mk
                                                             )
                                                             OR p.id IS NOT NULL
                                                         )
                                                         ORDER BY (CASE WHEN p.status IS NULL OR p.status = 'belum' THEN 0 ELSE 1 END), j.jam_mulai LIMIT 1"));

// Cek jadwal berikutnya jika tidak ada yang aktif
$jadwal_berikutnya = null;
if (!$jadwal_aktif) {
    $jadwal_berikutnya = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.*, l.nama_lab, mk.nama_mk
                                                                  FROM jadwal j 
                                                                  LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                                                  LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                                  WHERE j.tanggal = '$today' 
                                                                  AND j.kode_kelas = '$kelas'
                                                                  AND (j.sesi = 0 OR j.sesi = '$sesi')
                                                                  AND j.jam_mulai > '$now_time'
                                                                  AND (
                                                                      j.jenis != 'inhall'
                                                                      OR EXISTS (
                                                                          SELECT 1 FROM penggantian_inhall pi 
                                                                          JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                                                          WHERE pi.nim = '$nim' 
                                                                          AND pi.status IN ('terdaftar', 'hadir')
                                                                          AND jx.kode_mk = j.kode_mk
                                                                      )
                                                                  )
                                                                  ORDER BY j.jam_mulai LIMIT 1"));
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== WELCOME BANNER SCANNER ===== */
.welcome-banner-scanner {
    background: var(--banner-gradient);
    border-radius: 24px;
    padding: 40px;
    color: white;
    box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
    animation: fadeInUp 0.5s ease;
    position: relative;
    overflow: hidden;
}

.welcome-banner-scanner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse-glow-scanner 4s ease-in-out infinite;
}

@keyframes pulse-glow-scanner {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 0.6; }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.welcome-banner-scanner h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
}

.welcome-banner-scanner .banner-subtitle {
    font-size: 16px;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

.welcome-banner-scanner .banner-icon {
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

.welcome-banner-scanner .banner-badge {
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

/* Dark Mode Support */
[data-theme="dark"] .welcome-banner-scanner {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* Nav Tabs Theme Support */
.nav-tabs {
    border-bottom-color: var(--border-color);
}

.nav-tabs .nav-link {
    color: var(--text-main);
    border: 1px solid transparent;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover {
    border-color: var(--border-color);
    background-color: var(--bg-card);
}

.nav-tabs .nav-link.active {
    background-color: var(--bg-card);
    color: var(--primary-color, #0066cc);
    border-color: var(--border-color);
    border-bottom-color: var(--bg-card);
}

[data-theme="dark"] .nav-tabs {
    border-bottom-color: var(--border-color);
}

[data-theme="dark"] .nav-tabs .nav-link {
    color: var(--text-muted);
}

[data-theme="dark"] .nav-tabs .nav-link:hover {
    color: var(--text-main);
    background-color: rgba(255, 255, 255, 0.05);
    border-color: var(--border-color);
}

[data-theme="dark"] .nav-tabs .nav-link.active {
    background-color: var(--bg-card);
    color: var(--primary-color, #3a8fd9);
    border-color: var(--border-color);
    border-bottom-color: var(--bg-card);
}

/* Responsive Design */
@media (max-width: 576px) {
    .welcome-banner-scanner {
        padding: 24px;
        border-radius: 16px;
    }
    
    .welcome-banner-scanner h1 {
        font-size: 24px;
    }
    
    .welcome-banner-scanner .banner-icon {
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
                <!-- Welcome Banner -->
                <div class="welcome-banner-scanner mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Scan QR Code Presensi</h1>
                                    <p class="banner-subtitle mb-0">Lakukan presensi kehadiran praktikum dengan mudah</p>
                                </div>
                            </div>
                        </div>
                        <span class="banner-badge">
                            <i class="fas fa-camera me-1"></i>Presensi Digital
                        </span>
                    </div>
                </div>
                
                <?php if ($jadwal_aktif && $jadwal_aktif['presensi_status'] == 'hadir'): ?>
                    <!-- Sudah presensi -->
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                            <h4 class="text-success">Anda Sudah Presensi!</h4>
                            <p class="text-muted mb-3">
                                <?= $jadwal_aktif['nama_mk'] ?> - <?= $jadwal_aktif['materi'] ?><br>
                                <small><?= $jadwal_aktif['nama_lab'] ?></small>
                            </p>
                            <a href="index.php?page=mahasiswa_dashboard" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                
                <?php elseif ($jadwal_aktif && in_array($jadwal_aktif['presensi_status'], ['izin', 'sakit', 'alpha'])): ?>
                    <!-- Sudah izin/sakit/alpha -->
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-info-circle fa-5x text-<?= $jadwal_aktif['presensi_status'] == 'alpha' ? 'danger' : 'info' ?> mb-4"></i>
                            <h4>Status: <?= ucfirst($jadwal_aktif['presensi_status']) ?></h4>
                            <p class="text-muted mb-3">
                                <?php if ($jadwal_aktif['presensi_status'] == 'alpha'): ?>
                                    Anda tercatat alpha untuk jadwal ini.
                                <?php else: ?>
                                    Anda sudah mengajukan <?= $jadwal_aktif['presensi_status'] ?> untuk jadwal ini.<br>
                                    Silakan ikuti jadwal Inhall untuk mengganti.
                                <?php endif; ?>
                            </p>
                            <a href="index.php?page=mahasiswa_<?= $jadwal_aktif['presensi_status'] == 'alpha' ? 'riwayat' : 'inhall' ?>" class="btn btn-primary">
                                <i class="fas fa-<?= $jadwal_aktif['presensi_status'] == 'alpha' ? 'history' : 'sync' ?> me-2"></i>
                                <?= $jadwal_aktif['presensi_status'] == 'alpha' ? 'Lihat Riwayat' : 'Lihat Jadwal Inhall' ?>
                            </a>
                        </div>
                    </div>
                
                <?php elseif ($jadwal_aktif): ?>
                    <!-- Ada jadwal aktif (status null atau 'belum'), tampilkan scanner -->
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Jadwal Aktif:</strong> <?= $jadwal_aktif['nama_mk'] ?> - <?= $jadwal_aktif['materi'] ?> 
                        (<?= format_waktu($jadwal_aktif['jam_mulai']) ?> - <?= format_waktu($jadwal_aktif['jam_selesai']) ?>)
                        di <?= $jadwal_aktif['nama_lab'] ?>
                    </div>
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <!-- Tab untuk pilih metode -->
                            <ul class="nav nav-tabs mb-3" id="scanTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="camera-tab" data-bs-toggle="tab" data-bs-target="#camera" type="button">
                                        <i class="fas fa-camera me-2"></i>Scan Kamera
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button">
                                        <i class="fas fa-keyboard me-2"></i>Input Manual
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="scanTabContent">
                                <!-- Tab Kamera -->
                                <div class="tab-pane fade show active" id="camera" role="tabpanel">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <p class="text-muted">Arahkan kamera ke QR Code yang ditampilkan di lab</p>
                                            </div>
                                            
                                            <!-- Scanner Container -->
                                            <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                                            
                                            <!-- Camera Error Message -->
                                            <div id="camera-error" class="alert alert-warning text-center" style="display: none;">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Kamera tidak dapat diakses!</strong><br>
                                                <small>
                                                    Browser membutuhkan <strong>HTTPS</strong> untuk akses kamera.<br>
                                                    Pastikan mengakses dengan <code>https://</code> bukan <code>http://</code>
                                                </small>
                                                <hr>
                                                <button class="btn btn-primary btn-sm" onclick="document.getElementById('manual-tab').click()">
                                                    <i class="fas fa-keyboard me-2"></i>Gunakan Input Manual
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tab Input Manual -->
                                <div class="tab-pane fade" id="manual" role="tabpanel">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="text-center mb-4">
                                                <i class="fas fa-keyboard fa-3x text-primary mb-3"></i>
                                                <p class="text-muted">Masukkan kode QR yang ditampilkan di layar lab</p>
                                            </div>
                                            
                                            <form id="formManualQR" class="mx-auto" style="max-width: 400px;">
                                                <div class="mb-3">
                                                    <label class="form-label">Kode QR</label>
                                                    <input type="text" id="manualQrCode" class="form-control form-control-lg text-center" 
                                                           placeholder="Masukkan kode QR" required autofocus
                                                           style="font-family: monospace; letter-spacing: 2px;">
                                                    <small class="text-muted">Minta asisten menunjukkan kode QR</small>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                                    <i class="fas fa-check me-2"></i>Submit Presensi
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Result (shared - inside jadwal_aktif) -->
                            <div id="result" class="card mt-4 text-center" style="display: none;">
                                <div class="card-body py-5">
                                    <div id="result-icon"></div>
                                    <div id="result-message" class="h5 mt-3"></div>
                                    <div id="result-detail" class="text-muted"></div>
                                    <button class="btn btn-primary mt-4" onclick="location.reload()">
                                        <i class="fas fa-redo me-2"></i>Scan/Input Ulang
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Info -->
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h6><i class="fas fa-info-circle me-2"></i>Petunjuk:</h6>
                                    <ol class="mb-0">
                                        <li>Pastikan Anda berada di dalam lab sesuai jadwal</li>
                                        <li><strong>Scan Kamera:</strong> Izinkan akses kamera, arahkan ke QR Code</li>
                                        <li><strong>Input Manual:</strong> Minta asisten menunjukkan kode, ketik di form</li>
                                        <li>Tunggu hingga presensi berhasil dicatat</li>
                                    </ol>
                                </div>
                            </div>
                
                <?php elseif ($jadwal_berikutnya): ?>
                    <!-- Belum waktunya -->
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-clock fa-5x text-warning mb-4"></i>
                            <h4>Belum Waktunya Presensi</h4>
                            <p class="text-muted mb-3">
                                Jadwal berikutnya:<br>
                                <strong><?= $jadwal_berikutnya['nama_mk'] ?> - <?= $jadwal_berikutnya['materi'] ?></strong><br>
                                <?= format_waktu($jadwal_berikutnya['jam_mulai']) ?> - <?= format_waktu($jadwal_berikutnya['jam_selesai']) ?>
                                di <?= $jadwal_berikutnya['nama_lab'] ?>
                            </p>
                            <p class="text-muted">
                                <small>Presensi dibuka <?= TOLERANSI_SEBELUM ?> menit sebelum jadwal dimulai</small>
                            </p>
                            <a href="index.php?page=mahasiswa_dashboard" class="btn btn-secondary">
                                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                
                <?php else: ?>
                    <!-- Tidak ada jadwal hari ini -->
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-times fa-5x text-muted mb-4"></i>
                            <h4>Tidak Ada Jadwal</h4>
                            <p class="text-muted mb-3">
                                Tidak ada jadwal praktikum untuk hari ini,<br>
                                atau semua jadwal sudah selesai.
                            </p>
                            <a href="index.php?page=mahasiswa_jadwal" class="btn btn-primary">
                                <i class="fas fa-calendar-alt me-2"></i>Lihat Semua Jadwal
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php 
// Scanner script hanya dimuat jika jadwal aktif dan mahasiswa belum presensi
// Status 'belum' dianggap belum presensi (masih bisa scan)
$perlu_scanner = $jadwal_aktif && (empty($jadwal_aktif['presensi_status']) || $jadwal_aktif['presensi_status'] == 'belum');
if ($perlu_scanner): 
?>
<!-- Load jsQR FIRST before using it - only when scanner is active -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
// Fungsi untuk submit presensi ke server
function submitPresensi(qrCode) {
    // Hide scanner/form, show loading
    document.getElementById('camera').style.display = 'none';
    document.getElementById('manual').style.display = 'none';
    document.querySelector('.nav-tabs').style.display = 'none';
    
    document.getElementById('result').style.display = 'block';
    document.getElementById('result-icon').innerHTML = '<i class="fas fa-spinner fa-spin fa-5x text-primary"></i>';
    document.getElementById('result-message').innerHTML = 'Memproses presensi...';
    document.getElementById('result-detail').innerHTML = '';
    
    // [FITUR BARU] Ambil Lokasi GPS untuk mencegah scan dari rumah
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            sendPresensiData(qrCode, position.coords.latitude, position.coords.longitude);
        }, function(error) {
            // Handle error lokasi
            let msg = "Gagal mengambil lokasi.";
            if (error.code == error.PERMISSION_DENIED) {
                msg = "Wajib mengizinkan akses lokasi untuk presensi!";
            }
            document.getElementById('result-icon').innerHTML = '<i class="fas fa-map-marker-alt fa-5x text-warning"></i>';
            document.getElementById('result-message').innerHTML = '<span class="text-warning">Lokasi Diperlukan</span>';
            document.getElementById('result-detail').innerHTML = msg + '<br>Silakan refresh dan izinkan lokasi.';
        });
    } else {
        document.getElementById('result-detail').innerHTML = 'Browser tidak mendukung Geolocation.';
    }
}

// [FITUR BARU] Generate/Get Unique Device ID
function getDeviceId() {
    let deviceId = localStorage.getItem('device_fingerprint');
    if (!deviceId) {
        // Generate UUID sederhana
        deviceId = 'dev_' + Date.now().toString(36) + Math.random().toString(36).substr(2);
        localStorage.setItem('device_fingerprint', deviceId);
    }
    return deviceId;
}

function sendPresensiData(qrCode, lat, long) {
    // [OFFLINE SUPPORT] Cek koneksi internet
    if (!navigator.onLine) {
        saveOfflinePresensi(qrCode, lat, long);
        return;
    }

    fetch('api/scan_qr.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                qr_code: qrCode,
                nim: '<?= $mahasiswa['nim'] ?>',
                latitude: lat,
                longitude: long,
                device_fingerprint: getDeviceId() // Kirim ID unik perangkat
            })
            })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('result-icon').innerHTML = '<i class="fas fa-check-circle fa-5x text-success"></i>';
                document.getElementById('result-message').innerHTML = '<span class="text-success">Presensi Berhasil!</span>';
                document.getElementById('result-detail').innerHTML = data.message;
            } else {
                document.getElementById('result-icon').innerHTML = '<i class="fas fa-times-circle fa-5x text-danger"></i>';
                document.getElementById('result-message').innerHTML = '<span class="text-danger">Presensi Gagal!</span>';
                document.getElementById('result-detail').innerHTML = data.message;
            }
        })
        .catch(error => {
            document.getElementById('result-icon').innerHTML = '<i class="fas fa-exclamation-circle fa-5x text-warning"></i>';
            document.getElementById('result-message').innerHTML = '<span class="text-warning">Terjadi Kesalahan!</span>';
            document.getElementById('result-detail').innerHTML = 'Tidak dapat terhubung ke server.';
        });
}

// [OFFLINE SUPPORT] Fungsi simpan ke localStorage saat offline
function saveOfflinePresensi(qrCode, lat, long) {
    const data = {
        qr_code: qrCode,
        nim: '<?= $mahasiswa['nim'] ?>',
        latitude: lat,
        longitude: long,
        device_fingerprint: getDeviceId()
    };
    
    // Ambil antrian lama, tambah yang baru
    let pending = JSON.parse(localStorage.getItem('pending_presensi') || '[]');
    pending.push(data);
    localStorage.setItem('pending_presensi', JSON.stringify(pending));
    
    // Tampilkan pesan offline
    document.getElementById('result').style.display = 'block';
    document.getElementById('result-icon').innerHTML = '<i class="fas fa-wifi fa-5x text-warning"></i>';
    document.getElementById('result-message').innerHTML = '<span class="text-warning">Mode Offline</span>';
    document.getElementById('result-detail').innerHTML = 'Data disimpan di HP Anda.<br>Akan dikirim otomatis saat sinyal kembali.';
}

// [OFFLINE SUPPORT] Fungsi sinkronisasi saat online kembali
function syncOfflineData() {
    if (!navigator.onLine) return;
    
    let pending = JSON.parse(localStorage.getItem('pending_presensi') || '[]');
    if (pending.length === 0) return;
    
    // Ambil item pertama dari antrian
    const item = pending[0];
    
    fetch('api/scan_qr.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item)
    })
    .then(response => response.json())
    .then(data => {
        // Jika berhasil atau error validasi (bukan error jaringan), hapus dari antrian
        if (data.success || data.message) {
            pending.shift(); // Hapus item pertama
            localStorage.setItem('pending_presensi', JSON.stringify(pending));
            
            // Notifikasi kecil
            const toast = document.createElement('div');
            toast.className = 'alert alert-success position-fixed top-0 end-0 m-3 shadow';
            toast.style.zIndex = '9999';
            toast.innerHTML = '<i class="fas fa-check me-2"></i>Data offline terkirim!';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
            
            // Lanjut sync item berikutnya jika masih ada
            if (pending.length > 0) syncOfflineData();
        }
    })
    .catch(err => console.log('Sync pending... menunggu koneksi stabil'));
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle form manual input
    var formManualQR = document.getElementById('formManualQR');
    if (formManualQR) {
        formManualQR.addEventListener('submit', function(e) {
            e.preventDefault();
            var qrCode = document.getElementById('manualQrCode').value.trim();
            if (qrCode) {
                submitPresensi(qrCode);
            }
        });
    }
    
    var readerDiv = document.getElementById('reader');
    var cameraError = document.getElementById('camera-error');
    
    // Hanya jalankan kamera jika elemen ada
    if (!readerDiv || !cameraError) return;
    
    var videoElement = null;
    var scannerActive = false;
    
    // Buat video element untuk kamera
    readerDiv.innerHTML = `
        <div class="text-center">
            <video id="videoCamera" playsinline autoplay muted style="width:100%; max-width:400px; border-radius:10px; border:3px solid #0066cc;"></video>
            <canvas id="canvasQR" style="display:none;"></canvas>
            <p class="mt-2 text-muted"><small>Arahkan kamera ke QR Code</small></p>
            <div id="scanStatus" class="text-primary"><i class="fas fa-spinner fa-spin me-2"></i>Memuat kamera...</div>
        </div>
    `;
    
    videoElement = document.getElementById('videoCamera');
    var canvasElement = document.getElementById('canvasQR');
    var canvasContext = canvasElement.getContext('2d');
    var scanStatus = document.getElementById('scanStatus');
    
    // Request kamera
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'environment',
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        })
        .then(function(stream) {
            videoElement.srcObject = stream;
            videoElement.play();
            scannerActive = true;
            scanStatus.innerHTML = '<i class="fas fa-search me-2"></i>Scanning QR Code...';
            
            // Mulai scan QR
            requestAnimationFrame(scanQRCode);
        })
        .catch(function(err) {
            console.error('Camera error:', err);
            readerDiv.style.display = 'none';
            cameraError.style.display = 'block';
            
            // Check if accessed via IP (not localhost/HTTPS)
            var isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            var accessInfo = '';
            if (!isSecure) {
                accessInfo = '<br><span class="text-danger"><strong>Catatan:</strong> Akses via IP (' + location.hostname + ') memerlukan HTTPS untuk kamera.</span>';
            }
            
            cameraError.innerHTML = `
                <i class="fas fa-camera me-2"></i><strong>Kamera tidak dapat diakses</strong><br>
                <small>${err.message}</small>${accessInfo}<br><br>
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('manual-tab').click()">
                    <i class="fas fa-keyboard me-2"></i>Gunakan Input Manual
                </button>
            `;
        });
    } else {
        readerDiv.style.display = 'none';
        cameraError.style.display = 'block';
        cameraError.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Browser tidak mendukung kamera. Gunakan <strong>Input Manual</strong>.';
    }
    
    // Fungsi scan QR dari video
    function scanQRCode() {
        if (!scannerActive) return;
        
        if (videoElement.readyState === videoElement.HAVE_ENOUGH_DATA) {
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            canvasContext.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            
            var imageData = canvasContext.getImageData(0, 0, canvasElement.width, canvasElement.height);
            
            // Gunakan jsQR untuk decode
            if (typeof jsQR !== 'undefined') {
                var code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });
                
                if (code) {
                    scannerActive = false;
                    // Stop kamera
                    var stream = videoElement.srcObject;
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                    submitPresensi(code.data);
                    return;
                }
            }
        }
        
        requestAnimationFrame(scanQRCode);
    }
    
    // [OFFLINE SUPPORT] Listener otomatis saat sinyal nyala
    window.addEventListener('online', syncOfflineData);
    // Cek antrian saat halaman dibuka
    if (navigator.onLine) syncOfflineData();
});
</script>
<?php endif; ?>
