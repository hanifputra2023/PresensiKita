<?php
$page = 'admin_matakuliah';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        $kode = escape($_POST['kode_mk']);
        $nama = escape($_POST['nama_mk']);
        $sks = (int)$_POST['sks'];
        $semester = escape($_POST['semester']);
        
        // Prepared statement untuk cek kode
        $stmt_cek = mysqli_prepare($conn, "SELECT * FROM mata_kuliah WHERE kode_mk = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $kode);
        mysqli_stmt_execute($stmt_cek);
        $cek = mysqli_stmt_get_result($stmt_cek);
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'Kode mata kuliah sudah ada!');
        } else {
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO mata_kuliah VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "ssis", $kode, $nama, $sks, $semester);
            mysqli_stmt_execute($stmt_ins);
            set_alert('success', 'Mata kuliah berhasil ditambahkan!');
        }
    } elseif ($aksi == 'edit') {
        $kode = escape($_POST['kode_mk']);
        $nama = escape($_POST['nama_mk']);
        $sks = (int)$_POST['sks'];
        $semester = escape($_POST['semester']);
        
        $stmt_upd = mysqli_prepare($conn, "UPDATE mata_kuliah SET nama_mk=?, sks=?, semester=? WHERE kode_mk=?");
        mysqli_stmt_bind_param($stmt_upd, "siss", $nama, $sks, $semester, $kode);
        mysqli_stmt_execute($stmt_upd);
        set_alert('success', 'Mata kuliah berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $kode = escape($_POST['kode_mk']);
        $stmt_del = mysqli_prepare($conn, "DELETE FROM mata_kuliah WHERE kode_mk = ?");
        mysqli_stmt_bind_param($stmt_del, "s", $kode);
        mysqli_stmt_execute($stmt_del);
        set_alert('success', 'Mata kuliah berhasil dihapus!');
    } elseif ($aksi == 'hapus_banyak') {
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            $stmt_del = mysqli_prepare($conn, "DELETE FROM mata_kuliah WHERE kode_mk = ?");
            foreach ($ids as $id) {
                $safe_id = escape($id);
                mysqli_stmt_bind_param($stmt_del, "s", $safe_id);
                if(mysqli_stmt_execute($stmt_del)) $success_count++;
            }
            set_alert('success', $success_count . ' Mata kuliah berhasil dihapus!');
        }
    }
    
    header("Location: index.php?page=admin_matakuliah");
    exit;
}

// Pagination
$per_page = 9;
$current_page = get_current_page();

// Search - prepared statement
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$search_param = '%' . $search . '%';

if ($search) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM mata_kuliah WHERE nama_mk LIKE ? OR kode_mk LIKE ?");
    mysqli_stmt_bind_param($stmt_count, "ss", $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} else {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM mata_kuliah");
}
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

if ($search) {
    $stmt_matkul = mysqli_prepare($conn, "SELECT * FROM mata_kuliah WHERE nama_mk LIKE ? OR kode_mk LIKE ? ORDER BY kode_mk LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_matkul, "ssii", $search_param, $search_param, $offset, $per_page);
    mysqli_stmt_execute($stmt_matkul);
    $matkul = mysqli_stmt_get_result($stmt_matkul);
} else {
    $stmt_matkul = mysqli_prepare($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_matkul, "ii", $offset, $per_page);
    mysqli_stmt_execute($stmt_matkul);
    $matkul = mysqli_stmt_get_result($stmt_matkul);
}

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($matkul) > 0): ?>
            <?php while ($m = mysqli_fetch_assoc($matkul)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 matakuliah-card position-relative" id="card-<?= $m['kode_mk'] ?>">
                        <div class="card-select-overlay">
                            <input type="checkbox" class="form-check-input item-checkbox" 
                                   value="<?= htmlspecialchars($m['kode_mk']) ?>" 
                                   onchange="toggleSelection('<?= htmlspecialchars($m['kode_mk']) ?>')">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($m['kode_mk']) ?></span>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($m['nama_mk']) ?></h5>
                                    <span class="badge bg-info mb-2"><?= htmlspecialchars($m['kode_mk']) ?></span>
                                </div>
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars($m['sks']) ?> SKS
                                </span>
                            </div>
                            <p class="text-muted mb-2"><i class="fas fa-chalkboard-teacher me-2"></i>Semester <?= htmlspecialchars($m['semester']) ?></p>
                            
                            <div class="mt-auto action-buttons">
                                <button class="btn btn-sm btn-warning" onclick="editMK('<?= htmlspecialchars($m['kode_mk'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['nama_mk'], ENT_QUOTES) ?>', '<?= $m['sks'] ?>', '<?= $m['semester'] ?>')">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                        <button class="btn btn-sm btn-danger" onclick="hapusMK('<?= htmlspecialchars($m['kode_mk'], ENT_QUOTES) ?>')">
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
                    Data mata kuliah tidak ditemukan.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_data > 0): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_matakuliah', ['search' => $search]) ?>
    </div>
    <?php endif; ?>
    <?php
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Card Selection Styles */
    .matakuliah-card { transition: all 0.2s; border: 1px solid var(--border-color); }
    .matakuliah-card.selected { border-color: var(--primary-color); background-color: rgba(0, 102, 204, 0.05); box-shadow: 0 0 0 1px var(--primary-color); }
    [data-theme="dark"] .matakuliah-card.selected { background-color: rgba(0, 102, 204, 0.15); }
    .card-select-overlay { position: absolute; top: 10px; left: 10px; z-index: 5; display: none; opacity: 0; transition: opacity 0.3s; }
    .select-mode .card-select-overlay { display: block; opacity: 1; }
    .matakuliah-card .card-body { transition: padding-top 0.3s; }
    .select-mode .matakuliah-card .card-body { padding-top: 2.5rem; }
    .item-checkbox { width: 22px; height: 22px; cursor: pointer; border: 2px solid var(--text-muted); border-radius: 50%; }
    .item-checkbox:checked { background-color: var(--primary-color); border-color: var(--primary-color); }

    /* Bulk Action Bar */
    #bulkActionBar { position: fixed; bottom: -100px; left: 0; right: 0; background: var(--bg-card); box-shadow: 0 -5px 20px rgba(0,0,0,0.1); padding: 15px 30px; z-index: 1000; transition: bottom 0.3s ease-in-out; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
    #bulkActionBar.show { bottom: 0; }
    [data-theme="dark"] #bulkActionBar { box-shadow: 0 -5px 20px rgba(0,0,0,0.3); }
    body { padding-bottom: 80px; }
    
    /* Slider Confirm */
    .slider-container { position: relative; width: 100%; height: 55px; background: #f0f2f5; border-radius: 30px; user-select: none; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); }
    [data-theme="dark"] .slider-container { background: var(--bg-input); box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }
    .slider-text { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #888; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; z-index: 1; pointer-events: none; transition: opacity 0.3s; }
    .slider-handle { position: absolute; top: 5px; left: 5px; width: 45px; height: 45px; background: #dc3545; border-radius: 50%; cursor: pointer; z-index: 2; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.1s; }
    .slider-handle:active { cursor: grabbing; transform: scale(0.95); }
    .slider-progress { position: absolute; top: 0; left: 0; height: 100%; background: rgba(220, 53, 69, 0.2); width: 0; z-index: 0; }
    .slider-container.unlocked .slider-handle { width: calc(100% - 10px); border-radius: 30px; }
    .slider-container.unlocked .slider-text { opacity: 0; }
    @media (max-width: 576px) {
        #bulkActionBar { flex-direction: column; gap: 10px; padding: 15px; }
        #bulkActionBar > div { width: 100%; display: flex; justify-content: space-between; }
        #bulkActionBar button { flex: 1; }
    }

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
    .matakuliah-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .matakuliah-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.75rem rgba(58,59,69,.2) !important;
    }
    .matakuliah-card .card-title {
        font-weight: 600;
        color: var(--text-main);
    }
    .matakuliah-card .badge.bg-info {
        background-color: var(--info-color) !important;
    }
    .matakuliah-card .card-body p i {
        width: 20px;
        text-align: center;
        color: var(--text-muted);
    }
    .matakuliah-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    .matakuliah-card .action-buttons .btn {
        flex-grow: 1;
    }
    .modal-header {
        background: var(--banner-gradient);
        color: #fff;
    }
    /* Bulk Action Bar */
    #bulkActionBar { position: fixed; bottom: -100px; left: 0; right: 0; background: var(--bg-card); box-shadow: 0 -5px 20px rgba(0,0,0,0.1); padding: 15px 30px; z-index: 1000; transition: bottom 0.3s ease-in-out; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
    #bulkActionBar.show { bottom: 0; }
    [data-theme="dark"] #bulkActionBar { box-shadow: 0 -5px 20px rgba(0,0,0,0.3); }
    body { padding-bottom: 80px; }
    
    /* Slider Confirm */
    .slider-container { position: relative; width: 100%; height: 55px; background: #f0f2f5; border-radius: 30px; user-select: none; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); }
    [data-theme="dark"] .slider-container { background: var(--bg-input); box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }
    .slider-text { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #888; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; z-index: 1; pointer-events: none; transition: opacity 0.3s; }
    .slider-handle { position: absolute; top: 5px; left: 5px; width: 45px; height: 45px; background: #dc3545; border-radius: 50%; cursor: pointer; z-index: 2; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.1s; }
    .slider-handle:active { cursor: grabbing; transform: scale(0.95); }
    .slider-progress { position: absolute; top: 0; left: 0; height: 100%; background: rgba(220, 53, 69, 0.2); width: 0; z-index: 0; }
    .slider-container.unlocked .slider-handle { width: calc(100% - 10px); border-radius: 30px; }
    .slider-container.unlocked .slider-text { opacity: 0; }
    @media (max-width: 576px) {
        #bulkActionBar { flex-direction: column; gap: 10px; padding: 15px; }
        #bulkActionBar > div { width: 100%; display: flex; justify-content: space-between; }
        #bulkActionBar button { flex: 1; }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 pt-2">
                    <h4 class="mb-0"><i class="fas fa-book me-2"></i>Kelola Mata Kuliah</h4>
                    <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Tambah Mata Kuliah
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end" onsubmit="return false;">
                            <input type="hidden" name="page" value="admin_matakuliah">
                            <div class="col-12 col-md">
                                <label for="searchInput" class="form-label small">Cari Mata Kuliah/Kode</label>
                                <input type="text" name="search" id="searchInput" class="form-control" placeholder="Ketik untuk mencari..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-12 col-md-auto d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="btnSelectMode" onclick="toggleSelectMode()">
                                    <i class="fas fa-check-square me-1"></i> Pilih
                                </button>
                                <div class="d-none d-flex align-items-center justify-content-center justify-content-md-start mb-0" id="selectAllContainer">
                                    <input class="form-check-input item-checkbox m-0" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    <label class="form-check-label fw-bold ms-2 small" for="selectAll" style="cursor:pointer">Semua</label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="matkulContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($matkul) > 0): ?>
                        <?php while ($m = mysqli_fetch_assoc($matkul)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 matakuliah-card position-relative" id="card-<?= $m['kode_mk'] ?>">
                                    <div class="card-select-overlay">
                                        <input type="checkbox" class="form-check-input item-checkbox" 
                                               value="<?= htmlspecialchars($m['kode_mk']) ?>" 
                                               onchange="toggleSelection('<?= htmlspecialchars($m['kode_mk']) ?>')">
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <span class="badge bg-primary mb-2"><?= htmlspecialchars($m['kode_mk']) ?></span>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($m['nama_mk']) ?></h5>
                                                <span class="badge bg-info mb-2"><?= htmlspecialchars($m['kode_mk']) ?></span>
                                            </div>
                                            <span class="badge bg-primary">
                                                <?= htmlspecialchars($m['sks']) ?> SKS
                                            </span>
                                        </div>
                                        <p class="text-muted mb-2"><i class="fas fa-chalkboard-teacher me-2"></i>Semester <?= htmlspecialchars($m['semester']) ?></p>
                                        
                                        <div class="mt-auto action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editMK('<?= htmlspecialchars($m['kode_mk'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['nama_mk'], ENT_QUOTES) ?>', '<?= $m['sks'] ?>', '<?= $m['semester'] ?>')">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', '<?= htmlspecialchars($m['kode_mk'], ENT_QUOTES) ?>')">
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
                                Data mata kuliah tidak ditemukan.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_matakuliah', ['search' => $search]) ?>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="bulkActionBar">
    <div class="d-flex align-items-center">
        <span class="badge bg-primary me-2" style="font-size: 1rem;"><span id="selectedCount">0</span></span>
        <span class="text-dark fw-bold">MK Dipilih</span>
    </div>
    <div>
        <button class="btn btn-secondary me-2" onclick="toggleSelectMode()">Batal</button>
        <button class="btn btn-danger" onclick="confirmSlideDelete('bulk')"><i class="fas fa-trash-alt me-2"></i>Hapus Terpilih</button>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Mata Kuliah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Mata Kuliah</label>
                        <input type="text" name="kode_mk" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SKS</label>
                        <input type="number" name="sks" class="form-control" value="3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="Ganjil" selected>Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
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
                <input type="hidden" name="kode_mk" id="edit_kode">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Mata Kuliah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SKS</label>
                        <input type="number" name="sks" id="edit_sks" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" id="edit_semester" class="form-select" required>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
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

<div class="modal fade" id="modalSlideConfirm" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger"><i class="fas fa-exclamation-triangle fa-3x"></i></div>
                <h4 class="fw-bold text-danger mb-2">Konfirmasi Hapus</h4>
                <p class="text-muted mb-4" id="slideConfirmMsg">Apakah Anda yakin? Data yang dihapus tidak dapat dikembalikan.</p>
                <div class="slider-container" id="deleteSlider">
                    <div class="slider-progress" id="sliderProgress"></div>
                    <div class="slider-text">GESER UNTUK MENGHAPUS >></div>
                    <div class="slider-handle" id="sliderHandle"><i class="fas fa-trash"></i></div>
                </div>
                <button type="button" class="btn btn-link text-muted mt-3 text-decoration-none" data-bs-dismiss="modal" id="btnCancelSlide">Batal</button>
            </div>
        </div>
    </div>
</div>

<form id="formHapus" method="POST" class="d-none"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="kode_mk" id="hapus_kode"></form>
<form id="formHapusBulk" method="POST" class="d-none"><input type="hidden" name="aksi" value="hapus_banyak"><div id="bulkInputs"></div></form>

<script>
function editMK(kode, nama, sks, semester) {
    document.getElementById('edit_kode').value = kode;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_sks').value = sks;
    document.getElementById('edit_semester').value = semester;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusMK(id) {
    confirmSlideDelete('single', id);
}

// --- Selection & Bulk Action Logic ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const container = document.getElementById('matkulContainer');
    const btn = document.getElementById('btnSelectMode');
    const selectAllContainer = document.getElementById('selectAllContainer');
    
    if (isSelectMode) {
        container.classList.add('select-mode');
        btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Batal';
        selectAllContainer.classList.remove('d-none');
        selectAllContainer.classList.add('d-flex');
    } else {
        container.classList.remove('select-mode');
        btn.classList.replace('btn-secondary', 'btn-outline-secondary');
        btn.innerHTML = '<i class="fas fa-check-square me-1"></i> Pilih';
        selectAllContainer.classList.add('d-none');
        selectAllContainer.classList.remove('d-flex');
        selectedItems.clear();
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.matakuliah-card').forEach(c => c.classList.remove('selected'));
        updateBulkUI();
    }
}

function toggleSelection(id) {
    const card = document.getElementById('card-' + id);
    const checkbox = card.querySelector('.item-checkbox');
    const idStr = String(id);
    if (checkbox.checked) { selectedItems.add(idStr); card.classList.add('selected'); }
    else { selectedItems.delete(idStr); card.classList.remove('selected'); }
    updateBulkUI();
}

function toggleSelectAll() {
    const isChecked = document.getElementById('selectAll').checked;
    document.querySelectorAll('#matkulContainer .item-checkbox').forEach(cb => {
        const id = String(cb.value);
        const card = document.getElementById('card-' + id);
        if (cb.checked !== isChecked) {
            cb.checked = isChecked;
            if (isChecked) { selectedItems.add(id); card.classList.add('selected'); }
            else { selectedItems.delete(id); card.classList.remove('selected'); }
        }
    });
    updateBulkUI();
}

function updateBulkUI() {
    const bar = document.getElementById('bulkActionBar');
    document.getElementById('selectedCount').innerText = selectedItems.size;
    if (selectedItems.size > 0) bar.classList.add('show'); else bar.classList.remove('show');
}

// --- Slide to Confirm Logic ---
let deleteType = ''; let deleteTargetId = '';
function confirmSlideDelete(type, id = null) {
    deleteType = type; deleteTargetId = id;
    const modal = new bootstrap.Modal(document.getElementById('modalSlideConfirm'));
    const msg = document.getElementById('slideConfirmMsg');
    if (type === 'bulk') msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} mata kuliah</b> terpilih.`;
    else msg.innerHTML = `Anda akan menghapus mata kuliah ini.`;
    resetSlider(); modal.show();
}

const sliderContainer = document.getElementById('deleteSlider');
const sliderHandle = document.getElementById('sliderHandle');
const sliderProgress = document.getElementById('sliderProgress');
let isDragging = false;

sliderHandle.addEventListener('mousedown', startDrag); sliderHandle.addEventListener('touchstart', startDrag);
document.addEventListener('mouseup', endDrag); document.addEventListener('touchend', endDrag);
document.addEventListener('mousemove', drag); document.addEventListener('touchmove', drag);

function startDrag(e) { isDragging = true; }
function drag(e) { if(!isDragging) return; let clientX = e.clientX || e.touches[0].clientX; let rect = sliderContainer.getBoundingClientRect(); let x = clientX - rect.left - (sliderHandle.offsetWidth/2); let max = rect.width - sliderHandle.offsetWidth; if(x<0)x=0; if(x>max)x=max; sliderHandle.style.left = x+'px'; sliderProgress.style.width = (x+20)+'px'; if(x>=max*0.95) { isDragging=false; sliderContainer.classList.add('unlocked'); sliderHandle.style.left=max+'px'; sliderProgress.style.width='100%'; performDelete(); } }
function endDrag() { if(!isDragging) return; isDragging=false; if(!sliderContainer.classList.contains('unlocked')) { sliderHandle.style.left='5px'; sliderProgress.style.width='0'; } }
function resetSlider() { sliderContainer.classList.remove('unlocked'); sliderHandle.style.left='5px'; sliderProgress.style.width='0'; document.querySelector('.slider-text').style.opacity='1'; }

function performDelete() {
    setTimeout(() => {
        if (deleteType === 'single') { document.getElementById('hapus_kode').value = deleteTargetId; document.getElementById('formHapus').submit(); }
        else {
            const container = document.getElementById('bulkInputs'); container.innerHTML = '';
            selectedItems.forEach(id => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = id; container.appendChild(input); });
            document.getElementById('formHapusBulk').submit();
        }
    }, 300);
}

// Live Search
let searchTimeout = null;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchValue = this.value;
    const container = document.getElementById('matkulContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_matakuliah&ajax_search=1&search=${encodeURIComponent(searchValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Re-apply selection state
                selectedItems.forEach(id => {
                    const cb = container.querySelector(`.item-checkbox[value="${id}"]`); if(cb) cb.checked=true;
                    const card = document.getElementById('card-'+id); if(card) card.classList.add('selected');
                });
                if(isSelectMode) container.classList.add('select-mode');
            })
            .catch(error => console.error('Error:', error));
    }, 300);
});
</script>

<?php include 'includes/footer.php'; ?>
