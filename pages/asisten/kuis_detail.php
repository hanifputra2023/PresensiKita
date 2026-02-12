<?php
$page = 'asisten_kuis';
$asisten = get_asisten_login();
$kuis_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil Data Kuis
$stmt = mysqli_prepare($conn, "SELECT k.*, mk.nama_mk, kl.nama_kelas 
                               FROM kuis k 
                               JOIN jadwal j ON k.jadwal_id = j.id
                               JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               JOIN kelas kl ON j.kode_kelas = kl.kode_kelas
                               WHERE k.id = ?");
mysqli_stmt_bind_param($stmt, "i", $kuis_id);
mysqli_stmt_execute($stmt);
$kuis = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$kuis) {
    set_alert('danger', 'Kuis tidak ditemukan.');
    header("Location: index.php?page=asisten_kuis");
    exit;
}

// [BARU] Download Template CSV
if (isset($_GET['download_template'])) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_soal_kuis.csv"');
    
    // BOM for Excel agar UTF-8 terbaca benar
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Pertanyaan', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Kunci Jawaban (A/B/C/D)'), ';');
    fputcsv($output, array('Apa warna langit?', 'Merah', 'Kuning', 'Hijau', 'Biru', 'D'), ';');
    fputcsv($output, array('1 + 1 = ?', '2', '3', '4', '5', 'A'), ';');
    fclose($output);
    exit;
}

// [BARU] Proses Import Soal dari CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_soal'])) {
    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
        $file = $_FILES['file_csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'csv') {
            set_alert('danger', 'Format file harus CSV.');
        } else {
            // Baca seluruh file dan deteksi delimiter
            $content = file_get_contents($file);
            // Hapus BOM jika ada
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            
            // Deteksi delimiter: hitung mana yang lebih banyak di baris pertama
            $first_line = strtok($content, "\n");
            $semicolon_count = substr_count($first_line, ';');
            $comma_count = substr_count($first_line, ',');
            $delimiter = ($semicolon_count > $comma_count) ? ';' : ',';
            
            $handle = fopen($file, "r");
            $success = 0;
            $skipped = 0;
            $row_count = 0;
            
            // Prepared statement untuk import soal
            $stmt_import = mysqli_prepare($conn, "INSERT INTO soal_kuis (kuis_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Gunakan limit besar (0 = unlimited) untuk soal panjang
            while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $row_count++;
                
                // Skip baris kosong
                if (empty($data) || (count($data) == 1 && empty(trim($data[0])))) {
                    continue;
                }
                
                // Skip header (baris pertama yang mengandung kata kunci)
                if ($row_count === 1) {
                    $first_col = strtolower(trim($data[0]));
                    if (strpos($first_col, 'pertanyaan') !== false || strpos($first_col, 'question') !== false || strpos($first_col, 'soal') !== false) {
                        continue;
                    }
                }
                
                if (count($data) >= 6) {
                    $kunci = strtoupper(trim($data[5]));
                    // Simpan ke variabel dulu karena bind_param butuh referensi
                    $pertanyaan = trim($data[0]);
                    $opsi_a = trim($data[1]);
                    $opsi_b = trim($data[2]);
                    $opsi_c = trim($data[3]);
                    $opsi_d = trim($data[4]);
                    
                    // Validasi kunci jawaban dan pertanyaan tidak kosong
                    if (in_array($kunci, ['A', 'B', 'C', 'D']) && !empty($pertanyaan)) {
                        mysqli_stmt_bind_param($stmt_import, "issssss", $kuis_id, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $kunci);
                        if (mysqli_stmt_execute($stmt_import)) {
                            $success++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
            }
            fclose($handle);
            
            $msg = "Berhasil mengimport $success soal.";
            if ($skipped > 0) {
                $msg .= " ($skipped baris dilewati karena format tidak valid)";
            }
            set_alert($success > 0 ? 'success' : 'warning', $msg);
        }
    }
    header("Location: index.php?page=asisten_kuis_detail&id=$kuis_id");
    exit;
}

// Proses Tambah Soal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_soal'])) {
    $pertanyaan = escape($_POST['pertanyaan']);
    $opsi_a = escape($_POST['opsi_a']);
    $opsi_b = escape($_POST['opsi_b']);
    $opsi_c = escape($_POST['opsi_c']);
    $opsi_d = escape($_POST['opsi_d']);
    $kunci = escape($_POST['kunci']);
    $gambar = null;
    
    // Handle upload gambar soal
    if (isset($_FILES['gambar_soal']) && $_FILES['gambar_soal']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['gambar_soal']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['gambar_soal']['size'] <= 2 * 1024 * 1024) {
            $filename = 'soal_' . $kuis_id . '_' . time() . '.' . $ext;
            $target = 'uploads/soal_kuis/' . $filename;
            if (move_uploaded_file($_FILES['gambar_soal']['tmp_name'], $target)) {
                $gambar = $target;
            }
        }
    }
    
    $stmt_soal = mysqli_prepare($conn, "INSERT INTO soal_kuis (kuis_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_soal, "isssssss", $kuis_id, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $kunci, $gambar);
    
    if (mysqli_stmt_execute($stmt_soal)) {
        set_alert('success', 'Soal berhasil ditambahkan.');
    } else {
        set_alert('danger', 'Gagal menambah soal.');
    }
    header("Location: index.php?page=asisten_kuis_detail&id=$kuis_id");
    exit;
}

// Proses Hapus Soal
if (isset($_GET['hapus_soal'])) {
    $soal_id = (int)$_GET['hapus_soal'];
    // Hapus gambar jika ada
    $get_gambar = mysqli_query($conn, "SELECT gambar FROM soal_kuis WHERE id = $soal_id AND kuis_id = $kuis_id");
    if ($row_gambar = mysqli_fetch_assoc($get_gambar)) {
        if (!empty($row_gambar['gambar']) && file_exists($row_gambar['gambar'])) {
            unlink($row_gambar['gambar']);
        }
    }
    mysqli_query($conn, "DELETE FROM soal_kuis WHERE id = $soal_id AND kuis_id = $kuis_id");
    set_alert('success', 'Soal dihapus.');
    header("Location: index.php?page=asisten_kuis_detail&id=$kuis_id");
    exit;
}

// Proses Update Status
if (isset($_GET['status'])) {
    $status_baru = escape($_GET['status']);
    if (in_array($status_baru, ['draft', 'aktif', 'selesai'])) {
        mysqli_query($conn, "UPDATE kuis SET status = '$status_baru' WHERE id = $kuis_id");
        set_alert('success', "Status kuis diubah menjadi " . ucfirst($status_baru));
        header("Location: index.php?page=asisten_kuis_detail&id=$kuis_id");
        exit;
    }
}

// Ambil Daftar Soal
$soal_list = mysqli_query($conn, "SELECT * FROM soal_kuis WHERE kuis_id = $kuis_id ORDER BY id ASC");
$total_soal_count = mysqli_num_rows($soal_list); // Hitung total soal untuk kalkulasi persentase

// Ambil Hasil Kuis
$hasil_list = mysqli_query($conn, "SELECT hk.*, m.nama 
                                   FROM hasil_kuis hk 
                                   JOIN mahasiswa m ON hk.nim = m.nim 
                                   WHERE hk.kuis_id = $kuis_id 
                                   ORDER BY hk.nilai DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mt-2 mb-0"><?= htmlspecialchars($kuis['judul']) ?></h4>
                        <small class="text-muted"><?= $kuis['nama_mk'] ?> - <?= $kuis['nama_kelas'] ?></small>
                    </div>
                    <div class="btn-group">
                        <?php if ($kuis['status'] == 'draft'): ?>
                            <a href="index.php?page=asisten_kuis_detail&id=<?= $kuis_id ?>&status=aktif" class="btn btn-success" onclick="return confirm('Aktifkan kuis? Mahasiswa akan bisa mengerjakannya.')"><i class="fas fa-play me-1"></i>Aktifkan</a>
                        <?php elseif ($kuis['status'] == 'aktif'): ?>
                            <a href="index.php?page=asisten_kuis_detail&id=<?= $kuis_id ?>&status=selesai" class="btn btn-danger" onclick="return confirm('Tutup kuis? Mahasiswa tidak bisa mengerjakan lagi.')"><i class="fas fa-stop me-1"></i>Tutup Kuis</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Daftar Soal -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Daftar Soal (<?= mysqli_num_rows($soal_list) ?>)</h6>
                                <?php if ($kuis['status'] == 'draft'): ?>
                                    <div>
                                        <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImport"><i class="fas fa-file-excel me-1"></i> Import CSV</button>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalSoal"><i class="fas fa-plus"></i> Tambah</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($soal_list) > 0): ?>
                                    <?php $no = 1; while ($s = mysqli_fetch_assoc($soal_list)): ?>
                                        <div class="border-bottom pb-3 mb-3">
                                            <div class="d-flex justify-content-between">
                                                <p class="fw-bold mb-2"><?= $no++ ?>. <?= nl2br(htmlspecialchars($s['pertanyaan'])) ?></p>
                                                <?php if ($kuis['status'] == 'draft'): ?>
                                                    <a href="index.php?page=asisten_kuis_detail&id=<?= $kuis_id ?>&hapus_soal=<?= $s['id'] ?>" class="text-danger" onclick="return confirm('Hapus soal ini?')"><i class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($s['gambar'])): ?>
                                                <div class="mb-2">
                                                    <img src="<?= $s['gambar'] ?>" alt="Gambar Soal" class="img-fluid rounded" style="max-height: 200px; cursor: pointer;" onclick="window.open('<?= $s['gambar'] ?>', '_blank')">
                                                </div>
                                            <?php endif; ?>
                                            <div class="row g-2 small">
                                                <div class="col-md-6 <?= $s['kunci_jawaban'] == 'A' ? 'text-success fw-bold' : '' ?>">A. <?= htmlspecialchars($s['opsi_a']) ?></div>
                                                <div class="col-md-6 <?= $s['kunci_jawaban'] == 'B' ? 'text-success fw-bold' : '' ?>">B. <?= htmlspecialchars($s['opsi_b']) ?></div>
                                                <div class="col-md-6 <?= $s['kunci_jawaban'] == 'C' ? 'text-success fw-bold' : '' ?>">C. <?= htmlspecialchars($s['opsi_c']) ?></div>
                                                <div class="col-md-6 <?= $s['kunci_jawaban'] == 'D' ? 'text-success fw-bold' : '' ?>">D. <?= htmlspecialchars($s['opsi_d']) ?></div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-center text-muted">Belum ada soal.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Hasil Kuis -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Hasil Mahasiswa</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama</th>
                                                <th class="text-center">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($hasil_list) > 0): ?>
                                                <?php while ($h = mysqli_fetch_assoc($hasil_list)): 
                                                    // Hitung persentase untuk warna (biar kompatibel kalau nilai bukan skala 100)
                                                    $persen = ($total_soal_count > 0) ? ($h['benar'] / $total_soal_count) * 100 : 0;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <?= htmlspecialchars($h['nama']) ?><br>
                                                            <small class="text-muted"><?= $h['nim'] ?></small>
                                                        </td>
                                                        <td class="text-center fw-bold <?= $persen >= 70 ? 'text-success' : 'text-danger' ?>">
                                                            <?= $h['nilai'] ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="2" class="text-center text-muted py-3">Belum ada yang mengerjakan.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Soal -->
<div class="modal fade" id="modalSoal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tambah_soal" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Soal Pilihan Ganda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pertanyaan</label>
                        <textarea name="pertanyaan" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Soal <small class="text-muted">(Opsional, max 2MB)</small></label>
                        <input type="file" name="gambar_soal" class="form-control" accept="image/*">
                        <div class="form-text">Format: JPG, PNG, GIF, WEBP</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opsi A</label>
                            <input type="text" name="opsi_a" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opsi B</label>
                            <input type="text" name="opsi_b" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opsi C</label>
                            <input type="text" name="opsi_c" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opsi D</label>
                            <input type="text" name="opsi_d" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kunci Jawaban</label>
                        <select name="kunci" class="form-select" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Soal -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="import_soal" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Import Soal dari CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i> Gunakan file CSV dengan format kolom:<br>
                        <strong>Pertanyaan, Opsi A, Opsi B, Opsi C, Opsi D, Kunci (A/B/C/D)</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File CSV</label>
                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                        <div class="form-text text-end"><a href="index.php?page=asisten_kuis_detail&id=<?= $kuis_id ?>&download_template=1" class="text-decoration-none"><i class="fas fa-download me-1"></i>Download Template</a></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>