<?php
$page = 'admin_izin_asisten';

// Proses Approve
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    
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
        log_aktivitas($_SESSION['user_id'], 'APPROVE_IZIN_ASISTEN', 'absen_asisten', $id, 
                      "Admin menyetujui izin asisten {$detail['nama']}");
        set_alert('success', 'Pengajuan izin berhasil disetujui!');
    } else {
        set_alert('danger', 'Gagal menyetujui pengajuan izin!');
    }
    
    header("Location: index.php?page=admin_izin_asisten&tab=" . ($_GET['tab'] ?? 'pending'));
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

    /* Dark Mode Support */
    [data-theme="dark"] .info-label { color: #adb5bd; }
    [data-theme="dark"] .info-value { color: #e9ecef; }
    [data-theme="dark"] .info-row { border-bottom-color: #495057; }
    [data-theme="dark"] .izin-card {
        background-color: var(--bg-card);
        border-color: var(--border-color);
    }
</style>

<div class="container-fluid izin-asisten-page">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-user-clock me-2 text-primary"></i>Persetujuan Izin Asisten</h4>
                
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
                        <form method="GET" class="row g-2 align-items-center">
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
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1 d-none d-sm-inline"></i>Cari
                                </button>
                                <?php if ($search): ?>
                                    <a href="index.php?page=admin_izin_asisten&tab=<?= $active_tab ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Content -->
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <!-- Desktop Table -->
                    <div class="card d-none d-lg-block">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
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
                                            <tr>
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
                                                            <a href="index.php?page=admin_izin_asisten&approve=<?= $r['id'] ?>&tab=<?= $active_tab ?>" 
                                                               class="btn btn-success btn-sm btn-action"
                                                               onclick="return confirm('Setujui pengajuan izin dari <?= $r['nama_asisten'] ?>?')">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                            <button class="btn btn-danger btn-sm btn-action" data-bs-toggle="modal" 
                                                                    data-bs-target="#modalReject<?= $r['id'] ?>">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" 
                                                                    data-bs-target="#modalDetail<?= $r['id'] ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" 
                                                                data-bs-target="#modalDetail<?= $r['id'] ?>">
                                                            <i class="fas fa-eye"></i> Detail
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
                                                                <a href="index.php?page=admin_izin_asisten&approve=<?= $r['id'] ?>&tab=<?= $active_tab ?>" 
                                                                   class="btn btn-success"
                                                                   onclick="return confirm('Setujui pengajuan izin ini?')">
                                                                    <i class="fas fa-check me-1"></i>Setujui
                                                                </a>
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
                                                                    
                                                                    <div class="alert alert-light border mb-3">
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
                    <div class="d-lg-none">
                        <?php 
                        mysqli_data_seek($result, 0);
                        while ($r = mysqli_fetch_assoc($result)): 
                            $card_class = '';
                            if ($r['status_approval'] == 'pending') $card_class = 'pending-card';
                            elseif ($r['status_approval'] == 'approved') $card_class = 'approved-card';
                            else $card_class = 'rejected-card';
                        ?>
                            <div class="card izin-card <?= $card_class ?> mb-3">
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
                                        <div class="d-flex gap-2">
                                            <a href="index.php?page=admin_izin_asisten&approve=<?= $r['id'] ?>&tab=<?= $active_tab ?>" 
                                               class="btn btn-success btn-sm flex-fill btn-action justify-content-center"
                                               onclick="return confirm('Setujui pengajuan izin ini?')">
                                                <i class="fas fa-check"></i> Setujui
                                            </a>
                                            <button class="btn btn-danger btn-sm flex-fill btn-action justify-content-center" 
                                                    data-bs-toggle="modal" data-bs-target="#modalRejectMobile<?= $r['id'] ?>">
                                                <i class="fas fa-times"></i> Tolak
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
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="modal" 
                                                data-bs-target="#modalDetailMobile<?= $r['id'] ?>">
                                            <i class="fas fa-eye me-1"></i>Lihat Detail
                                        </button>
                                        
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

<?php include 'includes/footer.php'; ?>
