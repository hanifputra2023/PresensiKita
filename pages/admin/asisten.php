<?php
$page = 'admin_asisten';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        $kode = escape($_POST['kode_asisten']);
        $nama = escape($_POST['nama']);
        $hp = escape($_POST['no_hp']);
        $mk = escape($_POST['kode_mk']);
        $password = $_POST['password'];
        
        $cek = mysqli_query($conn, "SELECT * FROM asisten WHERE kode_asisten = '$kode'");
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'Kode asisten sudah ada!');
        } else {
            // Buat user dulu
            mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$kode', '$password', 'asisten')");
            $user_id = mysqli_insert_id($conn);
            
            $mk = $mk ?: null;
            $mk_sql = $mk ? "'$mk'" : "NULL";
            mysqli_query($conn, "INSERT INTO asisten (kode_asisten, user_id, nama, no_hp, kode_mk) VALUES ('$kode', '$user_id', '$nama', '$hp', $mk_sql)");
            set_alert('success', 'Asisten berhasil ditambahkan!');
        }
    } elseif ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        $nama = escape($_POST['nama']);
        $hp = escape($_POST['no_hp']);
        $mk = escape($_POST['kode_mk']);
        $status = escape($_POST['status']);
        
        $mk_sql = $mk ? "'$mk'" : "NULL";
        mysqli_query($conn, "UPDATE asisten SET nama='$nama', no_hp='$hp', kode_mk=$mk_sql, status='$status' WHERE id='$id'");
        set_alert('success', 'Data asisten berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $id = (int)$_POST['id'];
        $ast = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM asisten WHERE id = '$id'"));
        if ($ast && $ast['user_id']) {
            mysqli_query($conn, "DELETE FROM users WHERE id = '{$ast['user_id']}'");
        }
        mysqli_query($conn, "DELETE FROM asisten WHERE id = '$id'");
        set_alert('success', 'Asisten berhasil dihapus!');
    }
    
    header("Location: index.php?page=admin_asisten");
    exit;
}

// Pagination
$per_page = 9;
$current_page = get_current_page();

$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$where_sql = '';
if ($search) {
    $where_sql = "WHERE a.nama LIKE '%$search%' OR a.kode_asisten LIKE '%$search%'";
}

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM asisten a $where_sql");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

$asisten = mysqli_query($conn, "SELECT a.*, mk.nama_mk FROM asisten a LEFT JOIN mata_kuliah mk ON a.kode_mk = mk.kode_mk $where_sql ORDER BY a.kode_asisten LIMIT $offset, $per_page");
$matkul = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk");

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($asisten) > 0): ?>
            <?php while ($a = mysqli_fetch_assoc($asisten)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 asisten-card">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($a['nama']) ?></h5>
                                    <span class="badge bg-info mb-2"><?= htmlspecialchars($a['kode_asisten']) ?></span>
                                </div>
                                <span class="badge <?= $a['status'] == 'aktif' ? 'bg-success' : 'bg-secondary' ?> text-capitalize">
                                    <?= htmlspecialchars($a['status']) ?>
                                </span>
                            </div>
                            <p class="text-muted mb-2"><i class="fas fa-phone-alt me-2"></i><?= htmlspecialchars($a['no_hp'] ?: '-') ?></p>
                            <p class="text-muted mb-2"><i class="fas fa-star me-2"></i><?= htmlspecialchars($a['nama_mk'] ?: 'Belum ditentukan') ?></p>
                            
                            <div class="mt-auto action-buttons">
                                <button class="btn btn-sm btn-warning" onclick="editAst(<?= $a['id'] ?>, '<?= htmlspecialchars($a['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['no_hp'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['kode_mk'], ENT_QUOTES) ?>', '<?= $a['status'] ?>')">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="hapusAst(<?= $a['id'] ?>)">
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
                    Data asisten tidak ditemukan.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_data > 0): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_asisten', ['search' => $search]) ?>
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
    .asisten-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .asisten-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow) !important;
    }
    .asisten-card .card-title {
        font-weight: 600;
        color: var(--text-main);
    }
    .asisten-card .badge.bg-info {
        background-color: var(--info-color) !important;
    }
    .asisten-card .badge.bg-success {
        background-color: var(--success-color) !important;
    }
    .asisten-card .card-body p i {
        width: 20px;
        text-align: center;
        color: var(--text-muted);
    }
    .asisten-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    .asisten-card .action-buttons .btn {
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
                    <h4 class="mb-0"><i class="fas fa-user-tie me-2"></i>Kelola Asisten</h4>
                    <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Tambah Asisten
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="page" value="admin_asisten">
                            <div class="col-12 col-md">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama atau kode asisten..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-primary w-100 px-4"><i class="fas fa-search me-2"></i>Cari</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="asistenContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($asisten) > 0): ?>
                        <?php while ($a = mysqli_fetch_assoc($asisten)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 asisten-card">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($a['nama']) ?></h5>
                                                <span class="badge bg-info mb-2"><?= htmlspecialchars($a['kode_asisten']) ?></span>
                                            </div>
                                            <span class="badge <?= $a['status'] == 'aktif' ? 'bg-success' : 'bg-secondary' ?> text-capitalize">
                                                <?= htmlspecialchars($a['status']) ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-2"><i class="fas fa-phone-alt me-2"></i><?= htmlspecialchars($a['no_hp'] ?: '-') ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-star me-2"></i><?= htmlspecialchars($a['nama_mk'] ?: 'Belum ditentukan') ?></p>
                                        
                                        <div class="mt-auto action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editAst(<?= $a['id'] ?>, '<?= htmlspecialchars($a['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['no_hp'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['kode_mk'], ENT_QUOTES) ?>', '<?= $a['status'] ?>')">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="hapusAst(<?= $a['id'] ?>)">
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
                                Data asisten tidak ditemukan.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_asisten', ['search' => $search]) ?>
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
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Asisten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Asisten</label>
                        <input type="text" name="kode_asisten" class="form-control" placeholder="Contoh: AST001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keahlian Utama <span class="text-muted">(Opsional)</span></label>
                        <select name="kode_mk" class="form-select">
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php 
                            mysqli_data_seek($matkul, 0);
                            while ($m = mysqli_fetch_assoc($matkul)): 
                            ?>
                                <option value="<?= $m['kode_mk'] ?>"><?= htmlspecialchars($m['nama_mk']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" value="asisten123" required>
                        <small class="text-muted">Password default: asisten123</small>
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
                    <h5 class="modal-title" id="modalEditLabel">Edit Asisten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" id="edit_hp" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keahlian Utama <span class="text-muted">(Opsional)</span></label>
                        <select name="kode_mk" id="edit_mk" class="form-select">
                            <option value="">-- Tidak Ada --</option>
                            <?php 
                            mysqli_data_seek($matkul, 0);
                            while ($m = mysqli_fetch_assoc($matkul)): 
                            ?>
                                <option value="<?= $m['kode_mk'] ?>"><?= htmlspecialchars($m['nama_mk']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
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
    <input type="hidden" name="id" id="hapus_id">
</form>

<script>
function editAst(id, nama, hp, mk, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_hp').value = hp;
    document.getElementById('edit_mk').value = mk || '';
    document.getElementById('edit_status').value = status;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusAst(id) {
    if (confirm('Yakin ingin menghapus asisten ini? Akun login yang terkait juga akan dihapus.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}

// Live Search
let searchTimeout = null;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchValue = this.value;
    const container = document.getElementById('asistenContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_asisten&ajax_search=1&search=${encodeURIComponent(searchValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(error => console.error('Error:', error));
    }, 300);
});
</script>

<?php include 'includes/footer.php'; ?>
