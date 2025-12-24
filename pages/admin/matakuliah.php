<?php
$page = 'admin_matakuliah';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        $kode = escape($_POST['kode_mk']);
        $nama = escape($_POST['nama_mk']);
        $sks = (int)$_POST['sks'];
        $semester = escape($_POST['semester']);
        
        $cek = mysqli_query($conn, "SELECT * FROM mata_kuliah WHERE kode_mk = '$kode'");
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'Kode mata kuliah sudah ada!');
        } else {
            mysqli_query($conn, "INSERT INTO mata_kuliah VALUES ('$kode', '$nama', '$sks', '$semester')");
            set_alert('success', 'Mata kuliah berhasil ditambahkan!');
        }
    } elseif ($aksi == 'edit') {
        $kode = escape($_POST['kode_mk']);
        $nama = escape($_POST['nama_mk']);
        $sks = (int)$_POST['sks'];
        $semester = escape($_POST['semester']);
        
        mysqli_query($conn, "UPDATE mata_kuliah SET nama_mk='$nama', sks='$sks', semester='$semester' WHERE kode_mk='$kode'");
        set_alert('success', 'Mata kuliah berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $kode = escape($_POST['kode_mk']);
        mysqli_query($conn, "DELETE FROM mata_kuliah WHERE kode_mk = '$kode'");
        set_alert('success', 'Mata kuliah berhasil dihapus!');
    }
    
    header("Location: index.php?page=admin_matakuliah");
    exit;
}

// Pagination
$per_page = 9;
$current_page = get_current_page();

// Search
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$where_sql = '';
if ($search) {
    $where_sql = "WHERE nama_mk LIKE '%$search%' OR kode_mk LIKE '%$search%'";
}

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM mata_kuliah $where_sql");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

$matkul = mysqli_query($conn, "SELECT * FROM mata_kuliah $where_sql ORDER BY kode_mk LIMIT $offset, $per_page");

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($matkul) > 0): ?>
            <?php while ($m = mysqli_fetch_assoc($matkul)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 matakuliah-card">
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
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="page" value="admin_matakuliah">
                            <div class="col-12 col-md">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari mata kuliah atau kode..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-primary w-100 px-4"><i class="fas fa-search me-2"></i>Cari</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="matkulContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($matkul) > 0): ?>
                        <?php while ($m = mysqli_fetch_assoc($matkul)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 matakuliah-card">
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

<form id="formHapus" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus">
    <input type="hidden" name="kode_mk" id="hapus_kode">
</form>

<script>
function editMK(kode, nama, sks, semester) {
    document.getElementById('edit_kode').value = kode;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_sks').value = sks;
    document.getElementById('edit_semester').value = semester;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusMK(kode) {
    if (confirm('Yakin ingin menghapus mata kuliah ini?')) {
        document.getElementById('hapus_kode').value = kode;
        document.getElementById('formHapus').submit();
    }
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
            })
            .catch(error => console.error('Error:', error));
    }, 300);
});
</script>

<?php include 'includes/footer.php'; ?>
