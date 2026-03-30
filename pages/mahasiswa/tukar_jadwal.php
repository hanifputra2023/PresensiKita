<?php
$page = 'mahasiswa_tukar_jadwal';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];

// Handle pembuatan DB jika belum ada
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tukar_jadwal_sementara'");
if (mysqli_num_rows($check_table) == 0) {
    mysqli_query($conn, "CREATE TABLE `tukar_jadwal_sementara` (
        `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `nim_pengaju` varchar(15) NOT NULL,
        `jadwal_awal_id` int(11) NOT NULL,
        `nim_dituju` varchar(15) DEFAULT NULL,
        `jadwal_tujuan_id` int(11) NOT NULL,
        `alasan` text NOT NULL,
        `status` enum('menunggu_teman', 'menunggu_admin', 'disetujui', 'ditolak', 'dibatalkan') DEFAULT 'menunggu_teman',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// ... existing code ...

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_tukar'])) {
    $jadwal_awal_id = (int)$_POST['jadwal_awal_id'];
    $nim_dituju = escape($_POST['nim_dituju']); // ini didapat dari hidden input setelah pilih nama
    $jadwal_tujuan_id = (int)$_POST['jadwal_tujuan_id'];
    $alasan = escape($_POST['alasan']);

    if (empty($jadwal_awal_id) || empty($nim_dituju) || empty($jadwal_tujuan_id) || empty($alasan)) {
        $msg = 'Beberapa form belum diisi: ';
        if(empty($jadwal_awal_id)) $msg .= 'Jadwal Anda, ';
        if(empty($nim_dituju)) $msg .= 'Teman Pengganti (Harus diklik dari daftar pencarian), ';
        if(empty($jadwal_tujuan_id)) $msg .= 'Jadwal Tujuan, ';
        if(empty($alasan)) $msg .= 'Alasan';
        set_alert('danger', trim($msg, ', '));
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO tukar_jadwal_sementara (nim_pengaju, jadwal_awal_id, nim_dituju, jadwal_tujuan_id, alasan) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sisis", $nim, $jadwal_awal_id, $nim_dituju, $jadwal_tujuan_id, $alasan);
        if (mysqli_stmt_execute($stmt)) {
            set_alert('success', 'Pengajuan tukar jadwal berhasil dibuat. Menunggu persetujuan teman Anda.');
        } else {
            set_alert('danger', 'Terjadi kesalahan sistem.');
        }
    }
}

// Handle Konfirmasi oleh Teman (Mahasiswa yang diajak tukeran)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $tukar_id = (int)$_GET['id'];
    $action = $_GET['action']; // 'setuju' atau 'tolak'
    
    // Pastikan ini adalah request untuk mahasiswa ini
    $cek_req = mysqli_query($conn, "SELECT * FROM tukar_jadwal_sementara WHERE id = $tukar_id AND nim_dituju = '$nim' AND status = 'menunggu_teman'");
    if (mysqli_num_rows($cek_req) > 0) {
        $new_status = ($action == 'setuju') ? 'menunggu_admin' : 'ditolak';
        mysqli_query($conn, "UPDATE tukar_jadwal_sementara SET status = '$new_status' WHERE id = $tukar_id");
        $msg = ($action == 'setuju') ? 'Persetujuan berhasil, menunggu konfirmasi admin.' : 'Permintaan tukar jadwal ditolak.';
        set_alert('success', $msg);
        header("Location: index.php?page=mahasiswa_tukar_jadwal");
        exit;
    }
}

// Ambil Jadwal Aktif Mahasiswa (Untuk opsi jadwal awal)
$jadwal_saya_q = mysqli_query($conn, "
    SELECT j.*, mk.nama_mk 
    FROM jadwal j 
    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk 
    WHERE (j.kode_kelas = '{$mahasiswa['kode_kelas']}' AND (j.sesi = 0 OR j.sesi = {$mahasiswa['sesi']}))
    AND j.tanggal >= CURDATE()
    ORDER BY j.tanggal ASC LIMIT 10
");

// Ambil Request Tukar yang diajukan SAYA
$request_saya = mysqli_query($conn, "SELECT t.*, m.nama as nama_teman, mk.nama_mk, j.tanggal, j.jam_mulai, j.sesi
    FROM tukar_jadwal_sementara t
    LEFT JOIN mahasiswa m ON t.nim_dituju = m.nim
    JOIN jadwal j ON t.jadwal_awal_id = j.id
    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
    WHERE t.nim_pengaju = '$nim' ORDER BY t.created_at DESC");

// Ambil Request Tukar dari ORANG LAIN ke Saya
$request_ke_saya = mysqli_query($conn, "SELECT t.*, m.nama as nama_pengaju, mk.nama_mk, j.tanggal, j.jam_mulai, j.sesi,
    jt.tanggal as tgl_tujuan, jt.jam_mulai as jam_tujuan
    FROM tukar_jadwal_sementara t
    JOIN mahasiswa m ON t.nim_pengaju = m.nim
    JOIN jadwal j ON t.jadwal_awal_id = j.id
    JOIN jadwal jt ON t.jadwal_tujuan_id = jt.id
    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
    WHERE t.nim_dituju = '$nim' ORDER BY t.created_at DESC");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <!-- Welcome Banner Modern -->
                <style>
                    .welcome-banner-tukar {
                        background: var(--banner-gradient, linear-gradient(90deg, #0066cc, #0099ff, #16a1fdff));
                        border-radius: 24px;
                        padding: 40px;
                        color: white;
                        position: relative;
                        overflow: hidden;
                    }

                    .welcome-banner-tukar::before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        left: -50%;
                        width: 200%;
                        height: 200%;
                        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
                        animation: rotateBanner 20s linear infinite;
                    }

                    @keyframes rotateBanner {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }

                    .welcome-banner-tukar h1 {
                        font-size: 32px;
                        font-weight: 700;
                        margin: 0;
                        position: relative;
                        z-index: 1;
                    }

                    .welcome-banner-tukar .banner-subtitle {
                        font-size: 16px;
                        opacity: 0.95;
                        position: relative;
                        z-index: 1;
                    }

                    .welcome-banner-tukar .banner-icon {
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

                    .welcome-banner-tukar .banner-badge {
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
                        margin-top: 15px;
                    }
                    
                    /* Theme Dark adjustments context */
                    [data-theme="dark"] .welcome-banner-tukar {
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                    }

                    /* For Mobile banner adjustments */
                    @media (max-width: 768px) {
                        .welcome-banner-tukar {
                            padding: 24px;
                            border-radius: 16px;
                        }
                        .welcome-banner-tukar h1 {
                            font-size: 24px;
                        }
                        .welcome-banner-tukar .banner-icon {
                            width: 48px;
                            height: 48px;
                            font-size: 20px;
                        }
                    }
                </style>

                <div class="welcome-banner-tukar mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Tukar Jadwal</h1>
                                    <p class="banner-subtitle mb-0">Ajukan atau setujui pertukaran sesi praktikum dengan teman Anda</p>
                                </div>
                            </div>
                            <span class="banner-badge">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 6px;"></i>MAHASISWA
                            </span>
                        </div>
                    </div>
                </div>

    <?= show_alert() ?>

    <div class="row">
        <!-- FORM PENGAJUAN -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Buat Pengajuan Tukar Jadwal</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="formTukarJadwal">
                        <div class="mb-3">
                            <label class="form-label text-secondary small">1. Pilih Jadwal Anda yang ingin ditukar</label>
                            <select name="jadwal_awal_id" class="form-select" required>
                                <option value="">-- Pilih Jadwal Saya --</option>
                                <?php while($j = mysqli_fetch_assoc($jadwal_saya_q)): ?>
                                    <option value="<?= $j['id'] ?>"><?= date('d M Y', strtotime($j['tanggal'])) ?> - <?= $j['nama_mk'] ?> (Sesi <?= $j['sesi'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary small">2. Cari Teman Pengganti (Berdasarkan Nama atau NIM)</label>
                            <div class="position-relative">
                                <input type="text" id="searchTeman" class="form-control" placeholder="Ketik nama atau NIM mahasiswa lalu klik hasilnya..." autocomplete="off" required>
                                <input type="hidden" name="nim_dituju" id="nim_dituju">
                                <div id="searchResult" class="list-group position-absolute w-100 mt-1 shadow-sm" style="display: none; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                            </div>
                            <small class="text-success mt-1 fw-bold" id="selectedTemanInfo" style="display:none;"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small">3. Pilih Jadwal Teman (Jadwal Tujuan)</label>
                            <!-- Idealnya ini pake AJAX nge-load jadwal si anak, tapi untuk simplifikasi form select semua jadwal aktif -->
                            <?php
                            $all_jadwal = mysqli_query($conn, "SELECT j.*, mk.nama_mk FROM jadwal j JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk WHERE j.tanggal >= CURDATE() ORDER BY j.tanggal ASC");
                            ?>
                            <select name="jadwal_tujuan_id" class="form-select" required>
                                <option value="">-- Pilih Jadwal Tujuan --</option>
                                <?php while($aj = mysqli_fetch_assoc($all_jadwal)): ?>
                                    <option value="<?= $aj['id'] ?>"><?= date('d M Y', strtotime($aj['tanggal'])) ?> - <?= $aj['nama_mk'] ?> (Kelas <?= $aj['kode_kelas'] ?> / Sesi <?= $aj['sesi'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small">Alasan Tukar</label>
                            <textarea name="alasan" class="form-control" rows="3" required placeholder="Jelaskan alasan tukar jadwal"></textarea>
                        </div>

                        <button type="submit" name="ajukan_tukar" class="btn btn-primary w-100">Ajukan Tukar Jadwal</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- DAFTAR REQUEST -->
        <div class="col-lg-7">
            
            <!-- Permintaan MASUK ke Saya -->
            <div class="card shadow-sm border-0 mb-4 border-left-warning">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-bell me-2"></i> Permintaan Kepada Anda</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if(mysqli_num_rows($request_ke_saya) > 0): ?>
                            <?php while($req = mysqli_fetch_assoc($request_ke_saya)): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($req['nama_pengaju']) ?> <span class="badge bg-secondary ms-1">Minta Tukar</span></div>
                                            <div class="small mt-1 text-muted">
                                                Dia ingin bertukar jadwal <b><?= $req['nama_mk'] ?></b>.<br>
                                                Jadwal Pengaju: <?= date('d M Y', strtotime($req['tanggal'])) ?> (Sesi <?= $req['sesi'] ?>)<br>
                                                Jadwal Anda: <?= date('d M Y', strtotime($req['tgl_tujuan'])) ?><br>
                                                <i class="fas fa-quote-left text-muted me-1"></i> <i><?= htmlspecialchars($req['alasan']) ?></i>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if($req['status'] == 'menunggu_teman'): ?>
                                                <a href="index.php?page=mahasiswa_tukar_jadwal&action=setuju&id=<?= $req['id'] ?>" class="btn btn-sm btn-success mb-1" onclick="return confirm('Setuju bertukar jadwal?')"><i class="fas fa-check"></i> Setuju</a><br>
                                                <a href="index.php?page=mahasiswa_tukar_jadwal&action=tolak&id=<?= $req['id'] ?>" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Tolak</a>
                                            <?php else: ?>
                                                <span class="badge bg-<?= $req['status'] == 'menunggu_admin' ? 'info' : ($req['status'] == 'disetujui' ? 'success' : 'danger') ?>">
                                                    <?= strtoupper(str_replace('_', ' ', $req['status'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">Belum ada permintaan tukar dari teman.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Yang Saya Ajukan -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Pengajuan Saya</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if(mysqli_num_rows($request_saya) > 0): ?>
                            <?php while($my = mysqli_fetch_assoc($request_saya)): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold mt-1">
                                                Tukar dengan: <?= htmlspecialchars($my['nama_teman'] ?? 'Tidak Diketahui') ?>
                                            </div>
                                            <div class="small mt-1 text-muted">
                                                Jadwal Awal: <?= $my['nama_mk'] ?> - <?= date('d M Y', strtotime($my['tanggal'])) ?><br>
                                                Alasan: <?= htmlspecialchars($my['alasan']) ?>
                                            </div>
                                        </div>
                                        <div>
                                            <?php
                                            $badge_color = 'secondary';
                                            if ($my['status'] == 'menunggu_admin') $badge_color = 'info';
                                            if ($my['status'] == 'disetujui') $badge_color = 'success';
                                            if ($my['status'] == 'ditolak') $badge_color = 'danger';
                                            ?>
                                            <span class="badge bg-<?= $badge_color ?>"><?= strtoupper(str_replace('_', ' ', $my['status'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">Belum ada riwayat pengajuan tukar jadwal.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchTeman');
    const resultBox = document.getElementById('searchResult');
    const nimInput = document.getElementById('nim_dituju');
    const infoBox = document.getElementById('selectedTemanInfo');
    const formTukar = document.getElementById('formTukarJadwal');

    if (formTukar) {
        formTukar.addEventListener('submit', function(e) {
            if (!nimInput.value) {
                e.preventDefault();
                alert('Silakan cari dan KLIK nama teman pengganti dari daftar yang muncul!');
                searchInput.focus();
            }
        });
    }

    let timeoutId;

    searchInput.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const q = this.value;
        
        if (q.length < 2) {
            resultBox.style.display = 'none';
            return;
        }

        timeoutId = setTimeout(() => {
            fetch('api/search_mahasiswa.php?q=' + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    resultBox.innerHTML = '';
                    if(data.length > 0) {
                        data.forEach(item => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action text-start';
                            btn.textContent = item.text;
                            btn.onclick = function() {
                                searchInput.value = item.text.split(' (')[0]; // Ambil nama saja
                                nimInput.value = item.id;
                                infoBox.textContent = 'Trpilih: ' + item.text;
                                infoBox.style.display = 'block';
                                resultBox.style.display = 'none';
                            };
                            resultBox.appendChild(btn);
                        });
                        resultBox.style.display = 'block';
                    } else {
                        resultBox.innerHTML = '<div class="list-group-item text-muted">Tidak ditemukan</div>';
                        resultBox.style.display = 'block';
                    }
                });
        }, 300); // 300ms debounce
    });

    // Sembunyikan resultBox jika klik di luar
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && e.target !== resultBox) {
            resultBox.style.display = 'none';
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>
