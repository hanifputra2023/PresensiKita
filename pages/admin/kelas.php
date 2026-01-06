<?php
$page = 'admin_kelas';

// Proses tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        $kode = escape($_POST['kode_kelas']);
        $nama = escape($_POST['nama_kelas']);
        $prodi = escape($_POST['program_studi']);
        $tahun = escape($_POST['tahun_ajaran']);
        
        // Prepared statement
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
        $kode = escape($_POST['kode_kelas']);
        $nama = escape($_POST['nama_kelas']);
        $prodi = escape($_POST['program_studi']);
        $tahun = escape($_POST['tahun_ajaran']);
        
        $stmt_upd = mysqli_prepare($conn, "UPDATE kelas SET nama_kelas=?, program_studi=?, tahun_ajaran=? WHERE kode_kelas=?");
        mysqli_stmt_bind_param($stmt_upd, "ssss", $nama, $prodi, $tahun, $kode);
        mysqli_stmt_execute($stmt_upd);
        set_alert('success', 'Kelas berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $kode = escape($_POST['kode_kelas']);
        $stmt_del = mysqli_prepare($conn, "DELETE FROM kelas WHERE kode_kelas = ?");
        mysqli_stmt_bind_param($stmt_del, "s", $kode);
        mysqli_stmt_execute($stmt_del);
        set_alert('success', 'Kelas berhasil dihapus!');
    }
    
    header("Location: index.php?page=admin_kelas");
    exit;
}

// Search - prepared statement
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$search_param = '%' . $search . '%';

// Pagination
$per_page = 9;
$current_page = get_current_page();

if ($search) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM kelas k WHERE k.nama_kelas LIKE ? OR k.kode_kelas LIKE ? OR k.program_studi LIKE ?");
    mysqli_stmt_bind_param($stmt_count, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} else {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas");
}
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Ambil data kelas - prepared statement
if ($search) {
    $stmt_kelas = mysqli_prepare($conn, "SELECT k.*, 
                              (SELECT COUNT(*) FROM mahasiswa m WHERE m.kode_kelas = k.kode_kelas) as jml_mahasiswa
                              FROM kelas k 
                              WHERE k.nama_kelas LIKE ? OR k.kode_kelas LIKE ? OR k.program_studi LIKE ?
                              ORDER BY k.kode_kelas LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_kelas, "sssii", $search_param, $search_param, $search_param, $offset, $per_page);
    mysqli_stmt_execute($stmt_kelas);
    $kelas = mysqli_stmt_get_result($stmt_kelas);
} else {
    $stmt_kelas = mysqli_prepare($conn, "SELECT k.*, 
                              (SELECT COUNT(*) FROM mahasiswa m WHERE m.kode_kelas = k.kode_kelas) as jml_mahasiswa
                              FROM kelas k 
                              ORDER BY k.kode_kelas LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_kelas, "ii", $offset, $per_page);
    mysqli_stmt_execute($stmt_kelas);
    $kelas = mysqli_stmt_get_result($stmt_kelas);
}

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($kelas) > 0): ?>
            <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 class-card">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary mb-2">Kelas <?= htmlspecialchars($k['kode_kelas']) ?></span>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($k['nama_kelas']) ?></h5>
                                </div>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-users"></i> <?= htmlspecialchars($k['jml_mahasiswa']) ?>
                                </span>
                            </div>
                            <p class="text-muted mb-2"><i class="fas fa-graduation-cap me-2"></i><?= htmlspecialchars($k['program_studi']) ?></p>
                            <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($k['tahun_ajaran']) ?></p>
                            
                            <div class="mt-auto action-buttons">
                                <button class="btn btn-sm btn-warning" onclick="editKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['program_studi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['tahun_ajaran'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="hapusKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>')">
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
                    Data kelas tidak ditemukan.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_data > 0): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_kelas', ['search' => $search]) ?>
    </div>
    <?php endif; ?>
    <?php
    exit;
}
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
    .class-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .class-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.75rem rgba(58,59,69,.2) !important;
    }
    .class-card .card-title {
        font-weight: 600;
        color: var(--text-main);
    }
    .class-card .badge.bg-primary {
        background-color: var(--primary-color) !important;
    }
    .class-card .card-body p i {
        width: 20px;
        text-align: center;
        color: var(--text-muted);
    }
    .class-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    .class-card .action-buttons .btn {
        flex-grow: 1;
    }
    .modal-header {
        background: var(--banner-gradient);
        color: #fff;
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
                    <h4 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Kelola Kelas</h4>
                    <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Tambah Kelas
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="page" value="admin_kelas">
                            <div class="col-12 col-md">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari kelas, kode, atau prodi..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-primary w-100 px-4"><i class="fas fa-search me-2"></i>Cari</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="kelasContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($kelas) > 0): ?>
                        <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 class-card">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <span class="badge bg-primary mb-2">Kelas <?= htmlspecialchars($k['kode_kelas']) ?></span>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($k['nama_kelas']) ?></h5>
                                            </div>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-users"></i> <?= htmlspecialchars($k['jml_mahasiswa']) ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-2"><i class="fas fa-graduation-cap me-2"></i><?= htmlspecialchars($k['program_studi']) ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($k['tahun_ajaran']) ?></p>
                                        
                                        <div class="mt-auto action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['program_studi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['tahun_ajaran'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="hapusKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>')">
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
                                Data kelas tidak ditemukan.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_kelas', ['search' => $search]) ?>
                </div>
                <?php endif; ?>
                </div>
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
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Kelas</label>
                        <input type="text" name="kode_kelas" class="form-control" maxlength="1" required>
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
                        <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2024/2025" required>
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
                <input type="hidden" name="kode_kelas" id="edit_kode">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>
                        <input type="text" name="nama_kelas" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program Studi</label>
                        <input type="text" name="program_studi" id="edit_prodi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" id="edit_tahun" class="form-control" required>
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

<!-- Form Hapus -->
<form id="formHapus" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus">
    <input type="hidden" name="kode_kelas" id="hapus_kode">
</form>

<script>
function editKelas(kode, nama, prodi, tahun) {
    document.getElementById('edit_kode').value = kode;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_prodi').value = prodi;
    document.getElementById('edit_tahun').value = tahun;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusKelas(kode) {
    if (confirm('Yakin ingin menghapus kelas ini? Ini akan menghapus semua data terkait.')) {
        document.getElementById('hapus_kode').value = kode;
        document.getElementById('formHapus').submit();
    }
}

// Live Search
let searchTimeout = null;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchValue = this.value;
    const container = document.getElementById('kelasContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_kelas&ajax_search=1&search=${encodeURIComponent(searchValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(error => console.error('Error:', error));
    }, 300);
});
</script>

<?php include 'includes/footer.php'; ?>
