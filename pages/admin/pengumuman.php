<?php
$page = 'admin_pengumuman';

// Handle Post - prepared statements
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    if ($aksi == 'tambah') {
        $judul = escape($_POST['judul']);
        $isi = escape($_POST['isi']);
        $target = escape($_POST['target']);
        $user_id = $_SESSION['user_id'];
        
        $stmt = mysqli_prepare($conn, "INSERT INTO pengumuman (judul, isi, target_role, created_by) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssi", $judul, $isi, $target, $user_id);
        mysqli_stmt_execute($stmt);
        set_alert('success', 'Pengumuman berhasil dipublikasikan!');
    } elseif ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        $judul = escape($_POST['judul']);
        $isi = escape($_POST['isi']);
        $target = escape($_POST['target']);
        $stmt = mysqli_prepare($conn, "UPDATE pengumuman SET judul=?, isi=?, target_role=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $judul, $isi, $target, $id);
        mysqli_stmt_execute($stmt);
        set_alert('success', 'Pengumuman berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM pengumuman WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        set_alert('success', 'Pengumuman dihapus!');
    } elseif ($aksi == 'toggle_status') {
        $id = (int)$_POST['id'];
        $current_status = escape($_POST['status']);
        $new_status = ($current_status == 'active') ? 'inactive' : 'active';
        $stmt = mysqli_prepare($conn, "UPDATE pengumuman SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
        mysqli_stmt_execute($stmt);
        set_alert('success', 'Status pengumuman berhasil diubah!');
    }
    header("Location: index.php?page=admin_pengumuman");
    exit;
}

// Fetch Data - prepared statement
// Pagination
$per_page = 10;
$current_page = get_current_page();

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengumuman");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

$stmt_pengumuman = mysqli_prepare($conn, "SELECT p.*, u.username FROM pengumuman p LEFT JOIN users u ON p.created_by = u.id ORDER BY p.created_at DESC LIMIT ?, ?");
mysqli_stmt_bind_param($stmt_pengumuman, "ii", $offset, $per_page);
mysqli_stmt_execute($stmt_pengumuman);
$pengumuman_query = mysqli_stmt_get_result($stmt_pengumuman);
$pengumuman_data = [];
while ($row = mysqli_fetch_assoc($pengumuman_query)) {
    $pengumuman_data[] = $row;
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
                <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
                    <h4 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Kelola Pengumuman</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Buat Pengumuman
                    </button>
                </div>
                <?= show_alert() ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Judul & Isi</th>
                                        <th>Target</th>
                                        <th class="text-center">Status</th>
                                        <th>Oleh</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($pengumuman_data) > 0): ?>
                                        <?php foreach($pengumuman_data as $row): ?>
                                        <tr>
                                            <td style="white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['judul']) ?></strong><br>
                                                <small class="text-muted"><?= substr(htmlspecialchars($row['isi']), 0, 80) ?><?= strlen($row['isi']) > 80 ? '...' : '' ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $badge = $row['target_role'] == 'semua' ? 'bg-primary' : ($row['target_role'] == 'mahasiswa' ? 'bg-success' : 'bg-info');
                                                if ($row['target_role'] == 'admin') $badge = 'bg-danger';
                                                ?>
                                                <span class="badge <?= $badge_map[$row['target_role']] ?? 'bg-secondary' ?>"><?= ucfirst($row['target_role']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $status = $row['status'] ?? 'active';
                                                $btn_cls = $status == 'active' ? 'btn-success' : 'btn-secondary';
                                                $icon = $status == 'active' ? 'fa-toggle-on' : 'fa-toggle-off';
                                                ?>
                                                <button class="btn btn-sm <?= $btn_cls ?> rounded-pill" onclick="toggleStatus(<?= $row['id'] ?>, '<?= $status ?>')" title="Klik untuk mengubah status"><i class="fas <?= $icon ?> me-1"></i><?= ucfirst($status) ?></button>
                                            </td>
                                            <td><?= htmlspecialchars($row['username']) ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-info text-white" title="Lihat Detail" onclick='lihatPengumuman(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" title="Edit" onclick='editPengumuman(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" title="Hapus" onclick="hapusPengumuman(<?= $row['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada pengumuman.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View -->
                        <div class="d-lg-none">
                            <?php if(count($pengumuman_data) > 0): ?>
                                <?php foreach($pengumuman_data as $row): ?>
                                    <div class="card mb-3 border">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <strong class="d-block text-primary"><?= htmlspecialchars($row['judul']) ?></strong>
                                                <small class="text-muted"><?= format_tanggal_waktu($row['created_at']) ?></small>
                                                <?php 
                                                ?>
                                                <span class="badge <?= $badge_map[$row['target_role']] ?? 'bg-secondary' ?>"><?= ucfirst($row['target_role']) ?></span>
                                                <?php
                                                $status = $row['status'] ?? 'active';
                                                $badge_cls = $status == 'active' ? 'bg-success' : 'bg-secondary';
                                                $icon = $status == 'active' ? 'fa-toggle-on' : 'fa-toggle-off';
                                                ?>
                                                <button class="btn btn-sm <?= $badge_cls ?> py-0 px-2 ms-1" onclick="toggleStatus(<?= $row['id'] ?>, '<?= $status ?>')" style="font-size: 0.7rem;"><i class="fas <?= $icon ?>"></i> <?= ucfirst($status) ?></button>
                                            </div>
                                            <hr class="my-2">
                                            <p class="card-text small text-muted">
                                                <?= nl2br(htmlspecialchars(substr($row['isi'], 0, 150))) ?><?= strlen($row['isi']) > 150 ? '...' : '' ?>
                                            </p>
                                            <div class="mt-3 pt-2 border-top">
                                                <div class="mb-2">
                                                    <small class="text-muted"><i class="fas fa-user me-1"></i>Oleh: <?= htmlspecialchars($row['username']) ?></small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-info flex-fill" onclick='lihatPengumuman(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="fas fa-eye me-1"></i>Lihat
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning flex-fill" onclick='editPengumuman(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger flex-fill" onclick="hapusPengumuman(<?= $row['id'] ?>)">
                                                        <i class="fas fa-trash me-1"></i>Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">Belum ada pengumuman.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_data > 0): ?>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                            <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                            <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_pengumuman', []) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- [BARU] Form tersembunyi untuk aksi hapus -->
<form id="formHapus" method="POST" style="display: none;">
    <input type="hidden" name="aksi" value="hapus">
    <input type="hidden" name="id" id="hapus_id">
</form>

<form id="formToggle" method="POST" style="display: none;">
    <input type="hidden" name="aksi" value="toggle_status">
    <input type="hidden" name="id" id="toggle_id">
    <input type="hidden" name="status" id="toggle_status">
</form>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Buat Pengumuman Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Pengumuman</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: Perubahan Jadwal Praktikum">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Audience</label>
                        <select name="target" class="form-select">
                            <option value="semua">Semua User</option>
                            <option value="mahasiswa">Mahasiswa Saja</option>
                            <option value="asisten">Asisten Saja</option>
                            <option value="admin">Admin Saja</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi Pengumuman</label>
                        <textarea name="isi" class="form-control" rows="5" required placeholder="Tulis isi pengumuman di sini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Publikasikan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Pengumuman</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Pengumuman</label>
                        <input type="text" name="judul" id="edit_judul" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Audience</label>
                        <select name="target" id="edit_target" class="form-select">
                            <option value="semua">Semua User</option>
                            <option value="mahasiswa">Mahasiswa Saja</option>
                            <option value="asisten">Asisten Saja</option>
                            <option value="admin">Admin Saja</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi Pengumuman</label>
                        <textarea name="isi" id="edit_isi" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Lihat -->
<div class="modal fade" id="modalLihat" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lihat_judul"><i class="fas fa-info-circle me-2"></i>Detail Pengumuman</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="lihat_isi" style="white-space: pre-wrap;"></p>
                <hr>
                <small class="text-muted" id="lihat_meta"></small>
            </div>
        </div>
    </div>
</div>

<script>
function editPengumuman(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_judul').value = data.judul;
    document.getElementById('edit_isi').value = data.isi;
    document.getElementById('edit_target').value = data.target_role;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
function lihatPengumuman(data) {
    document.getElementById('lihat_judul').textContent = data.judul;
    document.getElementById('lihat_isi').textContent = data.isi;
    document.getElementById('lihat_meta').innerHTML = `Ditargetkan untuk: <strong>${data.target_role}</strong> | Dibuat oleh: <strong>${data.username}</strong>`;
    new bootstrap.Modal(document.getElementById('modalLihat')).show();
}

// [BARU] Fungsi untuk menangani hapus dengan form tersembunyi
function hapusPengumuman(id) {
    if (confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}

function toggleStatus(id, currentStatus) {
    document.getElementById('toggle_id').value = id;
    document.getElementById('toggle_status').value = currentStatus;
    document.getElementById('formToggle').submit();
}
</script>
<?php include 'includes/footer.php'; ?>