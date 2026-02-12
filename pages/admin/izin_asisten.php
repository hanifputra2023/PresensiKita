<?php
$page = 'admin_izin_asisten';

// [BARU] Proses Hapus & Hapus Banyak
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    if ($_POST['aksi'] == 'hapus') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM absen_asisten WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        set_alert('success', 'Data izin berhasil dihapus!');
        header("Location: index.php?page=admin_izin_asisten&tab=" . ($_GET['tab'] ?? 'pending'));
        exit;
    } elseif ($_POST['aksi'] == 'hapus_banyak') {
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            $stmt_del = mysqli_prepare($conn, "DELETE FROM absen_asisten WHERE id = ?");
            foreach ($ids as $id) {
                $safe_id = (int)$id;
                mysqli_stmt_bind_param($stmt_del, "i", $safe_id);
                if(mysqli_stmt_execute($stmt_del)) $success_count++;
            }
            set_alert('success', $success_count . ' Data izin berhasil dihapus!');
        }
        header("Location: index.php?page=admin_izin_asisten&tab=" . ($_GET['tab'] ?? 'pending'));
        exit;
    }
}

// Proses Approve - HARUS via POST dengan CSRF token
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve'])) {
    // Validasi CSRF token
    validate_csrf_token(); // Fungsi ini otomatis cek $_POST['csrf_token'] dan die() jika gagal
    
    $id = (int)$_POST['approve'];
    
    $stmt = mysqli_prepare($conn, "UPDATE absen_asisten 
                                   SET status_approval = 'approved', 
                                       approved_by = ?, 
                                       approved_at = NOW() 
                                   WHERE id = ? AND status_approval = 'pending'");
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $id);
    
    if (mysqli_stmt_execute($stmt) && mysqli_affected_rows($conn) > 0) {
        $detail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT aa.*, a.nama 
                                                          FROM absen_asisten aa 
                                                          JOIN asisten a ON aa.kode_asisten = a.kode_asisten 
                                                          WHERE aa.id = $id"));
        
        // [LOGIKA BARU] Update tabel jadwal jika ada pengganti
        if (!empty($detail['pengganti'])) {
            $jadwal_id = $detail['jadwal_id'];
            $asisten_lama = $detail['kode_asisten'];
            $asisten_baru = $detail['pengganti'];

            // Cek posisi asisten lama di jadwal (apakah Asisten 1 atau Asisten 2)
            $cek_jadwal = mysqli_query($conn, "SELECT kode_asisten_1, kode_asisten_2 FROM jadwal WHERE id = '$jadwal_id'");
            if ($row_j = mysqli_fetch_assoc($cek_jadwal)) {
                $col_update = '';
                if ($row_j['kode_asisten_1'] == $asisten_lama) {
                    $col_update = 'kode_asisten_1';
                } elseif ($row_j['kode_asisten_2'] == $asisten_lama) {
                    $col_update = 'kode_asisten_2';
                }

                if ($col_update) {
                    // Update jadwal: Ganti asisten lama dengan pengganti
                    mysqli_query($conn, "UPDATE jadwal SET $col_update = '$asisten_baru' WHERE id = '$jadwal_id'");
                }
            }
        }

        log_aktivitas($_SESSION['user_id'], 'APPROVE_IZIN_ASISTEN', 'absen_asisten', $id, 
                      "Admin menyetujui izin asisten {$detail['nama']}");
        set_alert('success', 'Pengajuan izin disetujui dan jadwal telah diperbarui dengan asisten pengganti!');
    } else {
        set_alert('danger', 'Gagal menyetujui pengajuan izin!');
    }
    
    header("Location: index.php?page=admin_izin_asisten&tab=" . ($_POST['tab'] ?? 'pending'));
    exit;
}

// Proses Reject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject'])) {
    $id = (int)$_POST['id'];
    $alasan_reject = escape($_POST['alasan_reject']);
    
    if (empty($alasan_reject)) {
        set_alert('danger', 'Alasan penolakan wajib diisi!');
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE absen_asisten 
                                       SET status_approval = 'rejected', 
                                           approved_by = ?, 
                                           approved_at = NOW(),
                                           alasan_reject = ?
                                       WHERE id = ? AND status_approval = 'pending'");
        mysqli_stmt_bind_param($stmt, "isi", $_SESSION['user_id'], $alasan_reject, $id);
        
        if (mysqli_stmt_execute($stmt) && mysqli_affected_rows($conn) > 0) {
            $detail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT aa.*, a.nama 
                                                              FROM absen_asisten aa 
                                                              JOIN asisten a ON aa.kode_asisten = a.kode_asisten 
                                                              WHERE aa.id = $id"));
            log_aktivitas($_SESSION['user_id'], 'REJECT_IZIN_ASISTEN', 'absen_asisten', $id, 
                          "Admin menolak izin asisten {$detail['nama']}: $alasan_reject");
            set_alert('info', 'Pengajuan izin berhasil ditolak!');
        } else {
            set_alert('danger', 'Gagal menolak pengajuan izin!');
        }
    }
    
    header("Location: index.php?page=admin_izin_asisten&tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
}

// Tab aktif
$active_tab = $_GET['tab'] ?? 'pending';
$search = isset($_GET['search']) ? escape($_GET['search']) : '';

// Pagination
$per_page = 10;
$current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($current_page - 1) * $per_page;

// Build query berdasarkan tab
$where_status = "";
if ($active_tab == 'pending') {
    $where_status = "AND aa.status_approval = 'pending'";
} elseif ($active_tab == 'approved') {
    $where_status = "AND aa.status_approval = 'approved'";
} elseif ($active_tab == 'rejected') {
    $where_status = "AND aa.status_approval = 'rejected'";
}

$where_search = "";
if ($search) {
    $where_search = "AND (a.nama LIKE '%$search%' OR mk.nama_mk LIKE '%$search%' OR k.nama_kelas LIKE '%$search%')";
}

// Count total untuk pagination
$count_query = "SELECT COUNT(*) as total FROM absen_asisten aa
                JOIN jadwal j ON aa.jadwal_id = j.id
                JOIN asisten a ON aa.kode_asisten = a.kode_asisten
                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                WHERE aa.status IN ('izin', 'sakit') $where_status $where_search";
$total_data = mysqli_fetch_assoc(mysqli_query($conn, $count_query))['total'];
$total_pages = ceil($total_data / $per_page);

// Query pengajuan izin asisten
$query = "SELECT aa.*, 
                 j.tanggal, j.jam_mulai, j.jam_selesai, j.materi,
                 a.nama as nama_asisten, a.kode_asisten, a.foto as foto_asisten,
                 ap.nama as nama_pengganti, ap.foto as foto_pengganti,
                 mk.nama_mk, k.nama_kelas, l.nama_lab,
                 u.username as approved_by_name
          FROM absen_asisten aa
          JOIN jadwal j ON aa.jadwal_id = j.id
          JOIN asisten a ON aa.kode_asisten = a.kode_asisten
          LEFT JOIN asisten ap ON aa.pengganti = ap.kode_asisten
          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
          LEFT JOIN users u ON aa.approved_by = u.id
          WHERE aa.status IN ('izin', 'sakit')
          $where_status
          $where_search
          ORDER BY j.tanggal ASC, j.jam_mulai ASC
          LIMIT $per_page OFFSET $offset";
$result = mysqli_query($conn, $query);

// Hitung statistik
$count_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM absen_asisten WHERE status IN ('izin', 'sakit') AND status_approval = 'pending'"))['total'];
$count_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM absen_asisten WHERE status IN ('izin', 'sakit') AND status_approval = 'approved'"))['total'];
$count_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM absen_asisten WHERE status IN ('izin', 'sakit') AND status_approval = 'rejected'"))['total'];

?>
<?php include 'includes/header.php'; ?>

<style>
    /* Welcome Banner Modern */
    .welcome-banner-izin {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
        margin-bottom: 28px;
    }
    
    .welcome-banner-izin::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-glow-izin 4s ease-in-out infinite;
    }
    
    @keyframes pulse-glow-izin {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.6;
        }
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
    
    .welcome-banner-izin h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px 0;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-izin .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
        margin: 0;
    }
    
    .welcome-banner-izin .banner-icon {
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
    
    .welcome-banner-izin .banner-badge {
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
        margin-bottom: 16px;
    }
    
    .welcome-banner-izin .banner-badge i {
        font-size: 8px;
        animation: pulse-badge-izin 2s infinite;
    }
    
    @keyframes pulse-badge-izin {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* Dark Mode */
    [data-theme="dark"] .welcome-banner-izin {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .welcome-banner-izin {
            padding: 24px;
            border-radius: 16px;
        }
        
        .welcome-banner-izin h1 {
            font-size: 24px;
        }
        
        .welcome-banner-izin .banner-subtitle {
            font-size: 14px;
        }
        
        .welcome-banner-izin .banner-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
        }
    }
    
    @media (max-width: 576px) {
        .welcome-banner-izin {
            padding: 20px;
            border-radius: 14px;
        }
        
        .welcome-banner-izin h1 {
            font-size: 20px;
        }
        
        .welcome-banner-izin .banner-subtitle {
            font-size: 13px;
        }
        
        .welcome-banner-izin .banner-icon {
            width: 45px;
            height: 45px;
            font-size: 20px;
        }
        
        .welcome-banner-izin .banner-badge {
            font-size: 11px;
            padding: 6px 16px;
        }
    }

    /* Avatar Styling */
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        overflow: hidden;
        flex-shrink: 0;
    }
    .avatar-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Action Buttons */
    .btn-action {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    
    /* Override width untuk mobile button grid */
    .mobile-btn-item .btn-action,
    .mobile-btn-item.btn-action {
        width: 100% !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important;
    }
    
    /* Modal Styling */
    .modal-header.custom-gradient {
        background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
        color: white;
        border-bottom: none;
        border-radius: var(--bs-modal-inner-border-radius) var(--bs-modal-inner-border-radius) 0 0;
    }
    .modal-header.custom-gradient-danger {
        background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
        color: white;
        border-bottom: none;
        border-radius: var(--bs-modal-inner-border-radius) var(--bs-modal-inner-border-radius) 0 0;
    }
    .info-row {
        display: flex;
        margin-bottom: 12px;
        border-bottom: 1px dashed #eee;
        padding-bottom: 8px;
    }
    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .info-label {
        width: 130px;
        font-weight: 600;
        color: #6c757d;
        flex-shrink: 0;
    }
    .info-value {
        flex: 1;
        color: #333;
    }

    /* Mobile Card Styling */
    .izin-card {
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    .izin-card.pending-card { border-left: 4px solid #ffc107; }
    .izin-card.approved-card { border-left: 4px solid #198754; }
    .izin-card.rejected-card { border-left: 4px solid #dc3545; }

    /* Bulk Selection Styles */
    .select-checkbox-col { display: none; width: 40px; text-align: center; }
    .select-mode .select-checkbox-col { display: table-cell; }
    .izin-card { position: relative; transition: all 0.2s; }
    .izin-card.selected { border-color: var(--primary-color); background-color: rgba(0, 102, 204, 0.05); }
    [data-theme="dark"] .izin-card.selected { background-color: rgba(0, 102, 204, 0.15); }
    .card-select-overlay { position: absolute; top: 10px; left: 10px; z-index: 5; display: none; }
    .select-mode .card-select-overlay { display: block; }
    .izin-card .card-body { transition: padding-top 0.2s; }
    .select-mode .izin-card .card-body { padding-top: 3rem !important; }
    .item-checkbox { width: 22px; height: 22px; cursor: pointer; border: 2px solid var(--text-muted); border-radius: 50%; }
    .item-checkbox:checked { background-color: var(--primary-color); border-color: var(--primary-color); }

    /* Bulk Action Bar */
    #bulkActionBar { position: fixed; bottom: -100px; left: 0; right: 0; background: var(--bg-card); box-shadow: 0 -5px 20px rgba(0,0,0,0.1); padding: 15px 30px; z-index: 1000; transition: bottom 0.3s ease-in-out; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
    #bulkActionBar.show { bottom: 0; }
    [data-theme="dark"] #bulkActionBar { box-shadow: 0 -5px 20px rgba(0,0,0,0.3); }
    
    /* Slider Confirm Styles */
    .slider-container { position: relative; width: 100%; height: 55px; background: #f0f2f5; border-radius: 30px; user-select: none; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); }
    [data-theme="dark"] .slider-container { background: var(--bg-input); box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }
    .slider-text { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #888; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; z-index: 1; pointer-events: none; transition: opacity 0.3s; }
    .slider-handle { position: absolute; top: 5px; left: 5px; width: 45px; height: 45px; background: #dc3545; border-radius: 50%; cursor: pointer; z-index: 2; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.1s; }
    .slider-handle:active { cursor: grabbing; transform: scale(0.95); }
    .slider-progress { position: absolute; top: 0; left: 0; height: 100%; background: rgba(220, 53, 69, 0.2); width: 0; z-index: 0; }
    .slider-container.unlocked .slider-handle { width: calc(100% - 10px); border-radius: 30px; }
    .slider-container.unlocked .slider-text { opacity: 0; }

    /* Dark Mode Support */
    [data-theme="dark"] .info-label { color: #adb5bd; }
    [data-theme="dark"] .info-value { color: #e9ecef; }
    [data-theme="dark"] .info-row { border-bottom-color: #495057; }
    [data-theme="dark"] .izin-card {
        background-color: var(--bg-card);
        border-color: var(--border-color);
    }
    
    /* Mobile Tab Navigation 2x2 Layout */
    @media (max-width: 576px) {
        .izin-asisten-page .nav-pills {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 0.5rem !important;
        }
        
        .izin-asisten-page .nav-pills .nav-item {
            flex: 0 0 calc(50% - 0.25rem) !important;
            max-width: calc(50% - 0.25rem) !important;
        }
        
        .izin-asisten-page .nav-pills .nav-item .nav-link {
            width: 100% !important;
            text-align: center !important;
            padding: 0.5rem 0.25rem !important;
            font-size: 0.8rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
    }

    /* Mobile Button Grid for 2x2 Layout */
    .mobile-btn-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
    }
    
    .mobile-btn-item {
        flex: 0 0 calc(50% - 0.25rem) !important;
        max-width: calc(50% - 0.25rem) !important;
        min-width: calc(50% - 0.25rem) !important;
    }
    
    .mobile-btn-item.btn {
        width: 100% !important;
    }
    
    .mobile-btn-item form {
        flex: 0 0 calc(50% - 0.25rem) !important;
        max-width: calc(50% - 0.25rem) !important;
        width: 100% !important;
    }
    
    .mobile-btn-item form button {
        width: 100% !important;
    }
</style>

<div class="container-fluid izin-asisten-page">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <!-- Welcome Banner -->
                <div class="welcome-banner-izin">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="banner-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div>
                            <h1 class="mb-1">Persetujuan Izin Asisten</h1>
                            <p class="banner-subtitle mb-0">Kelola dan setujui pengajuan izin dari asisten praktikum</p>
                        </div>
                    </div>
                    <span class="banner-badge">
                        <i class="fas fa-circle"></i>
                        MANAJEMEN IZIN
                    </span>
                </div>
                
                <?= show_alert() ?>
                
                <!-- Tab Navigation -->
                <ul class="nav nav-pills mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'pending' ? 'active' : '' ?>" 
                           href="index.php?page=admin_izin_asisten&tab=pending">
                            <i class="fas fa-clock me-1"></i>Menunggu
                            <?php if ($count_pending > 0): ?>
                                <span class="badge bg-warning text-dark ms-1"><?= $count_pending ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'approved' ? 'active' : '' ?>" 
                           href="index.php?page=admin_izin_asisten&tab=approved">
                            <i class="fas fa-check-circle me-1"></i>Disetujui
                            <span class="badge bg-light text-dark ms-1"><?= $count_approved ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'rejected' ? 'active' : '' ?>" 
                           href="index.php?page=admin_izin_asisten&tab=rejected">
                            <i class="fas fa-times-circle me-1"></i>Ditolak
                            <span class="badge bg-light text-dark ms-1"><?= $count_rejected ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'all' ? 'active' : '' ?>" 
                           href="index.php?page=admin_izin_asisten&tab=all">
                            <i class="fas fa-list me-1"></i>Semua
                        </a>
                    </li>
                </ul>
                
                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body py-3">
                        <form method="GET" class="row g-2 align-items-center" id="searchForm">
                            <input type="hidden" name="page" value="admin_izin_asisten">
                            <input type="hidden" name="tab" value="<?= $active_tab ?>">
                            <div class="col">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0" 
                                           value="<?= htmlspecialchars($search) ?>" 
                                           placeholder="Cari nama asisten, mata kuliah, atau kelas...">
                                </div>
                            </div>
                            <div class="col-auto d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1 d-none d-sm-inline"></i>Cari
                                </button>
                                <?php if ($search): ?>
                                    <a href="index.php?page=admin_izin_asisten&tab=<?= $active_tab ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
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
                
                <!-- Content -->
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <!-- Desktop Table -->
                    <div class="card d-none d-lg-block" id="izinTableContainer">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="select-checkbox-col"><i class="fas fa-check-square"></i></th>
                                            <th class="ps-3">Asisten</th>
                                            <th>Jadwal</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Pengganti</th>
                                            <th>Alasan</th>
                                            <?php if ($active_tab != 'pending'): ?>
                                                <th>Approval</th>
                                            <?php endif; ?>
                                            <th class="text-center pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($r = mysqli_fetch_assoc($result)): ?>
                                            <tr id="row-<?= $r['id'] ?>">
                                                <td class="select-checkbox-col">
                                                    <input type="checkbox" class="form-check-input item-checkbox m-0" value="<?= $r['id'] ?>" onchange="toggleSelection(<?= $r['id'] ?>)">
                                                </td>
                                                <td class="ps-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar-circle bg-primary text-white">
                                                            <?php if ($r['foto_asisten'] && file_exists($r['foto_asisten'])): ?>
                                                                <img src="<?= $r['foto_asisten'] ?>" alt="<?= $r['nama_asisten'] ?>" loading="lazy">
                                                            <?php else: ?>
                                                                <?= strtoupper(substr($r['nama_asisten'], 0, 1)) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= $r['nama_asisten'] ?></strong>
                                                            <br><small class="text-muted"><?= $r['kode_asisten'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= $r['nama_mk'] ?></strong>
                                                    <br><small class="text-muted"><?= $r['nama_kelas'] ?> · <?= $r['nama_lab'] ?></small>
                                                </td>
                                                <td>
                                                    <i class="fas fa-calendar-alt text-muted me-1"></i><?= format_tanggal($r['tanggal']) ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i><?= format_waktu($r['jam_mulai']) ?> - <?= format_waktu($r['jam_selesai']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($r['status'] == 'izin'): ?>
                                                        <span class="badge badge-status bg-warning text-dark">
                                                            <i class="fas fa-calendar-times me-1"></i>Izin
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-status bg-info">
                                                            <i class="fas fa-notes-medical me-1"></i>Sakit
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($r['nama_pengganti']): ?>
                                                        <i class="fas fa-user-friends text-muted me-1"></i><?= $r['nama_pengganti'] ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="max-width: 200px;">
                                                    <?php if ($r['catatan']): ?>
                                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                              title="<?= htmlspecialchars($r['catatan']) ?>">
                                                            <?= htmlspecialchars($r['catatan']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($active_tab != 'pending'): ?>
                                                    <td>
                                                        <?php if ($r['status_approval'] == 'approved'): ?>
                                                            <span class="badge bg-success">Disetujui</span>
                                                            <?php if ($r['approved_at']): ?>
                                                                <br><small class="text-muted"><?= date('d M Y', strtotime($r['approved_at'])) ?></small>
                                                            <?php endif; ?>
                                                        <?php elseif ($r['status_approval'] == 'rejected'): ?>
                                                            <span class="badge bg-danger">Ditolak</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Menunggu</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td class="text-center pe-3">
                                                    <?php if ($r['status_approval'] == 'pending'): ?>
                                                        <div class="btn-group">
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Setujui pengajuan izin dari <?= $r['nama_asisten'] ?>?')">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="approve" value="<?= $r['id'] ?>">
                                                                <input type="hidden" name="tab" value="<?= $active_tab ?>">
                                                                <button type="submit" class="btn btn-success btn-sm btn-action">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <button class="btn btn-danger btn-sm btn-action" data-bs-toggle="modal" 
                                                                    data-bs-target="#modalReject<?= $r['id'] ?>">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" 
                                                                    data-bs-target="#modalDetail<?= $r['id'] ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger btn-sm" onclick="confirmSlideDelete('single', <?= $r['id'] ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-secondary btn-sm px-2 py-1" data-bs-toggle="modal" 
                                                                data-bs-target="#modalDetail<?= $r['id'] ?>">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm ms-1" onclick="confirmSlideDelete('single', <?= $r['id'] ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal Detail -->
                                            <div class="modal fade" id="modalDetail<?= $r['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header custom-gradient">
                                                            <h5 class="modal-title text-white">
                                                                <i class="fas fa-info-circle me-2"></i>Detail Pengajuan Izin
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="info-row">
                                                                <div class="info-label">Asisten</div>
                                                                <div class="info-value"><strong><?= $r['nama_asisten'] ?></strong> (<?= $r['kode_asisten'] ?>)</div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Status</div>
                                                                <div class="info-value">
                                                                    <?php if ($r['status'] == 'izin'): ?>
                                                                        <span class="badge bg-warning text-dark">Izin</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-info">Sakit</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Mata Kuliah</div>
                                                                <div class="info-value"><?= $r['nama_mk'] ?></div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Kelas</div>
                                                                <div class="info-value"><?= $r['nama_kelas'] ?> - <?= $r['nama_lab'] ?></div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Materi</div>
                                                                <div class="info-value"><?= $r['materi'] ?></div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Tanggal</div>
                                                                <div class="info-value"><?= format_tanggal($r['tanggal']) ?></div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Waktu</div>
                                                                <div class="info-value"><?= format_waktu($r['jam_mulai']) ?> - <?= format_waktu($r['jam_selesai']) ?></div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Pengganti</div>
                                                                <div class="info-value"><?= $r['nama_pengganti'] ?: '<span class="text-muted">Tidak ada</span>' ?></div>
                                                            </div>
                                                            <div class="info-row">
                                                                <div class="info-label">Alasan</div>
                                                                <div class="info-value"><?= $r['catatan'] ?: '<span class="text-muted">Tidak ada</span>' ?></div>
                                                            </div>
                                                            <?php if ($r['status_approval'] != 'pending'): ?>
                                                                <hr>
                                                                <div class="info-row">
                                                                    <div class="info-label">Status Approval</div>
                                                                    <div class="info-value">
                                                                        <?php if ($r['status_approval'] == 'approved'): ?>
                                                                            <span class="badge bg-success">Disetujui</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-danger">Ditolak</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <?php if ($r['approved_by_name']): ?>
                                                                    <div class="info-row">
                                                                        <div class="info-label">Oleh</div>
                                                                        <div class="info-value"><?= $r['approved_by_name'] ?></div>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($r['approved_at']): ?>
                                                                    <div class="info-row">
                                                                        <div class="info-label">Waktu</div>
                                                                        <div class="info-value"><?= date('d M Y H:i', strtotime($r['approved_at'])) ?></div>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($r['alasan_reject']): ?>
                                                                    <div class="info-row">
                                                                        <div class="info-label">Alasan Tolak</div>
                                                                        <div class="info-value text-danger"><?= $r['alasan_reject'] ?></div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <?php if ($r['status_approval'] == 'pending'): ?>
                                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Setujui pengajuan izin ini?')">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="approve" value="<?= $r['id'] ?>">
                                                                    <input type="hidden" name="tab" value="<?= $active_tab ?>">
                                                                    <button type="submit" class="btn btn-success">
                                                                        <i class="fas fa-check me-1"></i>Setujui
                                                                    </button>
                                                                </form>
                                                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalReject<?= $r['id'] ?>" data-bs-dismiss="modal">
                                                                    <i class="fas fa-times me-1"></i>Tolak
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal Reject -->
                                            <?php if ($r['status_approval'] == 'pending'): ?>
                                                <div class="modal fade" id="modalReject<?= $r['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header custom-gradient-danger">
                                                                <h5 class="modal-title text-white">
                                                                    <i class="fas fa-times-circle me-2"></i>Tolak Pengajuan
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                                    
                                                                    <div class="alert bg-white border mb-3">
                                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                                            <div class="avatar-circle bg-primary text-white" style="width: 45px; height: 45px;">
                                                                                <?php if ($r['foto_asisten'] && file_exists($r['foto_asisten'])): ?>
                                                                                    <img src="<?= $r['foto_asisten'] ?>" alt="<?= $r['nama_asisten'] ?>" loading="lazy">
                                                                                <?php else: ?>
                                                                                    <?= strtoupper(substr($r['nama_asisten'], 0, 1)) ?>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <div>
                                                                                <strong><?= $r['nama_asisten'] ?></strong>
                                                                                <br><small class="text-muted"><?= $r['nama_mk'] ?> · <?= format_tanggal($r['tanggal']) ?></small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                                        <textarea name="alasan_reject" class="form-control" rows="3" 
                                                                                  required placeholder="Jelaskan alasan penolakan..."></textarea>
                                                                        <small class="text-muted">Alasan ini akan dilihat oleh asisten yang bersangkutan</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="reject" class="btn btn-danger">
                                                                        <i class="fas fa-times me-1"></i>Tolak Pengajuan
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Cards -->
                    <div class="d-lg-none" id="izinCardsContainer">
                        <?php 
                        mysqli_data_seek($result, 0);
                        while ($r = mysqli_fetch_assoc($result)): 
                            $card_class = '';
                            if ($r['status_approval'] == 'pending') $card_class = 'pending-card';
                            elseif ($r['status_approval'] == 'approved') $card_class = 'approved-card';
                            else $card_class = 'rejected-card';
                        ?>
                            <div class="card izin-card <?= $card_class ?> mb-3" id="card-<?= $r['id'] ?>">
                                <div class="card-select-overlay">
                                    <input type="checkbox" class="form-check-input item-checkbox m-0" value="<?= $r['id'] ?>" onchange="toggleSelection(<?= $r['id'] ?>)">
                                </div>
                                <div class="card-body p-3">
                                    <!-- Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle bg-primary text-white" style="width: 40px; height: 40px; font-size: 1rem;">
                                                <?php if ($r['foto_asisten'] && file_exists($r['foto_asisten'])): ?>
                                                    <img src="<?= $r['foto_asisten'] ?>" alt="<?= $r['nama_asisten'] ?>" loading="lazy">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($r['nama_asisten'], 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?= $r['nama_asisten'] ?></strong>
                                                <br><small class="text-muted"><?= $r['kode_asisten'] ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($r['status'] == 'izin'): ?>
                                                <span class="badge bg-warning text-dark">Izin</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Sakit</span>
                                            <?php endif; ?>
                                            <?php if ($r['status_approval'] == 'pending'): ?>
                                                <br><span class="badge bg-secondary mt-1">Menunggu</span>
                                            <?php elseif ($r['status_approval'] == 'approved'): ?>
                                                <br><span class="badge bg-success mt-1">Disetujui</span>
                                            <?php else: ?>
                                                <br><span class="badge bg-danger mt-1">Ditolak</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Info -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center text-muted small mb-1">
                                            <i class="fas fa-book me-2" style="width: 16px;"></i>
                                            <span><?= $r['nama_mk'] ?> · <?= $r['nama_kelas'] ?></span>
                                        </div>
                                        <div class="d-flex align-items-center text-muted small mb-1">
                                            <i class="fas fa-calendar-alt me-2" style="width: 16px;"></i>
                                            <span><?= format_tanggal($r['tanggal']) ?> · <?= format_waktu($r['jam_mulai']) ?></span>
                                        </div>
                                        <?php if ($r['nama_pengganti']): ?>
                                            <div class="d-flex align-items-center text-muted small mb-1">
                                                <i class="fas fa-user-friends me-2" style="width: 16px;"></i>
                                                <span>Pengganti: <?= $r['nama_pengganti'] ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($r['catatan']): ?>
                                            <div class="d-flex align-items-start text-muted small">
                                                <i class="fas fa-comment me-2 mt-1" style="width: 16px;"></i>
                                                <span><?= htmlspecialchars(substr($r['catatan'], 0, 60)) ?><?= strlen($r['catatan']) > 60 ? '...' : '' ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                    <!-- Actions -->
                    <?php if ($r['status_approval'] == 'pending'): ?>
                        <div class="d-flex flex-wrap gap-2 mobile-btn-grid">
                            <form method="POST" class="mobile-btn-item" onsubmit="return confirm('Setujui pengajuan izin ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="approve" value="<?= $r['id'] ?>">
                                <input type="hidden" name="tab" value="<?= $active_tab ?>">
                                <button type="submit" class="btn btn-success btn-sm w-100 btn-action justify-content-center">
                                    <i class="fas fa-check"></i> Setujui
                                </button>
                            </form>
                            <button class="btn btn-danger btn-sm mobile-btn-item btn-action justify-content-center" 
                                    data-bs-toggle="modal" data-bs-target="#modalRejectMobile<?= $r['id'] ?>">
                                <i class="fas fa-times"></i> Tolak
                            </button>
                            <button class="btn btn-outline-primary btn-sm mobile-btn-item btn-action justify-content-center" 
                                    data-bs-toggle="modal" data-bs-target="#modalDetailMobile<?= $r['id'] ?>">
                                <i class="fas fa-eye"></i> Detail
                            </button>
                            <button class="btn btn-outline-danger btn-sm mobile-btn-item btn-action justify-content-center" 
                                    onclick="confirmSlideDelete('single', <?= $r['id'] ?>)">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                        
                        <!-- Modal Reject Mobile -->
                        <div class="modal fade" id="modalRejectMobile<?= $r['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header custom-gradient-danger">
                                            <h6 class="modal-title text-white">
                                                <i class="fas fa-times-circle me-2"></i>Tolak Pengajuan
                                            </h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <p class="small text-muted mb-3">
                                                Menolak izin <strong><?= $r['nama_asisten'] ?></strong> untuk jadwal 
                                                <?= $r['nama_mk'] ?> pada <?= format_tanggal($r['tanggal']) ?>
                                            </p>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                <textarea name="alasan_reject" class="form-control" rows="3" 
                                                          required placeholder="Alasan penolakan..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="reject" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i>Tolak
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Detail Mobile for Pending -->
                        <div class="modal fade" id="modalDetailMobile<?= $r['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header custom-gradient">
                                        <h6 class="modal-title text-white">
                                            <i class="fas fa-info-circle me-2"></i>Detail Pengajuan
                                        </h6>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="info-row">
                                            <div class="info-label">Asisten</div>
                                            <div class="info-value"><strong><?= $r['nama_asisten'] ?></strong></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Status</div>
                                            <div class="info-value">
                                                <?php if ($r['status'] == 'izin'): ?>
                                                    <span class="badge bg-warning text-dark">Izin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Sakit</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Jadwal</div>
                                            <div class="info-value"><?= $r['nama_mk'] ?> · <?= $r['nama_kelas'] ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Tanggal</div>
                                            <div class="info-value"><?= format_tanggal($r['tanggal']) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Waktu</div>
                                            <div class="info-value"><?= format_waktu($r['jam_mulai']) ?> - <?= format_waktu($r['jam_selesai']) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Pengganti</div>
                                            <div class="info-value"><?= $r['nama_pengganti'] ?: '<span class="text-muted">Tidak ada</span>' ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Alasan</div>
                                            <div class="info-value"><?= $r['catatan'] ?: '<span class="text-muted">Tidak ada</span>' ?></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" 
                                                    data-bs-target="#modalDetailMobile<?= $r['id'] ?>">
                                                <i class="fas fa-eye me-1"></i>Lihat Detail
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="confirmSlideDelete('single', <?= $r['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Modal Detail Mobile -->
                                        <div class="modal fade" id="modalDetailMobile<?= $r['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header custom-gradient">
                                                        <h6 class="modal-title text-white">
                                                            <i class="fas fa-info-circle me-2"></i>Detail
                                                        </h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="info-row">
                                                            <div class="info-label">Asisten</div>
                                                            <div class="info-value"><strong><?= $r['nama_asisten'] ?></strong></div>
                                                        </div>
                                                        <div class="info-row">
                                                            <div class="info-label">Jadwal</div>
                                                            <div class="info-value"><?= $r['nama_mk'] ?> · <?= $r['nama_kelas'] ?></div>
                                                        </div>
                                                        <div class="info-row">
                                                            <div class="info-label">Tanggal</div>
                                                            <div class="info-value"><?= format_tanggal($r['tanggal']) ?></div>
                                                        </div>
                                                        <div class="info-row">
                                                            <div class="info-label">Pengganti</div>
                                                            <div class="info-value"><?= $r['nama_pengganti'] ?: '-' ?></div>
                                                        </div>
                                                        <div class="info-row">
                                                            <div class="info-label">Alasan</div>
                                                            <div class="info-value"><?= $r['catatan'] ?: '-' ?></div>
                                                        </div>
                                                        <hr>
                                                        <div class="info-row">
                                                            <div class="info-label">Status</div>
                                                            <div class="info-value">
                                                                <?php if ($r['status_approval'] == 'approved'): ?>
                                                                    <span class="badge bg-success">Disetujui</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Ditolak</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <?php if ($r['alasan_reject']): ?>
                                                            <div class="info-row">
                                                                <div class="info-label">Alasan Tolak</div>
                                                                <div class="info-value text-danger"><?= $r['alasan_reject'] ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="card">
                        <div class="card-body empty-state text-center">
                            <?php if ($active_tab == 'pending'): ?>
                                <i class="fas fa-check-circle text-success"></i>
                                <h5>Tidak Ada Pengajuan</h5>
                                <p class="text-muted mb-0">Semua pengajuan izin sudah diproses</p>
                            <?php elseif ($active_tab == 'approved'): ?>
                                <i class="fas fa-folder-open text-muted"></i>
                                <h5>Belum Ada Yang Disetujui</h5>
                                <p class="text-muted mb-0">Belum ada pengajuan izin yang disetujui</p>
                            <?php elseif ($active_tab == 'rejected'): ?>
                                <i class="fas fa-folder-open text-muted"></i>
                                <h5>Belum Ada Yang Ditolak</h5>
                                <p class="text-muted mb-0">Belum ada pengajuan izin yang ditolak</p>
                            <?php else: ?>
                                <i class="fas fa-inbox text-muted"></i>
                                <h5>Tidak Ada Data</h5>
                                <p class="text-muted mb-0">Belum ada pengajuan izin dari asisten</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="index.php?page=admin_izin_asisten&tab=<?= $active_tab ?>&p=<?= $current_page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $current_page - 2);
                            $end = min($total_pages, $current_page + 2);
                            
                            if ($start > 1): ?>
                                <li class="page-item"><a class="page-link" href="index.php?page=admin_izin_asisten&tab=<?= $active_tab ?>&p=1<?= $search ? '&search=' . urlencode($search) : '' ?>">1</a></li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=admin_izin_asisten&tab=<?= $active_tab ?>&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="index.php?page=admin_izin_asisten&tab=<?= $active_tab ?>&p=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $total_pages ?></a></li>
                            <?php endif; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="index.php?page=admin_izin_asisten&tab=<?= $active_tab ?>&p=<?= $current_page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <p class="text-center text-muted small mt-2 mb-0">
                            Menampilkan <?= min($offset + 1, $total_data) ?>-<?= min($offset + $per_page, $total_data) ?> dari <?= $total_data ?> data
                        </p>
                    </nav>
                <?php endif; ?>
                
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

<script>
// --- Selection & Bulk Action Logic ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const tableContainer = document.getElementById('izinTableContainer');
    const cardsContainer = document.getElementById('izinCardsContainer');
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
        document.querySelectorAll('.izin-card').forEach(c => c.classList.remove('selected'));
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
    if (type === 'bulk') msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} data</b> terpilih.`;
    else msg.innerHTML = `Anda akan menghapus data izin ini.`;
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
