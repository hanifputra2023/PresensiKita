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
        
        // Prepared statement
        $stmt_cek = mysqli_prepare($conn, "SELECT * FROM asisten WHERE kode_asisten = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $kode);
        mysqli_stmt_execute($stmt_cek);
        $cek = mysqli_stmt_get_result($stmt_cek);
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'Kode asisten sudah ada!');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Buat user dulu
            $stmt_user = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, 'asisten')");
            mysqli_stmt_bind_param($stmt_user, "ss", $kode, $hashed_password);
            mysqli_stmt_execute($stmt_user);
            $user_id = mysqli_insert_id($conn);
            
            $mk = $mk ?: null;
            $stmt_ast = mysqli_prepare($conn, "INSERT INTO asisten (kode_asisten, user_id, nama, no_hp, kode_mk) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ast, "sisss", $kode, $user_id, $nama, $hp, $mk);
            mysqli_stmt_execute($stmt_ast);
            set_alert('success', 'Asisten berhasil ditambahkan!');
        }
    } elseif ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        $nama = escape($_POST['nama']);
        $hp = escape($_POST['no_hp']);
        $mk = escape($_POST['kode_mk']);
        $status = escape($_POST['status']);
        
        $mk = $mk ?: null;
        $stmt_upd = mysqli_prepare($conn, "UPDATE asisten SET nama=?, no_hp=?, kode_mk=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_upd, "ssssi", $nama, $hp, $mk, $status, $id);
        mysqli_stmt_execute($stmt_upd);
        set_alert('success', 'Data asisten berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $id = (int)$_POST['id'];
        $stmt_get = mysqli_prepare($conn, "SELECT user_id FROM asisten WHERE id = ?");
        mysqli_stmt_bind_param($stmt_get, "i", $id);
        mysqli_stmt_execute($stmt_get);
        $ast = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
        if ($ast && $ast['user_id']) {
            $stmt_del_user = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt_del_user, "i", $ast['user_id']);
            mysqli_stmt_execute($stmt_del_user);
        }
        $stmt_del = mysqli_prepare($conn, "DELETE FROM asisten WHERE id = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        mysqli_stmt_execute($stmt_del);
        set_alert('success', 'Asisten berhasil dihapus!');
    } elseif ($aksi == 'hapus_banyak') {
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            
            $stmt_get = mysqli_prepare($conn, "SELECT user_id FROM asisten WHERE id = ?");
            $stmt_del_user = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            $stmt_del_ast = mysqli_prepare($conn, "DELETE FROM asisten WHERE id = ?");
            
            foreach ($ids as $id) {
                $safe_id = (int)$id;
                mysqli_stmt_bind_param($stmt_get, "i", $safe_id);
                mysqli_stmt_execute($stmt_get);
                $ast = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
                if ($ast && $ast['user_id']) {
                    mysqli_stmt_bind_param($stmt_del_user, "i", $ast['user_id']);
                    mysqli_stmt_execute($stmt_del_user);
                }
                mysqli_stmt_bind_param($stmt_del_ast, "i", $safe_id);
                if(mysqli_stmt_execute($stmt_del_ast)) $success_count++;
            }
            set_alert('success', $success_count . ' Asisten berhasil dihapus!');
        }
    }
    
    header("Location: index.php?page=admin_asisten");
    exit;
}

// Pagination - prepared statement
$per_page = 9;
$current_page = get_current_page();

$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$search_param = '%' . $search . '%';

if ($search) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM asisten a WHERE a.nama LIKE ? OR a.kode_asisten LIKE ?");
    mysqli_stmt_bind_param($stmt_count, "ss", $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} else {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM asisten");
}
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

if ($search) {
    $stmt_asisten = mysqli_prepare($conn, "SELECT a.*, mk.nama_mk FROM asisten a LEFT JOIN mata_kuliah mk ON a.kode_mk = mk.kode_mk WHERE a.nama LIKE ? OR a.kode_asisten LIKE ? ORDER BY a.kode_asisten LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_asisten, "ssii", $search_param, $search_param, $offset, $per_page);
    mysqli_stmt_execute($stmt_asisten);
    $asisten = mysqli_stmt_get_result($stmt_asisten);
} else {
    $stmt_asisten = mysqli_prepare($conn, "SELECT a.*, mk.nama_mk FROM asisten a LEFT JOIN mata_kuliah mk ON a.kode_mk = mk.kode_mk ORDER BY a.kode_asisten LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_asisten, "ii", $offset, $per_page);
    mysqli_stmt_execute($stmt_asisten);
    $asisten = mysqli_stmt_get_result($stmt_asisten);
}
$matkul = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk");

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($asisten) > 0): ?>
            <?php while ($a = mysqli_fetch_assoc($asisten)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 asisten-card position-relative" id="card-<?= $a['id'] ?>">
                        <div class="card-select-overlay">
                            <input type="checkbox" class="form-check-input item-checkbox" 
                                   value="<?= $a['id'] ?>" 
                                   onchange="toggleSelection(<?= $a['id'] ?>)">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <?php 
                                    $foto_profil = (!empty($a['foto']) && file_exists($a['foto'])) ? $a['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($a['nama']) . '&background=random&color=fff&rounded=true';
                                    ?>
                                    <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($a['nama']) ?>" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;" loading="lazy">
                                    <div>
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($a['nama']) ?></h5>
                                        <span class="badge bg-info"><?= htmlspecialchars($a['kode_asisten']) ?></span>
                                    </div>
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
                                <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', <?= $a['id'] ?>)">
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
    /* Welcome Banner Modern */
    .welcome-banner-asisten {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner-asisten::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-glow-asisten 4s ease-in-out infinite;
    }
    
    @keyframes pulse-glow-asisten {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.6;
        }
    }
    
    .welcome-banner-asisten h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-asisten .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-asisten .banner-icon {
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
    
    .welcome-banner-asisten .banner-badge {
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
    }
    
    .welcome-banner-asisten .btn-banner {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-asisten .btn-banner:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .welcome-banner-asisten .btn-banner-primary {
        background: white;
        color: var(--primary-color);
        border-color: white;
    }
    
    .welcome-banner-asisten .btn-banner-primary:hover {
        background: rgba(255, 255, 255, 0.95);
        color: var(--primary-color);
    }
    
    /* Filter Bar Modern */
    .filter-bar-asisten {
        background: var(--bg-card);
        padding: 24px;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        margin-bottom: 24px;
        border: 1px solid var(--border-color);
    }
    
    .filter-bar-asisten .form-label {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
    }
    
    .filter-bar-asisten .form-control {
        border-radius: 12px;
        border: 2px solid var(--border-color);
        padding: 10px 14px;
        transition: all 0.3s ease;
        background: var(--bg-card);
    }
    
    .filter-bar-asisten .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
        background: var(--bg-card);
    }
    
    .filter-bar-asisten .btn {
        border-radius: 12px;
        font-weight: 600;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }
    
    .filter-bar-asisten .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* Asisten Card Modern */
    .asisten-card {
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        height: 100%;
        animation: fadeInUp 0.5s ease both;
    }
    
    .asisten-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }
    
    .asisten-card.selected {
        background: linear-gradient(135deg, rgba(0, 102, 204, 0.05) 0%, rgba(0, 76, 153, 0.08) 100%);
        border: 2px solid var(--primary-color);
        box-shadow: 0 4px 16px rgba(0, 102, 204, 0.2);
    }
    
    .asisten-card .card-title {
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 12px;
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
        border-radius: 8px;
        font-weight: 600;
    }
    
    /* Card Selection Styles */
    .card-select-overlay {
        position: absolute;
        top: 16px;
        left: 16px;
        z-index: 5;
        display: none;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .select-mode .card-select-overlay {
        display: block;
        opacity: 1;
    }
    
    .asisten-card .card-body {
        transition: padding-top 0.3s;
    }
    
    .select-mode .asisten-card .card-body {
        padding-top: 2.5rem;
    }
    
    .item-checkbox {
        width: 22px;
        height: 22px;
        cursor: pointer;
        border: 2px solid var(--text-muted);
        border-radius: 8px;
    }
    
    .item-checkbox:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    /* Modal Modern Styling */
    .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
        background: var(--banner-gradient);
        color: white;
        border-radius: 20px 20px 0 0;
        padding: 24px 30px;
        border: none;
    }
    
    .modal-header .modal-title {
        font-weight: 700;
        font-size: 1.3rem;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }
    
    .modal-header .btn-close:hover {
        opacity: 1;
    }
    
    .modal-body {
        padding: 30px;
    }
    
    .modal-body .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .modal-body .form-control,
    .modal-body .form-select {
        border-radius: 12px;
        border: 2px solid var(--border-color);
        padding: 12px 16px;
        font-size: 15px;
        transition: all 0.3s ease;
    }
    
    .modal-body .form-control:focus,
    .modal-body .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
    }
    
    .modal-footer {
        padding: 20px 30px;
        border-top: 2px solid var(--border-color);
    }
    
    .modal-footer .btn {
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
    }
    
    /* Slider Confirm Modern */
    .slider-container {
        position: relative;
        width: 100%;
        height: 60px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        user-select: none;
    }
    
    .slider-container.unlocked {
        background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    }
    
    .slider-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-weight: 700;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
        pointer-events: none;
        z-index: 1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: opacity 0.3s;
    }
    
    .slider-container.unlocked .slider-text {
        opacity: 0;
    }
    
    .slider-handle {
        position: absolute;
        left: 5px;
        top: 5px;
        width: 50px;
        height: 50px;
        background: white;
        border-radius: 50%;
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #dc3545;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        z-index: 2;
    }
    
    .slider-handle:active {
        cursor: grabbing;
        transform: scale(1.1);
    }
    
    .slider-container.unlocked .slider-handle {
        color: #28a745;
        width: calc(100% - 10px);
        border-radius: 30px;
    }
    
    .slider-progress {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        width: 0;
        z-index: 0;
        transition: width 0.1s;
    }
    
    /* Bulk Action Bar */
    .bulk-action-bar,
    #bulkActionBar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-top: 2px solid var(--primary-color);
        padding: 20px 30px;
        box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
        transform: translateY(100%);
        transition: transform 0.3s ease;
        z-index: 1040;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .bulk-action-bar.show,
    #bulkActionBar.show {
        transform: translateY(0);
    }
    
    .bulk-action-bar .selected-count,
    #bulkActionBar .selected-count {
        font-weight: 700;
        font-size: 18px;
        color: var(--primary-color);
    }
    
    body {
        padding-bottom: 80px;
    }
    
    /* Animations */
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
    
    /* Staggered animations for cards */
    .asisten-card:nth-child(1) { animation-delay: 0.1s; }
    .asisten-card:nth-child(2) { animation-delay: 0.15s; }
    .asisten-card:nth-child(3) { animation-delay: 0.2s; }
    .asisten-card:nth-child(4) { animation-delay: 0.25s; }
    .asisten-card:nth-child(5) { animation-delay: 0.3s; }
    .asisten-card:nth-child(6) { animation-delay: 0.35s; }
    .asisten-card:nth-child(7) { animation-delay: 0.4s; }
    .asisten-card:nth-child(8) { animation-delay: 0.45s; }
    .asisten-card:nth-child(n+9) { animation-delay: 0.5s; }
    
    /* Dark Mode Support */
    [data-theme="dark"] .welcome-banner-asisten {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    [data-theme="dark"] .filter-bar-asisten {
        background: rgba(255, 255, 255, 0.05);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    [data-theme="dark"] .filter-bar-asisten .form-label-modern {
        color: #aaa;
    }
    
    [data-theme="dark"] .asisten-card {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
    }
    
    [data-theme="dark"] .asisten-card:hover {
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
    }
    
    [data-theme="dark"] .asisten-card.selected {
        background: rgba(0, 102, 204, 0.15);
    }
    
    [data-theme="dark"] .bulk-action-bar,
    [data-theme="dark"] #bulkActionBar {
        background: rgba(30, 30, 30, 0.98);
        border-top-color: var(--primary-color);
        box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.3);
    }
    
    /* Responsive Design */
    @media (max-width: 576px) {
        .welcome-banner-asisten {
            padding: 24px;
            border-radius: 16px;
        }
        
        .welcome-banner-asisten h1 {
            font-size: 24px;
        }
        
        .welcome-banner-asisten .banner-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
        }
        
        .welcome-banner-asisten .btn-banner {
            width: 100%;
            justify-content: center;
        }
        
        .filter-bar-asisten {
            padding: 16px;
        }
        
        .bulk-action-bar,
        #bulkActionBar {
            flex-direction: column;
            gap: 10px;
            padding: 15px;
        }
        
        .bulk-action-bar > div,
        #bulkActionBar > div {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        
        .bulk-action-bar button,
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
                <!-- Welcome Banner -->
                <div class="welcome-banner-asisten mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Kelola Asisten</h1>
                                    <p class="banner-subtitle mb-0">Manajemen data asisten dan status</p>
                                </div>
                            </div>
                            <span class="banner-badge">
                                <i class="fas fa-users-cog me-1"></i>Manajemen Asisten
                            </span>
                        </div>
                        <button class="btn btn-banner btn-banner-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="fas fa-plus me-2"></i>Tambah Asisten
                        </button>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <!-- Filter Bar -->
                <div class="filter-bar-asisten">
                    <form method="GET" class="row g-3 align-items-end" onsubmit="return false;">
                        <input type="hidden" name="page" value="admin_asisten">
                        <div class="col-12 col-md-8">
                            <label for="searchInput" class="form-label">Cari Asisten</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0" style="border-radius: 12px 0 0 12px; border: 2px solid var(--border-color); border-right: none;">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama atau kode asisten..." value="<?= htmlspecialchars($search) ?>" style="border-left: none !important; border: 2px solid var(--border-color); border-radius: 0 12px 12px 0; padding: 10px 14px;">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 d-flex flex-column flex-md-row align-items-stretch align-items-md-end justify-content-md-end gap-2">
                            <button type="button" class="btn btn-outline-primary" id="btnSelectMode" onclick="toggleSelectMode()">
                                <i class="fas fa-check-square me-1"></i> Mode Pilih
                            </button>
                            <div class="d-none align-items-center justify-content-center px-3 py-2 bg-light rounded" id="selectAllContainer" style="border: 2px solid var(--border-color);">
                                <input class="form-check-input item-checkbox m-0" type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="cursor: pointer;">
                                <label class="form-check-label fw-bold ms-2 small mb-0" for="selectAll" style="cursor:pointer;">Pilih Semua</label>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="asistenContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($asisten) > 0): ?>
                        <?php while ($a = mysqli_fetch_assoc($asisten)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 asisten-card position-relative" id="card-<?= $a['id'] ?>">
                                    <div class="card-select-overlay">
                                        <input type="checkbox" class="form-check-input item-checkbox" 
                                               value="<?= $a['id'] ?>" 
                                               onchange="toggleSelection(<?= $a['id'] ?>)">
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php 
                                                $foto_profil = (!empty($a['foto']) && file_exists($a['foto'])) ? $a['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($a['nama']) . '&background=random&color=fff&rounded=true';
                                                ?>
                                                <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($a['nama']) ?>" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;" loading="lazy">
                                                <div>
                                                    <h5 class="card-title mb-1"><?= htmlspecialchars($a['nama']) ?></h5>
                                                    <span class="badge bg-info"><?= htmlspecialchars($a['kode_asisten']) ?></span>
                                                </div>
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

<div id="bulkActionBar">
    <div class="d-flex align-items-center">
        <span class="badge bg-primary me-2" style="font-size: 1rem;"><span id="selectedCount">0</span></span>
        <span class="text-dark fw-bold">Asisten Dipilih</span>
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
function editAst(id, nama, hp, mk, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_hp').value = hp;
    document.getElementById('edit_mk').value = mk || '';
    document.getElementById('edit_status').value = status;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusAst(id) {
    confirmSlideDelete('single', id);
}

// --- Selection & Bulk Action Logic ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const container = document.getElementById('asistenContainer');
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
        document.querySelectorAll('.asisten-card').forEach(c => c.classList.remove('selected'));
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
    document.querySelectorAll('#asistenContainer .item-checkbox').forEach(cb => {
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
    if (type === 'bulk') msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} asisten</b> terpilih.<br>Akun login terkait juga akan dihapus.`;
    else msg.innerHTML = `Anda akan menghapus asisten ini.<br>Akun login terkait juga akan dihapus.`;
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
    const container = document.getElementById('asistenContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_asisten&ajax_search=1&search=${encodeURIComponent(searchValue)}`)
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
