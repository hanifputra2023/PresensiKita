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
    } elseif ($aksi == 'hapus_banyak') {
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            $stmt_del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            foreach ($ids as $id) {
                $safe_id = (int)$id;
                mysqli_stmt_bind_param($stmt_del, "i", $safe_id);
                if(mysqli_stmt_execute($stmt_del)) $success_count++;
            }
            set_alert('success', $success_count . ' User berhasil dihapus!');
        }
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
                    <div class="card h-100 user-card position-relative" id="card-<?= $u['id'] ?>">
                        <div class="card-select-overlay">
                            <input type="checkbox" class="form-check-input item-checkbox" 
                                   value="<?= $u['id'] ?>" 
                                   onchange="toggleSelection(<?= $u['id'] ?>)">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start mb-3">
                                <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($u['username']) ?>" class="user-avatar me-3" loading="lazy">
                                <div class="flex-grow-1">
                                    <h5 class="card-title"><?= htmlspecialchars($u['nama_lengkap']) ?></h5>
                                    <div class="username-text mb-2"><?= htmlspecialchars($u['username']) ?></div>
                                    <span class="badge <?= $badge_role[$u['role']] ?>"><?= ucfirst(htmlspecialchars($u['role'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="info-text">
                                <i class="fas fa-hashtag"></i>
                                <span>ID: <?= $u['id'] ?></span>
                            </div>
                            
                            <div class="info-text">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></span>
                            </div>
                            
                            <div class="mt-auto action-buttons">
                                <button class="btn btn-sm btn-warning" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= $u['role'] ?>', '<?= htmlspecialchars($u['password'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', <?= $u['id'] ?>)">
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
    /* Card Selection Styles */
    .user-card { transition: all 0.2s; border: 1px solid var(--border-color); }
    .user-card.selected { border-color: var(--primary-color); background-color: rgba(0, 102, 204, 0.05); box-shadow: 0 0 0 1px var(--primary-color); }
    [data-theme="dark"] .user-card.selected { background-color: rgba(0, 102, 204, 0.15); }
    .card-select-overlay { position: absolute; top: 10px; left: 10px; z-index: 5; display: none; opacity: 0; transition: opacity 0.3s; }
    .select-mode .card-select-overlay { display: block; opacity: 1; }
    .user-card .card-body { transition: padding-top 0.3s; }
    .select-mode .user-card .card-body { padding-top: 2.5rem; }
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
    .user-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .user-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow) !important;
    }
    
    .user-card .card-body {
        padding: 1.5rem;
    }
    
    .user-card .card-title {
        font-weight: 600;
        color: var(--text-main);
        font-size: 1.05rem;
        margin-bottom: 0.25rem;
    }
    
    .user-card .username-text {
        color: var(--text-muted);
        font-size: 0.875rem;
        font-weight: 400;
    }
    
    .user-avatar {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--border-color);
        transition: all 0.25s ease;
    }
    
    .user-card:hover .user-avatar {
        border-color: var(--primary-color);
    }
    
    .user-card .badge {
        border-radius: 20px;
        padding: 4px 12px;
        font-weight: 500;
        font-size: 0.75rem;
        text-transform: capitalize;
    }
    
    .user-card .info-text {
        color: var(--text-muted);
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }
    
    .user-card .info-text i {
        width: 18px;
        color: var(--text-muted);
        margin-right: 8px;
    }
    
    .user-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }
    
    .user-card .action-buttons .btn {
        flex-grow: 1;
    }
    
    .modal-header {
        background: var(--banner-gradient);
        color: #fff;
    }
    
    .password-field {
        position: relative;
    }
    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 5px 10px;
        z-index: 10;
    }
    .password-toggle:hover {
        color: var(--text-main);
    }
    .password-info {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }
    
    /* Dark Mode Fixes */
    [data-theme="dark"] .btn-warning,
    [data-theme="dark"] .btn-info {
        color: #212529 !important;
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
                        <form method="GET" class="row g-3 align-items-end" onsubmit="return false;">
                            <input type="hidden" name="page" value="admin_users">
                            <div class="col-12 col-md">
                                <label for="searchInput" class="form-label small">Cari Nama/NIM</label>
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
                                <div class="card h-100 user-card position-relative" id="card-<?= $u['id'] ?>">
                                    <div class="card-select-overlay">
                                        <input type="checkbox" class="form-check-input item-checkbox" 
                                               value="<?= $u['id'] ?>" 
                                               onchange="toggleSelection(<?= $u['id'] ?>)">
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-start mb-3">
                                            <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($u['username']) ?>" class="user-avatar me-3" loading="lazy">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title"><?= htmlspecialchars($u['nama_lengkap']) ?></h5>
                                                <div class="username-text mb-2"><?= htmlspecialchars($u['username']) ?></div>
                                                <span class="badge <?= $badge_role[$u['role']] ?>"><?= ucfirst(htmlspecialchars($u['role'])) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-text">
                                            <i class="fas fa-hashtag"></i>
                                            <span>ID: <?= $u['id'] ?></span>
                                        </div>
                                        
                                        <div class="info-text">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></span>
                                        </div>
                                        
                                        <div class="mt-auto action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= $u['role'] ?>', '<?= htmlspecialchars($u['password'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', <?= $u['id'] ?>)">
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

<div id="bulkActionBar">
    <div class="d-flex align-items-center">
        <span class="badge bg-primary me-2" style="font-size: 1rem;"><span id="selectedCount">0</span></span>
        <span class="text-dark fw-bold">User Dipilih</span>
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
                        <div class="password-field">
                            <input type="password" name="password" id="tambah_password" class="form-control" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('tambah_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
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
                        <label class="form-label">
                            Password Lama 
                            <small class="text-muted">(Hash)</small>
                        </label>
                        <div class="password-field">
                            <input type="password" id="edit_old_password" class="form-control bg-light" readonly>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('edit_old_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-info">
                            <i class="fas fa-info-circle me-1"></i>
                            Hash password yang tersimpan di database (hanya untuk referensi)
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <div class="password-field">
                            <input type="password" name="password" id="edit_new_password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('edit_new_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-info">
                            <i class="fas fa-info-circle me-1"></i>
                            Isi hanya jika ingin mengubah password
                        </div>
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

<form id="formHapus" method="POST" class="d-none"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" id="hapus_id"></form>
<form id="formHapusBulk" method="POST" class="d-none"><input type="hidden" name="aksi" value="hapus_banyak"><div id="bulkInputs"></div></form>

<script>
function editUser(id, username, role, passwordHash) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_old_password').value = passwordHash || '';
    document.getElementById('edit_new_password').value = '';
    
    // Reset password visibility
    const oldPassField = document.getElementById('edit_old_password');
    const newPassField = document.getElementById('edit_new_password');
    oldPassField.type = 'password';
    newPassField.type = 'password';
    
    // Reset eye icons
    document.querySelectorAll('.password-toggle i').forEach(icon => {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    });
    
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function hapusUser(id) {
    confirmSlideDelete('single', id);
}

// --- Selection & Bulk Action Logic ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const container = document.getElementById('usersContainer');
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
        document.querySelectorAll('.user-card').forEach(c => c.classList.remove('selected'));
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
    document.querySelectorAll('#usersContainer .item-checkbox').forEach(cb => {
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
    if (type === 'bulk') msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} user</b> terpilih.<br>Data terkait (mahasiswa/asisten) mungkin akan terpengaruh.`;
    else msg.innerHTML = `Anda akan menghapus user ini.<br>Data terkait (mahasiswa/asisten) mungkin akan terpengaruh.`;
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
        if (deleteType === 'single') { document.getElementById('hapus_id').value = deleteTargetId; document.getElementById('formHapus').submit(); }
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
    const container = document.getElementById('usersContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_users&ajax_search=1&search=${encodeURIComponent(searchValue)}`)
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
