<?php
$page = 'asisten_kuis';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

// Proses Tambah Kuis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kuis'])) {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $judul = escape($_POST['judul']);
    $deskripsi = escape($_POST['deskripsi']);
    $durasi = (int)$_POST['durasi'];
    $metode = escape($_POST['metode_penilaian']);
    $bobot = isset($_POST['bobot_per_soal']) ? (int)$_POST['bobot_per_soal'] : 0;
    
    $stmt = mysqli_prepare($conn, "INSERT INTO kuis (jadwal_id, judul, deskripsi, durasi_menit, metode_penilaian, bobot_per_soal, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
    mysqli_stmt_bind_param($stmt, "issisi", $jadwal_id, $judul, $deskripsi, $durasi, $metode, $bobot);
    
    if (mysqli_stmt_execute($stmt)) {
        set_alert('success', 'Kuis berhasil dibuat! Silakan tambahkan soal.');
        header("Location: index.php?page=asisten_kuis_detail&id=" . mysqli_insert_id($conn));
        exit;
    } else {
        set_alert('danger', 'Gagal membuat kuis.');
    }
}

// Ambil Jadwal Asisten yang belum lewat terlalu lama (misal 7 hari terakhir + mendatang)
$jadwal_list = mysqli_query($conn, "SELECT j.id, j.tanggal, j.jam_mulai, mk.nama_mk, k.nama_kelas 
                                    FROM jadwal j
                                    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                    JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                    WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                    AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                    ORDER BY j.tanggal DESC, j.jam_mulai DESC");

// Ambil Daftar Kuis
$kuis_list = mysqli_query($conn, "SELECT k.*, j.tanggal, mk.nama_mk, kl.nama_kelas,
                                  (SELECT COUNT(*) FROM soal_kuis sk WHERE sk.kuis_id = k.id) as total_soal,
                                  (SELECT COUNT(*) FROM hasil_kuis hk WHERE hk.kuis_id = k.id) as total_peserta
                                  FROM kuis k
                                  JOIN jadwal j ON k.jadwal_id = j.id
                                  JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                  JOIN kelas kl ON j.kode_kelas = kl.kode_kelas
                                  WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                  ORDER BY k.created_at DESC");
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Welcome Banner Modern */
    .welcome-banner-kuis-asisten {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner-kuis-asisten::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-glow-kuis 4s ease-in-out infinite;
    }
    
    @keyframes pulse-glow-kuis {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.6;
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .welcome-banner-kuis-asisten h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-kuis-asisten .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-kuis-asisten .banner-icon {
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
    
    .welcome-banner-kuis-asisten .banner-badge {
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
    
    .welcome-banner-kuis-asisten .btn-banner {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }
    
    .welcome-banner-kuis-asisten .btn-banner:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    /* Dark Mode Support */
    [data-theme="dark"] .welcome-banner-kuis-asisten {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .welcome-banner-kuis-asisten {
            padding: 30px;
        }
        .welcome-banner-kuis-asisten h1 {
            font-size: 28px;
        }
    }
    @media (max-width: 576px) {
        .welcome-banner-kuis-asisten {
            padding: 20px;
            border-radius: 16px;
        }
        
        .welcome-banner-kuis-asisten h1 {
            font-size: 24px;
        }
        
        .welcome-banner-kuis-asisten .banner-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
        }
        
        .welcome-banner-kuis-asisten .btn-banner {
            width: 100%;
            justify-content: center;
            margin-top: 10px;
        }
        
        .welcome-banner-kuis-asisten .banner-subtitle {
            font-size: 14px;
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
                <div class="welcome-banner-kuis-asisten mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-pencil-alt"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Manajemen Kuis</h1>
                                    <p class="banner-subtitle mb-0">Kelola kuis dan evaluasi untuk mahasiswa</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="banner-badge">
                                <i class="fas fa-clipboard-check me-1"></i>Evaluasi Pembelajaran
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-2"></i>Buat Kuis Baru
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($kuis_list) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Judul Kuis</th>
                                            <th>Jadwal</th>
                                            <th>Soal</th>
                                            <th>Peserta</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($k = mysqli_fetch_assoc($kuis_list)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($k['judul']) ?></strong><br>
                                                    <small class="text-muted"><?= $k['durasi_menit'] ?> Menit</small>
                                                </td>
                                                <td>
                                                    <?= $k['nama_mk'] ?><br>
                                                    <small><?= $k['nama_kelas'] ?> - <?= format_tanggal($k['tanggal']) ?></small>
                                                </td>
                                                <td><span class="badge bg-info"><?= $k['total_soal'] ?></span></td>
                                                <td><span class="badge bg-secondary"><?= $k['total_peserta'] ?></span></td>
                                                <td>
                                                    <?php if ($k['status'] == 'draft'): ?>
                                                        <span class="badge bg-secondary">Draft</span>
                                                    <?php elseif ($k['status'] == 'aktif'): ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Selesai</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="index.php?page=asisten_kuis_detail&id=<?= $k['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-cog me-1"></i>Kelola
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                <p>Belum ada kuis yang dibuat.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="tambah_kuis" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Kuis Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Jadwal</label>
                        <select name="jadwal_id" class="form-select" required>
                            <option value="">-- Pilih Jadwal --</option>
                            <?php while ($j = mysqli_fetch_assoc($jadwal_list)): ?>
                                <option value="<?= $j['id'] ?>">
                                    <?= format_tanggal($j['tanggal']) ?> - <?= $j['nama_mk'] ?> (<?= $j['nama_kelas'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Judul Kuis</label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Pre-test Modul 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi (Opsional)</label>
                        <textarea name="deskripsi" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durasi (Menit)</label>
                        <input type="number" name="durasi" class="form-control" value="15" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Metode Penilaian</label>
                        <select name="metode_penilaian" id="metodePenilaian" class="form-select" onchange="toggleBobot()">
                            <option value="skala_100">Skala 100 (Persentase)</option>
                            <option value="poin_murni">Poin Murni (Jumlah Benar)</option>
                            <option value="bobot_kustom">Bobot Kustom (Nilai per Soal)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="divBobot" style="display:none;">
                        <label class="form-label">Nilai per Soal</label>
                        <input type="number" name="bobot_per_soal" class="form-control" placeholder="Contoh: 20">
                        <div class="form-text small">Total Nilai = Jumlah Benar x Nilai per Soal</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Buat Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleBobot() {
    var metode = document.getElementById('metodePenilaian').value;
    var div = document.getElementById('divBobot');
    div.style.display = (metode == 'bobot_kustom') ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>