<?php
$page = 'admin_kelas';

// Proses tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        // ... (Kode tambah sama seperti sebelumnya) ...
        $kode = escape($_POST['kode_kelas']);
        $nama = escape($_POST['nama_kelas']);
        $prodi = escape($_POST['program_studi']);
        $tahun = escape($_POST['tahun_ajaran']);
        
        $stmt_cek = mysqli_prepare($conn, "SELECT * FROM kelas WHERE kode_kelas = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $kode);
        mysqli_stmt_execute($stmt_cek);
        $cek = mysqli_stmt_get_result($stmt_cek);
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'Kode kelas sudah ada!');
        } else {
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO kelas VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "ssss", $kode, $nama, $prodi, $tahun);
            mysqli_stmt_execute($stmt_ins);
            set_alert('success', 'Kelas berhasil ditambahkan!');
        }

    } elseif ($aksi == 'edit') {
        // ... (Kode edit sama seperti sebelumnya) ...
        $kode = escape($_POST['kode_kelas']);
        $nama = escape($_POST['nama_kelas']);
        $prodi = escape($_POST['program_studi']);
        $tahun = escape($_POST['tahun_ajaran']);
        
        $stmt_upd = mysqli_prepare($conn, "UPDATE kelas SET nama_kelas=?, program_studi=?, tahun_ajaran=? WHERE kode_kelas=?");
        mysqli_stmt_bind_param($stmt_upd, "ssss", $nama, $prodi, $tahun, $kode);
        mysqli_stmt_execute($stmt_upd);
        set_alert('success', 'Kelas berhasil diupdate!');

    } elseif ($aksi == 'hapus') {
        // Hapus Single
        $kode = escape($_POST['kode_kelas']);
        $stmt_del = mysqli_prepare($conn, "DELETE FROM kelas WHERE kode_kelas = ?");
        mysqli_stmt_bind_param($stmt_del, "s", $kode);
        mysqli_stmt_execute($stmt_del);
        set_alert('success', 'Kelas berhasil dihapus!');

    } elseif ($aksi == 'hapus_banyak') {
        // FITUR BARU: Hapus Bulk (Banyak sekaligus)
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            
            $stmt_del = mysqli_prepare($conn, "DELETE FROM kelas WHERE kode_kelas = ?");
            foreach ($ids as $id) {
                $safe_id = escape($id);
                mysqli_stmt_bind_param($stmt_del, "s", $safe_id);
                if(mysqli_stmt_execute($stmt_del)){
                    $success_count++;
                }
            }
            set_alert('success', $success_count . ' Kelas berhasil dihapus!');
        }
    }
    
    echo "<script>window.location.href='index.php?page=admin_kelas';</script>";
    exit;
}

// --- [MODIFIKASI] Penambahan filter prodi dan perbaikan logika query ---
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$filter_prodi = isset($_GET['prodi']) ? escape($_GET['prodi']) : ''; // BARU
$search_param = '%' . $search . '%';

// Pagination
$per_page = 9;
$current_page = get_current_page();

$where_conditions = [];
$bind_types = "";
$bind_values = [];

if ($search) {
    $where_conditions[] = "(k.nama_kelas LIKE ? OR k.kode_kelas LIKE ? OR k.program_studi LIKE ?)";
    $bind_types .= "sss";
    array_push($bind_values, $search_param, $search_param, $search_param);
}
if ($filter_prodi) { // BARU
    $where_conditions[] = "k.program_studi = ?";
    $bind_types .= "s";
    $bind_values[] = $filter_prodi;
}

$where_sql = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

$count_query_sql = "SELECT COUNT(*) as total FROM kelas k $where_sql";
$stmt_count = mysqli_prepare($conn, $count_query_sql);
if (!empty($bind_values)) {
    mysqli_stmt_bind_param($stmt_count, $bind_types, ...$bind_values);
}
mysqli_stmt_execute($stmt_count);
$total_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Ambil data kelas
$data_query_sql = "SELECT k.*, (SELECT COUNT(*) FROM mahasiswa m WHERE m.kode_kelas = k.kode_kelas) as jml_mahasiswa FROM kelas k $where_sql ORDER BY k.kode_kelas LIMIT ?, ?";
$stmt_kelas = mysqli_prepare($conn, $data_query_sql);
$data_bind_types = $bind_types . "ii";
$data_bind_values = array_merge($bind_values, [$offset, $per_page]);
mysqli_stmt_bind_param($stmt_kelas, $data_bind_types, ...$data_bind_values);
mysqli_stmt_execute($stmt_kelas);
$kelas = mysqli_stmt_get_result($stmt_kelas);

// [BARU] Ambil daftar prodi untuk filter
$prodi_list = mysqli_query($conn, "SELECT DISTINCT program_studi FROM kelas WHERE program_studi IS NOT NULL AND program_studi != '' ORDER BY program_studi");

// --- MODIFIKASI AJAX SEARCH (Looping Card) ---
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($kelas) > 0): ?>
            <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 class-card position-relative" id="card-<?= $k['kode_kelas'] ?>">
                        <div class="card-select-overlay">
                            <input type="checkbox" class="form-check-input item-checkbox" 
                                   value="<?= htmlspecialchars($k['kode_kelas']) ?>" 
                                   onchange="toggleSelection('<?= htmlspecialchars($k['kode_kelas']) ?>')">
                        </div>

                        <div class="card-body d-flex flex-column"> <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary mb-2">Kelas <?= htmlspecialchars($k['kode_kelas']) ?></span>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($k['nama_kelas']) ?></h5>
                                </div>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-users"></i> <?= htmlspecialchars($k['jml_mahasiswa']) ?>
                                </span>
                            </div>
                            <p class="text-muted mb-2"><i class="fas fa-graduation-cap me-2"></i><?= htmlspecialchars($k['program_studi']) ?></p>
                            <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i>T.A. <?= htmlspecialchars($k['tahun_ajaran']) ?></p>
                            
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-warning" onclick="editKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['program_studi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['tahun_ajaran'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', '<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash me-1"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info text-center">Data kelas tidak ditemukan.</div></div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_data > 0): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_kelas', ['search' => $search, 'prodi' => $filter_prodi]) ?>
    </div>
    <?php endif; ?>
    <?php
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
    /* [FIX] Beri ruang lebih di atas konten card agar tidak tertimpa checkbox */
    .class-card .card-body {
        padding-top: 1.5rem;
        transition: padding-top 0.3s ease;
    }
    
    /* Padding tambahan saat mode select aktif */
    .select-mode .class-card .card-body {
        padding-top: 3.5rem;
    }

    /* [FIX] Styling konsisten untuk tombol aksi di dalam card */
    .class-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: auto; /* Mendorong tombol ke bawah */
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }
    .class-card .action-buttons .btn {
        flex-grow: 1; /* Membuat tombol berbagi lebar yang sama */
    }

    /* Card Styling */
    .class-card { 
        transition: all 0.2s; 
        border: 1px solid var(--border-color);
        background-color: var(--bg-card);
    }
    
    /* Style untuk Checkbox Selection */
    .class-card.selected { 
        border-color: var(--primary-color); 
        background-color: rgba(0, 102, 204, 0.05); 
        box-shadow: 0 0 0 1px var(--primary-color);
    }
    
    [data-theme="dark"] .class-card.selected {
        background-color: rgba(0, 102, 204, 0.15);
    }

    .card-select-overlay { 
        position: absolute; 
        top: 15px; 
        left: 15px; 
        z-index: 5; 
        display: none; /* Sembunyikan default */
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .select-mode .card-select-overlay {
        display: block;
        opacity: 1;
    }
    
    .item-checkbox { 
        width: 22px; 
        height: 22px; 
        cursor: pointer; 
        border: 2px solid var(--text-muted); 
        border-radius: 50%; /* [FIX] Membuat checkbox bulat */
        -webkit-appearance: none;
        appearance: none;
        transition: background-color 0.2s, border-color 0.2s;
        position: relative;
    }
    
    .item-checkbox:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .item-checkbox:checked::after {
        content: '\f00c'; /* Font Awesome check icon */
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: white;
        font-size: 12px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    /* Sticky Bottom Bar untuk Bulk Action */
    #bulkActionBar {
        position: fixed; 
        bottom: -100px; 
        left: 0; 
        right: 0;
        background: var(--bg-card);
        box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        padding: 15px 30px; 
        z-index: 1000;
        transition: bottom 0.3s ease-in-out;
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        border-top: 1px solid var(--border-color);
    }
    
    #bulkActionBar.show { 
        bottom: 0; 
    }
    
    [data-theme="dark"] #bulkActionBar {
        box-shadow: 0 -5px 20px rgba(0,0,0,0.3);
    }

    /* Penyesuaian agar konten tidak tertutup bar */
    body { padding-bottom: 80px; } 

    /* SLIDER CONFIRM STYLE */
    .slider-container {
        position: relative; width: 100%; height: 55px;
        background: #f0f2f5; border-radius: 30px;
        user-select: none; overflow: hidden;
        box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
    }
    [data-theme="dark"] .slider-container {
        background: var(--bg-input);
        box-shadow: inset 0 2px 5px rgba(0,0,0,0.3);
    }

    .slider-text {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; color: #888; font-size: 14px;
        text-transform: uppercase; letter-spacing: 1px;
        z-index: 1; pointer-events: none;
        transition: opacity 0.3s;
    }
    [data-theme="dark"] .slider-text {
        color: var(--text-muted);
    }

    .slider-handle {
        position: absolute; top: 5px; left: 5px;
        width: 45px; height: 45px;
        background: #dc3545; /* Merah Danger */
        border-radius: 50%; cursor: pointer; z-index: 2;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 18px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: transform 0.1s;
    }
    .slider-handle:active { cursor: grabbing; transform: scale(0.95); }
    .slider-progress {
        position: absolute; top: 0; left: 0; height: 100%;
        background: rgba(220, 53, 69, 0.2); /* Merah transparan */
        width: 0; z-index: 0;
    }
    /* Ketika sukses di-slide */
    .slider-container.unlocked .slider-handle { width: calc(100% - 10px); border-radius: 30px; }
    .slider-container.unlocked .slider-text { opacity: 0; }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        #bulkActionBar {
            flex-direction: column;
            gap: 10px;
            padding: 15px;
        }
        #bulkActionBar > div {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        #bulkActionBar button {
            flex: 1;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Kelola Kelas</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Tambah
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end" onsubmit="return false;">
                            <input type="hidden" name="page" value="admin_kelas">
                            <div class="col-12 col-md-5">
                                <label for="searchInput" class="form-label small">Cari Nama/Kode Kelas</label>
                                <input type="text" name="search" id="searchInput" class="form-control" placeholder="Ketik untuk mencari..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-12 col-md-5">
                                <label for="prodiFilter" class="form-label small">Filter Program Studi</label>
                                <select name="prodi" id="prodiFilter" class="form-select">
                                    <option value="">Semua Prodi</option>
                                    <?php mysqli_data_seek($prodi_list, 0); while ($p = mysqli_fetch_assoc($prodi_list)): ?>
                                        <option value="<?= htmlspecialchars($p['program_studi']) ?>" <?= $filter_prodi == $p['program_studi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['program_studi']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 d-flex flex-column flex-md-row align-items-stretch align-items-md-end justify-content-md-end gap-2">
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

                <div id="kelasContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($kelas) > 0): ?>
                        <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 class-card position-relative" id="card-<?= $k['kode_kelas'] ?>">
                                    <div class="card-select-overlay">
                                        <input type="checkbox" class="form-check-input item-checkbox" 
                                               value="<?= htmlspecialchars($k['kode_kelas']) ?>" 
                                               onchange="toggleSelection('<?= htmlspecialchars($k['kode_kelas']) ?>')">
                                    </div>

                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <span class="badge bg-primary mb-2">Kelas <?= htmlspecialchars($k['kode_kelas']) ?></span>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($k['nama_kelas']) ?></h5>
                                            </div>
                                            <span class="badge bg-secondary"><i class="fas fa-users"></i> <?= htmlspecialchars($k['jml_mahasiswa']) ?></span>
                                        </div>
                                        <p class="text-muted mb-2"><i class="fas fa-graduation-cap me-2"></i><?= htmlspecialchars($k['program_studi']) ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i>T.A. <?= htmlspecialchars($k['tahun_ajaran']) ?></p>
                                        
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['program_studi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['tahun_ajaran'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', '<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12"><div class="alert alert-info text-center">Data kelas tidak ditemukan.</div></div>
                    <?php endif; ?>
                </div>
                <?php if ($total_data > 0): ?>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_kelas', ['search' => $search, 'prodi' => $filter_prodi]) ?>
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
        <span class="text-dark fw-bold">Kelas Dipilih</span>
    </div>
    <div>
        <button class="btn btn-secondary me-2" onclick="toggleSelectAll(false)">Batal</button>
        <button class="btn btn-danger" onclick="confirmSlideDelete('bulk')">
            <i class="fas fa-trash-alt me-2"></i>Hapus Terpilih
        </button>
    </div>
</div>

<!-- [FIX] Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Kelas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Kelas</label>
                        <input type="text" name="kode_kelas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program Studi</label>
                        <input type="text" name="program_studi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2023/2024" required>
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

<!-- [FIX] Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="kode_kelas" id="edit_kode">
                <div class="modal-header"><h5 class="modal-title" id="modalEditLabel">Edit Kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Kode Kelas</label><input type="text" id="edit_kode_display" class="form-control" disabled></div>
                    <div class="mb-3"><label class="form-label">Nama Kelas</label><input type="text" name="nama_kelas" id="edit_nama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Program Studi</label><input type="text" name="program_studi" id="edit_prodi" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Tahun Ajaran</label><input type="text" name="tahun_ajaran" id="edit_tahun" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSlideConfirm" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                </div>
                <h4 class="fw-bold text-danger mb-2">Konfirmasi Hapus</h4>
                <p class="text-muted mb-4" id="slideConfirmMsg">Apakah Anda yakin? Data yang dihapus tidak dapat dikembalikan.</p>
                
                <div class="slider-container" id="deleteSlider">
                    <div class="slider-progress" id="sliderProgress"></div>
                    <div class="slider-text">GESER UNTUK MENGHAPUS >></div>
                    <div class="slider-handle" id="sliderHandle">
                        <i class="fas fa-trash"></i>
                    </div>
                </div>
                
                <button type="button" class="btn btn-link text-muted mt-3 text-decoration-none" data-bs-dismiss="modal" id="btnCancelSlide">Batal</button>
            </div>
        </div>
    </div>
</div>

<form id="formHapusSingle" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus">
    <input type="hidden" name="kode_kelas" id="hapus_kode_single">
</form>

<form id="formHapusBulk" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus_banyak">
    <div id="bulkInputs"></div>
</form>

<script>
// --- LOGIKA SELEKSI (CHECKBOX) ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const container = document.getElementById('kelasContainer');
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
        document.querySelectorAll('.class-card').forEach(c => c.classList.remove('selected'));
        updateBulkUI();
    }
}

// [BARU] Fungsi untuk modal edit
function editKelas(kode, nama, prodi, tahun) {
    document.getElementById('edit_kode').value = kode;
    document.getElementById('edit_kode_display').value = kode;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_prodi').value = prodi;
    document.getElementById('edit_tahun').value = tahun;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function toggleSelection(kode) {
    const card = document.getElementById('card-' + kode);
    const checkbox = card.querySelector('.item-checkbox');
    
    if (checkbox.checked) {
        selectedItems.add(kode);
        card.classList.add('selected');
    } else {
        selectedItems.delete(kode);
        card.classList.remove('selected');
    }
    updateBulkUI();
}

function toggleSelectAll() {
    const masterCheck = document.getElementById('selectAll');
    const isChecked = masterCheck.checked;
    const itemCheckboxes = document.querySelectorAll('#kelasContainer .item-checkbox');

    itemCheckboxes.forEach(cb => {
        const kode = cb.value;
        const card = document.getElementById('card-' + kode);

        // Hanya ubah jika statusnya berbeda untuk efisiensi
        if (cb.checked !== isChecked) {
            cb.checked = isChecked;
            if (isChecked) {
                selectedItems.add(kode);
                card.classList.add('selected');
            } else {
                selectedItems.delete(kode);
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
    const itemCheckboxes = document.querySelectorAll('#kelasContainer .item-checkbox');
    
    countSpan.innerText = selectedItems.size;
    
    if (selectedItems.size > 0) {
        bar.classList.add('show');
        // Cek apakah semua item terpilih, jika ya, centang master checkbox
        if (itemCheckboxes.length > 0 && selectedItems.size === itemCheckboxes.length) {
            masterCheck.checked = true;
        } else {
            masterCheck.checked = false;
        }
    } else {
        bar.classList.remove('show');
    }
}

// --- LOGIKA SLIDE TO CONFIRM ---
let deleteType = ''; // 'single' atau 'bulk'
let deleteTargetId = ''; 

function confirmSlideDelete(type, id = null) {
    deleteType = type;
    deleteTargetId = id;
    
    const modal = new bootstrap.Modal(document.getElementById('modalSlideConfirm'));
    const msg = document.getElementById('slideConfirmMsg');
    
    if (type === 'bulk') {
        msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} kelas</b> terpilih.<br>Aksi ini tidak dapat dibatalkan.`;
    } else {
        msg.innerHTML = `Anda akan menghapus Kelas <b>${id}</b>.<br>Semua data mahasiswa di dalamnya juga akan terhapus.`;
    }
    
    resetSlider();
    modal.show();
}

// Slider JavaScript Logic
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

function startDrag(e) {
    isDragging = true;
}

function drag(e) {
    if (!isDragging) return;
    
    // Support mouse & touch
    let clientX = e.clientX || e.touches[0].clientX;
    
    let rect = sliderContainer.getBoundingClientRect();
    let x = clientX - rect.left - (sliderHandle.offsetWidth / 2);
    let max = rect.width - sliderHandle.offsetWidth;
    
    // Batasi pergerakan
    if (x < 0) x = 0;
    if (x > max) x = max;
    
    sliderHandle.style.left = x + 'px';
    sliderProgress.style.width = (x + 20) + 'px'; // +20 for visual overlap
    
    // Jika mencapai ujung (95%)
    if (x >= max * 0.95) {
        isDragging = false;
        sliderContainer.classList.add('unlocked');
        sliderHandle.style.left = max + 'px';
        sliderProgress.style.width = '100%';
        performDelete(); // EKSEKUSI HAPUS
    }
}

function endDrag() {
    if (!isDragging) return;
    isDragging = false;
    
    // Jika dilepas sebelum selesai, kembalikan ke awal
    if (!sliderContainer.classList.contains('unlocked')) {
        sliderHandle.style.left = '5px';
        sliderProgress.style.width = '0';
    }
}

function resetSlider() {
    sliderContainer.classList.remove('unlocked');
    sliderHandle.style.left = '5px';
    sliderProgress.style.width = '0';
    document.querySelector('.slider-text').style.opacity = '1';
}

function performDelete() {
    // Beri jeda sedikit untuk efek visual selesai
    setTimeout(() => {
        if (deleteType === 'single') {
            document.getElementById('hapus_kode_single').value = deleteTargetId;
            document.getElementById('formHapusSingle').submit();
        } else if (deleteType === 'bulk') {
            const container = document.getElementById('bulkInputs');
            container.innerHTML = ''; // Reset
            selectedItems.forEach(kode => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = kode;
                container.appendChild(input);
            });
            document.getElementById('formHapusBulk').submit();
        }
    }, 300);
}

// [BARU] Live Search Logic
let searchTimeout = null;
const searchInput = document.getElementById('searchInput');
const prodiSelect = document.getElementById('prodiFilter');

function performSearch() {
    clearTimeout(searchTimeout);
    const searchValue = searchInput.value;
    const prodiValue = prodiSelect.value;
    const container = document.getElementById('kelasContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_kelas&ajax_search=1&search=${encodeURIComponent(searchValue)}&prodi=${encodeURIComponent(prodiValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Re-check selected items after DOM update
                selectedItems.forEach(kode => {
                    const checkbox = container.querySelector(`.item-checkbox[value="${kode}"]`);
                    if (checkbox) checkbox.checked = true;
                    const card = document.getElementById('card-' + kode);
                    if(card) card.classList.add('selected');
                });
                updateBulkUI();
            })
            .catch(error => console.error('Error:', error));
    }, 300);
}

searchInput.addEventListener('input', performSearch);
prodiSelect.addEventListener('change', performSearch);
</script>

<?php include 'includes/footer.php'; ?>