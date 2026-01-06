<?php
$page = 'admin_users';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        $username = escape($_POST['username']);
        $password = $_POST['password'];
        $role = escape($_POST['role']);
        
        // Prepared statement untuk cek username
        $stmt_cek = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $username);
        mysqli_stmt_execute($stmt_cek);
        $cek = mysqli_stmt_get_result($stmt_cek);
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'Username sudah ada!');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Prepared statement untuk insert user
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "sss", $username, $hashed_password, $role);
            mysqli_stmt_execute($stmt_ins);
            set_alert('success', 'User berhasil ditambahkan!');
        }
    } elseif ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        $password = $_POST['password'];
        $role = escape($_POST['role']);
        
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Prepared statement untuk update dengan password
            $stmt_upd = mysqli_prepare($conn, "UPDATE users SET password=?, role=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_upd, "ssi", $hashed_password, $role, $id);
            mysqli_stmt_execute($stmt_upd);
        } else {
            // Prepared statement untuk update tanpa password
            $stmt_upd = mysqli_prepare($conn, "UPDATE users SET role=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_upd, "si", $role, $id);
            mysqli_stmt_execute($stmt_upd);
        }
        set_alert('success', 'User berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $id = (int)$_POST['id'];
        // Prepared statement untuk hapus user
        $stmt_del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        mysqli_stmt_execute($stmt_del);
        set_alert('success', 'User berhasil dihapus!');
    }
    
    header("Location: index.php?page=admin_users");
    exit;
}

// Pagination
$per_page = 9;
$current_page = get_current_page();

$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$search_param = '%' . $search . '%';

// Prepared statement untuk count
if ($search) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users u 
                                    LEFT JOIN mahasiswa m ON u.id = m.user_id
                                    LEFT JOIN asisten a ON u.id = a.user_id
                                    WHERE (u.username LIKE ? OR m.nama LIKE ? OR a.nama LIKE ?)");
    mysqli_stmt_bind_param($stmt_count, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} else {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
}
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Prepared statement untuk fetch users
if ($search) {
    $stmt_users = mysqli_prepare($conn, "SELECT 
                                    u.*,
                                    COALESCE(m.foto, a.foto) as foto,
                                    COALESCE(m.nama, a.nama, u.username) as nama_lengkap
                                  FROM 
                                    users u
                                  LEFT JOIN 
                                    mahasiswa m ON u.id = m.user_id
                                  LEFT JOIN 
                                    asisten a ON u.id = a.user_id
                                  WHERE (u.username LIKE ? OR m.nama LIKE ? OR a.nama LIKE ?)
                                  ORDER BY 
                                    u.role, u.username 
                                  LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_users, "sssii", $search_param, $search_param, $search_param, $offset, $per_page);
    mysqli_stmt_execute($stmt_users);
    $users = mysqli_stmt_get_result($stmt_users);
} else {
    $stmt_users = mysqli_prepare($conn, "SELECT 
                                    u.*,
                                    COALESCE(m.foto, a.foto) as foto,
                                    COALESCE(m.nama, a.nama, u.username) as nama_lengkap
                                  FROM 
                                    users u
                                  LEFT JOIN 
                                    mahasiswa m ON u.id = m.user_id
                                  LEFT JOIN 
                                    asisten a ON u.id = a.user_id
                                  ORDER BY 
                                    u.role, u.username 
                                  LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_users, "ii", $offset, $per_page);
    mysqli_stmt_execute($stmt_users);
    $users = mysqli_stmt_get_result($stmt_users);
}

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
         <?php if (mysqli_num_rows($users) > 0): ?>
            <?php 
            while ($u = mysqli_fetch_assoc($users)): 
                $badge_role = ['admin' => 'bg-danger', 'asisten' => 'bg-info', 'mahasiswa' => 'bg-success'];
                
                $foto_profil = (!empty($u['foto']) && file_exists($u['foto'])) 
                               ? $u['foto'] 
                               : 'https://ui-avatars.com/api/?name=' . urlencode($u['username']) . '&background=random&color=fff&rounded=true';
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 user-card">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($u['username']) ?>" class="user-avatar me-3" loading="lazy">
                                <div>
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($u['nama_lengkap']) ?></h5>
                                    <div class="small text-muted"><?= htmlspecialchars($u['username']) ?></div>
                                    <span class="badge <?= $badge_role[$u['role']] ?> mt-1"><?= ucfirst(htmlspecialchars($u['role'])) ?></span>
                                </div>
                            </div>
                            <p class="text-muted mb-2"><i class="fas fa-hashtag me-2"></i>ID Pengguna: <?= $u['id'] ?></p>
                            <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i>Dibuat pada: <?= date('d M Y, H:i', strtotime($u['created_at'])) ?></p>
                            
                            <div class="mt-auto action-buttons">
                                <button class="btn btn-sm btn-warning" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= $u['role'] ?>')">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="hapusUser(<?= $u['id'] ?>)">
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
                    Data pengguna tidak ditemukan.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_data > 0): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_users', ['search' => $search]) ?>
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
    .user-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .user-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow) !important;
    }
    .user-card .card-title {
        font-weight: 600;
        color: var(--text-main);
    }
    .user-card .card-body p i {
        width: 20px;
        text-align: center;
        color: var(--text-muted);
    }
    .user-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    .user-card .action-buttons .btn {
        flex-grow: 1;
    }
    .user-avatar {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 50%;
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
                    <h4 class="mb-0"><i class="fas fa-user-cog me-2"></i>Kelola Pengguna</h4>
                    <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus me-1"></i>Tambah Pengguna
                    </button>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="page" value="admin_users">
                            <div class="col-12 col-md">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari Nama atau NIM..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-primary w-100 px-4"><i class="fas fa-search me-2"></i>Cari</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="usersContainer">
                <div class="row">
                     <?php if (mysqli_num_rows($users) > 0): ?>
                        <?php 
                        while ($u = mysqli_fetch_assoc($users)): 
                            $badge_role = ['admin' => 'bg-danger', 'asisten' => 'bg-info', 'mahasiswa' => 'bg-success'];
                            
                            $foto_profil = (!empty($u['foto']) && file_exists($u['foto'])) 
                                           ? $u['foto'] 
                                           : 'https://ui-avatars.com/api/?name=' . urlencode($u['username']) . '&background=random&color=fff&rounded=true';
                        ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 user-card">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($u['username']) ?>" class="user-avatar me-3" loading="lazy">
                                            <div>
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($u['nama_lengkap']) ?></h5>
                                                <div class="small text-muted"><?= htmlspecialchars($u['username']) ?></div>
                                                <span class="badge <?= $badge_role[$u['role']] ?> mt-1"><?= ucfirst(htmlspecialchars($u['role'])) ?></span>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-2"><i class="fas fa-hashtag me-2"></i>ID Pengguna: <?= $u['id'] ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i>Dibuat pada: <?= date('d M Y, H:i', strtotime($u['created_at'])) ?></p>
                                        
                                        <div class="mt-auto action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= $u['role'] ?>')">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="hapusUser(<?= $u['id'] ?>)">
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
                                Data pengguna tidak ditemukan.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_users', ['search' => $search]) ?>
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
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="mahasiswa" selected>Mahasiswa</option>
                            <option value="asisten">Asisten</option>
                            <option value="admin">Admin</option>
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
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" id="edit_username" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="asisten">Asisten</option>
                            <option value="mahasiswa">Mahasiswa</option>
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
function editUser(id, username, role) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_role').value = role;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusUser(id) {
    if (confirm('Yakin ingin menghapus user ini? Aksi ini tidak bisa dibatalkan.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}

// Live Search
let searchTimeout = null;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchValue = this.value;
    const container = document.getElementById('usersContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_users&ajax_search=1&search=${encodeURIComponent(searchValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(error => console.error('Error:', error));
    }, 300);
});
</script>

<?php include 'includes/footer.php'; ?>
