<?php
$page = 'admin_lab';

// Proses tambah/edit/hapus - prepared statements
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    mysqli_begin_transaction($conn);

    try {
        if ($aksi == 'tambah') {
            $kode = escape($_POST['kode_lab']);
            $nama = escape($_POST['nama_lab']);
            $kapasitas = (int)$_POST['kapasitas'];
            $lokasi = escape($_POST['lokasi']);
            $latitude = escape($_POST['latitude']);
            $longitude = escape($_POST['longitude']);
            $status = escape($_POST['status']);
            $kode_mks = isset($_POST['kode_mk']) ? (array)$_POST['kode_mk'] : [];
            
            $stmt_cek = mysqli_prepare($conn, "SELECT * FROM lab WHERE kode_lab = ?");
            mysqli_stmt_bind_param($stmt_cek, "s", $kode);
            mysqli_stmt_execute($stmt_cek);
            $cek = mysqli_stmt_get_result($stmt_cek);
            if (mysqli_num_rows($cek) > 0) {
                throw new Exception('Kode lab sudah ada!');
            }

            $stmt_ins = mysqli_prepare($conn, "INSERT INTO lab (kode_lab, nama_lab, kapasitas, lokasi, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "ssissss", $kode, $nama, $kapasitas, $lokasi, $latitude, $longitude, $status);
            mysqli_stmt_execute($stmt_ins);
            $id_lab_baru = mysqli_insert_id($conn);

            $stmt_mk = mysqli_prepare($conn, "INSERT INTO lab_matakuliah (id_lab, kode_mk) VALUES (?, ?)");
            foreach ($kode_mks as $kode_mk) {
                $kmk = escape($kode_mk);
                mysqli_stmt_bind_param($stmt_mk, "is", $id_lab_baru, $kmk);
                mysqli_stmt_execute($stmt_mk);
            }

            set_alert('success', 'Lab berhasil ditambahkan!');

        } elseif ($aksi == 'edit') {
            $id = (int)$_POST['id'];
            $nama = escape($_POST['nama_lab']);
            $kapasitas = (int)$_POST['kapasitas'];
            $lokasi = escape($_POST['lokasi']);
            $latitude = escape($_POST['latitude']);
            $longitude = escape($_POST['longitude']);
            $status = escape($_POST['status']);
            $kode_mks = isset($_POST['kode_mk']) ? (array)$_POST['kode_mk'] : [];

            $stmt_upd = mysqli_prepare($conn, "UPDATE lab SET nama_lab=?, kapasitas=?, lokasi=?, latitude=?, longitude=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_upd, "sissssi", $nama, $kapasitas, $lokasi, $latitude, $longitude, $status, $id);
            mysqli_stmt_execute($stmt_upd);
            
            // Hapus relasi lama
            $stmt_del_rel = mysqli_prepare($conn, "DELETE FROM lab_matakuliah WHERE id_lab = ?");
            mysqli_stmt_bind_param($stmt_del_rel, "i", $id);
            mysqli_stmt_execute($stmt_del_rel);
            
            // Tambah relasi baru
            $stmt_mk = mysqli_prepare($conn, "INSERT INTO lab_matakuliah (id_lab, kode_mk) VALUES (?, ?)");
            foreach ($kode_mks as $kode_mk) {
                $kmk = escape($kode_mk);
                mysqli_stmt_bind_param($stmt_mk, "is", $id, $kmk);
                mysqli_stmt_execute($stmt_mk);
            }

            set_alert('success', 'Lab berhasil diupdate!');

        } elseif ($aksi == 'hapus') {
            $id = (int)$_POST['id'];
            // ON DELETE CASCADE akan menghapus relasi di lab_matakuliah secara otomatis
            $stmt_del = mysqli_prepare($conn, "DELETE FROM lab WHERE id = ?");
            mysqli_stmt_bind_param($stmt_del, "i", $id);
            mysqli_stmt_execute($stmt_del);
            set_alert('success', 'Lab berhasil dihapus!');
        
        } elseif ($aksi == 'hapus_banyak') {
            if (isset($_POST['ids']) && is_array($_POST['ids'])) {
                $ids = $_POST['ids'];
                $success_count = 0;
                
                $stmt_del = mysqli_prepare($conn, "DELETE FROM lab WHERE id = ?");
                foreach ($ids as $id) {
                    $safe_id = (int)$id;
                    mysqli_stmt_bind_param($stmt_del, "i", $safe_id);
                    if(mysqli_stmt_execute($stmt_del)){
                        $success_count++;
                    }
                }
                set_alert('success', $success_count . ' Lab berhasil dihapus!');
            }
        }
        
        mysqli_commit($conn);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_alert('danger', 'Terjadi kesalahan: ' . $e->getMessage());
    }

    echo "<script>window.location.href='index.php?page=admin_lab';</script>";
    exit;
}

// Pagination
$per_page = 9;
$current_page = get_current_page();

// Search - prepared statement
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$search_param = '%' . $search . '%';

if ($search) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM lab l WHERE l.nama_lab LIKE ? OR l.kode_lab LIKE ? OR l.lokasi LIKE ?");
    mysqli_stmt_bind_param($stmt_count, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} else {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM lab");
}
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Query utama untuk mengambil data lab beserta matakuliah terkait - prepared statement
if ($search) {
    $stmt_labs = mysqli_prepare($conn, "
        SELECT 
            l.*, 
            GROUP_CONCAT(mk.nama_mk SEPARATOR ', ') as daftar_mata_kuliah,
            GROUP_CONCAT(mk.kode_mk SEPARATOR ',') as daftar_kode_mk
        FROM lab l 
        LEFT JOIN lab_matakuliah lm ON l.id = lm.id_lab
        LEFT JOIN mata_kuliah mk ON lm.kode_mk = mk.kode_mk
        WHERE l.nama_lab LIKE ? OR l.kode_lab LIKE ? OR l.lokasi LIKE ?
        GROUP BY l.id
        ORDER BY l.kode_lab 
        LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_labs, "sssii", $search_param, $search_param, $search_param, $offset, $per_page);
} else {
    $stmt_labs = mysqli_prepare($conn, "
        SELECT 
            l.*, 
            GROUP_CONCAT(mk.nama_mk SEPARATOR ', ') as daftar_mata_kuliah,
            GROUP_CONCAT(mk.kode_mk SEPARATOR ',') as daftar_kode_mk
        FROM lab l 
        LEFT JOIN lab_matakuliah lm ON l.id = lm.id_lab
        LEFT JOIN mata_kuliah mk ON lm.kode_mk = mk.kode_mk
        GROUP BY l.id
        ORDER BY l.kode_lab 
        LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_labs, "ii", $offset, $per_page);
}
mysqli_stmt_execute($stmt_labs);
$labs = mysqli_stmt_get_result($stmt_labs);

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($labs) > 0): ?>
            <?php while ($l = mysqli_fetch_assoc($labs)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 lab-card position-relative" id="card-<?= $l['id'] ?>">
                        <div class="card-select-overlay">
                            <input type="checkbox" class="form-check-input item-checkbox" 
                                   value="<?= $l['id'] ?>" 
                                   onchange="toggleSelection('<?= $l['id'] ?>')">
                        </div>
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
                                <?php $daftar_kode_mk_json = json_encode(explode(',', $l['daftar_kode_mk'] ?? '')); ?>
                                <button class="btn btn-sm btn-warning" onclick="editLab(<?= $l['id'] ?>, '<?= htmlspecialchars($l['nama_lab'], ENT_QUOTES) ?>', <?= $l['kapasitas'] ?>, '<?= htmlspecialchars($l['lokasi'], ENT_QUOTES) ?>', '<?= $l['latitude'] ?: 0 ?>', '<?= $l['longitude'] ?: 0 ?>', '<?= $l['status'] ?>', <?= htmlspecialchars($daftar_kode_mk_json, ENT_QUOTES) ?>)">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', <?= $l['id'] ?>)">
                                    <i class="fas fa-trash me-1"></i>Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info text-center"><i class="fas fa-info-circle me-2"></i>Data laboratorium tidak ditemukan.</div></div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_data > 0): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_lab', ['search' => $search]) ?>
    </div>
    <?php endif; ?>
    <?php
    exit;
}

$mk_list = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY nama_mk");
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Styling Dasar Layout */
    .page-header { border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem; }
    .page-header h4 { font-weight: 700; color: var(--text-main); }
    .page-header h4 i { color: var(--primary-color); }
    
    /* Card Styling & Selection */
    .lab-card { transition: all 0.2s; border: 1px solid var(--border-color); background-color: var(--bg-card); }
    .lab-card .card-title { font-weight: 600; color: var(--text-main); }
    
    .lab-card.selected { 
        border-color: var(--primary-color); 
        background-color: rgba(0, 102, 204, 0.05); 
        box-shadow: 0 0 0 1px var(--primary-color);
    }
    [data-theme="dark"] .lab-card.selected { background-color: rgba(0, 102, 204, 0.15); }

    .card-select-overlay { 
        position: absolute; top: 15px; left: 15px; z-index: 5; 
        display: none; opacity: 0; transition: opacity 0.3s ease;
    }
    .select-mode .card-select-overlay { display: block; opacity: 1; }
    
    .item-checkbox { 
        width: 22px; height: 22px; cursor: pointer; 
        border: 2px solid var(--text-muted); border-radius: 50%;
        -webkit-appearance: none; appearance: none;
        transition: background-color 0.2s, border-color 0.2s; position: relative;
    }
    .item-checkbox:checked { background-color: var(--primary-color); border-color: var(--primary-color); }
    .item-checkbox:checked::after {
        content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
        color: white; font-size: 12px; position: absolute;
        top: 50%; left: 50%; transform: translate(-50%, -50%);
    }

    /* Padding Card Body saat Select Mode agar tidak tertimpa Checkbox */
    .lab-card .card-body { padding-top: 1.5rem; transition: padding-top 0.3s ease; }
    .select-mode .lab-card .card-body { padding-top: 3.5rem; }

    .lab-card .action-buttons {
        display: flex; gap: 0.5rem; margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 1rem;
    }
    .lab-card .action-buttons .btn { flex-grow: 1; }

    /* Sticky Bottom Bar untuk Bulk Action */
    #bulkActionBar {
        position: fixed; bottom: -100px; left: 0; right: 0;
        background: var(--bg-card); box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        padding: 15px 30px; z-index: 1000;
        transition: bottom 0.3s ease-in-out;
        display: flex; justify-content: space-between; align-items: center;
        border-top: 1px solid var(--border-color);
    }
    #bulkActionBar.show { bottom: 0; }
    [data-theme="dark"] #bulkActionBar { box-shadow: 0 -5px 20px rgba(0,0,0,0.3); }
    
    /* Penyesuaian agar konten tidak tertutup bar */
    body { padding-bottom: 80px; } 

    /* SLIDER CONFIRM STYLE */
    .slider-container {
        position: relative; width: 100%; height: 55px;
        background: #f0f2f5; border-radius: 30px;
        user-select: none; overflow: hidden;
        box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
    }
    [data-theme="dark"] .slider-container { background: var(--bg-input); box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }

    .slider-text {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; color: #888; font-size: 14px;
        text-transform: uppercase; letter-spacing: 1px;
        z-index: 1; pointer-events: none; transition: opacity 0.3s;
    }
    [data-theme="dark"] .slider-text { color: var(--text-muted); }

    .slider-handle {
        position: absolute; top: 5px; left: 5px;
        width: 45px; height: 45px;
        background: #dc3545; border-radius: 50%; cursor: pointer; z-index: 2;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: transform 0.1s;
    }
    .slider-handle:active { cursor: grabbing; transform: scale(0.95); }
    .slider-progress {
        position: absolute; top: 0; left: 0; height: 100%;
        background: rgba(220, 53, 69, 0.2); width: 0; z-index: 0;
    }
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
                    <h4 class="mb-0"><i class="fas fa-flask me-2"></i>Kelola Laboratorium</h4>
                    <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Tambah Lab
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end" onsubmit="return false;">
                            <input type="hidden" name="page" value="admin_lab">
                            <div class="col-12 col-md">
                                <label for="searchInput" class="form-label small">Cari Nama/Kode Lab</label>
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

                <div id="labContainer">
                    <div class="row">
                        <?php if (mysqli_num_rows($labs) > 0): ?>
                            <?php while ($l = mysqli_fetch_assoc($labs)): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100 lab-card position-relative" id="card-<?= $l['id'] ?>">
                                        <div class="card-select-overlay">
                                            <input type="checkbox" class="form-check-input item-checkbox" 
                                                   value="<?= $l['id'] ?>" 
                                                   onchange="toggleSelection('<?= $l['id'] ?>')">
                                        </div>
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
                                                <?php $daftar_kode_mk_json = json_encode(explode(',', $l['daftar_kode_mk'] ?? '')); ?>
                                                <button class="btn btn-sm btn-warning" onclick="editLab(<?= $l['id'] ?>, '<?= htmlspecialchars($l['nama_lab'], ENT_QUOTES) ?>', <?= $l['kapasitas'] ?>, '<?= htmlspecialchars($l['lokasi'], ENT_QUOTES) ?>', '<?= $l['latitude'] ?: 0 ?>', '<?= $l['longitude'] ?: 0 ?>', '<?= $l['status'] ?>', <?= htmlspecialchars($daftar_kode_mk_json, ENT_QUOTES) ?>)">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', <?= $l['id'] ?>)">
                                                    <i class="fas fa-trash me-1"></i> Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12"><div class="alert alert-info text-center"><i class="fas fa-info-circle me-2"></i>Belum ada data laboratorium.</div></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($total_data > 0): ?>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_lab', ['search' => $search]) ?>
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
        <span class="text-dark fw-bold">Lab Dipilih</span>
    </div>
    <div>
        <button class="btn btn-secondary me-2" onclick="toggleSelectMode()">Batal</button>
        <button class="btn btn-danger" onclick="confirmSlideDelete('bulk')">
            <i class="fas fa-trash-alt me-2"></i>Hapus Terpilih
        </button>
    </div>
</div>

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
                        <label class="form-label">Koordinat (Latitude, Longitude)</label>
                        <div class="input-group mb-1">
                            <input type="text" id="tambah_koordinat" class="form-control" placeholder="Paste koordinat dari Google Maps di sini" oninput="handleCoordinatePaste('tambah_koordinat', 'tambah_latitude', 'tambah_longitude')" required>
                            <a href="https://www.google.com/maps" target="_blank" class="btn btn-outline-secondary" title="Buka Google Maps"><i class="fas fa-map-marked-alt"></i></a>
                        </div>
                        <input type="hidden" name="latitude" id="tambah_latitude">
                        <input type="hidden" name="longitude" id="tambah_longitude">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-1"></i>Cara ambil: Buka Google Maps > Klik kanan lokasi Lab > Klik angka koordinat paling atas untuk copy.
                        </small>
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
                            <?php mysqli_data_seek($mk_list, 0); while ($mk = mysqli_fetch_assoc($mk_list)): ?>
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
                        <label class="form-label">Koordinat (Latitude, Longitude)</label>
                        <div class="input-group mb-1">
                            <input type="text" id="edit_koordinat" class="form-control" placeholder="Paste koordinat dari Google Maps di sini" oninput="handleCoordinatePaste('edit_koordinat', 'edit_latitude_hidden', 'edit_longitude_hidden')" required>
                            <a href="https://www.google.com/maps" target="_blank" class="btn btn-outline-secondary" title="Buka Google Maps"><i class="fas fa-map-marked-alt"></i></a>
                        </div>
                        <input type="hidden" name="latitude" id="edit_latitude_hidden">
                        <input type="hidden" name="longitude" id="edit_longitude_hidden">
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
                            <?php mysqli_data_seek($mk_list, 0); while ($mk = mysqli_fetch_assoc($mk_list)): ?>
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

<form id="formHapusSingle" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus">
    <input type="hidden" name="id" id="hapus_id_single">
</form>

<form id="formHapusBulk" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus_banyak">
    <div id="bulkInputs"></div>
</form>

<script>
// --- LOGIKA SELEKSI (CHECKBOX) - Diadaptasi dari kelas.php ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const container = document.getElementById('labContainer');
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
        
        // Reset selection
        selectedItems.clear();
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.lab-card').forEach(c => c.classList.remove('selected'));
        updateBulkUI();
    }
}

function toggleSelection(id) {
    const card = document.getElementById('card-' + id);
    const checkbox = card.querySelector('.item-checkbox');
    
    // Pastikan ID diperlakukan sebagai string agar konsisten dengan Set
    const idStr = String(id);
    
    if (checkbox.checked) {
        selectedItems.add(idStr);
        card.classList.add('selected');
    } else {
        selectedItems.delete(idStr);
        card.classList.remove('selected');
    }
    updateBulkUI();
}

function toggleSelectAll() {
    const masterCheck = document.getElementById('selectAll');
    const isChecked = masterCheck.checked;
    const itemCheckboxes = document.querySelectorAll('#labContainer .item-checkbox');

    itemCheckboxes.forEach(cb => {
        const id = String(cb.value);
        const card = document.getElementById('card-' + id);

        if (cb.checked !== isChecked) {
            cb.checked = isChecked;
            if (isChecked) {
                selectedItems.add(id);
                card.classList.add('selected');
            } else {
                selectedItems.delete(id);
                card.classList.remove('selected');
            }
        }
    });
    updateBulkUI();
}

function updateBulkUI() {
    const bar = document.getElementById('bulkActionBar');
    const countSpan = document.getElementById('selectedCount');
    const masterCheck = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('#labContainer .item-checkbox');
    
    countSpan.innerText = selectedItems.size;
    
    if (selectedItems.size > 0) {
        bar.classList.add('show');
        if (itemCheckboxes.length > 0 && selectedItems.size === itemCheckboxes.length) {
            masterCheck.checked = true;
        } else {
            masterCheck.checked = false;
        }
    } else {
        bar.classList.remove('show');
    }
}

// --- LOGIKA EDIT LAB ---
function editLab(id, nama, kapasitas, lokasi, lat, long, status, daftar_kode_mk) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_kapasitas').value = kapasitas;
    document.getElementById('edit_lokasi').value = lokasi;
    document.getElementById('edit_status').value = status;    

    const latVal = lat || 0;
    const longVal = long || 0;
    document.getElementById('edit_koordinat').value = latVal + ', ' + longVal;
    document.getElementById('edit_latitude_hidden').value = latVal;
    document.getElementById('edit_longitude_hidden').value = longVal;

    const kode_mk_values = Array.isArray(daftar_kode_mk) ? daftar_kode_mk.filter(Boolean) : [];
    
    const checkboxes = document.querySelectorAll('#edit_kode_mk_container .form-check-input');
    checkboxes.forEach(cb => { cb.checked = false; });

    kode_mk_values.forEach(kode_mk => {
        const checkbox = document.getElementById('edit_mk_' + kode_mk);
        if (checkbox) { checkbox.checked = true; }
    });
    
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// --- LOGIKA SLIDE TO CONFIRM ---
let deleteType = ''; 
let deleteTargetId = ''; 

function confirmSlideDelete(type, id = null) {
    deleteType = type;
    deleteTargetId = id;
    
    const modal = new bootstrap.Modal(document.getElementById('modalSlideConfirm'));
    const msg = document.getElementById('slideConfirmMsg');
    
    if (type === 'bulk') {
        msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} lab</b> terpilih.<br>Aksi ini tidak dapat dibatalkan.`;
    } else {
        msg.innerHTML = `Anda akan menghapus Lab ID <b>#${id}</b>.<br>Aksi ini tidak dapat dibatalkan.`;
    }
    
    resetSlider();
    modal.show();
}

const sliderContainer = document.getElementById('deleteSlider');
const sliderHandle = document.getElementById('sliderHandle');
const sliderProgress = document.getElementById('sliderProgress');
let isDragging = false;

sliderHandle.addEventListener('mousedown', startDrag);
sliderHandle.addEventListener('touchstart', startDrag);
document.addEventListener('mouseup', endDrag);
document.addEventListener('touchend', endDrag);
document.addEventListener('mousemove', drag);
document.addEventListener('touchmove', drag);

function startDrag(e) { isDragging = true; }
function drag(e) {
    if (!isDragging) return;
    let clientX = e.clientX || e.touches[0].clientX;
    let rect = sliderContainer.getBoundingClientRect();
    let x = clientX - rect.left - (sliderHandle.offsetWidth / 2);
    let max = rect.width - sliderHandle.offsetWidth;
    if (x < 0) x = 0; if (x > max) x = max;
    sliderHandle.style.left = x + 'px';
    sliderProgress.style.width = (x + 20) + 'px';
    if (x >= max * 0.95) {
        isDragging = false;
        sliderContainer.classList.add('unlocked');
        sliderHandle.style.left = max + 'px';
        sliderProgress.style.width = '100%';
        performDelete();
    }
}
function endDrag() {
    if (!isDragging) return; isDragging = false;
    if (!sliderContainer.classList.contains('unlocked')) {
        sliderHandle.style.left = '5px'; sliderProgress.style.width = '0';
    }
}
function resetSlider() {
    sliderContainer.classList.remove('unlocked');
    sliderHandle.style.left = '5px'; sliderProgress.style.width = '0';
    document.querySelector('.slider-text').style.opacity = '1';
}

function performDelete() {
    setTimeout(() => {
        if (deleteType === 'single') {
            document.getElementById('hapus_id_single').value = deleteTargetId;
            document.getElementById('formHapusSingle').submit();
        } else if (deleteType === 'bulk') {
            const container = document.getElementById('bulkInputs');
            container.innerHTML = '';
            selectedItems.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                container.appendChild(input);
            });
            document.getElementById('formHapusBulk').submit();
        }
    }, 300);
}

function handleCoordinatePaste(sourceId, latTargetId, lonTargetId) {
    const sourceInput = document.getElementById(sourceId);
    const latInput = document.getElementById(latTargetId);
    const lonInput = document.getElementById(lonTargetId);
    const value = sourceInput.value;
    if (value.includes(',')) {
        const parts = value.split(',');
        latInput.value = parts[0].trim();
        lonInput.value = parts[1].trim();
    }
}

// Live Search with State Persistence
let searchTimeout = null;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchValue = this.value;
    const container = document.getElementById('labContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_lab&ajax_search=1&search=${encodeURIComponent(searchValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Re-apply selection states
                selectedItems.forEach(id => {
                    const checkbox = container.querySelector(`.item-checkbox[value="${id}"]`);
                    if (checkbox) checkbox.checked = true;
                    const card = document.getElementById('card-' + id);
                    if(card) card.classList.add('selected');
                });
                // Re-apply Select Mode Layout
                if (isSelectMode) {
                    container.classList.add('select-mode');
                }
                updateBulkUI();
            })
            .catch(error => console.error('Error:', error));
    }, 300);
});
</script>

<?php include 'includes/footer.php'; ?>