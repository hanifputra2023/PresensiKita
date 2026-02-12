<?php
$page = 'mahasiswa_kuis';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kuis_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil Data Hasil Kuis
$stmt = mysqli_prepare($conn, "SELECT hk.*, k.judul, k.deskripsi 
                               FROM hasil_kuis hk 
                               JOIN kuis k ON hk.kuis_id = k.id 
                               WHERE hk.kuis_id = ? AND hk.nim = ?");
mysqli_stmt_bind_param($stmt, "is", $kuis_id, $nim);
mysqli_stmt_execute($stmt);
$hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$hasil) {
    set_alert('danger', 'Hasil kuis tidak ditemukan.');
    header("Location: index.php?page=mahasiswa_kuis");
    exit;
}

// Ambil Detail Jawaban dan Soal
$query_detail = "SELECT d.*, s.pertanyaan, s.opsi_a, s.opsi_b, s.opsi_c, s.opsi_d, s.kunci_jawaban, s.gambar 
                 FROM detail_jawaban_kuis d 
                 JOIN soal_kuis s ON d.soal_id = s.id 
                 WHERE d.hasil_kuis_id = {$hasil['id']}
                 ORDER BY s.id ASC";
$details = mysqli_query($conn, $query_detail);
$jumlah_soal = mysqli_num_rows($details);
?>
<?php include 'includes/header.php'; ?>

<style>
    .result-card {
        border-radius: 12px;
        border: 1px solid var(--border-color);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .result-header {
        padding: 15px 20px;
        background-color: var(--bg-card);
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
    }
    .result-body {
        padding: 20px;
        background-color: var(--bg-body);
    }
    .option-item {
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 8px;
        border: 1px solid var(--border-color);
        background-color: var(--bg-input);
        display: flex;
        align-items: center;
    }
    
    /* Styling untuk jawaban */
    .option-item.correct-answer {
        background-color: rgba(25, 135, 84, 0.1);
        border-color: #198754;
        color: #155724;
    }
    .option-item.wrong-answer {
        background-color: rgba(220, 53, 69, 0.1);
        border-color: #dc3545;
        color: #721c24;
    }
    .option-item.key-answer {
        border-left: 4px solid #198754;
    }
    
    /* Dark mode adjustments */
    [data-theme="dark"] .option-item.correct-answer {
        background-color: rgba(25, 135, 84, 0.2);
        color: #75b798;
    }
    [data-theme="dark"] .option-item.wrong-answer {
        background-color: rgba(220, 53, 69, 0.2);
        color: #ea868f;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="fas fa-poll me-2"></i>Hasil Kuis: <?= htmlspecialchars($hasil['judul']) ?></h4>
 
                </div>

                <div class="card mb-4 border-primary shadow-sm">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-primary"><?= $hasil['nilai'] ?></h2>
                        <p class="text-muted mb-0">
                            Benar: <span class="text-success fw-bold"><?= $hasil['benar'] ?></span> | 
                            Salah: <span class="text-danger fw-bold"><?= $hasil['salah'] ?></span>
                        </p>
                    </div>
                </div>

                <?php if ($jumlah_soal > 0): ?>
                    <?php $no = 1; while ($d = mysqli_fetch_assoc($details)): ?>
                        <div class="result-card shadow-sm">
                            <div class="result-header d-flex justify-content-between">
                                <span>Soal No. <?= $no++ ?></span>
                                <?php if ($d['is_benar']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Benar</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Salah</span>
                                <?php endif; ?>
                            </div>
                            <div class="result-body">
                                <p class="mb-3 fw-bold"><?= nl2br(htmlspecialchars($d['pertanyaan'])) ?></p>
                                
                                <?php if (!empty($d['gambar'])): ?>
                                    <div class="mb-3 text-center">
                                        <img src="<?= $d['gambar'] ?>" alt="Gambar Soal" class="img-fluid rounded" style="max-height: 200px; cursor: pointer;" onclick="window.open('<?= $d['gambar'] ?>', '_blank')">
                                    </div>
                                <?php endif; ?>
                                
                                <?php foreach (['A', 'B', 'C', 'D'] as $opt): 
                                    $class = '';
                                    // Jika ini jawaban mahasiswa dan salah
                                    if ($d['jawaban_mahasiswa'] == $opt && !$d['is_benar']) $class .= ' wrong-answer';
                                    // Jika ini jawaban mahasiswa dan benar
                                    if ($d['jawaban_mahasiswa'] == $opt && $d['is_benar']) $class .= ' correct-answer';
                                    // Tandai kunci jawaban jika mahasiswa salah
                                    if ($d['kunci_jawaban'] == $opt && !$d['is_benar']) $class .= ' key-answer text-success fw-bold';
                                ?>
                                    <div class="option-item <?= $class ?>">
                                        <span class="me-2 fw-bold"><?= $opt ?>.</span>
                                        <?= htmlspecialchars($d['opsi_' . strtolower($opt)]) ?>
                                        <?php if ($d['jawaban_mahasiswa'] == $opt): ?>
                                            <i class="fas fa-user-edit ms-auto"></i>
                                        <?php endif; ?>
                                        <?php if ($d['kunci_jawaban'] == $opt && !$d['is_benar']): ?>
                                            <i class="fas fa-check-circle ms-auto"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Detail pembahasan tidak tersedia untuk kuis ini (mungkin dikerjakan sebelum fitur ini ada).
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>