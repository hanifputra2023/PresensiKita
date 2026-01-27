<?php
$page = 'mahasiswa_riwayat';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];

// Handle Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    if (ob_get_length()) ob_end_clean();
    $filename = 'riwayat_presensi_' . $nim . '_' . date('Ymd') . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>table{border-collapse:collapse;}th,td{border:1px solid #000;padding:5px;}</style></head><body>';
    echo '<h3>Riwayat Presensi - ' . $mahasiswa['nama'] . ' (' . $nim . ')</h3>';
    echo '<table><thead><tr>
            <th>Pertemuan</th><th>Tanggal</th><th>Waktu</th><th>Mata Kuliah</th><th>Materi</th><th>Lab</th><th>Status</th><th>Waktu Presensi</th>
          </tr></thead><tbody>';
    
    $q_export = mysqli_query($conn, "SELECT p.*, j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, l.nama_lab, mk.nama_mk
                                     FROM presensi_mahasiswa p
                                     JOIN jadwal j ON p.jadwal_id = j.id
                                     LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                     LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                     WHERE p.nim = '$nim' AND p.status != 'belum'
                                     ORDER BY j.tanggal DESC");
    while($r = mysqli_fetch_assoc($q_export)) {
        echo "<tr>
            <td>{$r['pertemuan_ke']}</td><td>" . format_tanggal($r['tanggal']) . "</td>
            <td>" . format_waktu($r['jam_mulai']) . " - " . format_waktu($r['jam_selesai']) . "</td>
            <td>{$r['nama_mk']}</td><td>{$r['materi']}</td><td>{$r['nama_lab']}</td>
            <td>" . ucfirst($r['status']) . "</td><td>{$r['waktu_presensi']}</td>
        </tr>";
    }
    echo '</tbody></table></body></html>';
    exit;
}

// Handle Kirim Ulasan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kirim_ulasan'])) {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $rating = (int)$_POST['rating'];
    $komentar = escape($_POST['komentar']);
    $anonim = isset($_POST['anonim']) ? 1 : 0;
    
    // Cek duplikasi
    $cek = mysqli_query($conn, "SELECT id FROM feedback_praktikum WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'");
    if (mysqli_num_rows($cek) == 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO feedback_praktikum (jadwal_id, nim, rating, komentar, is_anonim, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "isisi", $jadwal_id, $nim, $rating, $komentar, $anonim);
        if (mysqli_stmt_execute($stmt)) {
            set_alert('success', 'Terima kasih! Ulasan Anda berhasil dikirim.');
            log_aktivitas($_SESSION['user_id'], 'BERI_ULASAN', 'feedback_praktikum', mysqli_insert_id($conn), "Rating: $rating bintang");
        }
    }
    header("Location: index.php?page=mahasiswa_riwayat");
    exit;
}

// Pagination - prepared statement
$per_page = 10;
$current_page = get_current_page();

// Hitung total (exclude status 'belum' karena itu jadwal yang masih berjalan)
$stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM presensi_mahasiswa WHERE nim = ? AND status != 'belum'");
mysqli_stmt_bind_param($stmt_count, "s", $nim);
mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Prepared statement untuk fetch riwayat
$stmt_riwayat = mysqli_prepare($conn, "SELECT p.*, j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, j.jenis,
                                 l.nama_lab, mk.nama_mk,
                                 f.rating as ulasan_rating, f.komentar as ulasan_komentar
                                 FROM presensi_mahasiswa p
                                 JOIN jadwal j ON p.jadwal_id = j.id
                                 LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                 LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                 LEFT JOIN feedback_praktikum f ON f.jadwal_id = j.id AND f.nim = p.nim
                                 WHERE p.nim = ? AND p.status != 'belum'
                                 ORDER BY j.tanggal DESC, j.jam_mulai DESC
                                 LIMIT ?, ?");
mysqli_stmt_bind_param($stmt_riwayat, "sii", $nim, $offset, $per_page);
mysqli_stmt_execute($stmt_riwayat);
$riwayat = mysqli_stmt_get_result($stmt_riwayat);
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Presensi</h4>
                    <div class="btn-group">
                        <a href="index.php?page=mahasiswa_riwayat&export=excel" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </a>
                        <button onclick="exportPDF()" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf me-1"></i>PDF</button>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($riwayat) > 0): ?>
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-md-block" id="printableArea">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pertemuan</th>
                                            <th>Tanggal</th>
                                            <th>Waktu</th>
                                            <th>Mata Kuliah</th>
                                            <th>Materi</th>
                                            <th>Lab</th>
                                            <th>Status</th>
                                            <th>Metode</th>
                                            <th>Waktu Presensi</th>
                                            <th>Ulasan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($riwayat, 0);
                                        while ($r = mysqli_fetch_assoc($riwayat)): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?= $r['pertemuan_ke'] ?></span></td>
                                                <td><?= format_tanggal($r['tanggal']) ?></td>
                                                <td><?= format_waktu($r['jam_mulai']) ?> - <?= format_waktu($r['jam_selesai']) ?></td>
                                                <td><?= $r['nama_mk'] ?></td>
                                                <td><?= $r['materi'] ?></td>
                                                <td><?= $r['nama_lab'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $r['status'] == 'hadir' ? 'success' : ($r['status'] == 'izin' ? 'warning' : ($r['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                        <?= ucfirst($r['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $r['metode'] == 'qr' ? 'primary' : 'secondary' ?>">
                                                        <?= strtoupper($r['metode']) ?>
                                                    </span>
                                                </td>
                                                <td><small><?= date('d/m/Y H:i', strtotime($r['waktu_presensi'])) ?></small></td>
                                                <td>
                                                    <?php if ($r['ulasan_rating']): ?>
                                                        <div class="text-warning small">
                                                            <?php for($i=0; $i<$r['ulasan_rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                                        </div>
                                                    <?php elseif ($r['status'] == 'hadir'): ?>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="openUlasanModal(<?= $r['jadwal_id'] ?>, '<?= htmlspecialchars($r['nama_mk']) ?>')">
                                                            <i class="fas fa-star me-1"></i>Nilai
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-md-none">
                                <?php 
                                mysqli_data_seek($riwayat, 0);
                                while ($r = mysqli_fetch_assoc($riwayat)): ?>
                                    <div class="card mb-3 border">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?= $r['nama_mk'] ?></h6>
                                                    <small class="text-muted"><?= $r['materi'] ?></small>
                                                </div>
                                                <span class="badge bg-<?= $r['status'] == 'hadir' ? 'success' : ($r['status'] == 'izin' ? 'warning' : ($r['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                    <?= ucfirst($r['status']) ?>
                                                </span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="row small">
                                                <div class="col-6">
                                                    <i class="fas fa-calendar me-1 text-muted"></i><?= format_tanggal($r['tanggal']) ?>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <span class="badge bg-secondary">P<?= $r['pertemuan_ke'] ?></span>
                                                </div>
                                            </div>
                                            <div class="row small mt-1">
                                                <div class="col-6">
                                                    <i class="fas fa-clock me-1 text-muted"></i><?= format_waktu($r['jam_mulai']) ?>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <i class="fas fa-map-marker-alt me-1 text-muted"></i><?= $r['nama_lab'] ?>
                                                </div>
                                            </div>
                                            <div class="row small mt-1">
                                                <div class="col-6">
                                                    <span class="badge bg-<?= $r['metode'] == 'qr' ? 'primary' : 'secondary' ?>"><?= strtoupper($r['metode']) ?></span>
                                                </div>
                                                <div class="col-6 text-end text-muted">
                                                    <?= date('d/m H:i', strtotime($r['waktu_presensi'])) ?>
                                                </div>
                                            </div>
                                            <?php if ($r['status'] == 'hadir' && !$r['ulasan_rating']): ?>
                                                <div class="d-grid mt-2">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="openUlasanModal(<?= $r['jadwal_id'] ?>, '<?= htmlspecialchars($r['nama_mk']) ?>')">
                                                        <i class="fas fa-star me-1"></i>Beri Ulasan Asisten
                                                    </button>
                                                </div>
                                            <?php elseif ($r['ulasan_rating']): ?>
                                                <div class="text-center mt-2 text-warning small">
                                                    <?php for($i=0; $i<$r['ulasan_rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                                <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                                <?= render_pagination($current_page, $total_pages, 'index.php?page=mahasiswa_riwayat', []) ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada riwayat presensi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Library html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    // Clone elemen tabel untuk dimodifikasi sebelum cetak
    const element = document.getElementById('printableArea').cloneNode(true);
    
    // Hapus kolom aksi/ulasan agar rapi di PDF
    const headers = element.querySelectorAll('th');
    if(headers.length > 0) headers[headers.length-1].remove(); // Hapus header terakhir
    
    const rows = element.querySelectorAll('tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if(cells.length > 0) cells[cells.length-1].remove(); // Hapus sel terakhir
    });

    // Tambahkan judul
    const title = document.createElement('h4');
    title.innerHTML = 'Riwayat Presensi - <?= $mahasiswa['nama'] ?>';
    title.style.textAlign = 'center';
    title.style.marginBottom = '20px';
    element.insertBefore(title, element.firstChild);

    const opt = {
        margin: 10,
        filename: 'riwayat_presensi_<?= $nim ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<!-- Modal Ulasan -->
<div class="modal fade" id="modalUlasan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="kirim_ulasan" value="1">
                <input type="hidden" name="jadwal_id" id="ulasan_jadwal_id">
                <div class="modal-header">
                    <h5 class="modal-title">Beri Ulasan Praktikum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Bagaimana performa asisten dan materi pada praktikum <strong id="ulasan_mk_nama"></strong>?</p>
                    
                    <div class="mb-3 text-center">
                        <label class="form-label d-block">Rating</label>
                        <div class="rating-css">
                            <div class="star-icon">
                                <input type="radio" name="rating" value="1" id="rating1"> <label for="rating1" class="fas fa-star"></label>
                                <input type="radio" name="rating" value="2" id="rating2"> <label for="rating2" class="fas fa-star"></label>
                                <input type="radio" name="rating" value="3" id="rating3"> <label for="rating3" class="fas fa-star"></label>
                                <input type="radio" name="rating" value="4" id="rating4"> <label for="rating4" class="fas fa-star"></label>
                                <input type="radio" name="rating" value="5" id="rating5" checked> <label for="rating5" class="fas fa-star"></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Komentar / Saran (Opsional)</label>
                        <textarea name="komentar" class="form-control" rows="3" placeholder="Tulis masukan untuk asisten..."></textarea>
                    </div>
                    
                    <div class="form-check text-start">
                        <input class="form-check-input" type="checkbox" name="anonim" id="anonimCheck">
                        <label class="form-check-label small" for="anonimCheck">Kirim sebagai Anonim</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rating-css div { color: #ffe400; font-size: 30px; font-family: sans-serif; font-weight: 800; text-align: center; text-transform: uppercase; padding: 10px 0; }
.rating-css input { display: none; }
.rating-css input + label { font-size: 40px; text-shadow: 1px 1px 0 #8f8420; cursor: pointer; }
.rating-css input:checked + label ~ label { color: #b4b4b4; }
.rating-css label:active { transform: scale(0.8); transition: 0.3s ease; }
</style>

<script>
function openUlasanModal(id, nama) {
    document.getElementById('ulasan_jadwal_id').value = id;
    document.getElementById('ulasan_mk_nama').innerText = nama;
    new bootstrap.Modal(document.getElementById('modalUlasan')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
