<?php
$page = 'asisten_berita_acara';

// Ambil data asisten dari session user_id
$user_id = $_SESSION['user_id'];
$asisten = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM asisten WHERE user_id = '$user_id'"));

if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Data asisten tidak ditemukan.</div>';
    return;
}

$kode_asisten = $asisten['kode_asisten'];

// Proses Simpan BAP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_bap'])) {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $catatan = escape($_POST['catatan']);
    
    // Gabungkan tanggal jadwal dengan jam inputan
    $tanggal_jadwal = escape($_POST['tanggal_jadwal']); // Y-m-d
    $jam_mulai = escape($_POST['jam_mulai_real']);
    $jam_selesai = escape($_POST['jam_selesai_real']);
    
    $waktu_mulai_real = $tanggal_jadwal . ' ' . $jam_mulai;
    $waktu_selesai_real = $tanggal_jadwal . ' ' . $jam_selesai;
    
    // Handle Upload Foto
    $foto_bukti = null;
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['foto_bukti']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = 'bap_' . $jadwal_id . '_' . time() . '.' . $ext;
            $dest = 'uploads/bap/';
            if (!is_dir($dest)) mkdir($dest, 0777, true);
            
            if (move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $dest . $new_name)) {
                $foto_bukti = $dest . $new_name;
            }
        }
    }
    
    // Cek apakah update atau insert
    $cek = mysqli_query($conn, "SELECT id, foto_bukti FROM berita_acara WHERE jadwal_id = '$jadwal_id'");
    if (mysqli_num_rows($cek) > 0) {
        $row = mysqli_fetch_assoc($cek);
        // Jika tidak ada foto baru, pakai foto lama
        if (!$foto_bukti) $foto_bukti = $row['foto_bukti'];
        
        $stmt = mysqli_prepare($conn, "UPDATE berita_acara SET waktu_mulai_real=?, waktu_selesai_real=?, catatan=?, foto_bukti=? WHERE jadwal_id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $waktu_mulai_real, $waktu_selesai_real, $catatan, $foto_bukti, $jadwal_id);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO berita_acara (jadwal_id, kode_asisten, waktu_mulai_real, waktu_selesai_real, catatan, foto_bukti) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssss", $jadwal_id, $kode_asisten, $waktu_mulai_real, $waktu_selesai_real, $catatan, $foto_bukti);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        set_alert('success', 'Berita Acara berhasil disimpan!');
    } else {
        set_alert('danger', 'Gagal menyimpan BAP.');
    }
    
    header("Location: index.php?page=asisten_berita_acara");
    exit;
}

// Pagination
$per_page = 10;
$current_page = get_current_page();

// Hitung total data
$count_query = "SELECT COUNT(*) as total FROM jadwal j WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten') AND j.tanggal <= CURDATE()";
$count_result = mysqli_query($conn, $count_query);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Ambil Jadwal Asisten (Hanya yang hari ini atau lewat)
$query = "SELECT j.*, k.nama_kelas, mk.nama_mk, l.nama_lab,
          ba.id as bap_id, ba.waktu_mulai_real, ba.waktu_selesai_real, ba.catatan, ba.foto_bukti
          FROM jadwal j
          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
          LEFT JOIN berita_acara ba ON j.id = ba.jadwal_id
          WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
          AND j.tanggal <= CURDATE()
          ORDER BY j.tanggal DESC, j.jam_mulai DESC
          LIMIT $offset, $per_page";
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
                <h4 class="mb-4"><i class="fas fa-file-signature me-2"></i>Berita Acara Praktikum</h4>
                
                <?= show_alert() ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>Mata Kuliah</th>
                                            <th>Kelas / Lab</th>
                                            <th>Status BAP</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td>
                                                    <?= format_tanggal($row['tanggal']) ?><br>
                                                    <small class="text-muted"><?= format_waktu($row['jam_mulai']) ?> - <?= format_waktu($row['jam_selesai']) ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= $row['nama_mk'] ?></strong><br>
                                                    <small class="text-muted"><?= $row['materi'] ?></small>
                                                </td>
                                                <td>
                                                    <?= $row['nama_kelas'] ?><br>
                                                    <small class="text-primary"><i class="fas fa-map-marker-alt me-1"></i><?= $row['nama_lab'] ?></small>
                                                </td>
                                                <td>
                                                    <?php if($row['bap_id']): ?>
                                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Diisi</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle me-1"></i>Belum Diisi</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick='isiBAP(<?= json_encode($row) ?>)'>
                                                        <i class="fas fa-edit me-1"></i><?= $row['bap_id'] ? 'Edit' : 'Isi' ?> BAP
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>Belum ada jadwal praktikum yang selesai.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pagination Controls -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=asisten_berita_acara', []) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Isi BAP -->
<div class="modal fade" id="modalBAP" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="simpan_bap" value="1">
                <input type="hidden" name="jadwal_id" id="bap_jadwal_id">
                <input type="hidden" name="tanggal_jadwal" id="bap_tanggal_jadwal">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-signature me-2"></i>Isi Berita Acara</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <small id="bap_info_jadwal"></small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Mulai (Aktual)</label>
                            <input type="time" name="jam_mulai_real" id="bap_jam_mulai" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Selesai (Aktual)</label>
                            <input type="time" name="jam_selesai_real" id="bap_jam_selesai" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Catatan / Kejadian Khusus</label>
                        <textarea name="catatan" id="bap_catatan" class="form-control" rows="3" placeholder="Contoh: Komputer 05 error, Materi selesai lebih cepat, dll."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Foto Bukti Kegiatan (Opsional)</label>
                        <input type="file" name="foto_bukti" class="form-control" accept="image/*">
                        <div id="link_foto_lama" class="mt-2 small"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function isiBAP(data) {
    document.getElementById('bap_jadwal_id').value = data.id;
    document.getElementById('bap_tanggal_jadwal').value = data.tanggal;
    document.getElementById('bap_info_jadwal').innerHTML = `<strong>${data.nama_mk}</strong><br>${data.nama_kelas} - ${data.nama_lab}`;
    
    // Set waktu (ambil dari real jika ada, jika tidak ambil dari jadwal)
    let start = data.waktu_mulai_real ? data.waktu_mulai_real.split(' ')[1] : data.jam_mulai;
    let end = data.waktu_selesai_real ? data.waktu_selesai_real.split(' ')[1] : data.jam_selesai;
    
    // Format HH:mm
    document.getElementById('bap_jam_mulai').value = start.substring(0, 5);
    document.getElementById('bap_jam_selesai').value = end.substring(0, 5);
    
    document.getElementById('bap_catatan').value = data.catatan || '';
    
    let linkDiv = document.getElementById('link_foto_lama');
    if (data.foto_bukti) {
        linkDiv.innerHTML = `<a href="${data.foto_bukti}" target="_blank" class="text-decoration-none"><i class="fas fa-image me-1"></i>Lihat Foto Sebelumnya</a>`;
    } else {
        linkDiv.innerHTML = '';
    }
    
    new bootstrap.Modal(document.getElementById('modalBAP')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
