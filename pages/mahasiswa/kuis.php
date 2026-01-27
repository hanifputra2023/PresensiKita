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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-pencil-alt me-2"></i>Kuis Online</h4>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($k = mysqli_fetch_assoc($result)): ?>
                            <div class="col-md-6 mb-4">
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