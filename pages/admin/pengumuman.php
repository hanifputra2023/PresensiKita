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
    } elseif ($aksi == 'hapus_banyak') {
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            $stmt_del = mysqli_prepare($conn, "DELETE FROM pengumuman WHERE id = ?");
            foreach ($ids as $id) {
                $safe_id = (int)$id;
                mysqli_stmt_bind_param($stmt_del, "i", $safe_id);
                if(mysqli_stmt_execute($stmt_del)) $success_count++;
            }
            set_alert('success', $success_count . ' Pengumuman berhasil dihapus!');
        }
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

<style>
    /* Welcome Banner Modern */
    .welcome-banner-pengumuman {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner-pengumuman::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-glow-pengumuman 4s ease-in-out infinite;
    }
    
    @keyframes pulse-glow-pengumuman {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.6;
        }
    }
    
    @keyframes pulse-badge-pengumuman {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 8px rgba(255, 255, 255, 0);
        }
    }
    
    .welcome-banner-pengumuman h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-pengumuman .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-pengumuman .banner-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-pengumuman .banner-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        z-index: 1;
        animation: pulse-badge-pengumuman 2s ease-in-out infinite;
    }
    
    .welcome-banner-pengumuman .banner-badge i {
        font-size: 8px;
        margin-right: 6px;
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Bulk Selection Styles */
    .select-checkbox-col { display: none; width: 40px; text-align: center; }
    .select-mode .select-checkbox-col { display: table-cell; }
    .pengumuman-card { position: relative; transition: all 0.2s; }
    .pengumuman-card.selected { border-color: var(--primary-color); background-color: rgba(0, 102, 204, 0.05); }
    [data-theme="dark"] .pengumuman-card.selected { background-color: rgba(0, 102, 204, 0.15); }
    .card-select-overlay { position: absolute; top: 10px; left: 10px; z-index: 5; display: none; }
    .select-mode .card-select-overlay { display: block; }
    .pengumuman-card .card-body { transition: padding-top 0.2s; }
    .select-mode .pengumuman-card .card-body { padding-top: 3rem !important; }
    .item-checkbox { width: 22px; height: 22px; cursor: pointer; border: 2px solid var(--text-muted); border-radius: 50%; }
    .item-checkbox:checked { background-color: var(--primary-color); border-color: var(--primary-color); }

    /* Bulk Action Bar */
    #bulkActionBar { position: fixed; bottom: -100px; left: 0; right: 0; background: var(--bg-card); box-shadow: 0 -5px 20px rgba(0,0,0,0.1); padding: 15px 30px; z-index: 1000; transition: bottom 0.3s ease-in-out; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
    #bulkActionBar.show { bottom: 0; }
    [data-theme="dark"] #bulkActionBar { box-shadow: 0 -5px 20px rgba(0,0,0,0.3); }
    
    /* Slider Confirm */
    .slider-container { position: relative; width: 100%; height: 55px; background: #f0f2f5; border-radius: 30px; user-select: none; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); }
    [data-theme="dark"] .slider-container { background: var(--bg-input); box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }
    .slider-text { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #888; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; z-index: 1; pointer-events: none; transition: opacity 0.3s; }
    .slider-handle { position: absolute; top: 5px; left: 5px; width: 45px; height: 45px; background: #dc3545; border-radius: 50%; cursor: pointer; z-index: 2; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.1s; }
    .slider-handle:active { cursor: grabbing; transform: scale(0.95); }
    .slider-progress { position: absolute; top: 0; left: 0; height: 100%; background: rgba(220, 53, 69, 0.2); width: 0; z-index: 0; }
    .slider-container.unlocked .slider-handle { width: calc(100% - 10px); border-radius: 30px; }
    .slider-container.unlocked .slider-text { opacity: 0; }
    
    /* Dark Mode Support */
    [data-theme="dark"] .welcome-banner-pengumuman {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .welcome-banner-pengumuman {
            padding: 24px;
            border-radius: 16px;
        }
        
        .welcome-banner-pengumuman h1 {
            font-size: 24px;
        }
        
        .welcome-banner-pengumuman .banner-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
        }
    }
    
    @media (max-width: 576px) {
        .welcome-banner-pengumuman .d-flex.gap-2.align-items-center {
            width: 100%;
            flex-direction: column;
        }
        
        .welcome-banner-pengumuman .d-flex.gap-2.align-items-center .btn {
            width: 100%;
            padding: 12px 20px;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .welcome-banner-pengumuman #selectAllContainer {
            width: 100%;
            justify-content: center;
        }
        
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
                <!-- Welcome Banner -->
                <div class="welcome-banner-pengumuman mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Kelola Pengumuman</h1>
                                    <p class="banner-subtitle mb-0">Buat dan kelola pengumuman untuk mahasiswa dan asisten</p>
                                </div>
                            </div>
                            <span class="banner-badge">
                                <i class="fas fa-circle"></i>SISTEM PENGUMUMAN
                            </span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="d-none d-flex align-items-center me-2" id="selectAllContainer" style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 10px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                                <input class="form-check-input item-checkbox m-0" type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="border-color: rgba(255,255,255,0.6);">
                                <label class="form-check-label fw-bold ms-2 small text-white" for="selectAll" style="cursor:pointer">Semua</label>
                            </div>
                            <button type="button" class="btn" id="btnSelectMode" onclick="toggleSelectMode()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px); padding: 10px 20px; border-radius: 10px; font-weight: 600;">
                                <i class="fas fa-check-square me-1"></i> Pilih
                            </button>
                            <button class="btn" data-bs-toggle="modal" data-bs-target="#modalTambah" style="background: white; color: var(--primary-color); border: 2px solid white; padding: 10px 20px; border-radius: 10px; font-weight: 600;">
                                <i class="fas fa-plus me-1"></i>Buat Pengumuman
                            </button>
                        </div>
                    </div>
                </div>
                
                <?= show_alert() ?>
                <div class="card shadow-sm border-0" id="pengumumanContainer">
                    <div class="card-body">
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-lg-block" id="pengumumanTableContainer">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="select-checkbox-col"><i class="fas fa-check-square"></i></th>
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
                                        <tr id="row-<?= $row['id'] ?>">
                                            <td class="select-checkbox-col">
                                                <input type="checkbox" class="form-check-input item-checkbox m-0" value="<?= $row['id'] ?>" onchange="toggleSelection(<?= $row['id'] ?>)">
                                            </td>
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
                                                    <button class="btn btn-sm btn-danger" title="Hapus" onclick="confirmSlideDelete('single', <?= $row['id'] ?>)">
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
                        <div class="d-lg-none" id="pengumumanCardsContainer">
                            <?php if(count($pengumuman_data) > 0): ?>
                                <?php foreach($pengumuman_data as $row): ?>
                                    <div class="card mb-3 border pengumuman-card" id="card-<?= $row['id'] ?>">
                                        <div class="card-select-overlay">
                                            <input type="checkbox" class="form-check-input item-checkbox m-0" value="<?= $row['id'] ?>" onchange="toggleSelection(<?= $row['id'] ?>)">
                                        </div>
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
                                                    <button class="btn btn-sm btn-outline-danger flex-fill" onclick="confirmSlideDelete('single', <?= $row['id'] ?>)">
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

<div id="bulkActionBar">
    <div class="d-flex align-items-center">
        <span class="badge bg-primary me-2" style="font-size: 1rem;"><span id="selectedCount">0</span></span>
        <span class="text-dark fw-bold">Item Dipilih</span>
    </div>
    <div>
        <button class="btn btn-secondary me-2" onclick="toggleSelectMode()">Batal</button>
        <button class="btn btn-danger" onclick="confirmSlideDelete('bulk')"><i class="fas fa-trash-alt me-2"></i>Hapus Terpilih</button>
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

function hapusPengumuman(id) {
    confirmSlideDelete('single', id);
}

function toggleStatus(id, currentStatus) {
    document.getElementById('toggle_id').value = id;
    document.getElementById('toggle_status').value = currentStatus;
    document.getElementById('formToggle').submit();
}

// --- Selection & Bulk Action Logic ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const tableContainer = document.getElementById('pengumumanTableContainer');
    const cardsContainer = document.getElementById('pengumumanCardsContainer');
    const btn = document.getElementById('btnSelectMode');
    const selectAllContainer = document.getElementById('selectAllContainer');
    
    if (isSelectMode) {
        if(tableContainer) tableContainer.classList.add('select-mode');
        if(cardsContainer) cardsContainer.classList.add('select-mode');
        btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Batal';
        selectAllContainer.classList.remove('d-none');
        selectAllContainer.classList.add('d-flex');
    } else {
        if(tableContainer) tableContainer.classList.remove('select-mode');
        if(cardsContainer) cardsContainer.classList.remove('select-mode');
        btn.classList.replace('btn-secondary', 'btn-outline-secondary');
        btn.innerHTML = '<i class="fas fa-check-square me-1"></i> Pilih';
        selectAllContainer.classList.add('d-none');
        selectAllContainer.classList.remove('d-flex');
        selectedItems.clear();
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.pengumuman-card').forEach(c => c.classList.remove('selected'));
        document.querySelectorAll('tr').forEach(r => r.classList.remove('selected'));
        updateBulkUI();
    }
}

function toggleSelection(id) {
    const idStr = String(id);
    const isSelected = selectedItems.has(idStr);
    if (!isSelected) selectedItems.add(idStr); else selectedItems.delete(idStr);
    
    const checkboxes = document.querySelectorAll(`.item-checkbox[value="${id}"]`);
    checkboxes.forEach(cb => cb.checked = !isSelected);
    
    const card = document.getElementById('card-' + id);
    if(card) card.classList.toggle('selected', !isSelected);
    const row = document.getElementById('row-' + id);
    if(row) row.classList.toggle('selected', !isSelected);
    
    updateBulkUI();
}

function toggleSelectAll() {
    const isChecked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        const id = String(cb.value);
        if (cb.checked !== isChecked) toggleSelection(id);
    });
}

function updateBulkUI() {
    const bar = document.getElementById('bulkActionBar');
    document.getElementById('selectedCount').innerText = selectedItems.size;
    if (selectedItems.size > 0) bar.classList.add('show'); else bar.classList.remove('show');
}

// --- Slide to Confirm Logic ---
let deleteType = ''; let deleteTargetId = null;
function confirmSlideDelete(type, id = null) {
    deleteType = type; deleteTargetId = id;
    const modal = new bootstrap.Modal(document.getElementById('modalSlideConfirm'));
    const msg = document.getElementById('slideConfirmMsg');
    if (type === 'bulk') msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} pengumuman</b> terpilih.`;
    else msg.innerHTML = `Anda akan menghapus pengumuman ini.`;
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
</script>
<?php include 'includes/footer.php'; ?>