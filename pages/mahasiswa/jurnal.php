<?php
$page = 'mahasiswa_jurnal';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];

// Handle Submit Jurnal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $kegiatan = escape($_POST['kegiatan']);
    $hasil = escape($_POST['hasil']);
    
    // Cek apakah sudah pernah isi
    $cek = mysqli_query($conn, "SELECT id FROM jurnal_praktikum WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'");
    if (mysqli_num_rows($cek) > 0) {
        // Update
        $stmt = mysqli_prepare($conn, "UPDATE jurnal_praktikum SET kegiatan=?, hasil=? WHERE jadwal_id=? AND nim=?");
        mysqli_stmt_bind_param($stmt, "ssis", $kegiatan, $hasil, $jadwal_id, $nim);
    } else {
        // Insert
        $stmt = mysqli_prepare($conn, "INSERT INTO jurnal_praktikum (jadwal_id, nim, kegiatan, hasil) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", $jadwal_id, $nim, $kegiatan, $hasil);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        set_alert('success', 'Jurnal praktikum berhasil disimpan!');
    } else {
        set_alert('danger', 'Gagal menyimpan jurnal.');
    }
    header("Location: index.php?page=mahasiswa_jurnal");
    exit;
}

// Ambil daftar praktikum yang SUDAH HADIR tapi BELUM ISI JURNAL (atau sudah isi untuk diedit)
// Limit 10 terakhir
$query = "SELECT j.id as jadwal_id, j.tanggal, j.jam_mulai, j.materi, mk.nama_mk, l.nama_lab,
          jp.id as jurnal_id, jp.kegiatan, jp.hasil
          FROM presensi_mahasiswa pm
          JOIN jadwal j ON pm.jadwal_id = j.id
          JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
          LEFT JOIN jurnal_praktikum jp ON j.id = jp.jadwal_id AND jp.nim = '$nim'
          WHERE pm.nim = '$nim' AND pm.status = 'hadir'
          ORDER BY j.tanggal DESC, j.jam_mulai DESC LIMIT 10";
$result = mysqli_query($conn, $query);
?>
<?php include 'includes/header.php'; ?>

<style>
    .content-wrapper{
        padding: 24px;
    
        animation: fadeIn 0.4s ease-out;
    }
    @media (max-width: 768px) {
        textarea.form-control {
            font-size: 16px !important; /* Mencegah zoom otomatis di iOS */
            min-height: 120px;
        }
    }

    /* ===== WELCOME BANNER JURNAL ===== */
    .welcome-banner-jurnal {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
    }

    .welcome-banner-jurnal::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-glow-jurnal 4s ease-in-out infinite;
    }

    @keyframes pulse-glow-jurnal {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.05); opacity: 0.6; }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .welcome-banner-jurnal h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .welcome-banner-jurnal .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }

    .welcome-banner-jurnal .banner-icon {
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

    .welcome-banner-jurnal .banner-badge {
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
    [data-theme="dark"] .welcome-banner-jurnal {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    /* Responsive Design */
    @media (max-width: 576px) {
        .welcome-banner-jurnal {
            padding: 24px;
            border-radius: 16px;
        }
        
        .welcome-banner-jurnal h1 {
            font-size: 24px;
        }
        
        .welcome-banner-jurnal .banner-icon {
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
            <div class="content-wrapper">
                <!-- Welcome Banner -->
                <div class="welcome-banner-jurnal mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Jurnal Praktikum</h1>
                                    <p class="banner-subtitle mb-0">Catat kegiatan dan hasil praktikum Anda di sini.</p>
                                </div>
                            </div>
                        </div>
                        <span class="banner-badge">
                            <i class="fas fa-pen-fancy me-1"></i>Catatan Harian Praktikum
                        </span>
                    </div>
                </div>

                <?= show_alert() ?>
                
                <div class="row justify-content-center">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <div class="col-12 mb-4">
                            <div class="card h-100 border-<?= $row['jurnal_id'] ? 'success' : 'warning' ?>">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="fas fa-calendar me-1"></i><?= format_tanggal($row['tanggal']) ?></small>
                                    <?php if($row['jurnal_id']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Diisi</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-pen me-1"></i>Belum Diisi</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $row['nama_mk'] ?></h5>
                                    <p class="card-text text-muted small mb-3">
                                        Materi: <?= $row['materi'] ?><br>
                                        Lab: <?= $row['nama_lab'] ?>
                                    </p>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="jadwal_id" value="<?= $row['jadwal_id'] ?>">
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">Ringkasan Kegiatan</label>
                                            <textarea name="kegiatan" class="form-control" rows="5" required placeholder="Jelaskan kegiatan praktikum yang dilakukan..." readonly><?= $row['kegiatan'] ?? '' ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Hasil / Kesimpulan</label>
                                            <textarea name="hasil" class="form-control" rows="5" required placeholder="Tuliskan hasil dan kesimpulan praktikum..." readonly><?= $row['hasil'] ?? '' ?></textarea>
                                        </div>
                                        
                                        <button type="button" class="btn btn-outline-primary w-100 btn-edit">
                                            <i class="fas fa-edit me-1"></i><?= $row['jurnal_id'] ? 'Edit Jurnal' : 'Isi Jurnal' ?>
                                        </button>
                                        
                                        <div class="action-buttons d-none">
                                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                                <i class="fas fa-save me-1"></i>Simpan Jurnal
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary w-100 btn-cancel">
                                                Batal
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">Belum ada riwayat kehadiran praktikum untuk diisi jurnalnya.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script agar textarea otomatis memanjang sesuai isi konten (Auto-resize)
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea.form-control');
    textareas.forEach(ta => {
        const resize = () => {
            ta.style.height = 'auto';
            ta.style.height = (ta.scrollHeight + 2) + 'px';
        };
        ta.addEventListener('input', resize);
        if(ta.value) resize(); // Resize saat halaman dimuat jika ada isi
    });
    
    // Handle Mode Edit/Baca agar tidak ter-edit saat scrolling
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('form');
            const textareas = form.querySelectorAll('textarea');
            const actions = form.querySelector('.action-buttons');
            
            textareas.forEach(ta => ta.removeAttribute('readonly'));
            this.classList.add('d-none');
            actions.classList.remove('d-none');
            textareas[0].focus(); // Langsung fokus ke kolom pertama
        });
    });
    
    document.querySelectorAll('.btn-cancel').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('form');
            const textareas = form.querySelectorAll('textarea');
            const btnEdit = form.querySelector('.btn-edit');
            const actions = form.querySelector('.action-buttons');
            
            textareas.forEach(ta => ta.setAttribute('readonly', true));
            btnEdit.classList.remove('d-none');
            actions.classList.add('d-none');
        });
    });
});
</script>
<?php include 'includes/footer.php'; ?>
