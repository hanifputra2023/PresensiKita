<?php
$page = 'mahasiswa_kuis';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kelas = $mahasiswa['kode_kelas'];

// Ambil Kuis yang tersedia untuk kelas mahasiswa ini
// Kuis harus berstatus 'aktif' atau 'selesai' (untuk lihat nilai)
$query = "SELECT k.*, j.tanggal, mk.nama_mk,
          (SELECT nilai FROM hasil_kuis hk WHERE hk.kuis_id = k.id AND hk.nim = '$nim') as nilai_saya,
          (SELECT id FROM hasil_kuis hk WHERE hk.kuis_id = k.id AND hk.nim = '$nim') as sudah_dikerjakan
          FROM kuis k
          JOIN jadwal j ON k.jadwal_id = j.id
          JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
          WHERE j.kode_kelas = '$kelas' AND k.status != 'draft'
          ORDER BY k.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<?php include 'includes/header.php'; ?>

<style>
    /* ===== WELCOME BANNER KUIS ===== */
    .welcome-banner-kuis {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
    }

    .welcome-banner-kuis::before {
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
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.05); opacity: 0.6; }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .welcome-banner-kuis h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .welcome-banner-kuis .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }

    .welcome-banner-kuis .banner-icon {
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

    .welcome-banner-kuis .banner-badge {
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
    [data-theme="dark"] .welcome-banner-kuis {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    /* Responsive Design */
    @media (max-width: 576px) {
        .welcome-banner-kuis {
            padding: 24px;
            border-radius: 16px;
        }
        
        .welcome-banner-kuis h1 {
            font-size: 24px;
        }
        
        .welcome-banner-kuis .banner-icon {
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
                <div class="welcome-banner-kuis mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-pencil-alt"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Kuis Online</h1>
                                    <p class="banner-subtitle mb-0">Kerjakan kuis praktikum dan lihat hasil evaluasi Anda.</p>
                                </div>
                            </div>
                        </div>
                        <span class="banner-badge">
                            <i class="fas fa-clipboard-check me-1"></i>Evaluasi & Penilaian
                        </span>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($k = mysqli_fetch_assoc($result)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 border-<?= $k['sudah_dikerjakan'] ? 'success' : ($k['status'] == 'aktif' ? 'primary' : 'secondary') ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($k['judul']) ?></h5>
                                            <?php if ($k['sudah_dikerjakan']): ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php elseif ($k['status'] == 'aktif'): ?>
                                                <span class="badge bg-primary">Tersedia</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Ditutup</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-muted small mb-3">
                                            <?= $k['nama_mk'] ?> &bull; <?= format_tanggal($k['tanggal']) ?><br>
                                            <i class="fas fa-clock me-1"></i>Durasi: <?= $k['durasi_menit'] ?> Menit
                                        </p>
                                        
                                        <?php if ($k['deskripsi']): ?>
                                            <p class="card-text small mb-3"><?= htmlspecialchars($k['deskripsi']) ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="mt-auto">
                                            <?php if ($k['sudah_dikerjakan']): ?>
                                                <div class="alert alert-success py-2 mb-2 text-center">
                                                    <strong>Nilai Anda: <?= $k['nilai_saya'] ?></strong>
                                                </div>
                                                <a href="index.php?page=mahasiswa_kuis_hasil&id=<?= $k['id'] ?>" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-eye me-1"></i>Lihat Pembahasan
                                                </a>
                                            <?php elseif ($k['status'] == 'aktif'): ?>
                                                <a href="index.php?page=mahasiswa_kuis_kerjakan&id=<?= $k['id'] ?>" class="btn btn-primary w-100">
                                                    <i class="fas fa-play me-1"></i>Kerjakan Sekarang
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary w-100" disabled>Kuis Ditutup</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                                <p>Belum ada kuis yang tersedia untuk kelas Anda.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>