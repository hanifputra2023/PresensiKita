<?php
$page = 'admin_lab';

// Proses tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    mysqli_begin_transaction($conn);

    try {
        if ($aksi == 'tambah') {
            $kode = escape($_POST['kode_lab']);
            $nama = escape($_POST['nama_lab']);
            $kapasitas = (int)$_POST['kapasitas'];
            $lokasi = escape($_POST['lokasi']);
            $status = escape($_POST['status']);
            $kode_mks = isset($_POST['kode_mk']) ? (array)$_POST['kode_mk'] : [];
            
            $cek = mysqli_query($conn, "SELECT * FROM lab WHERE kode_lab = '$kode'");
            if (mysqli_num_rows($cek) > 0) {
                throw new Exception('Kode lab sudah ada!');
            }

            mysqli_query($conn, "INSERT INTO lab (kode_lab, nama_lab, kapasitas, lokasi, status) VALUES ('$kode', '$nama', '$kapasitas', '$lokasi', '$status')");
            $id_lab_baru = mysqli_insert_id($conn);

            foreach ($kode_mks as $kode_mk) {
                $kmk = escape($kode_mk);
                mysqli_query($conn, "INSERT INTO lab_matakuliah (id_lab, kode_mk) VALUES ('$id_lab_baru', '$kmk')");
            }

            set_alert('success', 'Lab berhasil ditambahkan!');

        } elseif ($aksi == 'edit') {
            $id = (int)$_POST['id'];
            $nama = escape($_POST['nama_lab']);
            $kapasitas = (int)$_POST['kapasitas'];
            $lokasi = escape($_POST['lokasi']);
            $status = escape($_POST['status']);
            $kode_mks = isset($_POST['kode_mk']) ? (array)$_POST['kode_mk'] : [];

            mysqli_query($conn, "UPDATE lab SET nama_lab='$nama', kapasitas='$kapasitas', lokasi='$lokasi', status='$status' WHERE id='$id'");
            
            // Hapus relasi lama
            mysqli_query($conn, "DELETE FROM lab_matakuliah WHERE id_lab = '$id'");
            
            // Tambah relasi baru
            foreach ($kode_mks as $kode_mk) {
                $kmk = escape($kode_mk);
                mysqli_query($conn, "INSERT INTO lab_matakuliah (id_lab, kode_mk) VALUES ('$id', '$kmk')");
            }

            set_alert('success', 'Lab berhasil diupdate!');

        } elseif ($aksi == 'hapus') {
            $id = (int)$_POST['id'];
            // ON DELETE CASCADE akan menghapus relasi di lab_matakuliah secara otomatis
            mysqli_query($conn, "DELETE FROM lab WHERE id = '$id'");
            set_alert('success', 'Lab berhasil dihapus!');
        }
        
        mysqli_commit($conn);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_alert('danger', 'Terjadi kesalahan: ' . $e->getMessage());
    }
    
    header("Location: index.php?page=admin_lab");
    exit;
}

// Pagination
$per_page = 9;
$current_page = get_current_page();

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM lab");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Query utama untuk mengambil data lab beserta matakuliah terkait
$labs_query = "
    SELECT 
        l.*, 
        GROUP_CONCAT(mk.nama_mk SEPARATOR ', ') as daftar_mata_kuliah,
        GROUP_CONCAT(mk.kode_mk SEPARATOR ',') as daftar_kode_mk
    FROM lab l 
    LEFT JOIN lab_matakuliah lm ON l.id = lm.id_lab
    LEFT JOIN mata_kuliah mk ON lm.kode_mk = mk.kode_mk
    GROUP BY l.id
    ORDER BY l.kode_lab 
    LIMIT $offset, $per_page";
$labs = mysqli_query($conn, $labs_query);

$mk_list = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY nama_mk");
?>
<?php include 'includes/header.php'; ?>

<style>
    .page-header {
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }
    .page-header h4 {
        font-weight: 700;
        color: var(--text-main);
    }
    .page-header h4 i {
        color: var(--primary-color);
    }
    .lab-card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: var(--card-shadow);
    }
    .lab-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 2rem 0 rgba(0, 0, 0, 0.15);
    }
    .lab-card .card-title {
        font-weight: 600;
        color: var(--text-main);
    }
    .lab-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    .lab-card .action-buttons .btn {
        flex-grow: 1;
    }
    .modal-header {
        background: var(--banner-gradient);
        color: #fff;
    }
    @media (max-width: 575.98px) {
        .content-wrapper.p-4 { padding: 1.25rem 1rem !important; }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_admin.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 pt-2">
                    <h4 class="mb-0"><i class="fas fa-flask me-2"></i>Kelola Laboratorium</h4>
                    <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Tambah Lab
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <?php if (mysqli_num_rows($labs) > 0): ?>
                        <?php while ($l = mysqli_fetch_assoc($labs)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 lab-card">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <span class="badge bg-primary mb-2"><?= htmlspecialchars($l['kode_lab']) ?></span>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($l['nama_lab']) ?></h5>
                                            </div>
                                            <span class="badge <?= $l['status'] == 'active' ? 'bg-success' : 'bg-warning' ?> text-capitalize">
                                                <?= htmlspecialchars($l['status']) ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-2"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($l['lokasi']) ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-users me-2"></i>Kapasitas: <?= htmlspecialchars($l['kapasitas']) ?> orang</p>
                                        <p class="text-muted mb-2"><i class="fas fa-book me-2"></i><?= htmlspecialchars($l['daftar_mata_kuliah'] ?: 'Belum diset') ?></p>
                                        
                                        <div class="mt-auto action-buttons">
                                            <?php
                                            $daftar_kode_mk_json = json_encode(explode(',', $l['daftar_kode_mk']));
                                            ?>
                                            <button class="btn btn-sm btn-warning" onclick="editLab(<?= $l['id'] ?>, '<?= htmlspecialchars($l['nama_lab'], ENT_QUOTES) ?>', <?= $l['kapasitas'] ?>, '<?= htmlspecialchars($l['lokasi'], ENT_QUOTES) ?>', '<?= $l['status'] ?>', <?= htmlspecialchars($daftar_kode_mk_json, ENT_QUOTES) ?>)">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="hapusLab(<?= $l['id'] ?>)">
                                                <i class="fas fa-trash me-1"></i>Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                Belum ada data laboratorium. Silakan tambahkan lab baru.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_lab', []) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Lab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Lab</label>
                        <input type="text" name="kode_lab" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lab</label>
                        <input type="text" name="nama_lab" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" name="kapasitas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matakuliah yang Diizinkan</label>
                        <div class="border rounded p-2" style="height: 150px; overflow-y: auto;">
                            <?php 
                            mysqli_data_seek($mk_list, 0);
                            while ($mk = mysqli_fetch_assoc($mk_list)): 
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="kode_mk[]" value="<?= $mk['kode_mk'] ?>" id="tambah_mk_<?= $mk['kode_mk'] ?>">
                                <label class="form-check-label" for="tambah_mk_<?= $mk['kode_mk'] ?>">
                                    <?= htmlspecialchars($mk['nama_mk']) ?>
                                </label>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Lab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lab</label>
                        <input type="text" name="nama_lab" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" name="kapasitas" id="edit_kapasitas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="lokasi" id="edit_lokasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matakuliah yang Diizinkan</label>
                        <div id="edit_kode_mk_container" class="border rounded p-2" style="height: 150px; overflow-y: auto;">
                            <?php 
                            mysqli_data_seek($mk_list, 0);
                            while ($mk = mysqli_fetch_assoc($mk_list)): 
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="kode_mk[]" value="<?= $mk['kode_mk'] ?>" id="edit_mk_<?= $mk['kode_mk'] ?>">
                                <label class="form-check-label" for="edit_mk_<?= $mk['kode_mk'] ?>">
                                    <?= htmlspecialchars($mk['nama_mk']) ?>
                                </label>
                            </div>
                            <?php endwhile; ?>
                        </div>
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

<form id="formHapus" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus">
    <input type="hidden" name="id" id="hapus_id">
</form>

<script>
function editLab(id, nama, kapasitas, lokasi, status, daftar_kode_mk) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_kapasitas').value = kapasitas;
    document.getElementById('edit_lokasi').value = lokasi;
    document.getElementById('edit_status').value = status;
    
    // Filter out potential empty string from GROUP_CONCAT if no MK is associated
    const kode_mk_values = Array.isArray(daftar_kode_mk) ? daftar_kode_mk.filter(Boolean) : [];
    
    // Reset all checkboxes first
    const checkboxes = document.querySelectorAll('#edit_kode_mk_container .form-check-input');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });

    // Check the ones that are in the daftar_kode_mk array
    kode_mk_values.forEach(kode_mk => {
        const checkbox = document.getElementById('edit_mk_' + kode_mk);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusLab(id) {
    if (confirm('Yakin ingin menghapus lab ini?')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
