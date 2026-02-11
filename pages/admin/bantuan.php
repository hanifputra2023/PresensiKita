<?php
$page = 'admin_bantuan'; // Identifier untuk sidebar
cek_role(['admin']); // Pastikan hanya admin yang akses

// Proses Simpan Balasan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_id'])) {
    $id = (int)$_POST['ticket_id'];
    $tanggapan = escape($_POST['tanggapan']);
    $status = escape($_POST['status']);
    
    // Update database
    $stmt = mysqli_prepare($conn, "UPDATE tiket_bantuan SET tanggapan = ?, status = ?, updated_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $tanggapan, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Ambil info tiket untuk log (opsional)
        $info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nim FROM tiket_bantuan WHERE id=$id"));
        log_aktivitas($_SESSION['user_id'], 'BALAS_TIKET', 'tiket_bantuan', $id, "Admin membalas tiket dari NIM " . $info['nim']);
        
        set_alert('success', 'Tanggapan berhasil disimpan!');
    } else {
        set_alert('danger', 'Gagal menyimpan tanggapan: ' . mysqli_error($conn));
    }
    header("Location: index.php?page=admin_bantuan&tab=" . $status);
    exit;
}

// Proses Hapus Tiket
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_id'])) {
    $id = (int)$_POST['hapus_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM tiket_bantuan WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        log_aktivitas($_SESSION['user_id'], 'HAPUS_TIKET', 'tiket_bantuan', $id, "Admin menghapus tiket bantuan");
        set_alert('success', 'Tiket berhasil dihapus.');
    } else {
        set_alert('danger', 'Gagal menghapus tiket: ' . mysqli_error($conn));
    }
    header("Location: index.php?page=admin_bantuan&tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
}

// Proses Hapus Tanggapan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_tanggapan_id'])) {
    $id = (int)$_POST['hapus_tanggapan_id'];
    $stmt = mysqli_prepare($conn, "UPDATE tiket_bantuan SET tanggapan = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        log_aktivitas($_SESSION['user_id'], 'HAPUS_TANGGAPAN', 'tiket_bantuan', $id, "Admin menghapus tanggapan tiket");
        set_alert('success', 'Tanggapan berhasil dihapus.');
    } else {
        set_alert('danger', 'Gagal menghapus tanggapan: ' . mysqli_error($conn));
    }
    header("Location: index.php?page=admin_bantuan&tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
}

// Tab Logic
$active_tab = $_GET['tab'] ?? 'pending';
$where_clause = "";

if ($active_tab == 'pending') {
    $where_clause = "WHERE t.status = 'pending'";
} elseif ($active_tab == 'proses') {
    $where_clause = "WHERE t.status = 'proses'";
} elseif ($active_tab == 'selesai') {
    $where_clause = "WHERE t.status = 'selesai'";
} elseif ($active_tab == 'ditolak') {
    $where_clause = "WHERE t.status = 'ditolak'";
}

// Hitung badge notifikasi dan ambil tiket, tetapi lindungi jika tabel tidak ada
$tbl_exists = mysqli_query($conn, "SHOW TABLES LIKE 'tiket_bantuan'");
if (!$tbl_exists || mysqli_num_rows($tbl_exists) == 0) {
    // Tabel tidak ditemukan — hindari query fatal dan beri notifikasi ringan
    set_alert('danger', 'Tabel <code>tiket_bantuan</code> tidak ditemukan. Modul bantuan dinonaktifkan sementara.');
    $count_pending = 0;
    $count_proses = 0;
    $tickets = [];
} else {
    $count_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tiket_bantuan WHERE status = 'pending'"))['total'];
    $count_proses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tiket_bantuan WHERE status = 'proses'"))['total'];

    // Ambil Data Tiket (Join dengan tabel mahasiswa dan kelas untuk info lengkap)
    $query = "SELECT t.*, 
              COALESCE(m.nama, a.nama) as nama_pengirim, 
              COALESCE(k.nama_kelas, 'Asisten') as info_tambahan,
              t.nim as id_pengirim
              FROM tiket_bantuan t 
              LEFT JOIN mahasiswa m ON t.nim = m.nim 
              LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
              LEFT JOIN asisten a ON t.nim = a.kode_asisten
              $where_clause
              ORDER BY t.created_at DESC";
    $result = mysqli_query($conn, $query);
    $tickets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tickets[] = $row;
    }
}
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
                    <h4 class="mb-0"><i class="fas fa-headset me-2"></i>Manajemen Pesan Bantuan</h4>
                </div>
                
                <?= show_alert() ?>
                
                <!-- Tab Navigation -->
                <ul class="nav nav-pills mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'pending' ? 'active' : '' ?>" href="index.php?page=admin_bantuan&tab=pending">
                            <i class="fas fa-clock me-1"></i>Menunggu 
                            <?php if($count_pending > 0): ?><span class="badge bg-warning text-dark ms-1"><?= $count_pending ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'proses' ? 'active' : '' ?>" href="index.php?page=admin_bantuan&tab=proses">
                            <i class="fas fa-spinner me-1"></i>Diproses
                            <?php if($count_proses > 0): ?><span class="badge bg-info text-dark ms-1"><?= $count_proses ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'selesai' ? 'active' : '' ?>" href="index.php?page=admin_bantuan&tab=selesai">
                            <i class="fas fa-check-circle me-1"></i>Selesai
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'ditolak' ? 'active' : '' ?>" href="index.php?page=admin_bantuan&tab=ditolak">
                            <i class="fas fa-times-circle me-1"></i>Ditolak
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'all' ? 'active' : '' ?>" href="index.php?page=admin_bantuan&tab=all">
                            <i class="fas fa-list me-1"></i>Semua
                        </a>
                    </li>
                </ul>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0 p-md-3">
                        <!-- Desktop View (Table) -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Pengirim</th>
                                        <th>Kategori</th>
                                        <th>Subjek</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($tickets) > 0): ?>
                                        <?php foreach ($tickets as $row): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $badge = 'secondary';
                                                    if ($row['status'] == 'pending') $badge = 'warning text-dark';
                                                    elseif ($row['status'] == 'proses') $badge = 'info text-dark';
                                                    elseif ($row['status'] == 'selesai') $badge = 'success';
                                                    elseif ($row['status'] == 'ditolak') $badge = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span>
                                                </td>
                                                <td><small><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['nama_pengirim']) ?></strong><br>
                                                    <small class="text-muted"><?= $row['id_pengirim'] ?> - <?= $row['info_tambahan'] ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($row['subjek']) ?>
                                                    <?php if (!empty($row['lampiran'])): ?>
                                                        <i class="fas fa-paperclip text-muted ms-1" title="Ada lampiran"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-info text-white btn-balas" 
                                                                data-bs-toggle="modal" data-bs-target="#modalBalas"
                                                                data-id="<?= $row['id'] ?>"
                                                                data-pengirim="<?= htmlspecialchars($row['nama_pengirim']) ?>"
                                                                data-pesan="<?= htmlspecialchars($row['pesan']) ?>"
                                                                data-lampiran="<?= htmlspecialchars($row['lampiran'] ?? '') ?>"
                                                                data-tanggapan="<?= htmlspecialchars($row['tanggapan']) ?>"
                                                                data-status="<?= $row['status'] ?>" title="Lihat & Balas">
                                                            <i class="fas fa-eye me-1"></i>Lihat & Balas
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="hapusTiket(<?= $row['id'] ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">Belum ada tiket bantuan masuk.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View (Cards) -->
                        <div class="d-md-none p-3">
                            <?php if (count($tickets) > 0): ?>
                                <?php foreach ($tickets as $row): ?>
                                    <div class="card mb-3 border shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <?php
                                                    $badge = 'secondary';
                                                    if ($row['status'] == 'pending') $badge = 'warning text-dark';
                                                    elseif ($row['status'] == 'proses') $badge = 'info text-dark';
                                                    elseif ($row['status'] == 'selesai') $badge = 'success';
                                                    elseif ($row['status'] == 'ditolak') $badge = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?= $badge ?> mb-1"><?= ucfirst($row['status']) ?></span>
                                                    <div class="small text-muted"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></div>
                                                </div>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['kategori']) ?></span>
                                            </div>
                                            
                                            <h6 class="card-title fw-bold mb-1">
                                                <?= htmlspecialchars($row['subjek']) ?>
                                                <?php if (!empty($row['lampiran'])): ?>
                                                    <i class="fas fa-paperclip text-muted ms-1"></i>
                                                <?php endif; ?>
                                            </h6>
                                            <div class="small text-muted mb-3">
                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($row['nama_pengirim']) ?> (<?= $row['id_pengirim'] ?>)
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-info text-white flex-fill" 
                                                        data-bs-toggle="modal" data-bs-target="#modalBalas"
                                                        data-id="<?= $row['id'] ?>"
                                                        data-pengirim="<?= htmlspecialchars($row['nama_pengirim']) ?>"
                                                        data-pesan="<?= htmlspecialchars($row['pesan']) ?>"
                                                        data-lampiran="<?= htmlspecialchars($row['lampiran'] ?? '') ?>"
                                                        data-tanggapan="<?= htmlspecialchars($row['tanggapan']) ?>"
                                                        data-status="<?= $row['status'] ?>">
                                                    <i class="fas fa-eye me-1"></i>Lihat & Balas
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="hapusTiket(<?= $row['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                                    <p>Belum ada tiket bantuan masuk.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Balas -->
<div class="modal fade" id="modalBalas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tanggapi Pesan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="ticket_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pesan Dari (<span id="nama_pengirim"></span>)</label>
                        <div class="p-3 bg-light border rounded" id="isi_pesan" style="white-space: pre-wrap;"></div>
                    </div>
                    
                    <div class="mb-3" id="area_lampiran" style="display: none;">
                        <label class="form-label fw-bold">Lampiran</label>
                        <div>
                            <button type="button" id="btn_view_lampiran" class="btn btn-sm btn-outline-primary" onclick="viewLampiran()"><i class="fas fa-eye me-1"></i>Lihat Lampiran</button>
                        </div>
                        <div id="preview_lampiran" class="mt-3 bg-light p-2 rounded border text-center" style="display: none;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggapan Admin</label>
                        <textarea name="tanggapan" id="isi_tanggapan" class="form-control" rows="5" placeholder="Tulis balasan Anda di sini..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Update Status</label>
                        <select name="status" id="status_tiket" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="proses">Sedang Proses</option>
                            <option value="selesai">Selesai</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="btnHapusTanggapan" onclick="hapusTanggapan()" style="display: none;">
                        <i class="fas fa-trash-alt me-1"></i>Hapus Tanggapan
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan Tanggapan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus (Konfirmasi) -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Tiket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus tiket ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="hapus_id" id="hapus_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Form Hapus Tanggapan (Hidden) -->
<form id="formHapusTanggapan" method="POST" class="d-none">
    <input type="hidden" name="hapus_tanggapan_id" id="hapus_tanggapan_id">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalBalas = document.getElementById('modalBalas');
    modalBalas.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        document.getElementById('ticket_id').value = button.getAttribute('data-id');
        document.getElementById('nama_pengirim').textContent = button.getAttribute('data-pengirim');
        document.getElementById('isi_pesan').textContent = button.getAttribute('data-pesan');
        
        const lampiran = button.getAttribute('data-lampiran');
        if (lampiran) {
            document.getElementById('area_lampiran').style.display = 'block';
            document.getElementById('btn_view_lampiran').setAttribute('data-src', lampiran);
        } else {
            document.getElementById('area_lampiran').style.display = 'none';
        }
        
        // Reset preview state saat modal dibuka
        document.getElementById('preview_lampiran').style.display = 'none';
        document.getElementById('preview_lampiran').innerHTML = '';
        document.getElementById('btn_view_lampiran').innerHTML = '<i class="fas fa-eye me-1"></i>Lihat Lampiran';
        
        const tanggapan = button.getAttribute('data-tanggapan') || '';
        document.getElementById('isi_tanggapan').value = tanggapan;
        document.getElementById('status_tiket').value = button.getAttribute('data-status');
        
        // Tampilkan tombol hapus hanya jika ada tanggapan
        const btnHapus = document.getElementById('btnHapusTanggapan');
        if (tanggapan.trim() !== '') {
            btnHapus.style.display = 'block';
        } else {
            btnHapus.style.display = 'none';
        }
    });
});

function hapusTiket(id) {
    document.getElementById('hapus_id').value = id;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

function viewLampiran() {
    const btn = document.getElementById('btn_view_lampiran');
    const src = btn.getAttribute('data-src');
    const container = document.getElementById('preview_lampiran');
    
    // Toggle logic: Jika sudah terbuka, tutup
    if (container.style.display === 'block') {
        container.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-eye me-1"></i>Lihat Lampiran';
        return;
    }

    if (!src) return;
    
    const ext = src.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png'].includes(ext)) {
        container.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white border rounded shadow-sm">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomImg(-25)" title="Perkecil"><i class="fas fa-search-minus"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomImg(0)" title="Reset Ukuran">100%</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomImg(25)" title="Perbesar"><i class="fas fa-search-plus"></i></button>
                </div>
                <a href="${src}" target="_blank" class="btn btn-sm btn-primary" title="Buka di Tab Baru"><i class="fas fa-external-link-alt me-1"></i>Full</a>
            </div>
            <div style="overflow: auto; max-height: 60vh; border: 1px solid #dee2e6; background-color: #f8f9fa; text-align: center;">
                <img id="img_preview" src="${src}" style="max-width: 100%; height: auto; transition: width 0.2s ease;" alt="Lampiran">
            </div>
        `;
    } else if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${src}" style="width:100%; height:400px;" frameborder="0"></iframe>`;
    } else {
        container.innerHTML = `<div class="p-4"><i class="fas fa-file-alt fa-3x text-muted mb-3"></i><p class="text-muted mb-3">Format file tidak dapat dipreview.</p><a href="${src}" target="_blank" class="btn btn-primary"><i class="fas fa-download me-1"></i>Download File</a></div>`;
    }
    
    container.style.display = 'block';
    btn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Tutup Lampiran';
}

function zoomImg(step) {
    const img = document.getElementById('img_preview');
    if (!img) return;
    
    // Ambil width saat ini (default 100 jika belum diset)
    let currentWidth = 100;
    if (img.style.width && img.style.width.includes('%')) {
        currentWidth = parseInt(img.style.width);
    }
    
    if (step === 0) {
        // Reset ke kondisi awal (fit container)
        img.style.maxWidth = '100%';
        img.style.width = 'auto';
    } else {
        let newWidth = currentWidth + step;
        if (newWidth < 25) newWidth = 25; // Batas minimal 25%
        
        // Hapus max-width agar bisa melebihi container
        img.style.maxWidth = 'none'; 
        img.style.width = newWidth + '%';
    }
}

function hapusTanggapan() {
    if(confirm('Apakah Anda yakin ingin menghapus tanggapan ini?')) {
        const id = document.getElementById('ticket_id').value;
        document.getElementById('hapus_tanggapan_id').value = id;
        document.getElementById('formHapusTanggapan').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>