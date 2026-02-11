<?php
$page = 'admin_log';

// Handle Delete Log
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'hapus_log') {
    $id = (int)$_POST['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM log_presensi WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        set_alert('success', 'Log aktivitas berhasil dihapus.');
    } else {
        set_alert('danger', 'Gagal menghapus log aktivitas.');
    }
    header("Location: index.php?page=admin_log");
    exit;
}

// Pagination
$per_page = 20;
$current_page = get_current_page();

// Filter Variables
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? escape($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? escape($_GET['end_date']) : '';
$filter_aksi = isset($_GET['filter_aksi']) ? escape($_GET['filter_aksi']) : '';

// Build Query Conditions
$where_clauses = [];
$params = [];
$types = "";

if ($search) {
    $where_clauses[] = "(l.aksi LIKE ? OR l.tabel LIKE ? OR l.detail LIKE ? OR u.username LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
    $types .= "ssss";
}

if ($start_date) {
    $where_clauses[] = "DATE(l.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if ($end_date) {
    $where_clauses[] = "DATE(l.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if ($filter_aksi) {
    $where_clauses[] = "l.aksi LIKE ?";
    $params[] = "%" . $filter_aksi . "%";
    $types .= "s";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Hitung Total Data
$sql_count = "SELECT COUNT(*) as total FROM log_presensi l LEFT JOIN users u ON l.user_id = u.id $where_sql";
$stmt_count = mysqli_prepare($conn, $sql_count);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$total_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];

$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Fetch Data Logs
$sql_data = "SELECT l.*, u.username, COALESCE(m.foto, a.foto) as foto FROM log_presensi l 
             LEFT JOIN users u ON l.user_id = u.id 
             LEFT JOIN mahasiswa m ON u.id = m.user_id
             LEFT JOIN asisten a ON u.id = a.user_id
             $where_sql
             ORDER BY l.created_at DESC LIMIT ?, ?";

// Add pagination params
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";

$stmt_logs = mysqli_prepare($conn, $sql_data);
mysqli_stmt_bind_param($stmt_logs, $types, ...$params);
mysqli_stmt_execute($stmt_logs);
$logs = mysqli_stmt_get_result($stmt_logs);
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Modern Design Overhaul */
    .page-header-modern {
        background: var(--bg-card);
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    /* Timeline-like style for mobile logs */
    .log-timeline-item {
        position: relative;
        padding-left: 2rem;
        border-left: 2px solid var(--border-color);
        padding-bottom: 1.5rem;
    }
    .log-timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    .log-timeline-icon {
        position: absolute;
        left: -0.6rem;
        top: 0;
        width: 1.2rem;
        height: 1.2rem;
        border-radius: 50%;
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
    }
    .log-timeline-item.create .log-timeline-icon { border-color: var(--success-color); color: var(--success-color); }
    .log-timeline-item.update .log-timeline-icon { border-color: var(--primary-color); color: var(--primary-color); }
    .log-timeline-item.delete .log-timeline-icon { border-color: var(--danger-color); color: var(--danger-color); }
    .log-timeline-item.login .log-timeline-icon { border-color: var(--info-color); color: var(--info-color); }
    
    /* Elegant Table Styles */
    .card.shadow-sm {
        border: none;
        border-radius: 16px;
        box-shadow: var(--card-shadow) !important;
        overflow: hidden;
    }

    .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead th {
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        border-top: none;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
        font-size: 0.9rem;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Enhanced Row Styling */
    .table tbody tr {
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }

    .table tbody tr:hover {
        background-color: var(--bg-body, #f8f9fa);
        transform: translateX(2px);
    }
    
    .table tbody tr.row-create { border-left-color: var(--success-color); background-color: rgba(40, 167, 69, 0.08); }
    .table tbody tr.row-update { border-left-color: var(--primary-color); background-color: rgba(0, 123, 255, 0.08); }
    .table tbody tr.row-delete { border-left-color: var(--danger-color); background-color: rgba(220, 53, 69, 0.08); }
    .table tbody tr.row-login { border-left-color: var(--info-color); background-color: rgba(23, 162, 184, 0.08); }

    .avatar-circle {
        width: 38px;
        height: 38px;
        background-color: #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        color: #495057;
        font-weight: 700;
        margin-right: 12px;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        flex-shrink: 0;
    }
    
    .avatar-circle img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Soft Badge Styles */
    .badge-soft {
        padding: 0.5em 0.8em;
        font-weight: 600;
        border-radius: 50rem !important;
    }
    .badge-soft-success { background-color: rgba(40, 167, 69, 0.1); color: #28a745; }
    .badge-soft-primary { background-color: rgba(0, 123, 255, 0.1); color: #007bff; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-soft-info { background-color: rgba(23, 162, 184, 0.1); color: #17a2b8; }
    .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }

    [data-theme="dark"] .badge-soft-success { background-color: rgba(40, 167, 69, 0.2); color: #75b798; }
    [data-theme="dark"] .badge-soft-primary { background-color: rgba(13, 110, 253, 0.2); color: #6ea8fe; }
    [data-theme="dark"] .badge-soft-danger { background-color: rgba(220, 53, 69, 0.2); color: #ea868f; }
    [data-theme="dark"] .badge-soft-info { background-color: rgba(13, 202, 240, 0.2); color: #6edff6; }
    [data-theme="dark"] .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.2); color: #a7acb1; }
    
    [data-theme="dark"] .log-timeline-item .card { background-color: var(--bg-card) !important; }
    [data-theme="dark"] .log-timeline-item .text-dark { color: var(--text-main) !important; }
    
    [data-theme="dark"] .table thead th {
        background-color: rgba(255, 255, 255, 0.03);
        color: var(--text-muted);
        border-bottom-color: var(--border-color);
    }
    [data-theme="dark"] .table tbody tr.row-create { background-color: rgba(40, 167, 69, 0.05); }
    [data-theme="dark"] .table tbody tr.row-update { background-color: rgba(13, 110, 253, 0.05); }
    [data-theme="dark"] .table tbody tr.row-delete { background-color: rgba(220, 53, 69, 0.05); }
    [data-theme="dark"] .table tbody tr.row-login { background-color: rgba(13, 202, 240, 0.05); }
    
    [data-theme="dark"] .table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03);
    }
    [data-theme="dark"] .avatar-circle {
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--text-main);
        border-color: var(--bg-card);
    }
    [data-theme="dark"] .text-dark { color: var(--text-main) !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <div class="page-header-modern">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Log Aktivitas</h4>
                        <p class="mb-0 text-muted small">Memantau semua aktivitas sistem secara real-time</p>
                    </div>
                    <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                        Total: <strong><?= number_format($total_data) ?></strong> Record
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <!-- Search Filter -->
                <div class="filter-bar">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="admin_log">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Tipe Aksi</label>
                                <select name="filter_aksi" class="form-select">
                                    <option value="">Semua</option>
                                    <option value="LOGIN" <?= $filter_aksi == 'LOGIN' ? 'selected' : '' ?>>Login</option>
                                    <option value="INSERT" <?= $filter_aksi == 'INSERT' ? 'selected' : '' ?>>Insert</option>
                                    <option value="UPDATE" <?= $filter_aksi == 'UPDATE' ? 'selected' : '' ?>>Update</option>
                                    <option value="DELETE" <?= $filter_aksi == 'DELETE' ? 'selected' : '' ?>>Delete</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Pencarian</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="User, tabel, detail..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i></button>
                            </div>
                        </form>
                </div>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aktivitas</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($logs) > 0): ?>
                                        <?php while ($l = mysqli_fetch_assoc($logs)): 
                                            $aksi = strtoupper($l['aksi']);
                                            $badgeClass = 'bg-secondary';
                                            $statusClass = 'bg-secondary';
                                            $icon = 'fa-info';
                                            $aksiLabel = $l['aksi'];
                                            $rowClass = '';
                                            
                                            if (strpos($aksi, 'LOGIN') !== false) {
                                                $badgeClass = 'bg-info text-dark';
                                                $statusClass = 'status-login';
                                                $icon = 'fa-sign-in-alt';
                                                $aksiLabel = 'Login Sistem';
                                                $rowClass = 'row-login';
                                            } elseif (strpos($aksi, 'PRESENSI_QR') !== false) {
                                                $badgeClass = 'bg-primary';
                                                $statusClass = 'bg-primary';
                                                $icon = 'fa-qrcode';
                                                $aksiLabel = 'Scan QR';
                                            } elseif (strpos($aksi, 'BROADCAST') !== false) {
                                                $badgeClass = 'bg-success';
                                                $statusClass = 'bg-success';
                                                $icon = 'fa-bullhorn';
                                                $aksiLabel = 'Broadcast WA';
                                            } elseif (strpos($aksi, 'INSERT') !== false || strpos($aksi, 'ADD') !== false || strpos($aksi, 'CREATE') !== false || strpos($aksi, 'GENERATE') !== false) {
                                                $badgeClass = 'bg-success';
                                                $statusClass = 'status-create';
                                                $icon = 'fa-plus';
                                                $aksiLabel = 'Tambah Data';
                                                $rowClass = 'row-create';
                                            } elseif (strpos($aksi, 'UPDATE') !== false || strpos($aksi, 'EDIT') !== false || strpos($aksi, 'APPROVE') !== false) {
                                                $badgeClass = 'bg-warning text-dark';
                                                $statusClass = 'status-update';
                                                $icon = 'fa-pen';
                                                $aksiLabel = 'Ubah Data';
                                                $rowClass = 'row-update';
                                            } elseif (strpos($aksi, 'DELETE') !== false || strpos($aksi, 'REMOVE') !== false || strpos($aksi, 'REJECT') !== false) {
                                                $badgeClass = 'bg-danger';
                                                $statusClass = 'status-delete';
                                                $icon = 'fa-trash';
                                                $aksiLabel = 'Hapus Data';
                                                $rowClass = 'row-delete';
                                            } else {
                                                $aksiLabel = ucfirst(strtolower(str_replace('_', ' ', $aksi)));
                                            }
                                        ?>
                                        <tr class="<?= $rowClass ?>">
                                            <td class="text-nowrap">
                                                <div class="d-flex align-items-center">
                                                    <span class="status-indicator <?= $statusClass ?>"></span>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= date('d M Y', strtotime($l['created_at'])) ?></div>
                                                        <div class="small text-muted" style="font-size: 0.75rem;"><?= date('H:i:s', strtotime($l['created_at'])) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($l['foto']) && file_exists($l['foto'])): ?>
                                                        <div class="avatar-circle"><img src="<?= htmlspecialchars($l['foto']) ?>" alt="User"></div>
                                                    <?php else: 
                                                        $username = $l['username'] ?? 'System';
                                                        $initial = strtoupper(substr($username, 0, 1));
                                                        if (strtolower($username) === 'admin') {
                                                            $avatarBg = '#212529'; // Warna khusus Admin (Dark)
                                                        } else {
                                                            $bgColors = ['#0066cc', '#66cc00', '#ff9900', '#ff3333', '#00ccff', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6c757d'];
                                                            $bgIndex = ord($initial) % count($bgColors);
                                                            $avatarBg = $bgColors[$bgIndex];
                                                        }
                                                    ?>
                                                        <div class="avatar-circle" style="background-color: <?= $avatarBg ?>; color: #fff;">
                                                            <?= $initial ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($l['username'] ?? 'System') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column align-items-start">
                                                    <span class="badge <?= $badgeClass ?> mb-1" title="Target: <?= $l['tabel'] ?> #<?= $l['id_record'] ?> (<?= $aksi ?>)">
                                                        <i class="fas <?= $icon ?> me-1"></i><?= $aksiLabel ?>
                                                    </span>
                                                    <span class="text-dark small text-break">
                                                        <?= htmlspecialchars($l['detail']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus log ini?');" class="d-inline">
                                                    <input type="hidden" name="aksi" value="hapus_log">
                                                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Log">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-search fa-2x mb-3 d-block"></i>
                                                Tidak ada data log ditemukan.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View -->
                        <div class="d-lg-none p-3">
                            <?php 
                            if (mysqli_num_rows($logs) > 0):
                                mysqli_data_seek($logs, 0);
                                while ($l = mysqli_fetch_assoc($logs)): 
                                    $aksi = strtoupper($l['aksi']);
                                    $type = 'info';
                                    $icon = 'fa-info';
                                    $badgeClass = 'bg-secondary';
                                    $aksiLabel = $l['aksi'];
                                    
                                    if (strpos($aksi, 'LOGIN') !== false) {
                                        $type = 'login';
                                        $icon = 'fa-sign-in-alt';
                                        $badgeClass = 'bg-info text-dark';
                                        $aksiLabel = 'Login Sistem';
                                    } elseif (strpos($aksi, 'PRESENSI_QR') !== false) {
                                        $type = 'update';
                                        $icon = 'fa-qrcode';
                                        $badgeClass = 'bg-primary';
                                        $aksiLabel = 'Scan QR';
                                    } elseif (strpos($aksi, 'BROADCAST') !== false) {
                                        $type = 'create';
                                        $icon = 'fa-bullhorn';
                                        $badgeClass = 'bg-success';
                                        $aksiLabel = 'Broadcast WA';
                                    } elseif (strpos($aksi, 'INSERT') !== false || strpos($aksi, 'ADD') !== false || strpos($aksi, 'CREATE') !== false || strpos($aksi, 'GENERATE') !== false) {
                                        $type = 'create';
                                        $icon = 'fa-plus';
                                        $badgeClass = 'bg-success';
                                        $aksiLabel = 'Tambah Data';
                                    } elseif (strpos($aksi, 'UPDATE') !== false || strpos($aksi, 'EDIT') !== false || strpos($aksi, 'APPROVE') !== false) {
                                        $type = 'update';
                                        $icon = 'fa-pen';
                                        $badgeClass = 'bg-warning text-dark';
                                        $aksiLabel = 'Ubah Data';
                                    } elseif (strpos($aksi, 'DELETE') !== false || strpos($aksi, 'REMOVE') !== false || strpos($aksi, 'REJECT') !== false) {
                                        $type = 'delete';
                                        $icon = 'fa-trash';
                                        $badgeClass = 'bg-danger';
                                        $aksiLabel = 'Hapus Data';
                                    } else {
                                        $badgeClass = 'bg-secondary';
                                        $aksiLabel = ucfirst(strtolower($aksi));
                                    }
                            ?>
                            <div class="log-timeline-item <?= $type ?>">
                                <div class="log-timeline-icon">
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                                <div class="card border-0 shadow-sm bg-light">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge <?= $badgeClass ?>" title="Target: <?= $l['tabel'] ?> #<?= $l['id_record'] ?>"><?= $aksiLabel ?></span>
                                                <small class="text-muted"><?= date('d M H:i', strtotime($l['created_at'])) ?></small>
                                            </div>
                                            <small class="fw-bold text-dark"><?= htmlspecialchars($l['username'] ?? 'System') ?></small>
                                        </div>
                                        <p class="mb-0 small text-dark"><?= htmlspecialchars($l['detail']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-3 d-block"></i>
                                    Tidak ada data log ditemukan.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_data > 0): ?>
                    <div class="card-footer bg-transparent border-top py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                            <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                            <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_log', ['search' => $search, 'start_date' => $start_date, 'end_date' => $end_date, 'filter_aksi' => $filter_aksi]) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
